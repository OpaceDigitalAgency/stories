<?php
/**
 * API Diagnostic Tool
 * 
 * This script tests the API endpoints and checks the response format.
 */

// Check if running in web or CLI mode
$isWeb = php_sapi_name() !== 'cli';

// Function to output text based on environment
function output($text, $isHtml = false) {
    global $isWeb;
    if ($isWeb) {
        echo $isHtml ? $text : nl2br(htmlspecialchars($text)) . "<br>";
    } else {
        echo $text . ($isHtml ? '' : "\n");
    }
}

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>API Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .endpoint { background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-left: 4px solid #0066cc; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px; }
        .response { margin-top: 10px; }
        .fix-button { background: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>API Diagnostic Tool</h1>
', true);
}

output("API Diagnostic Tool");
output("==================");
output("");

// Test endpoints
$endpoints = [
    'stories' => '/api/v1/stories',
    'authors' => '/api/v1/authors',
    'tags' => '/api/v1/tags',
    'games' => '/api/v1/games',
    'directory' => '/api/v1/directory-items',
    'blog-posts' => '/api/v1/blog-posts',
    'ai-tools' => '/api/v1/ai-tools'
];

// Function to test an endpoint
function testEndpoint($name, $path) {
    global $isWeb;
    
    output("");
    if ($isWeb) output("<div class='endpoint'>", true);
    output("Testing endpoint: $name ($path)");
    output("----------------------------" . str_repeat('-', strlen($name) + strlen($path)));
    
    // Build the full URL
    $baseUrl = "https://api.storiesfromtheweb.org";
    $url = $baseUrl . $path;
    
    output("URL: $url");
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    // Output results
    output("HTTP Status: $httpCode");
    
    if ($httpCode >= 200 && $httpCode < 300) {
        if ($isWeb) output("<span class='success'>Success</span>", true);
        else output("Success");
    } else {
        if ($isWeb) output("<span class='error'>Error</span>", true);
        else output("Error");
    }
    
    // Parse and check response format
    $isJson = false;
    $jsonError = '';
    $decodedResponse = null;
    
    try {
        $decodedResponse = json_decode($body, true);
        $jsonError = json_last_error_msg();
        $isJson = json_last_error() === JSON_ERROR_NONE;
    } catch (Exception $e) {
        $jsonError = $e->getMessage();
    }
    
    output("Response is valid JSON: " . ($isJson ? "Yes" : "No"));
    if (!$isJson) {
        output("JSON Error: $jsonError");
    }
    
    // Check response structure
    if ($isJson && $decodedResponse) {
        $hasData = isset($decodedResponse['data']);
        $hasMeta = isset($decodedResponse['meta']);
        
        output("Response has 'data' field: " . ($hasData ? "Yes" : "No"));
        output("Response has 'meta' field: " . ($hasMeta ? "Yes" : "No"));
        
        if ($hasData) {
            $dataType = gettype($decodedResponse['data']);
            output("Data type: $dataType");
            
            if ($dataType === 'array') {
                output("Data count: " . count($decodedResponse['data']));
                
                if (count($decodedResponse['data']) > 0) {
                    $firstItem = $decodedResponse['data'][0];
                    $hasId = isset($firstItem['id']);
                    $hasAttributes = isset($firstItem['attributes']);
                    
                    output("First item has 'id' field: " . ($hasId ? "Yes" : "No"));
                    output("First item has 'attributes' field: " . ($hasAttributes ? "Yes" : "No"));
                    
                    if ($hasAttributes) {
                        output("Attributes keys: " . implode(', ', array_keys($firstItem['attributes'])));
                    }
                }
            }
        }
    }
    
    // Output response
    output("");
    output("Response Headers:");
    output("----------------");
    output($headers);
    
    output("");
    output("Response Body:");
    output("-------------");
    if ($isWeb) {
        output("<pre class='response'>" . htmlspecialchars($body) . "</pre>", true);
    } else {
        output($body);
    }
    
    // Check for common issues and suggest fixes
    if (!$isJson) {
        output("");
        output("Potential Issues:");
        output("----------------");
        output("1. Response is not valid JSON");
        output("2. PHP error might be occurring on the server");
        output("3. Authentication might be failing");
        
        output("");
        output("Suggested Fixes:");
        output("---------------");
        output("1. Check PHP error logs on the server");
        output("2. Verify the endpoint controller is returning proper JSON");
        output("3. Check authentication middleware");
        
        if ($isWeb) {
            output("<button class='fix-button' onclick='fixResponseFormat(\"$name\")'>Fix Response Format</button>", true);
        }
    } else if (!$hasData || !$hasMeta) {
        output("");
        output("Potential Issues:");
        output("----------------");
        output("1. Response format doesn't match expected structure (missing 'data' or 'meta')");
        output("2. Controller might not be using Response::sendSuccess() or Response::sendPaginated()");
        
        output("");
        output("Suggested Fixes:");
        output("---------------");
        output("1. Ensure controller is using Response::sendSuccess() or Response::sendPaginated()");
        output("2. Check Response.php for proper formatting");
        
        if ($isWeb) {
            output("<button class='fix-button' onclick='fixResponseFormat(\"$name\")'>Fix Response Format</button>", true);
        }
    }
    
    if ($isWeb) output("</div>", true);
}

// Test each endpoint
foreach ($endpoints as $name => $path) {
    testEndpoint($name, $path);
}

// Add JavaScript for fix buttons
if ($isWeb) {
    output("<script>
function fixResponseFormat(endpoint) {
    if (confirm('This will attempt to fix the response format for the ' + endpoint + ' endpoint. Continue?')) {
        window.location.href = 'fix_response.php?endpoint=' + endpoint;
    }
}
</script>", true);
    
    // Close HTML
    output('
    </div>
</body>
</html>', true);
}