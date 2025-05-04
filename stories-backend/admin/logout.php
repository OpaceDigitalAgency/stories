<?php
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

// Redirect to login page
header("Location: login.php");
exit;

// No footer needed for logout page
