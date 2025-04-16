<?php
// Check authentication status
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Authentication Status Check</h1>";

// Check session
echo "<h2>Session Data</h2>";
if (isset($_SESSION['user'])) {
    echo "<p>✅ User is logged in via session</p>";
    echo "<pre>" . print_r($_SESSION['user'], true) . "</pre>";
} else {
    echo "<p>❌ No user in session</p>";
}

// Check cookie
echo "<h2>Cookie Data</h2>";
if (isset($_COOKIE['auth_token'])) {
    echo "<p>✅ Auth token cookie exists</p>";
    echo "<p>Token: " . substr($_COOKIE['auth_token'], 0, 20) . "...</p>";
    
    // Try to decode token
    $parts = explode('.', $_COOKIE['auth_token']);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        echo "<p>Token payload:</p>";
        echo "<pre>" . print_r($payload, true) . "</pre>";
    }
} else {
    echo "<p>❌ No auth token cookie</p>";
}

// Provide links
echo "<h2>Actions</h2>";
echo "<p><a href='direct_login.php' class='btn btn-primary'>Log in directly</a></p>";
echo "<p><a href='admin/index.php' class='btn btn-success'>Go to Admin Dashboard</a></p>";
echo "<p><a href='admin/login.php' class='btn btn-secondary'>Go to Regular Login Page</a></p>";

// Provide logout option
echo "<h2>Logout</h2>";
echo "<p><a href='logout.php' class='btn btn-danger'>Logout</a></p>";