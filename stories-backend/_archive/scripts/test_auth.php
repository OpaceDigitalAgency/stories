<?php
/**
 * Test Authentication Script
 * 
 * This script tests the authentication flow for the API.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define API URL
$apiUrl = 'http://localhost/stories-backend/api';

// Function to make API requests
function apiRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    global $apiUrl;
    
    $url = $apiUrl . '/' . ltrim($endpoint, '/');
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    // Set headers
    $headers = [
        'Accept: application/json'
    ];
    
    // Add authentication token if provided
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    // Add content type for POST and PUT requests
    if ($method === 'POST' || $method === 'PUT') {
        $headers[] = 'Content-Type: application/json';
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Add request data for POST and PUT requests
    if (($method === 'POST' || $method === 'PUT') && $data !== null) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    }
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for errors
    if (curl_errno($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }
    
    // Close cURL
    curl_close($ch);
    
    // Parse response
    $responseData = json_decode($response, true);
    
    // Return response data and HTTP code
    return [
        'data' => $responseData,
        'code' => $httpCode
    ];
}

// Test login
echo "Testing login...\n";
$loginResponse = apiRequest('auth/login', 'POST', [
    'email' => 'admin@example.com',
    'password' => 'admin123'
]);

if (!$loginResponse) {
    echo "Login request failed\n";
    exit;
}

echo "Login response code: " . $loginResponse['code'] . "\n";
echo "Login response: " . json_encode($loginResponse['data'], JSON_PRETTY_PRINT) . "\n\n";

if ($loginResponse['code'] !== 200 || !isset($loginResponse['data']['token'])) {
    echo "Login failed\n";
    exit;
}

$token = $loginResponse['data']['token'];
echo "Token: " . $token . "\n\n";

// Test authenticated endpoint
echo "Testing authenticated endpoint...\n";
$authResponse = apiRequest('auth/me', 'GET', null, $token);

if (!$authResponse) {
    echo "Auth request failed\n";
    exit;
}

echo "Auth response code: " . $authResponse['code'] . "\n";
echo "Auth response: " . json_encode($authResponse['data'], JSON_PRETTY_PRINT) . "\n\n";

// Test CRUD operations
echo "Testing CRUD operations...\n";

// Test GET authors
echo "Testing GET authors...\n";
$authorsResponse = apiRequest('authors', 'GET', null, $token);

if (!$authorsResponse) {
    echo "Authors request failed\n";
    exit;
}

echo "Authors response code: " . $authorsResponse['code'] . "\n";
echo "Authors response: " . json_encode($authorsResponse['data'], JSON_PRETTY_PRINT) . "\n\n";

// Test POST author
echo "Testing POST author...\n";
$createResponse = apiRequest('authors', 'POST', [
    'name' => 'Test Author',
    'bio' => 'This is a test author'
], $token);

if (!$createResponse) {
    echo "Create request failed\n";
    exit;
}

echo "Create response code: " . $createResponse['code'] . "\n";
echo "Create response: " . json_encode($createResponse['data'], JSON_PRETTY_PRINT) . "\n\n";

// Test token refresh
echo "Testing token refresh...\n";
$refreshResponse = apiRequest('auth/refresh', 'POST', [
    'user_id' => $loginResponse['data']['user']['id']
]);

if (!$refreshResponse) {
    echo "Refresh request failed\n";
    exit;
}

echo "Refresh response code: " . $refreshResponse['code'] . "\n";
echo "Refresh response: " . json_encode($refreshResponse['data'], JSON_PRETTY_PRINT) . "\n\n";

echo "Tests completed\n";