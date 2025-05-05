<?php
/**
 * Check Auth Status
 * 
 * This tool checks the current authentication status.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include common functions
require_once __DIR__ . '/../includes/common.php';

// Include auth check
if (file_exists(__DIR__ . '/../../admin/includes/auth-check.php')) {
    require_once __DIR__ . '/../../admin/includes/auth-check.php';
} else if (file_exists(__DIR__ . '/../../admin/includes/Auth.php')) {
    require_once __DIR__ . '/../../admin/includes/Auth.php';
}

// Check if user is authenticated
$isAuthenticated = false;
$user = null;

// Try different methods to check authentication
if (function_exists('isAuthenticated')) {
    $isAuthenticated = isAuthenticated();
} else if (class_exists('Auth') && method_exists('Auth', 'checkAuth')) {
    $user = Auth::checkAuth();
    $isAuthenticated = $user !== false;
} else if (isset($_SESSION['user_id']) || isset($_SESSION['user'])) {
    $isAuthenticated = true;
    $user = isset($_SESSION['user']) ? $_SESSION['user'] : ['id' => $_SESSION['user_id']];
}

// Get session and cookie information
$sessionData = $_SESSION;
$cookieData = $_COOKIE;

// Check for authentication tokens
$hasSessionToken = isset($_SESSION['token']);
$hasCookieToken = isset($_COOKIE['auth_token']);

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Check Auth Status</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .success {
            color: #4CAF50;
        }
        .error {
            color: #F44336;
        }
        .warning {
            color: #FF9800;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Check Auth Status</h1>
        <p class='lead'>This tool checks the current authentication status.</p>
        
        <div class='alert alert-" . ($isAuthenticated ? 'success' : 'warning') . " mb-4'>
            <h4 class='alert-heading'>Authentication Status: " . ($isAuthenticated ? 'Authenticated' : 'Not Authenticated') . "</h4>
            <p>" . ($isAuthenticated ? 'You are currently authenticated.' : 'You are not currently authenticated.') . "</p>
        </div>";

// Display user information
if ($isAuthenticated && $user) {
    echo "<div class='card mb-4'>";
    echo "<div class='card-header bg-info text-white'>";
    echo "<h2 class='m-0'>User Information</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<pre>" . htmlspecialchars(print_r($user, true)) . "</pre>";
    
    echo "</div>";
    echo "</div>";
}

// Display token information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Token Information</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<p><strong>Session Token:</strong> " . ($hasSessionToken ? '<span class="success">Present</span>' : '<span class="error">Missing</span>') . "</p>";
echo "<p><strong>Cookie Token:</strong> " . ($hasCookieToken ? '<span class="success">Present</span>' : '<span class="error">Missing</span>') . "</p>";

if ($hasSessionToken) {
    echo "<p><strong>Session Token Value:</strong> " . substr($_SESSION['token'], 0, 20) . "...</p>";
}

if ($hasCookieToken) {
    echo "<p><strong>Cookie Token Value:</strong> " . substr($_COOKIE['auth_token'], 0, 20) . "...</p>";
}

echo "</div>";
echo "</div>";

// Display session information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Session Information</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<p><strong>Session Data:</strong></p>";
echo "<pre>" . htmlspecialchars(print_r($sessionData, true)) . "</pre>";

echo "</div>";
echo "</div>";

// Display cookie information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Cookie Information</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<pre>" . htmlspecialchars(print_r($cookieData, true)) . "</pre>";

echo "</div>";
echo "</div>";

// Display actions
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Actions</h2>";
echo "</div>";
echo "<div class='card-body'>";

if ($isAuthenticated) {
    echo "<a href='../auth/logout.php' class='btn btn-danger me-2'>";
    echo "<i class='fas fa-sign-out-alt'></i> Logout";
    echo "</a>";
} else {
    echo "<a href='../../admin/login.php' class='btn btn-primary me-2'>";
    echo "<i class='fas fa-sign-in-alt'></i> Login";
    echo "</a>";
}

echo "<a href='../auth/clear_session.php' class='btn btn-warning me-2'>";
echo "<i class='fas fa-trash-alt'></i> Clear Session";
echo "</a>";

echo "</div>";
echo "</div>";

// HTML footer
echo "
        <div class='mt-4'>
            <a href='/diagnostic-dashboard.php' class='btn btn-primary'>
                <i class='fas fa-arrow-left'></i> Back to Diagnostic Dashboard
            </a>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
