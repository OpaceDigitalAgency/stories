<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to make API request
function makeRequest($endpoint, $method = 'GET', $data = null) {
    $baseUrl = 'https://api.storiesfromtheweb.org/api/v1/auth';
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $headers = ['Content-Type: application/json'];
    if (isset($_COOKIE['auth_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_COOKIE['auth_token'];
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Function to output JSON response
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Get action from query string
$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'login':
        // Test login with default admin credentials
        $result = makeRequest('/login', 'POST', [
            'email' => 'admin@storiesfromtheweb.org',
            'password' => 'admin123'
        ]);
        
        jsonResponse([
            'action' => 'login',
            'success' => $result['code'] === 200,
            'code' => $result['code'],
            'response' => $result['response']
        ]);
        break;
        
    case 'logout':
        // Test logout
        $result = makeRequest('/logout', 'POST');
        
        jsonResponse([
            'action' => 'logout',
            'success' => $result['code'] === 200,
            'code' => $result['code'],
            'response' => $result['response']
        ]);
        break;
        
    default:
        // Check current auth status
        $result = makeRequest('/me');
        
        jsonResponse([
            'action' => 'status',
            'success' => $result['code'] === 200,
            'code' => $result['code'],
            'response' => $result['response'],
            'cookie' => [
                'auth_token' => $_COOKIE['auth_token'] ?? null
            ]
        ]);
        break;
}