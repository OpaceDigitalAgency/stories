<?php
/**
 * Token Refresh Test Script
 * 
 * This script tests the token refresh mechanism in the admin panel.
 */

// Include required files
require_once __DIR__ . '/admin/includes/config.php';
require_once __DIR__ . '/admin/includes/Database.php';
require_once __DIR__ . '/admin/includes/Auth.php';
require_once __DIR__ . '/admin/includes/ApiClient.php';

// Initialize Auth with config
Auth::init($config['security']);

// Output test information
echo "Token Refresh Test\n";
echo "=================\n\n";

// Test user data
$testUser = [
    'id' => 3,
    'name' => 'Test User',
    'email' => 'test@example.com',
    'role' => 'admin'
];

// Generate a token that's about to expire (5 minutes from now)
$payload = [
    'user_id' => $testUser['id'],
    'role' => $testUser['role'],
    'iat' => time(),
    'exp' => time() + 300 // 5 minutes from now
];

// Create JWT header
$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
$header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');

// Create JWT payload
$jwtPayload = json_encode($payload);
$jwtPayload = rtrim(strtr(base64_encode($jwtPayload), '+/', '-_'), '=');

// Create signature
$signature = hash_hmac('sha256', "$header.$jwtPayload", $config['security']['jwt_secret'], true);
$signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

// Create JWT token
$token = "$header.$jwtPayload.$signature";

echo "Generated token with 30-second expiry: " . $token . "\n";
echo "Token payload: " . json_encode($payload) . "\n\n";

// Store token in session and cookie
$_SESSION['token'] = $token;
$_SESSION['user'] = $testUser;
setcookie('auth_token', $token, time() + 86400, '/', '', false, true);

echo "Token stored in session and cookie\n\n";

// Initialize API client with the token
$apiClient = new ApiClient(API_URL, $token);

// Test token refresh
echo "Testing token refresh...\n";

// Method 1: Using Auth::refreshToken
echo "Method 1: Using Auth::refreshToken\n";
$refreshedToken1 = Auth::refreshToken($testUser);
echo "Refreshed token: " . ($refreshedToken1 ? "Success" : "Failed") . "\n";
if ($refreshedToken1) {
    echo "New token: " . $refreshedToken1 . "\n";
    
    // Decode token to verify expiry
    $parts = explode('.', $refreshedToken1);
    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    echo "New token expiry: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";
    echo "Expires in: " . ($payload['exp'] - time()) . " seconds\n";
}
echo "\n";

// Method 2: Using ApiClient::refreshToken (private method, need to use reflection)
echo "Method 2: Using ApiClient::refreshToken\n";
$reflectionClass = new ReflectionClass('ApiClient');
$refreshTokenMethod = $reflectionClass->getMethod('refreshToken');
$refreshTokenMethod->setAccessible(true);
$refreshResult = $refreshTokenMethod->invoke($apiClient);
echo "Refresh result: " . ($refreshResult ? "Success" : "Failed") . "\n";
if ($refreshResult) {
    echo "New token in session: " . (isset($_SESSION['token']) ? "Present" : "Missing") . "\n";
    echo "New token in cookie: " . (isset($_COOKIE['auth_token']) ? "Present" : "Missing") . "\n";
}
echo "\n";

// Method 3: Using API endpoint directly
echo "Method 3: Using API endpoint directly\n";
$ch = curl_init();
$url = rtrim(API_URL, '/') . '/auth/refresh';

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]
]);

$requestData = [
    'user_id' => $testUser['id'],
    'force' => true
];
$jsonData = json_encode($requestData);
echo "Raw JSON data: $jsonData\n";
echo "PHP array: " . print_r($requestData, true) . "\n";
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

// Print request details for debugging
echo "Request URL: $url\n";
echo "Request Headers: " . json_encode(curl_getinfo($ch, CURLINFO_HEADER_OUT)) . "\n";
echo "Request Data: $jsonData\n";

// Enable verbose output
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
echo "Verbose information:\n" . $verboseLog . "\n";

if (curl_errno($ch)) {
    echo "cURL error: " . curl_error($ch) . "\n";
} else {
    echo "HTTP response code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
    
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['token'])) {
        echo "New token from API: " . $responseData['token'] . "\n";
    }
}

curl_close($ch);

// Output debug information
echo "\nDebug Information\n";
echo "================\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Error Reporting: " . ini_get('error_reporting') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Log Errors: " . ini_get('log_errors') . "\n";
echo "Error Log: " . ini_get('error_log') . "\n";