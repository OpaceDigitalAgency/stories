<?php
/**
 * Verify API
 * 
 * This tool verifies API connectivity and functionality.
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

// Define API tests
$tests = [
    [
        'name' => 'API Base URL',
        'description' => 'Verify that the API base URL is accessible',
        'test' => function() {
            $baseUrl = getBaseApiUrl();
            
            // Initialize cURL
            $ch = curl_init();
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $baseUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Check for errors
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return [
                    'success' => false,
                    'message' => "Error: $error",
                    'details' => null
                ];
            }
            
            // Close cURL
            curl_close($ch);
            
            return [
                'success' => $httpCode >= 200 && $httpCode < 300,
                'message' => $httpCode >= 200 && $httpCode < 300 ? 'API base URL is accessible' : "HTTP error: $httpCode",
                'details' => [
                    'url' => $baseUrl,
                    'http_code' => $httpCode,
                    'response' => $response
                ]
            ];
        }
    ],
    [
        'name' => 'API Authentication',
        'description' => 'Verify that the API authentication endpoint is working',
        'test' => function() {
            $baseUrl = getBaseApiUrl();
            $url = $baseUrl . '/auth/login';
            
            // Initialize cURL
            $ch = curl_init();
            
            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            
            // Set test credentials (these should fail but the endpoint should respond)
            $data = json_encode([
                'email' => 'test@example.com',
                'password' => 'test_password'
            ]);
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Check for errors
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return [
                    'success' => false,
                    'message' => "Error: $error",
                    'details' => null
                ];
            }
            
            // Close cURL
            curl_close($ch);
            
            // Parse response
            $responseData = json_decode($response, true);
            
            // Check if endpoint is working (even if auth fails, it should respond)
            $isWorking = $httpCode !== 0 && $httpCode !== 404 && $httpCode !== 500;
            
            return [
                'success' => $isWorking,
                'message' => $isWorking ? 'Authentication endpoint is working' : 'Authentication endpoint is not working',
                'details' => [
                    'url' => $url,
                    'http_code' => $httpCode,
                    'response' => $responseData ?: $response
                ]
            ];
        }
    ],
    [
        'name' => 'API Content Endpoints',
        'description' => 'Verify that the API content endpoints are working',
        'test' => function() {
            $baseUrl = getBaseApiUrl();
            $endpoints = ['/stories', '/authors', '/blog-posts', '/games'];
            $results = [];
            
            foreach ($endpoints as $endpoint) {
                $url = $baseUrl . $endpoint;
                
                // Initialize cURL
                $ch = curl_init();
                
                // Set cURL options
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                // Execute request
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                // Check for errors
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    $results[$endpoint] = [
                        'success' => false,
                        'message' => "Error: $error",
                        'http_code' => 0
                    ];
                    continue;
                }
                
                // Close cURL
                curl_close($ch);
                
                // Check if endpoint is working
                $isWorking = $httpCode >= 200 && $httpCode < 300;
                
                $results[$endpoint] = [
                    'success' => $isWorking,
                    'message' => $isWorking ? 'Endpoint is working' : "HTTP error: $httpCode",
                    'http_code' => $httpCode
                ];
            }
            
            // Overall success if at least one endpoint is working
            $overallSuccess = false;
            foreach ($results as $result) {
                if ($result['success']) {
                    $overallSuccess = true;
                    break;
                }
            }
            
            return [
                'success' => $overallSuccess,
                'message' => $overallSuccess ? 'At least one content endpoint is working' : 'All content endpoints are failing',
                'details' => $results
            ];
        }
    ]
];

// Run all tests
$results = [];
foreach ($tests as $test) {
    $results[$test['name']] = [
        'description' => $test['description'],
        'result' => $test['test']()
    ];
}

// Calculate overall status
$overallSuccess = true;
foreach ($results as $result) {
    if (!$result['result']['success']) {
        $overallSuccess = false;
        break;
    }
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verify API</title>
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
        <h1>Verify API</h1>
        <p class='lead'>This tool verifies API connectivity and functionality.</p>
        
        <div class='alert alert-" . ($overallSuccess ? 'success' : 'danger') . " mb-4'>
            <h4 class='alert-heading'>" . ($overallSuccess ? 'API is working properly' : 'API has issues') . "</h4>
            <p>" . ($overallSuccess ? 'All tests passed successfully.' : 'Some tests failed. See details below.') . "</p>
        </div>";

// Display results
foreach ($results as $name => $data) {
    $description = $data['description'];
    $result = $data['result'];
    
    echo "<div class='card mb-4'>";
    echo "<div class='card-header " . ($result['success'] ? 'bg-success text-white' : 'bg-danger text-white') . "'>";
    echo "<h2 class='m-0'>" . htmlspecialchars($name) . "</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<p>" . htmlspecialchars($description) . "</p>";
    echo "<p><strong>Status:</strong> " . ($result['success'] ? '<span class="success">Success</span>' : '<span class="error">Failed</span>') . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($result['message']) . "</p>";
    
    if (isset($result['details']) && !empty($result['details'])) {
        echo "<div class='mt-3'>";
        echo "<h5>Details:</h5>";
        
        if (is_array($result['details'])) {
            echo "<pre>" . htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            echo "<p>" . htmlspecialchars($result['details']) . "</p>";
        }
        
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
}

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
