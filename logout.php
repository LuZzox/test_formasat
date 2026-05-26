<?php
// Logout script for FormaSat application This script will end the user's session and redirect them to the login page
session_start();
// Unset all session variables
session_unset();
// Destroy the session
session_destroy();
// Redirect to the login page
header('Location: login.php');
exit;
?>