<?php
/**
 * API Endpoints Test Script for Admin
 * 
 * This script includes the API's endpoints test script.
 */

// Include authentication check
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Auth.php';

// Initialize Auth
Auth::init(require __DIR__ . '/includes/config.php');

// Check if user is authenticated
$user = Auth::checkAuth();
if (!$user) {
    // Redirect to login page
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the API's test_endpoints.php script
include_once __DIR__ . '/../api/test_endpoints.php';
?>