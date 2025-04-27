<?php
/**
 * Token Refresh Test Script
 * 
 * This script tests the token refresh functionality with the new fixes.
 */

// Include necessary files
require_once __DIR__ . '/api/v1/config/config.php';
require_once __DIR__ . '/api/v1/Core/Database.php';
require_once __DIR__ . '/api/v1/Core/Auth.php';

use StoriesAPI\Core\Auth;

// Initialize Auth with config
Auth::init($config['security']);

echo "Token Refresh Test (Fixed Version)\n";
echo "=================\n";

// Generate a test token with a short expiry (30 seconds)
$userId = 3; // Use a valid user ID from your database
$shortExpiryToken = generateShortExpiryToken($userId);

echo "Generated token with 30-second expiry: $shortExpiryToken\n";

// Decode the token to show the payload
$parts = explode('.', $shortExpiryToken);
if (count($parts) === 3) {
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    echo "Token payload: " . json_encode($payload) . "\n";
    
    // Calculate and display expiry time
    if (isset($payload['exp'])) {
        $expiryTime = date('Y-m-d H:i:s', $payload['exp']);
        $expiresIn = $payload['exp'] - time();
        echo "Token expires at: $expiryTime (in $expiresIn seconds)\n";
    }
}

// Store token in session and cookie for testing
$_SESSION['token'] = $shortExpiryToken;
setcookie('auth_token', $shortExpiryToken, time() + 3600, '/', '', false, true);
echo "Token stored in session and cookie\n";

// Test token refresh
echo "\nTesting token refresh...\n";

// Method 1: Using Auth::refreshToken directly
echo "Method 1: Using Auth::refreshToken\n";
$refreshed = Auth::refreshToken($userId, true); // Force refresh
if ($refreshed) {
    echo "Refreshed token: Success\n";
    echo "New token: $refreshed\n";
    
    // Decode the new token to show the payload
    $parts = explode('.', $refreshed);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (isset($payload['exp'])) {
            $expiryTime = date('Y-m-d H:i:s', $payload['exp']);
            $expiresIn = $payload['exp'] - time();
            echo "New token expiry: $expiryTime\n";
            echo "Expires in: $expiresIn seconds\n";
        }
    }
} else {
    echo "Refreshed token: Failed\n";
}

// Method 2: Using API endpoint directly
echo "\nMethod 3: Using API endpoint directly\n";

// Prepare request data
$requestData = [
    'user_id' => $userId,
    'force' => true
];
$jsonData = json_encode($requestData);

echo "Raw JSON data: $jsonData\n";
echo "PHP array: " . print_r($requestData, true) . "\n";

// Set up cURL request
$url = 'https://api.storiesfromtheweb.org/api/v1/auth/refresh';
echo "Request URL: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $shortExpiryToken
]);

// Enable verbose output
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

// Output results
echo "Request Headers: " . print_r(curl_getinfo($ch, CURLINFO_HEADER_OUT), true) . "\n";
echo "Request Data: $jsonData\n";
echo "Verbose information: $verboseLog\n";
echo "HTTP response code: $httpCode\n";
echo "Response: $response\n";

curl_close($ch);

// Debug information
echo "\nDebug Information\n";
echo "================\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Error Reporting: " . ini_get('error_reporting') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Log Errors: " . ini_get('log_errors') . "\n";
echo "Error Log: " . ini_get('error_log') . "\n";

// Helper function to generate a token with a short expiry
function generateShortExpiryToken($userId) {
    global $config;
    
    // Get user data
    $db = StoriesAPI\Core\Database::getInstance();
    $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1";
    $stmt = $db->query($query, [$userId]);
    
    if ($stmt->rowCount() === 0) {
        die("User not found with ID: $userId\n");
    }
    
    $user = $stmt->fetch();
    
    // Create payload with short expiry (30 seconds)
    $issuedAt = time();
    $payload = [
        'user_id' => $user['id'],
        'role' => $user['role'],
        'iat' => $issuedAt,
        'exp' => $issuedAt + 300 // 5 minutes expiry for testing
    ];
    
    // Create JWT header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $header = base64UrlEncode($header);
    
    // Create JWT payload
    $payloadJson = json_encode($payload);
    $payloadBase64 = base64UrlEncode($payloadJson);
    
    // Create signature
    $signature = hash_hmac('sha256', "$header.$payloadBase64", $config['security']['jwt_secret'], true);
    $signature = base64UrlEncode($signature);
    
    // Create JWT token
    return "$header.$payloadBase64.$signature";
}

// Helper function for base64 URL encoding
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}