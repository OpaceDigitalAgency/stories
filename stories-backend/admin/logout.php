<?php
/**
 * Logout Page
 * 
 * This page handles user logout for the admin UI.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

// Include required files
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize Auth
Auth::init($config['security']);

// Logout user
Auth::logout();

// Set success message
if (!isset($_SESSION['success'])) {
    $_SESSION['success'] = [];
}
$_SESSION['success'][] = 'You have been successfully logged out.';

// Redirect to login page
header('Location: ' . ADMIN_URL . '/login.php');
exit;