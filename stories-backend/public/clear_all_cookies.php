<?php
/**
 * Clear All Cookies Script
 * 
 * This script clears all cookies and sessions to fix redirect loop issues.
 * Access this page directly in your browser when experiencing redirect loops.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear auth_token cookie
setcookie('auth_token', '', time() - 3600, '/', '', false, true);

// Clear all cookies
foreach ($_COOKIE as $name => $value) {
    setcookie($name, '', time() - 3600, '/', '', false, true);
}

// Output success message
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookies Cleared</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
        }
        h1 {
            color: #333;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>All Cookies and Sessions Cleared</h1>
        <p class="success">✓ All session data and cookies have been cleared successfully.</p>
        <p>This should fix any redirect loop issues you were experiencing.</p>
        <p>If you continue to experience issues, please try:</p>
        <ol>
            <li>Clearing your browser cache</li>
            <li>Using a different browser</li>
            <li>Contacting the administrator</li>
        </ol>
        <a href="/admin/login.php" class="btn">Go to Login Page</a>
    </div>
</body>
</html>';
