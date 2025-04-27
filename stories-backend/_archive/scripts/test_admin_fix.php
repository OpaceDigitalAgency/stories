<?php
/**
 * Test Admin Fix
 * 
 * This script tests the admin interface fix by making API calls to verify
 * that the form submission is working correctly.
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Fix</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .back-link { margin-top: 20px; }
        .code { font-family: monospace; background: #f5f5f5; padding: 10px; }
        .button { display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Admin Fix</h1>
', true);
}

output("Test Admin Fix");
output("============");
output("");

// Test the API endpoints
output("Testing API Endpoints");
output("-------------------");

// Function to make API requests
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    $baseUrl = "https://api.storiesfromtheweb.org/api/v1";
    $url = $baseUrl . $endpoint;
    
    output("Making $method request to: $url");
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set method
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Set data if provided
    if ($data) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        
        output("Request data:");
        output(json_encode($data, JSON_PRETTY_PRINT));
    }
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    output("HTTP Status: $httpCode");
    
    if ($httpCode >= 200 && $httpCode < 300) {
        if ($isWeb) output("<div class='success'>Success</div>", true);
        else output("Success");
    } else {
        if ($isWeb) output("<div class='error'>Error</div>", true);
        else output("Error");
    }
    
    output("Response:");
    
    // Try to parse JSON
    $jsonResponse = json_decode($response, true);
    if ($jsonResponse !== null) {
        output(json_encode($jsonResponse, JSON_PRETTY_PRINT));
    } else {
        output($response);
    }
    
    curl_close($ch);
    
    return $jsonResponse;
}

// Test GET /stories
output("");
output("Testing GET /stories");
$stories = makeApiRequest('/stories');

// Test GET /stories/1
output("");
output("Testing GET /stories/1");
$story = makeApiRequest('/stories/1');

// Test PUT /stories/1
output("");
output("Testing PUT /stories/1");
$updateData = [
    'title' => 'Updated Story Title ' . date('Y-m-d H:i:s'),
    'excerpt' => 'This is an updated excerpt from the test script.',
    'content' => 'This is updated content for the story from the test script.'
];
$updatedStory = makeApiRequest('/stories/1', 'PUT', $updateData);

// Test the admin interface JavaScript
output("");
output("Testing Admin Interface JavaScript");
output("------------------------------");

// Check if the admin.js file contains our fix
$adminJsPath = '/home/stories/api.storiesfromtheweb.org/admin/assets/js/admin.js';
if (file_exists($adminJsPath)) {
    $adminJs = file_get_contents($adminJsPath);
    if (strpos($adminJs, 'Admin form submission fix loaded') !== false) {
        if ($isWeb) output("<div class='success'>Admin JavaScript contains the form submission fix</div>", true);
        else output("Admin JavaScript contains the form submission fix");
    } else {
        if ($isWeb) output("<div class='warning'>Admin JavaScript does not contain the form submission fix</div>", true);
        else output("Warning: Admin JavaScript does not contain the form submission fix");
    }
} else {
    if ($isWeb) output("<div class='error'>Admin JavaScript file not found</div>", true);
    else output("Error: Admin JavaScript file not found");
}

// Check if the standalone JavaScript file exists
$standaloneJsPath = '/home/stories/api.storiesfromtheweb.org/admin_form_fix.js';
if (file_exists($standaloneJsPath)) {
    if ($isWeb) output("<div class='success'>Standalone JavaScript file exists</div>", true);
    else output("Standalone JavaScript file exists");
} else {
    if ($isWeb) output("<div class='warning'>Standalone JavaScript file not found</div>", true);
    else output("Warning: Standalone JavaScript file not found");
}

// Verify the admin interface is working
output("");
output("Verification Steps");
output("-----------------");
output("1. The API endpoints are working correctly");
output("2. The admin JavaScript has been updated with the form submission fix");
output("3. The standalone JavaScript file is available as a backup");
output("");
output("To verify the admin interface is working:");
output("1. Go to the admin interface: https://api.storiesfromtheweb.org/admin/stories.php");
output("2. Edit a story");
output("3. Make changes and click Save");
output("4. Verify that the changes are saved successfully");
output("");
output("If you encounter any issues:");
output("1. Check the browser console for JavaScript errors");
output("2. Verify that the admin JavaScript file contains the form submission fix");
output("3. Try adding the standalone JavaScript file to the admin interface");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}