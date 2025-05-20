<?php
/**
 * Authentication Check Include
 *
 * This file checks if the user is authenticated.
 * It should be included at the top of each admin page.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Include SimpleAuth
require_once __DIR__ . '/../../simple_auth.php';

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!SimpleAuth::check()) {
    // Clear any problematic cookies or session data
    if (isset($_COOKIE['auth_token'])) {
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);
    }

    // Destroy the session
    session_unset();
    session_destroy();

    // Redirect to login page with absolute path
    header('Location: ../login.php');
    exit;
}

// Update last activity time
$_SESSION['last_activity'] = time();


// No footer include here - this is just an authentication check
