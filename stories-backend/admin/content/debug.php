<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'Debug';
$currentPage = 'debug';

// Start session
session_start();

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

// Check if user is logged in using SimpleAuth
require_once '../../simple_auth.php';

// Initialize SimpleAuth
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

SimpleAuth::initDB($config);

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
