<?php
/**
 * runClustal.php
 * Perform multiple sequence alignment using Clustal-Omega 
 */
// Load configuration
require_once "config.php";

// Function to detect sequence type (DNA, RNA or Protein)
function detectSequenceType($sequence) {
    // Remove FASTA header and whitespace 
    $sequence = preg_replace('/^>.*/m', '', $sequence);
    $sequence = preg_replace('/\s+/', '', $sequence);

    // Calculate percentages of nucleotides and amino acids
    $length = strlen($sequence);
    if ($length == 0) return 'unknown';

    $dnaCount = preg_match_all('/[ATCGatcg]/', $sequence);
    $rnaCount = preg_match_all('/[AUCGaucg]/', $sequence);
    $proteinCount = preg_match_all('/[ACDEFGHIKLMNPQRSTVWY]/', $sequence);

    $dnaPercent = $dnaCount / $length;
    $rnaPercent = $rnaCount / $length;
    $proteinPercent = $proteinCount / $length;

    // Determine sequence type based on percentages 
    if ($dnaPercent > 0.9) {
        return 'dna';
    } elseif ($rnaPercent > 0.9) {
        return 'rna';
    } elseif ($proteinPercent > 0.9) {
        return 'protein';
    } else {
        return 'unknown';
    }

}

// Uniprot IDs 
function fetchUniprotSequence($id) {
    $url= "https://www.uniprot.org/uniprot/{$id}.fasta";
    $sequence = @file_get_contents($url);
    return $sequence ?: false;
}

function validateFasta($sequence) {
    if (empty($sequence)) return false;
    $lines = explode("\n", trim($sequence));
    return substr($lines[0], 0, 1) == '>' && count($lines) > 1;
}

// Process input 
try {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    $sequences = "";
    switch($_POST['input_type']) {
        case 'sequences':
            if (empty($_POST['seqQuery'])) {
                throw new Exception("No sequences provided");
            }
            $sequences = $_POST['seqQuery'];
            break;
        
        case 'uniprot':
            if (empty($_POST['uniprotIds'])) {
                throw new Exception("No UniProt IDs provided");
            }
            $ids = array_filter(explode("\n", trim($_POST['uniprotIds'])));
            foreach ($ids as $id) {
                $seq = fetchUniprotSequence(trim($id));
                if (!$seq) {
                    throw new Exception("Failed to fetch sequence for ID: $id");
                }
                $sequences .= $seq . "\n";
            }
            break;
        
        case 'file':
            if (!isset($_FILES['fastaFile']) || $_FILES['fastaFile']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload failed");
            }
            $sequences = file_get_contents($_FILES['fastaFile']['tmp_name']);
            break;
    }

    if (!validateFasta($sequences)) {
        throw new Exception("Invalid FASTA format");
    }

    // Detect sequence type 
    $seqType = detectSequenceType($sequences);
    $_SESSION['sequence_type'] = $seqType;

    // Create a unique temporary file 
    $tempFile = $tmpDir . "/" . uniqid('msa');
    $queryFile = $tempFile . ".fasta";
    $outputFile = $tempFile . ".aln";

    // Save query sequence to a file 
    file_put_contents($queryFile, $sequences);

    // Build Clustal command 
    $cmd = $clustalExe; 
    $cmd .= " -i " . escapeshellarg($queryFile);
    $cmd .= " -o " . escapeshellarg($outputFile);
    $cmd .= " --guidetree-out=" . escapeshellarg($outputFile . '.dnd');

    // Handle output format 
    $outfmt = $_POST['outfmt'];
    switch ($outfmt) {
        case 'clu':
        case 'fa':
        case 'msf':
            $cmd .= " --outfmt=" . escapeshellarg($outfmt);
            break;
        case 'nexus':
        case 'phylip':
        case 'selex':
        case 'stockholm':
        case 'vienna':
            $cmd .= " --outfmt=fa";
            break;
        default:
            $cmd .= " --outfmt=clu";
    }

    // Set sequence type if detected 
    if ($seqType !== 'unknown') {
        $cmd .= " --seqtype=" . escapeshellarg($seqType);
    }

    if (isset($_POST['full'])) $cmd .= " --full";
    if (isset($_POST['force'])) $cmd .= " --force";
    if (!empty($_POST['iterations'])) {
        $cmd .= " --iterations=" . escapeshellarg($_POST['iterations']);
    }

    exec($cmd . " 2>&1", $output, $returnVar);

    if ($returnVar !== 0) {
        throw new Exception("Clustal-Omega execution failed: " . implode("\n", $output));
    }

    // Convert format if needed 
    if (in_array($outfmt, ['nexus', 'phylip', 'selex', 'stockholm', 'vienna'])) {
        $convertedFile = $tempFile . "." . $outfmt;
        // Use a format conversion library or tool
        $convertCmd = "seqret -sequence " . escapeshellarg($outputFile) .
                        " -outseq " . escapeshellarg($convertedFile) .
                        " -osformat " . escapeshellarg($outfmt);
        exec($convertCmd . " 2>&1", $convertOutput, $convertReturnVar);

        if ($convertReturnVar == 0 && file_exists($convertedFile)) {
            $outputFile = $convertedFile;
        } else {
            // If conversion fails, keep the original format, but log the error
            error_log("Format conversion to $outfmt failed: " . implode("\n", $convertOutput));
        }
    }

    // Store title if provided 
    if (!empty($_POST['alignment_title'])) {
        $_SESSION['alignment_title'] = $_POST['alignment_title'];
    } else {
        $_SESSION['alignment_title'] = "Clustal Omega Alignment Results";
    }

    $_SESSION['msaResult'] = $outputFile;
    $_SESSION['outfmt'] = $outfmt;
    header('Location: getResults.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: error.php');
    exit();
}
?>