<?php
/**
 * error.php
 * Display user-friendly error messages 
 */

require "config.php";
?>
<!DOCTYPE html>
<html lang="en">
   <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Clustal Alignment</title>
    <link rel="stylesheet" href="css/styles_clustal.css">
</head>
<body>
    <div class="container">
        <h2>Alignment Error</h1>
        <div class="error-box">
            <?php
            // Display error message from session
            if (isset($_SESSION['error'])) {
                echo htmlspecialchars($_SESSION['error']);
                // Clear the error after displaying
                unset($_SESSION['error']);
            } else {
                echo "An unknown error occurred during sequence alignment.";
            }
            ?>
        </div>
        <div class="actions">
            <a href="clustal.php" class="btn btn-primary">Start New Search</a>
        </div>
    </div>
</body>
</html>
<?php
// End session if needed
session_write_close();
?>