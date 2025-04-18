<?php
/**
 * Authentication Diagnostic Script
 * 
 * This script tests the authentication flow and provides detailed information
 * about any issues. It can be used to verify that the authentication fixes
 * are working correctly.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/admin/includes/config.php';
require_once __DIR__ . '/admin/includes/Database.php';
require_once __DIR__ . '/admin/includes/Auth.php';

// Initialize Auth
Auth::init($config['security']);

// Function to output diagnostic information
function outputDiagnostic($title, $data, $success = true) {
    echo "<div style='margin-bottom: 20px; padding: 10px; border: 1px solid " . ($success ? "#4CAF50" : "#F44336") . "; border-radius: 5px;'>";
    echo "<h3 style='margin-top: 0; color: " . ($success ? "#4CAF50" : "#F44336") . ";'>$title</h3>";
    
    if (is_array($data) || is_object($data)) {
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<p>$data</p>";
    }
    
    echo "</div>";
}

// Function to test API connection
function testApiConnection($url, $token = null) {
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set headers
    $headers = [
        'Accept: application/json'
    ];
    
    // Add authentication token if provided
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'error' => $error,
            'code' => 0,
            'response' => null
        ];
    }
    
    // Close cURL
    curl_close($ch);
    
    // Parse response
    $responseData = json_decode($response, true);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'code' => $httpCode,
        'response' => $responseData,
        'raw' => $response
    ];
}

// Function to decode JWT token
function decodeJwtToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    
    try {
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        
        return [
            'header' => $header,
            'payload' => $payload,
            'signature' => $parts[2]
        ];
    } catch (Exception $e) {
        return null;
    }
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Authentication Diagnostic</title>
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
        .action-button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .action-button.secondary {
            background-color: #2196F3;
        }
        .action-button.danger {
            background-color: #F44336;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Authentication Diagnostic</h1>";

// Check if we're performing an action
$action = $_GET['action'] ?? '';

// Handle actions
switch ($action) {
    case 'login':
        // Handle login
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) ? (bool)$_POST['remember'] : false;
            
            // Authenticate user
            $user = Auth::authenticate($email, $password);
            
            if ($user) {
                // Login user
                Auth::login($email, $password, $remember);
                
                outputDiagnostic("Login Successful", [
                    'user' => $user,
                    'session_token' => isset($_SESSION['token']) ? 'Present' : 'Missing',
                    'cookie_token' => isset($_COOKIE['auth_token']) ? 'Present' : 'Missing'
                ]);
                
                // Redirect to diagnostic page
                echo "<p>Redirecting to diagnostic page...</p>";
                echo "<script>setTimeout(function() { window.location.href = 'auth_diagnostic.php'; }, 2000);</script>";
            } else {
                outputDiagnostic("Login Failed", "Invalid email or password", false);
                
                // Show login form again
                echo "<p>Please try again:</p>";
                showLoginForm();
            }
        } else {
            // Show login form
            showLoginForm();
        }
        break;
        
    case 'logout':
        // Handle logout
        Auth::logout();
        
        outputDiagnostic("Logout Successful", [
            'session_token' => isset($_SESSION['token']) ? 'Present' : 'Missing',
            'cookie_token' => isset($_COOKIE['auth_token']) ? 'Present' : 'Missing'
        ]);
        
        // Redirect to diagnostic page
        echo "<p>Redirecting to diagnostic page...</p>";
        echo "<script>setTimeout(function() { window.location.href = 'auth_diagnostic.php'; }, 2000);</script>";
        break;
        
    case 'refresh':
        // Handle token refresh
        $user = Auth::checkAuth();
        
        if ($user) {
            // Refresh token
            $token = Auth::refreshToken($user, true);
            
            if ($token) {
                outputDiagnostic("Token Refresh Successful", [
                    'user' => $user,
                    'session_token' => isset($_SESSION['token']) ? 'Present' : 'Missing',
                    'cookie_token' => isset($_COOKIE['auth_token']) ? 'Present' : 'Missing'
                ]);
            } else {
                outputDiagnostic("Token Refresh Failed", "Failed to refresh token", false);
            }
        } else {
            outputDiagnostic("Token Refresh Failed", "User not authenticated", false);
        }
        
        // Redirect to diagnostic page
        echo "<p>Redirecting to diagnostic page...</p>";
        echo "<script>setTimeout(function() { window.location.href = 'auth_diagnostic.php'; }, 2000);</script>";
        break;
        
    case 'test_api':
        // Test API connection
        $apiUrl = API_URL;
        $token = $_SESSION['token'] ?? null;
        
        // Test unauthenticated endpoint
        $publicEndpoint = $apiUrl . '/stories?pageSize=1';
        $publicResult = testApiConnection($publicEndpoint);
        
        outputDiagnostic(
            "Public API Endpoint Test (/stories)",
            [
                'url' => $publicEndpoint,
                'status_code' => $publicResult['code'],
                'success' => $publicResult['success'],
                'response' => $publicResult['response']
            ],
            $publicResult['success']
        );
        
        // Test authenticated endpoint
        if ($token) {
            $privateEndpoint = $apiUrl . '/auth/me';
            $privateResult = testApiConnection($privateEndpoint, $token);
            
            outputDiagnostic(
                "Private API Endpoint Test (/auth/me)",
                [
                    'url' => $privateEndpoint,
                    'token' => $token,
                    'status_code' => $privateResult['code'],
                    'success' => $privateResult['success'],
                    'response' => $privateResult['response']
                ],
                $privateResult['success']
            );
        } else {
            outputDiagnostic("Private API Endpoint Test", "No authentication token available", false);
        }
        
        // Back to diagnostic page
        echo "<p><a href='auth_diagnostic.php' class='action-button'>Back to Diagnostic</a></p>";
        break;
        
    default:
        // Show diagnostic information
        showDiagnosticInfo();
        break;
}

// Function to show login form
function showLoginForm() {
    echo "
    <h2>Login</h2>
    <form method='post' action='auth_diagnostic.php?action=login'>
        <div style='margin-bottom: 15px;'>
            <label for='email' style='display: block; margin-bottom: 5px;'>Email:</label>
            <input type='email' id='email' name='email' required style='padding: 8px; width: 300px;'>
        </div>
        <div style='margin-bottom: 15px;'>
            <label for='password' style='display: block; margin-bottom: 5px;'>Password:</label>
            <input type='password' id='password' name='password' required style='padding: 8px; width: 300px;'>
        </div>
        <div style='margin-bottom: 15px;'>
            <label>
                <input type='checkbox' name='remember' value='1'>
                Remember me
            </label>
        </div>
        <div>
            <button type='submit' style='padding: 10px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer;'>Login</button>
            <a href='auth_diagnostic.php' style='margin-left: 10px; text-decoration: none;'>Cancel</a>
        </div>
    </form>";
}

// Function to show diagnostic information
function showDiagnosticInfo() {
    global $config;
    
    // Check if user is authenticated
    $user = Auth::checkAuth();
    $authenticated = $user !== false;
    
    // Get session and cookie information
    $sessionToken = isset($_SESSION['token']) ? $_SESSION['token'] : null;
    $cookieToken = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
    
    // Decode tokens if available
    $sessionTokenData = $sessionToken ? decodeJwtToken($sessionToken) : null;
    $cookieTokenData = $cookieToken ? decodeJwtToken($cookieToken) : null;
    
    // Check token consistency
    $tokensConsistent = ($sessionToken && $cookieToken) ? ($sessionToken === $cookieToken) : true;
    
    // Check token expiration
    $sessionTokenExpired = false;
    $cookieTokenExpired = false;
    
    if ($sessionTokenData && isset($sessionTokenData['payload']['exp'])) {
        $sessionTokenExpired = $sessionTokenData['payload']['exp'] < time();
    }
    
    if ($cookieTokenData && isset($cookieTokenData['payload']['exp'])) {
        $cookieTokenExpired = $cookieTokenData['payload']['exp'] < time();
    }
    
    // Output authentication status
    echo "<h2>Authentication Status</h2>";
    echo "<p><strong>Authenticated:</strong> " . ($authenticated ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    
    if ($authenticated) {
        echo "<p><strong>User:</strong></p>";
        echo "<pre>" . print_r($user, true) . "</pre>";
    }
    
    // Output token information
    echo "<h2>Token Information</h2>";
    echo "<p><strong>Session Token:</strong> " . ($sessionToken ? "<span class='success'>Present</span>" : "<span class='error'>Missing</span>") . "</p>";
    echo "<p><strong>Cookie Token:</strong> " . ($cookieToken ? "<span class='success'>Present</span>" : "<span class='error'>Missing</span>") . "</p>";
    echo "<p><strong>Tokens Consistent:</strong> " . ($tokensConsistent ? "<span class='success'>Yes</span>" : "<span class='error'>No</span>") . "</p>";
    
    if ($sessionToken) {
        echo "<p><strong>Session Token Expired:</strong> " . ($sessionTokenExpired ? "<span class='error'>Yes</span>" : "<span class='success'>No</span>") . "</p>";
        
        if ($sessionTokenData) {
            echo "<p><strong>Session Token Data:</strong></p>";
            echo "<pre>" . print_r($sessionTokenData, true) . "</pre>";
        }
    }
    
    if ($cookieToken) {
        echo "<p><strong>Cookie Token Expired:</strong> " . ($cookieTokenExpired ? "<span class='error'>Yes</span>" : "<span class='success'>No</span>") . "</p>";
        
        if ($cookieTokenData) {
            echo "<p><strong>Cookie Token Data:</strong></p>";
            echo "<pre>" . print_r($cookieTokenData, true) . "</pre>";
        }
    }
    
    // Output configuration information
    echo "<h2>Configuration</h2>";
    echo "<p><strong>JWT Secret:</strong> " . (isset($config['security']['jwt_secret']) ? substr($config['security']['jwt_secret'], 0, 5) . '...' : 'Not set') . "</p>";
    echo "<p><strong>Token Expiry:</strong> " . (isset($config['security']['token_expiry']) ? $config['security']['token_expiry'] . ' seconds (' . round($config['security']['token_expiry'] / 3600, 2) . ' hours)' : 'Not set') . "</p>";
    echo "<p><strong>API URL:</strong> " . (defined('API_URL') ? API_URL : 'Not defined') . "</p>";
    
    // Output session information
    echo "<h2>Session Information</h2>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
    echo "<p><strong>Session Data:</strong></p>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    // Output cookie information
    echo "<h2>Cookie Information</h2>";
    echo "<pre>" . print_r($_COOKIE, true) . "</pre>";
    
    // Output actions
    echo "<h2>Actions</h2>";
    
    if ($authenticated) {
        echo "<a href='auth_diagnostic.php?action=logout' class='action-button danger'>Logout</a>";
        echo "<a href='auth_diagnostic.php?action=refresh' class='action-button'>Refresh Token</a>";
    } else {
        echo "<a href='auth_diagnostic.php?action=login' class='action-button'>Login</a>";
    }
    
    echo "<a href='auth_diagnostic.php?action=test_api' class='action-button secondary'>Test API Connection</a>";
}

// HTML footer
echo "
    </div>
</body>
</html>";

?>