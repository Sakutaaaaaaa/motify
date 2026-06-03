<?php
// logout.php
session_start();
session_unset();    // Remove all session variables
session_destroy();  // Destroy the session completely
header("Location: index.php"); // Redirect back to the login page
exit();
?>