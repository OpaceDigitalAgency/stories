<?php
/**
 * Fix API Response Format
 * 
 * This script fixes the response format for API endpoints.
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
    <title>Fix API Response Format</title>
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
        <h1>Fix API Response Format</h1>
', true);
}

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

// Get the endpoint to fix
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

if (empty($endpoint)) {
    if ($isWeb) output("<div class='error'>No endpoint specified</div>", true);
    else output("Error: No endpoint specified");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='api_diagnostic.php'>Back to Diagnostic Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

output("Fixing Response Format for Endpoint: $endpoint");
output("===========================================" . str_repeat('=', strlen($endpoint)));
output("");

// Map endpoint to controller file
$controllerMap = [
    'stories' => 'StoriesController.php',
    'authors' => 'AuthorsController.php',
    'tags' => 'TagsController.php',
    'games' => 'GamesController.php',
    'directory-items' => 'DirectoryItemsController.php',
    'blog-posts' => 'BlogPostsController.php',
    'ai-tools' => 'AiToolsController.php'
];

if (!isset($controllerMap[$endpoint])) {
    if ($isWeb) output("<div class='error'>Unknown endpoint: $endpoint</div>", true);
    else output("Error: Unknown endpoint: $endpoint");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='api_diagnostic.php'>Back to Diagnostic Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

$controllerFile = __DIR__ . '/api/v1/Endpoints/' . $controllerMap[$endpoint];

if (!file_exists($controllerFile)) {
    if ($isWeb) output("<div class='error'>Controller file not found: $controllerFile</div>", true);
    else output("Error: Controller file not found: $controllerFile");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='api_diagnostic.php'>Back to Diagnostic Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

output("Controller file: $controllerFile");
output("");

// Backup the controller file
$backupFile = $controllerFile . '.bak.' . date('YmdHis');
if (!copy($controllerFile, $backupFile)) {
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

// Read the controller file
$content = file_get_contents($controllerFile);

// Check for common issues and fix them
$fixes = [];

// Fix 1: Check for direct echo or print statements
if (preg_match('/\becho\s+[\'"]/', $content) || preg_match('/\bprint\s+[\'"]/', $content)) {
    $fixes[] = "Found direct echo or print statements - these should be replaced with Response::sendSuccess()";
    
    // Replace echo/print with Response::sendSuccess()
    $content = preg_replace('/\becho\s+[\'"](.+?)[\'"];/', 'Response::sendSuccess(["message" => "$1"]);', $content);
    $content = preg_replace('/\bprint\s+[\'"](.+?)[\'"];/', 'Response::sendSuccess(["message" => "$1"]);', $content);
}

// Fix 2: Check for json_encode without proper structure
if (preg_match('/\bjson_encode\s*\((?!\s*\[\s*[\'"]data[\'"])/i', $content)) {
    $fixes[] = "Found json_encode without proper structure - these should be replaced with Response::sendSuccess()";
    
    // Replace json_encode with Response::sendSuccess()
    $content = preg_replace('/\becho\s+json_encode\s*\((.+?)\);/', 'Response::sendSuccess($1);', $content);
    $content = preg_replace('/\bprint\s+json_encode\s*\((.+?)\);/', 'Response::sendSuccess($1);', $content);
}

// Fix 3: Check for missing Response class import
if (!preg_match('/use\s+StoriesAPI\\\\Utils\\\\Response;/i', $content)) {
    $fixes[] = "Missing Response class import - adding it";
    
    // Add Response class import
    $content = preg_replace('/(namespace\s+StoriesAPI\\\\Endpoints;.*?)(\s+use\s+)/s', '$1$2StoriesAPI\\Utils\\Response;$2', $content);
    
    if (!preg_match('/use\s+StoriesAPI\\\\Utils\\\\Response;/i', $content)) {
        // If still not found, add it after namespace declaration
        $content = preg_replace('/(namespace\s+StoriesAPI\\\\Endpoints;)/s', '$1' . "\n\nuse StoriesAPI\\Utils\\Response;", $content);
    }
}

// Fix 4: Check for Response::send instead of Response::sendSuccess
$content = str_replace('Response::send(', 'Response::sendSuccess(', $content);

// Fix 5: Check for Response::json instead of Response::sendSuccess
$content = str_replace('Response::json(', 'Response::sendSuccess(', $content);

// Fix 6: Check for direct return of arrays without Response::sendSuccess
$pattern = '/return\s+\[\s*[\'"]data[\'"]\s*=>/';
if (preg_match($pattern, $content)) {
    $fixes[] = "Found direct return of arrays - these should be replaced with Response::sendSuccess()";
    
    // Replace direct returns with Response::sendSuccess()
    $content = preg_replace('/return\s+(\[\s*[\'"]data[\'"]\s*=>.*?\]);/s', 'Response::sendSuccess($1);' . "\nreturn;", $content);
}

// Write the modified content back to the file
if (!empty($fixes)) {
    if (file_put_contents($controllerFile, $content)) {
        if ($isWeb) output("<div class='success'>Successfully applied fixes:</div>", true);
        else output("Successfully applied fixes:");
        
        foreach ($fixes as $fix) {
            output("- $fix");
        }
    } else {
        if ($isWeb) output("<div class='error'>Failed to write modified content to file</div>", true);
        else output("Error: Failed to write modified content to file");
    }
} else {
    if ($isWeb) output("<div class='warning'>No issues found that could be automatically fixed</div>", true);
    else output("Warning: No issues found that could be automatically fixed");
    
    // Check Response.php file
    $responseFile = __DIR__ . '/api/v1/Utils/Response.php';
    if (file_exists($responseFile)) {
        output("");
        output("Checking Response.php file...");
        
        $responseContent = file_get_contents($responseFile);
        
        // Check for common issues in Response.php
        $responseIssues = [];
        
        // Check for proper sendSuccess method
        if (!preg_match('/public\s+static\s+function\s+sendSuccess/i', $responseContent)) {
            $responseIssues[] = "Missing sendSuccess method in Response.php";
        }
        
        // Check for proper formatData method
        if (!preg_match('/function\s+formatData/i', $responseContent)) {
            $responseIssues[] = "Missing formatData method in Response.php";
        }
        
        if (!empty($responseIssues)) {
            if ($isWeb) output("<div class='warning'>Issues found in Response.php:</div>", true);
            else output("Issues found in Response.php:");
            
            foreach ($responseIssues as $issue) {
                output("- $issue");
            }
            
            output("");
            output("Consider updating Response.php with the correct implementation");
        } else {
            output("Response.php appears to be correctly implemented");
        }
    } else {
        if ($isWeb) output("<div class='error'>Response.php file not found</div>", true);
        else output("Error: Response.php file not found");
    }
}

output("");
output("Manual Fixes to Consider:");
output("1. Ensure all controller methods use Response::sendSuccess() or Response::sendPaginated()");
output("2. Check that the data is properly formatted with 'id' and 'attributes' fields");
output("3. Verify that the Response.php file correctly formats the response");
output("4. Check for any PHP errors in the server logs");

if ($isWeb) {
    output("<div class='back-link'><a href='api_diagnostic.php'>Back to Diagnostic Tool</a></div>", true);
    output('</div></body></html>', true);
}