<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Page variables
$pageTitle = 'Logout';
$currentPage = 'logout';

require_once '../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Logout user
SimpleAuth::logout();

// Explicitly destroy the session
session_unset();
session_destroy();

// Clear any problematic cookies
if (isset($_COOKIE['auth_token'])) {
    setcookie('auth_token', '', time() - 3600, '/', '', false, true);
}

// Redirect to login page
header("Location: login.php");
exit;

// No footer needed for logout page
