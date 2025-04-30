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

// Determine the correct path to simple_auth.php based on the current file location
$basePath = dirname($_SERVER['SCRIPT_FILENAME']);
$adminDir = strpos($basePath, '/admin/content') !== false ? '../../' : '../';
require_once $adminDir . 'simple_auth.php';

// Initialize SimpleAuth with database config
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

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    // If this is an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Otherwise, redirect to login page
    $loginPath = $adminDir === '../' ? 'login.php' : '../login.php';
    header("Location: $loginPath");
    exit;
}
