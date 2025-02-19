<?php
/**
 * config.php
 * Configuration settings for web application
 */

 // Session configuration 
 ini_set('session.cookie_httponly', 1);
 ini_set('session.use_strict_mode', 1);

 session_start(); 

 // Base directories 
 $baseDir = dirname($_SERVER['SCRIPT_FILENAME']);
 $baseURL = dirname($_SERVER['SCRIPT_NAME']);

 // Temporary directory with cleanup
 $tmpDir = "$baseDir/tmp";
 if (!file_exists($tmpDir)) {
     if (!@mkdir($tmpDir, 0777, true)) {
         die("Error: Unable to create temporary directory");
     }
     chmod($tmpDir, 0777);
 }
 
// Ensure tmp directory is writable
if (!is_writable($tmpDir)) {
    chmod($tmpDir, 0777);
    if (!is_writable($tmpDir)) {
        die("Error: Temporary directory is not writable");
    }
}

  // Clustal configuration
 $clustalHome = "/usr/local/bin";
 $clustalExe = "$clustalHome/clustalo";


 // Session configuration
 ini_set('session.cookie_httponly', 1);
 ini_set('session.use_strict_mode', 1);

 // Validate Clustal Installation 
 if (!file_exists($clustalExe)) {
    die("Error: Clustal Omega not found at $clustalExe");
 }

 //Input Validation settings 
 define('MAX_SEQUENCE_LENGTH', 50000);
 define('MAX_FILE_SIZE', 5242880); //5MB 

 // Security headers 
 header('X-Frame-Options: DENY');
 header('X-Content-Type-Options: nosniff');
 header('X-XSS-Protection: 1; mode=block');

 //Error handling 
 error_reporting(E_ALL);
 ini_set('display_errors', 1);

 // Temporary file cleanup function
 function cleanupTempFiles($tmpDir) {
    $oldFiles = glob($tmpDir . "/msa*");
    foreach ($oldFiles as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
 }

 // Set the timezone 
 date_default_timezone_set('Europe/London');
 ?>