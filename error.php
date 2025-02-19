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
    <title>Error - Clustal Alignment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }
        .error-box {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .actions {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Alignment Error</h1>
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
        <a href="clustal.php" style="text-decoration: none; background-color: #007bff; color: white; padding: 10px 20px; border-radius: 5px;">Start New Search</a>
    </div>
</body>
</html>
<?php
// End session if needed
session_write_close();
?>