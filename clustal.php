<?php

// Load configuration
require "config.php"; 
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Clustal Omega - Multiple Sequence Alignment</title>
        <link rel="stylesheet" href="css/styles_clustal.css">
        <script> 
        document.addEventListener('DOMContentLoaded', function() {
                // Function to toggle visibility of input sections
                function toggleInputSections() {
                    const inputType = document.querySelector('input[name="input_type"]:checked').value;
                    
                    // Hide all input sections first
                    document.getElementById('seqQuerySection').style.display = 'none';
                    document.getElementById('uniprotIdsSection').style.display = 'none';
                    document.getElementById('fastaFileSection').style.display = 'none';
                    
                    // Show the selected input section
                    switch(inputType) {
                        case 'sequences':
                            document.getElementById('seqQuerySection').style.display = 'block';
                            break;
                        case 'uniprot':
                            document.getElementById('uniprotIdsSection').style.display = 'block';
                            break;
                        case 'file':
                            document.getElementById('fastaFileSection').style.display = 'block';
                            break;
                    }
                }
                
                // Add event listeners to the radio buttons
                const inputTypeRadios = document.querySelectorAll('input[name="input_type"]');
                inputTypeRadios.forEach(radio => {
                    radio.addEventListener('change', toggleInputSections);
                });
                
                // Initialize display based on default selection
                toggleInputSections();
            });
        </script>
</head>
<body>
    <div class="container">
        <h2>Run Clustal Omega</h2>
        <form action="runClustal.php" method="post" enctype="multipart/form-data" id="clustalForm">
            <div class="form-selection">
                <div class="input-selection">
                    <h3>Input Sequences</h3>
                    <div class="input-type-selection">
                        <label>
                            <input type="radio" name="input_type" value="sequences" checked>
                            Enter sequences:
                        </label>
                        <label>
                            <input type="radio" name="input_type" value="uniprot">
                            Enter Uniprot IDs (one per line):
                        </label>
                        <label>
                            <input type="radio" name="input_type" value="file">
                            Upload FASTA file:
                        </label>
                    </div>

                    <div id="seqQuerySection">
                        <textarea name="seqQuery" id="seqQuery" rows="6" placeholder="Enter FASTA format sequences"></textarea>
                    </div>

                    <div id="uniprotIdsSection">
                        <textarea name="uniprotIds" id="uniprotIds" rows="6" placeholder="Enter Uniprot IDs (one per line)"></textarea>
                    </div>

                    <div id="fastaFileSection">
                        <input type="file" name="fastaFile" id="fastaFile" accept=".fasta,.fa,.txt">
                    </div>
                    
                    <div class="options-section">
                        <h3>Output Options</h3>
                        <label>Alignment Title:
                            <input type="text" name="alignment_title" placeholder="My Sequence Alignment">
                        </label>

                        <label>Output Format:
                        <select name="outfmt" required>
                            <option value="clu">Clustal format</option>
                            <option value="fa">FASTA format</option>
                            <option value="msf">MSF format</option>
                            <option value="nexus">Nexus format</option>
                            <option value="phylip">Phylip format</option>
                            <option value="selex">Selex format</option>
                            <option value="stockholm">Stockholm format</option>
                            <option value="vienna">Vienna format</option>
                        </select>
                    </label>

                    <h4>Additional Options</h4>
                    <label><input type="checkbox" name="full" value="1">Full distance matrix</label>
                    <label><input type="checkbox" name="force" value="1">Force overwrite</label>
                    <label>Number of iterations:
                        <input type="number" name="iterations" min="0" max="5" value="0">
                    </label>
                </div>
            </div>

            <div style="clear: both; width: 100%; text-align: center;">
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">Run Clustal-Omega</button>
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                    <a href="clustal.php" class="btn btn-secondary">New Alignment</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
