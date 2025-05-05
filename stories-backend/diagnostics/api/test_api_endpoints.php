<?php
/**
 * Test API Endpoints
 * 
 * This tool tests API endpoints and functionality.
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
        'name' => 'Stories Endpoint',
        'url' => '/api/stories',
        'method' => 'GET',
        'description' => 'Retrieves a list of stories'
    ],
    [
        'name' => 'Authors Endpoint',
        'url' => '/api/authors',
        'method' => 'GET',
        'description' => 'Retrieves a list of authors'
    ],
    [
        'name' => 'Blog Posts Endpoint',
        'url' => '/api/blog-posts',
        'method' => 'GET',
        'description' => 'Retrieves a list of blog posts'
    ],
    [
        'name' => 'Games Endpoint',
        'url' => '/api/games',
        'method' => 'GET',
        'description' => 'Retrieves a list of games'
    ],
    [
        'name' => 'Directory Items Endpoint',
        'url' => '/api/directory-items',
        'method' => 'GET',
        'description' => 'Retrieves a list of directory items'
    ],
    [
        'name' => 'AI Tools Endpoint',
        'url' => '/api/ai-tools',
        'method' => 'GET',
        'description' => 'Retrieves a list of AI tools'
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Set method
    if ($endpoint['method'] === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($endpoint['method'] !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $endpoint['method']);
    }
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    // Check for errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'success' => false,
            'error' => $error,
            'code' => 0,
            'content_type' => null,
            'response' => null
        ];
    }
    
    // Close cURL
    curl_close($ch);
    
    // Parse response if JSON
    $responseData = null;
    if (strpos($contentType, 'application/json') !== false) {
        $responseData = json_decode($response, true);
    }
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'code' => $httpCode,
        'content_type' => $contentType,
        'response' => $responseData ?: $response
    ];
}

// Test all endpoints
$results = [];
foreach ($endpoints as $endpoint) {
    $results[$endpoint['name']] = [
        'endpoint' => $endpoint,
        'result' => testEndpoint($endpoint)
    ];
}

// HTML header
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test API Endpoints</title>
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
        <h1>Test API Endpoints</h1>
        <p class='lead'>This tool tests API endpoints and functionality.</p>";

// Display results
foreach ($results as $name => $data) {
    $endpoint = $data['endpoint'];
    $result = $data['result'];
    
    echo "<div class='card mb-4'>";
    echo "<div class='card-header'>";
    echo "<h2>" . htmlspecialchars($name) . "</h2>";
    echo "<p>" . htmlspecialchars($endpoint['description']) . "</p>";
    echo "</div>";
    echo "<div class='card-body'>";
    
    echo "<p><strong>URL:</strong> " . htmlspecialchars($endpoint['url']) . "</p>";
    echo "<p><strong>Method:</strong> " . htmlspecialchars($endpoint['method']) . "</p>";
    
    if ($result['success']) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> Endpoint is working";
        echo "</div>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<i class='fas fa-times-circle'></i> Endpoint is not working";
        echo "</div>";
        
        if (isset($result['error'])) {
            echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>";
        }
    }
    
    echo "<p><strong>HTTP Code:</strong> " . $result['code'] . "</p>";
    echo "<p><strong>Content Type:</strong> " . htmlspecialchars($result['content_type'] ?? 'Unknown') . "</p>";
    
    if ($result['response']) {
        echo "<div class='mt-3'>";
        echo "<h5>Response:</h5>";
        
        if (is_array($result['response'])) {
            echo "<pre>" . htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) . "</pre>";
        } else {
            // Limit response length to avoid huge outputs
            $response = strlen($result['response']) > 1000 
                ? substr($result['response'], 0, 1000) . '...' 
                : $result['response'];
            
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
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
