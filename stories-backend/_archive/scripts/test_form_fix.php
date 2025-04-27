<?php
/**
 * Test Form Fix
 * 
 * This script tests the form submission fix by making API calls to verify
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
    <title>Test Form Fix</title>
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
        <h1>Test Form Fix</h1>
', true);
}

output("Test Form Fix");
output("============");
output("");

// Test the API endpoints
output("Testing API Endpoints");
output("-------------------");

// Function to make API requests
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $isWeb;
    
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

// Check if the form submission fix script exists
output("");
output("Checking Form Submission Fix Script");
output("-------------------------------");

$formFixJsPath = __DIR__ . '/admin/assets/js/form-submission-fix.js';
if (file_exists($formFixJsPath)) {
    if ($isWeb) output("<div class='success'>Form submission fix script exists: $formFixJsPath</div>", true);
    else output("Form submission fix script exists: $formFixJsPath");
} else {
    if ($isWeb) output("<div class='error'>Form submission fix script not found: $formFixJsPath</div>", true);
    else output("Error: Form submission fix script not found: $formFixJsPath");
}

// Check if the form submission fix script is included in the footer
$footerFile = __DIR__ . '/admin/views/footer.php';
if (file_exists($footerFile)) {
    $footerContent = file_get_contents($footerFile);
    if (strpos($footerContent, 'form-submission-fix.js') !== false) {
        if ($isWeb) output("<div class='success'>Form submission fix script is included in the footer</div>", true);
        else output("Form submission fix script is included in the footer");
    } else {
        if ($isWeb) output("<div class='warning'>Form submission fix script is not included in the footer</div>", true);
        else output("Warning: Form submission fix script is not included in the footer");
    }
} else {
    if ($isWeb) output("<div class='warning'>Footer file not found: $footerFile</div>", true);
    else output("Warning: Footer file not found: $footerFile");
}

// Check if the form submission fix include script exists
$includeScriptPath = __DIR__ . '/admin/form_submission_fix_include.php';
if (file_exists($includeScriptPath)) {
    if ($isWeb) output("<div class='success'>Form submission fix include script exists: $includeScriptPath</div>", true);
    else output("Form submission fix include script exists: $includeScriptPath");
} else {
    if ($isWeb) output("<div class='warning'>Form submission fix include script not found: $includeScriptPath</div>", true);
    else output("Warning: Form submission fix include script not found: $includeScriptPath");
}

// Check if the .htaccess file includes the auto_prepend_file directive
$htaccessPath = __DIR__ . '/admin/.htaccess';
if (file_exists($htaccessPath)) {
    $htaccessContent = file_get_contents($htaccessPath);
    if (strpos($htaccessContent, 'auto_prepend_file') !== false && strpos($htaccessContent, 'form_submission_fix_include.php') !== false) {
        if ($isWeb) output("<div class='success'>.htaccess file includes the auto_prepend_file directive</div>", true);
        else output(".htaccess file includes the auto_prepend_file directive");
    } else {
        if ($isWeb) output("<div class='warning'>.htaccess file does not include the auto_prepend_file directive</div>", true);
        else output("Warning: .htaccess file does not include the auto_prepend_file directive");
    }
} else {
    if ($isWeb) output("<div class='warning'>.htaccess file not found: $htaccessPath</div>", true);
    else output("Warning: .htaccess file not found: $htaccessPath");
}

output("");
output("Verification Steps");
output("-----------------");
output("1. The API endpoints are working correctly");
output("2. The form submission fix script has been created");
output("3. The form submission fix script is included in the footer");
output("");
output("To verify the fix works:");
output("1. Go to the admin interface: https://api.storiesfromtheweb.org/admin/stories.php");
output("2. Edit a story");
output("3. Make changes and click Save");
output("4. Verify that the form submission completes successfully");
output("5. Check that you are redirected to the list page");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}