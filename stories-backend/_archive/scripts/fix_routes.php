<?php
/**
 * Fix Routes File
 * 
 * This script fixes the routes.php file to use the existing AuthMiddleware instead of SimpleAuthMiddleware.
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

// Set content type for web
if ($isWeb) {
    header('Content-Type: text/html; charset=utf-8');
    output('<!DOCTYPE html>
<html>
<head>
    <title>Fix Routes File</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .back-link { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Routes File</h1>
', true);
}

output("Fix Routes File");
output("==============");
output("");

// Path to routes.php file
$routesFile = __DIR__ . '/api/v1/routes.php';

if (!file_exists($routesFile)) {
    if ($isWeb) output("<div class='error'>Routes file not found: $routesFile</div>", true);
    else output("Error: Routes file not found: $routesFile");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='api_diagnostic.php'>Back to Diagnostic Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

output("Routes file: $routesFile");
output("");

// Backup the routes file
$backupFile = $routesFile . '.bak.' . date('YmdHis');
if (!copy($routesFile, $backupFile)) {
    if ($isWeb) output("<div class='error'>Failed to create backup file</div>", true);
    else output("Error: Failed to create backup file");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='api_diagnostic.php'>Back to Diagnostic Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

output("Backup created: $backupFile");
output("");

// Read the routes file
$content = file_get_contents($routesFile);

// Check if SimpleAuthMiddleware is being used
if (strpos($content, 'SimpleAuthMiddleware') !== false) {
    output("Found SimpleAuthMiddleware in routes.php");
    
    // Replace SimpleAuthMiddleware with AuthMiddleware
    $newContent = str_replace(
        'SimpleAuthMiddleware',
        'AuthMiddleware',
        $content
    );
    
    // Write the modified content back to the file
    if (file_put_contents($routesFile, $newContent)) {
        if ($isWeb) output("<div class='success'>Successfully replaced SimpleAuthMiddleware with AuthMiddleware</div>", true);
        else output("Successfully replaced SimpleAuthMiddleware with AuthMiddleware");
    } else {
        if ($isWeb) output("<div class='error'>Failed to write modified content to file</div>", true);
        else output("Error: Failed to write modified content to file");
    }
} else {
    if ($isWeb) output("<div class='warning'>SimpleAuthMiddleware not found in routes.php</div>", true);
    else output("Warning: SimpleAuthMiddleware not found in routes.php");
}

output("");
output("Next Steps:");
output("1. Refresh the API endpoints to see if they work now");
output("2. If the issue persists, check the server logs for more details");
output("3. Ensure the middleware files are properly deployed to the server");

if ($isWeb) {
    output("<div class='back-link'><a href='api_diagnostic.php'>Back to Diagnostic Tool</a></div>", true);
    output('</div></body></html>', true);
}