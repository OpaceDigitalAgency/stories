<?php
// Logout script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear session
unset($_SESSION['user']);
session_destroy();

// Clear cookie
setcookie('auth_token', '', time() - 3600, '/', '', false, true);

echo "<h1>Logged Out</h1>";
echo "<p>You have been successfully logged out.</p>";
echo "<p><a href='direct_login.php'>Log in again</a></p>";