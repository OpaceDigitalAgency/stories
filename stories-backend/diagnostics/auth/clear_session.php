<?php
/**
 * Clear Session
 * 
 * This tool clears all session data and cookies to fix login issues.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store session data for display
$oldSessionData = $_SESSION;
$oldCookieData = $_COOKIE;

// Clear session
$_SESSION = [];

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Clear auth cookies
setcookie('auth_token', '', time() - 3600, '/');
setcookie('remember_token', '', time() - 3600, '/');
setcookie('user_id', '', time() - 3600, '/');

// Start new session
session_start();

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Clear Session</title>
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
        <h1>Clear Session</h1>
        <p class='lead'>This tool clears all session data and cookies to fix login issues.</p>
        
        <div class='alert alert-success mb-4'>
            <h4 class='alert-heading'>Session Cleared Successfully</h4>
            <p>All session data and authentication cookies have been cleared.</p>
        </div>";

// Display old session information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Previous Session Data</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<pre>" . htmlspecialchars(print_r($oldSessionData, true)) . "</pre>";

echo "</div>";
echo "</div>";

// Display old cookie information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Previous Cookie Data</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<pre>" . htmlspecialchars(print_r($oldCookieData, true)) . "</pre>";

echo "</div>";
echo "</div>";

// Display new session information
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>New Session Information</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
echo "<p><strong>Session Data:</strong></p>";
echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";

echo "</div>";
echo "</div>";

// Display actions
echo "<div class='card mb-4'>";
echo "<div class='card-header bg-info text-white'>";
echo "<h2 class='m-0'>Actions</h2>";
echo "</div>";
echo "<div class='card-body'>";

echo "<a href='../../admin/login.php' class='btn btn-primary me-2'>";
echo "<i class='fas fa-sign-in-alt'></i> Go to Login Page";
echo "</a>";

echo "<a href='../auth/check_auth_status.php' class='btn btn-info me-2'>";
echo "<i class='fas fa-user-check'></i> Check Auth Status";
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
