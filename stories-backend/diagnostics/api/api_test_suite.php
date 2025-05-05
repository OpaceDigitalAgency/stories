<?php
/**
 * API Test Suite
 * 
 * Comprehensive API testing tool that checks endpoint availability, 
 * response format consistency, and validates data structures.
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

// Define API endpoints to test
$endpoints = [
    [
        'name' => 'Stories API',
        'url' => '/api/stories',
        'method' => 'GET',
        'auth_required' => false,
        'expected_fields' => ['id', 'title', 'content', 'author_id', 'created_at']
    ],
    [
        'name' => 'Authors API',
        'url' => '/api/authors',
        'method' => 'GET',
        'auth_required' => false,
        'expected_fields' => ['id', 'name', 'bio', 'created_at']
    ],
    [
        'name' => 'Blog Posts API',
        'url' => '/api/blog-posts',
        'method' => 'GET',
        'auth_required' => false,
        'expected_fields' => ['id', 'title', 'content', 'author_id', 'created_at']
    ],
    [
        'name' => 'User Authentication',
        'url' => '/api/auth/me',
        'method' => 'GET',
        'auth_required' => true,
        'expected_fields' => ['id', 'email', 'name', 'role']
    ]
];

// Function to test API endpoint
function testEndpoint($endpoint) {
    $baseUrl = getBaseApiUrl();
    $url = $baseUrl . $endpoint['url'];
    
    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set method
    if ($endpoint['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($endpoint['method'] !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $endpoint['method']);
    }
    
    // Set headers
    $headers = ['Accept: application/json'];
    
    // Add authentication token if required
    if ($endpoint['auth_required'] && isset($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
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
    
    // Check if response is valid JSON
    if ($responseData === null && $response !== '') {
        return [
            'success' => false,
            'error' => 'Invalid JSON response',
            'code' => $httpCode,
            'response' => $response
        ];
    }
    
    // Check if response contains expected fields
    $missingFields = [];
    if ($responseData && is_array($responseData)) {
        $dataToCheck = isset($responseData['data']) ? $responseData['data'] : $responseData;
        
        // If it's an array of items, check the first item
        if (is_array($dataToCheck) && isset($dataToCheck[0])) {
            $firstItem = $dataToCheck[0];
            
            foreach ($endpoint['expected_fields'] as $field) {
                if (!isset($firstItem[$field])) {
                    $missingFields[] = $field;
                }
            }
        } else {
            // Check the data object directly
            foreach ($endpoint['expected_fields'] as $field) {
                if (!isset($dataToCheck[$field])) {
                    $missingFields[] = $field;
                }
            }
        }
    }
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300 && empty($missingFields),
        'code' => $httpCode,
        'response' => $responseData,
        'missing_fields' => $missingFields
    ];
}

// Test all endpoints
$results = [];
foreach ($endpoints as $endpoint) {
    $results[$endpoint['name']] = testEndpoint($endpoint);
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>API Test Suite</title>
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
        <h1>API Test Suite</h1>
        <p class='lead'>This tool tests API endpoints for availability, response format, and data structure.</p>";

// Display results
foreach ($results as $name => $result) {
    echo "<div class='card mb-4'>";
    echo "<div class='card-header'>";
    echo "<h2>" . htmlspecialchars($name) . "</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    if ($result['success']) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> Test passed successfully";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<i class='fas fa-times-circle'></i> Test failed";
        echo "</div>";
        
        if (isset($result['error'])) {
            echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>";
        }
        
        if (!empty($result['missing_fields'])) {
            echo "<p><strong>Missing fields:</strong> " . implode(', ', $result['missing_fields']) . "</p>";
        }
    }
    
    echo "<p><strong>HTTP Code:</strong> " . $result['code'] . "</p>";
    
    if ($result['response']) {
        echo "<div class='mt-3'>";
        echo "<h5>Response:</h5>";
        echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";
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
