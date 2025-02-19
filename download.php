<?php
/**
 * download.php 
 * Handle file downloads for alignment results 
 */

 require "config.php";

 // Check if results exist 
 if (!isset($_SESSION['alignment_data']) || !isset($_SESSION['file_extension'])) {
    $_SESSION['error'] = "No alignment results available";
    header('Location: error.php');
    exit();
 }

 // Get the results from session
 $alignmentResults = $_SESSION['alignment_data'];
 $extension = $_SESSION['file_extension'];

 // Set appropriate header for download 
 header('Content-Type: text/plain');
 header("Content-Disposition: attachment; filename=clustal_alignment.$extension");
 header('Cache-Control: must-revalidate');
 header('Pragma: public');

 // Output the results 
 echo $alignmentResults;

 // Cleanup session data after download 
 unset($_SESSION['alignment_data']);
 unset($_SESSION['file_extension']); 
 exit();
 ?>