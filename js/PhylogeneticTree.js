window.PhylogeneticTree = function() {
    const [treeData, setTreeData] = React.useState(null);
    const [zoomLevel, setZoomLevel] = React.useState(1);
    const [displayMode, setDisplayMode] = React.useState('linear');
    
    // Improved Newick parser
    const parseNewick = (newickString) => {
      // Basic validation
      if (!newickString || typeof newickString !== 'string') {
        console.error('Invalid Newick string:', newickString);
        return { nodes: [], connections: [] };
      }
      
      let cleanNewick = newickString.trim();
      if (!cleanNewick.endsWith(';')) {
        cleanNewick += ';'; // Ensure proper termination
      }
      
      const nodes = [];
      const connections = [];
      let currentId = 0;
      const seenLabels = new Set();
      
      // Function to generate a unique node ID
      const generateNodeId = () => `node_${currentId++}`;
      
      // Parse a leaf node (format: "label:distance")
      const parseLeaf = (str, parentId) => {
        const nodeId = generateNodeId();
        const parts = str.split(':');
        const label = parts[0].trim();
        const distance = parts.length > 1 ? parseFloat(parts[1]) || 0 : 0;
        
        // Only add if we haven't seen this label before
        if (!seenLabels.has(label)) {
          seenLabels.add(label);
          nodes.push({
            id: nodeId,
            label: label,
            distance: distance,
            isLeaf: true
          });
          
          if (parentId) {
            connections.push({ from: parentId, to: nodeId });
          }
        }
        
        return nodeId;
      };
      
      // Recursive function to parse a subtree
      const parseSubtree = (str, parentId = null) => {
        // Simple case: this is a leaf node
        if (str.indexOf('(') === -1) {
          return parseLeaf(str, parentId);
        }
        
        // This is an internal node
        const nodeId = generateNodeId();
        
        // Extract the label and distance for this internal node
        let label = '';
        let distance = 0;
        
        // Find the closing parenthesis and check for label/distance
        let closingIndex = -1;
        let openCount = 0;
        
        for (let i = 0; i < str.length; i++) {
          if (str[i] === '(') openCount++;
          else if (str[i] === ')') {
            openCount--;
            if (openCount === 0) {
              closingIndex = i;
              break;
            }
          }
        }
        
        if (closingIndex >= 0 && closingIndex < str.length - 1) {
          const rest = str.substring(closingIndex + 1);
          const labelMatch = rest.match(/([^:,)]*):?([0-9.]*)/);
          
          if (labelMatch) {
            label = labelMatch[1].trim();
            distance = labelMatch[2] ? parseFloat(labelMatch[2]) : 0;
          }
        }
        
        // Add the internal node
        nodes.push({
          id: nodeId,
          label: label,
          distance: distance,
          isLeaf: false
        });
        
        if (parentId) {
          connections.push({ from: parentId, to: nodeId });
        }
        
        // Extract and parse the children
        const childrenStr = str.substring(1, closingIndex);
        const children = [];
        
        let currentChild = '';
        let depth = 0;
        
        for (let i = 0; i < childrenStr.length; i++) {
          const char = childrenStr[i];
          
          if (char === '(') {
            depth++;
            currentChild += char;
          } else if (char === ')') {
            depth--;
            currentChild += char;
          } else if (char === ',' && depth === 0) {
            if (currentChild.trim()) {
              children.push(currentChild.trim());
            }
            currentChild = '';
          } else {
            currentChild += char;
          }
        }
        
        if (currentChild.trim()) {
          children.push(currentChild.trim());
        }
        
        // Parse each child
        children.forEach(child => {
          parseSubtree(child, nodeId);
        });
        
        return nodeId;
      };
      
      // Start parsing
      parseSubtree(cleanNewick);
      
      return { nodes, connections };
    };
    
    // Handle zoom in
    const handleZoomIn = () => {
      setZoomLevel(prevZoom => Math.min(prevZoom + 0.2, 3));
    };
    
    // Handle zoom out
    const handleZoomOut = () => {
      setZoomLevel(prevZoom => Math.max(prevZoom - 0.2, 0.5));
    };
    
    // Toggle between linear and radial display
    const toggleRadial = () => {
      setDisplayMode(prevMode => prevMode === 'linear' ? 'radial' : 'linear');
    };
    
    // Effect to load tree data
    React.useEffect(() => {
      // Get tree data from a global variable set by PHP
      const newickData = window.newickString || '';
      console.log("Loading Newick data:", newickData);
      if (newickData) {
        try {
          const parsed = parseNewick(newickData);
          console.log("Parsed tree data:", parsed);
          setTreeData(parsed);
        } catch (err) {
          console.error("Error parsing Newick data:", err);
        }
      }
      
      // Expose methods to global scope
      window.phyloTreeComponent = {
        handleZoomIn,
        handleZoomOut,
        toggleRadial
      };
      
      // Cleanup on unmount
      return () => {
        delete window.phyloTreeComponent;
      };
    }, []);
    
    const renderTree = () => {
      if (!treeData || !treeData.nodes.length) {
        console.log("No tree data to render");
        return React.createElement('div', { className: "text-center py-10" }, "No phylogenetic tree data available");
      }
      
      const leafNodes = treeData.nodes.filter(node => node.isLeaf);
      const canvasWidth = 800;
      const canvasHeight = Math.max(400, leafNodes.length * 30);
      
      // Calculate node positions
      const nodePositions = {};
      
      if (displayMode === 'linear') {
        // Linear layout
        const leafCount = leafNodes.length;
        
        // First position leaf nodes evenly
        leafNodes.forEach((node, index) => {
          nodePositions[node.id] = {
            x: canvasWidth - 350,
            y: 50 + ((canvasHeight - 100) / Math.max(1, leafCount - 1)) * index
          };
        });
        
        // Then position internal nodes - work backwards from leaves
        // Collect all connections in a more accessible format
        const nodeConnections = {};
        treeData.connections.forEach(conn => {
          if (!nodeConnections[conn.from]) nodeConnections[conn.from] = [];
          nodeConnections[conn.from].push(conn.to);
        });
        
        // Define a helper to get parent node
        const getParentNode = (nodeId) => {
          const conn = treeData.connections.find(c => c.to === nodeId);
          return conn ? conn.from : null;
        };
        
        // Position internal nodes based on their children
        const positionInternalNodes = () => {
          let changed = false;
          
          treeData.nodes.forEach(node => {
            if (node.isLeaf) return; // Skip leaf nodes
            
            // If this node already has a position, skip
            if (nodePositions[node.id]) return;
            
            // Get children of this node
            const children = nodeConnections[node.id] || [];
            
            // Check if all children have positions
            const allChildrenPositioned = children.every(childId => !!nodePositions[childId]);
            
            if (!allChildrenPositioned) return;
            
            // Position at average of children's X and Y, but pull X left
            let totalX = 0, totalY = 0;
            children.forEach(childId => {
              totalX += nodePositions[childId].x;
              totalY += nodePositions[childId].y;
            });
            
            nodePositions[node.id] = {
              x: (totalX / children.length) - 120,//Pll left from children
              y: totalY / children.length
            };
            
            changed = true;
          });
          
          return changed;
        };
        
        // Keep positioning internal nodes until no more changes
        let iterations = 0;
        let changed = true;
        while (changed && iterations < 10) {
          changed = positionInternalNodes();
          iterations++;
        }
        
        // Position any remaining nodes (fallback)
        treeData.nodes.forEach(node => {
          if (!nodePositions[node.id]) {
            nodePositions[node.id] = { x: 100, y: canvasHeight / 2 };
          }
        });
        
      } else {
        // Radial layout
        const centerX = canvasWidth / 2;
        const centerY = canvasHeight / 2;
        const radius = Math.min(centerX, centerY) - 80;
        
        // Position leaf nodes in a circle
        const leafCount = leafNodes.length;
        leafNodes.forEach((node, index) => {
          const angle = (index / leafCount) * 2 * Math.PI;
          nodePositions[node.id] = {
            x: centerX + radius * Math.cos(angle),
            y: centerY + radius * Math.sin(angle)
          };
        });
        
        // Same connection mapping as linear layout
        const nodeConnections = {};
        treeData.connections.forEach(conn => {
          if (!nodeConnections[conn.from]) nodeConnections[conn.from] = [];
          nodeConnections[conn.from].push(conn.to);
        });
        
        // Position internal nodes
        const positionInternalNodes = () => {
          let changed = false;
          
          treeData.nodes.forEach(node => {
            if (node.isLeaf) return; // Skip leaf nodes
            
            // If this node already has a position, skip
            if (nodePositions[node.id]) return;
            
            // Get children of this node
            const children = nodeConnections[node.id] || [];
            
            // Check if all children have positions
            const allChildrenPositioned = children.every(childId => !!nodePositions[childId]);
            
            if (!allChildrenPositioned) return;
            
            // Position at average of children's position, but pull toward center
            let totalX = 0, totalY = 0;
            children.forEach(childId => {
              totalX += nodePositions[childId].x;
              totalY += nodePositions[childId].y;
            });
            
            const avgX = totalX / children.length;
            const avgY = totalY / children.length;
            
            // Calculate vector from center to average position
            const vx = avgX - centerX;
            const vy = avgY - centerY;
            
            // Calculate distance from center
            const dist = Math.sqrt(vx * vx + vy * vy);
            
            // Position node closer to center (60% of the way from center to average)
            const ratio = 0.6;
            nodePositions[node.id] = {
              x: centerX + (vx / dist) * (dist * ratio),
              y: centerY + (vy / dist) * (dist * ratio)
            };
            
            changed = true;
          });
          
          return changed;
        };
        
        // Keep positioning internal nodes until no more changes
        let iterations = 0;
        let changed = true;
        while (changed && iterations < 10) {
          changed = positionInternalNodes();
          iterations++;
        }
        
        // Position any remaining nodes (fallback)
        treeData.nodes.forEach(node => {
          if (!nodePositions[node.id]) {
            nodePositions[node.id] = { x: centerX, y: centerY };
          }
        });
      }
      
      // Apply zoom transformation
      const transform = `scale(${zoomLevel})`;
      
      return React.createElement('svg', {
        viewBox: `0 0 ${canvasWidth} ${canvasHeight}`,
        className: "w-full h-auto border border-gray-200 rounded",
      }, React.createElement('g', {
        transform: transform,
        style: {
          transformOrigin: 'center',
          transition: 'transform 0.3s ease-in-out'
        }
      }, [
        // Draw connections
        ...treeData.connections.map((conn, i) => {
          const from = nodePositions[conn.from];
          const to = nodePositions[conn.to];
          if (from && to) {
            return React.createElement('line', {
              key: `conn-${i}`,
              x1: from.x,
              y1: from.y,
              x2: to.x,
              y2: to.y,
              stroke: "#555",
              strokeWidth: "1"
            });
          }
          return null;
        }),
        
        // Draw nodes
        ...treeData.nodes.map((node, i) => {
          const pos = nodePositions[node.id];
          if (!pos) return null;
          
          return React.createElement('g', { key: `node-${i}` }, [
            // Node circle
            React.createElement('circle', {
              cx: pos.x,
              cy: pos.y,
              r: node.isLeaf ? 4 : 3,
              fill: node.isLeaf ? "#3730a3" : "#888"
            }),
            
            // Only show labels for leaf nodes
            node.isLeaf && React.createElement('text', {
              x: displayMode === 'linear' ? pos.x + 10 : pos.x + (pos.x > canvasWidth/2 ? 10 : -10),
              y: pos.y + 4,
              textAnchor: displayMode === 'linear' ? 'start' : (pos.x > canvasWidth/2 ? 'start' : 'end'),
              className: "text-sm",
              style: { fontSize: '12px' }
            }, `${node.label} ${node.distance.toFixed(5)}`)
          ]);
        })
      ]));
    };
  
    // Use plain divs instead of Card component
    return React.createElement('div', { className: "w-full space-y-4" }, [
      React.createElement('div', { className: "p-4 border rounded shadow-sm" }, [
        React.createElement('h3', { className: "text-lg font-semibold mb-2 text-purple-700" }, "Guide Tree"),
        React.createElement('div', { 
          className: "font-mono text-sm whitespace-pre overflow-x-auto" 
        }, window.newickString || 'No tree data available')
      ]),
  
      React.createElement('div', { className: "p-4 border rounded shadow-sm" }, [
        React.createElement('h3', { className: "text-lg font-semibold mb-2 text-purple-700" }, "Phylogram"),
        React.createElement('div', { 
          className: "relative overflow-auto",
          style: { minHeight: '400px' }
        }, renderTree()),
        
        React.createElement('div', { className: "flex justify-center gap-4 mt-4" }, [
            React.createElement('button', { 
              className: "px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition-colors shadow-sm",
              onClick: handleZoomIn
            }, "Zoom In"),
            React.createElement('button', { 
              className: "px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition-colors shadow-sm",
              onClick: handleZoomOut
            }, "Zoom Out"),
            React.createElement('button', { 
              className: "px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 transition-colors shadow-sm",
              onClick: toggleRadial
            }, displayMode === 'linear' ? "Switch to Radial" : "Switch to Linear")
            // "Selected: 0 branches" text has been removed
          ])
      ])
    ]);
  };