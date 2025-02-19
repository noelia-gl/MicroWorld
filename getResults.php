<?php
/**
 * getResults.php
 * Retrieve and display Clustal-Omega alignment results 
 */

 // Load global variables and session data 
 require "config.php";

// Store alignment data in session 
if (!isset($_SESSION['alignment_data'])) {
   // Check if results exist 
   if (!isset($_SESSION['msaResult']) || !file_exists($_SESSION['msaResult'])) {
       $_SESSION['error'] = "No alignment results available";
      header('Location: error.php');
      exit();
   }
   // Read and store the alignment results 
   $_SESSION['alignment_data'] = file_get_contents($_SESSION['msaResult']);
}

$alignmentResults = $_SESSION['alignment_data'];


 // Determine the file extension
 $extension = '';
 switch ($_SESSION['outfmt']) {
   case 'clu':
      $extension = 'aln'; 
      break;
   case 'fa':
      $extension = 'fasta';
      break; 
   case 'msf':
      $extension = 'msf';
      break;
   case 'nexus':
      $extension = 'nex';
      break;
   case 'phylip':
      $extension = 'phy';
      break;
   case 'selex':
      $extension = 'slx';
      break;
   case 'stockholm':
      $extension = 'sto';
      break;
   case 'vienna':
      $extension = 'vna';
      break;
 }

 // Store the extension in session for download 
 $_SESSION['file_extension'] = $extension; 

 // Get the sequence type (if detected)
 $seqType = isset($_SESSION['sequence_type']) ? $_SESSION['sequence_type'] : 'unknown';

 // Get the alignment title 
 $alignmentTitle = isset($_SESSION['alignment_title']) ? $_SESSION['alignment_title'] : 'Alignment Results';


 // Flag for colorized display 
$showColors = true;
$_SESSION['show_colors'] = true;


 // Function to color Clustal alignment 
 function colorizeClustalAlignment($alignmentText, $useColors = true) {
   if (empty($alignmentText)) return $alignmentText;

   // If colors are disabled, return plain text
   if (!$useColors) {
      return '<pre>' . htmlspecialchars($alignmentText) . '</pre>';
   }

   // Define Clustal color schemes 
   $colors = [
      'A' => '#80a0f0', 
      'R' => '#f01505', 
      'N' => '#00ff00', 
      'D' => '#c048c0', 
      'C' => '#f08080', 
      'Q' => '#00ff00', 
      'E' => '#c048c0', 
      'G' => '#f09048',
      'H' => '#15a4a4', 
      'I' => '#80a0f0', 
      'L' => '#80a0f0', 
      'K' => '#f01505', 
      'M' => '#80a0f0', 
      'F' => '#80a0f0', 
      'P' => '#c0c000', 
      'S' => '#00ff00',
      'T' => '#00ff00', 
      'W' => '#80a0f0', 
      'Y' => '#15a4a4', 
      'V' => '#80a0f0', 
      
      // Nucleotide colors
      'a' => '#a0a0ff', 
      't' => '#a0ffa0', 
      'u' => '#a0ffa0',
      'g' => '#ffa0a0', 
      'c' => '#ffff80'  
  ];

  // Break the alignment into lines 
  $lines = explode("\n", $alignmentText);
  $colorized = [];

  foreach ($lines as $line) {
   if (empty(trim($line))) {
      $colorized[] = $line;
      continue;
   }

   // If this is a sequence line (not a header or consensus line)
   if (preg_match('/^\S+\s+[\w\-]+$/', $line)) {
      // Split into identifier and sequence
      if (preg_match('/^(\S+\s+)([\w\-]+)$/', $line, $matches)) {
         $identifier = $matches[1];
         $sequence = $matches[2];

         // Colorize each amino acid / nucleotide
         $colorizedSeq = '';
         for ($i = 0; $i < strlen($sequence); $i++) {
            $char = $sequence[$i];
            $upperChar = strtoupper($char);

            if (isset($colors[$upperChar])) {
               $colorizedSeq .= '<span style="color:' . $colors[$upperChar] . '">' . $char . '</span>';
            } else {
               $colorizedSeq .= $char;
            }
         }

         $colorized[] = $identifier . $colorizedSeq;
      } else {
         $colorized[] = $line;
      }
   } else {
      $colorized[] = $line;
   }
  }
  return implode("\n", $colorized);
 }


 // 1. Ensure we have access to the guide tree file
$guideTreeFile = isset($_SESSION['msaResult']) ? $_SESSION['msaResult'] . '.dnd' : '';

// 2. Validate the guide tree file and get its content
$treeContent = '';
if (!empty($guideTreeFile) && file_exists($guideTreeFile) && filesize($guideTreeFile) > 0) {
    $treeContent = file_get_contents($guideTreeFile);
    // Basic validation - should contain parentheses and colons for Newick format
    if (strpos($treeContent, '(') === false || strpos($treeContent, ':') === false) {
        $treeContent = ''; // Invalid format, reset to empty
    }
} 

// Debug: Check if we have tree content
error_log("Tree content: " . substr($treeContent, 0, 100) . "...");

// 3. If no valid tree content, generate a simple one based on sequence headers
if (empty($treeContent) && isset($_SESSION['msaResult']) && file_exists($_SESSION['msaResult'])) {
    $alignmentData = file_get_contents($_SESSION['msaResult']);
    $seqNames = [];
    $lines = explode("\n", $alignmentData);
    
    // Extract sequence names
    foreach ($lines as $line) {
        if (preg_match('/^(\S+)\s+/', $line, $matches) && !empty($matches[1]) && $matches[1] != '*') {
            if (!in_array($matches[1], $seqNames)) {
                $seqNames[] = $matches[1];
            }
        }
    }
    
    if (!empty($seqNames)) {
        // Generate simple tree
        $treeContent = '(' . implode(':1.0,', $seqNames) . ':1.0);';
    }
}

// 4. Last resort: provide a very simple fallback tree
if (empty($treeContent)) {
    $treeContent = '(seq1:1.0,seq2:1.0);';
}

// Calculate runtime if start time is set 
$runtime = '';
if (isset($_SESSION['job_start_time']) && !empty($_SESSION['job_start_time'])) {
    try {
        $startTime = new DateTime($_SESSION['job_start_time']);
        $endTime = isset($_SESSION['job_end_time']) 
            ? new DateTime($_SESSION['job_end_time'])
            : new DateTime();
        
        $interval = $startTime->diff($endTime);
        
        // Format runtime as minutes:seconds
        if ($interval->h > 0) {
            $runtime = $interval->format('%h hours, %i minutes, %s seconds');
        } else {
            $runtime = $interval->format('%i minutes, %s seconds');
        }
    } catch (Exception $e) {
        error_log("Runtime calculation error: " . $e->getMessage());
        $runtime = '';
    }
}

// Format the display time correctly
function formatDisplayTime($timeStr) {
   if (empty($timeStr)) {
       return date('Y-m-d H:i:s');
   }
   
   try {
       $time = new DateTime($timeStr);
       return $time->format('Y-m-d H:i:s');
   } catch (Exception $e) {
       error_log("Time formatting error: " . $e->getMessage());
       return $timeStr;
   }
}

?>

 <!DOCTYPE html>
 <html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo htmlspecialchars($alignmentTitle); ?> - Clustal Omega</title>
      <link rel="stylesheet" href="css/styles_clustal.css">
      <script src="https://unpkg.com/react@17/umd/react.production.min.js"></script>
      <script src="https://unpkg.com/react-dom@17/umd/react-dom.production.min.js"></script>
      <script src="js/PhylogeneticTree.js"></script>

   </head>
   <body>
      <div class="container">
         <h1>Alignment Results</h1> 
         
         <div class="alignment-title">
            <?php echo htmlspecialchars($alignmentTitle); ?>
         </div>

         <div class="tab-container">
            <div class="tab-buttons">
               <div class="tab-button active" data-tab="alignment">Alignment</div>
               <div class="tab-button" data-tab="tree">Guide Tree</div>
               <div class="tab-button" data-tab="details">Job Details</div>
            </div>

            <!-- Alignment Tab -->
            <div id="alignment" class="tab-content active">
               <div class= "info-box">
                  <p><strong>Sequence Type:</strong> <?php echo ucfirst($seqType); ?></p>
                  <p><strong>Output Format:</strong> <?php echo strtoupper($_SESSION['outfmt']) ?></p>
               </div>

               <div class="results-container">
                  <?php 
                  if ($_SESSION['outfmt'] == 'clu') {
                     echo colorizeClustalAlignment($alignmentResults, $showColors);
                  } else {
                     echo '<pre>' . htmlspecialchars($alignmentResults) . '<pre>';
                  }
                  ?>
               </div> 
            </div>

            <!-- Phylogenetic Tree Tab -->
            <div id="tree" class="tab-content">
               <script>
                  // Make the Newick string available to the React component
                  window.newickString = <?php echo json_encode($treeContent); ?>;
               </script>
               <div id="phylogenetic-tree-root"></div>
            </div>

            <!-- Job Details Tab -->
            <div id="details" class="tab-content">
               <div style="padding: 20px;">
                  <h3>Submission Information</h3>
                  <table class="details-table">
                     <tr>
                        <td><strong>Alignment Title:</strong></td>
                        <td><?php echo htmlspecialchars($alignmentTitle); ?></td>
                     </tr>
                     <tr>
                        <td><strong>Program:</strong></td>
                        <td>Clustal Omega</td>
                     </tr>
                     <tr>
                        <td><strong>Sequence Type:</strong></td>
                        <td><?php echo ucfirst($seqType); ?></td>
                     </tr>
                     <tr>
                        <td><strong>Output Format:</strong></td>
                        <td><?php echo strtoupper($_SESSION['outfmt']); ?></td>
                     </tr>
                     <tr>
                        <td><strong>Submission Time:</strong></td>
                        <td><?php echo isset($_SESSION['job_start_time']) ? formatDisplayTime($_SESSION['job_start_time']) : formatDisplayTime(''); ?></td>
                     </tr>
                     <?php if (!empty($runtime)): ?>
                        <tr>
                           <td><strong>Runtime:</strong></td>
                           <td><?php echo htmlspecialchars($runtime); ?></td>
                        </tr>
                     <?php endif; ?>
                  </table>
               </div>
            </div>
         </div>

         <div class="button-group">
            <a href="clustal.php" class="button">New Alignment</a>
            <a href="download.php" class="button">Download Results</a>
         </div>
      </div>

      <script>
         // JavaScript for tab switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Deactivate all tabs
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Activate clicked tab
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
      </script>
      <script>
      document.addEventListener('DOMContentLoaded', function() {
         if (typeof PhylogeneticTree !== 'undefined') {
            const treeContainer = document.getElementById('phylogenetic-tree-root');
            ReactDOM.render(React.createElement(PhylogeneticTree), treeContainer);
         } else {
            console.error('PhylogeneticTree component not found');
            document.getElementById('phylogenetic-tree-root').innerHTML = 
               '<div class="error-message">Error loading tree visualization component</div>';
         }
      });
      </script>

   </body>
</html>

<?php

if (file_exists($guideTreeFile)) {
   $_SESSION['guideTreeFile'] = $guideTreeFile; 
} else {
   $_SESSION['guideTreeFile'] = '';
}


//Cleanup temporary files 
unlink($_SESSION['msaResult']);
unset($_SESSION['msaResult']); 
?>