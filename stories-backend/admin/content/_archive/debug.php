<?php

// Include auth check
require_once '../includes/auth-check.php';

// Page variables
$pageTitle = 'Debug';
$currentPage = 'debug';

// Include header
require_once '../includes/header.php';

// Output all session data
echo "<h1>Session Data</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Output all cookie data
echo "<h1>Cookie Data</h1>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Output server info
echo "<h1>Server Info</h1>";
echo "<pre>";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "</pre>";

// Check authentication
$user = SimpleAuth::check();
echo "<h1>Authentication Status</h1>";
echo "<pre>";
echo "Is authenticated: " . ($user ? "YES" : "NO") . "\n";
if ($user) {
    echo "User data: \n";
    print_r($user);
}
echo "</pre>";
?>


// Include footer
require_once '../includes/footer.php';
