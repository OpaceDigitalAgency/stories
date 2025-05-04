<?php
/**
 * Authentication Check Include
 *
 * This file checks if the user is authenticated.
 * It should be included at the top of each admin page.
 *
 * Usage:
 * include '../includes/auth-check.php';
 * // Now $user is available for use
 */

// Start session if not already started and no output has been sent
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    session_start();
} elseif (session_status() == PHP_SESSION_NONE && headers_sent()) {
    error_log("Warning: Attempted to start session after headers were sent in auth-check.php");
}

// Determine if we're in the content directory or main admin directory
$isContentDir = strpos($_SERVER['SCRIPT_FILENAME'], '/admin/content/') !== false;

// Set the correct path to simple_auth.php
$simpleAuthPath = $isContentDir ? '../../simple_auth.php' : '../simple_auth.php';

// Include simple_auth.php
require_once $simpleAuthPath;

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

// Get the current script name
$currentScript = basename($_SERVER['SCRIPT_FILENAME']);

// Check if user is logged in
$user = SimpleAuth::check();

// If not logged in and not on login page, redirect to login
if (!$user && $currentScript !== 'login.php') {
    // Set the correct login path
    $loginPath = $isContentDir ? '../login.php' : 'login.php';

    // Redirect to login
    header("Location: $loginPath");
    exit;
}


// No footer include here - this is just an authentication check
