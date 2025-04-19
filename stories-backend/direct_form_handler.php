<?php
/**
 * Direct Form Handler
 * 
 * This script creates a direct form handler that completely bypasses JavaScript.
 * It injects a PHP form handler directly into the page that intercepts form submissions.
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
    <title>Direct Form Handler</title>
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
        <h1>Direct Form Handler</h1>
', true);
}

output("Direct Form Handler");
output("==================");
output("");

// Define the admin pages to modify
$adminPages = [
    'stories.php',
    'authors.php',
    'tags.php',
    'blog-posts.php',
    'games.php',
    'directory-items.php',
    'ai-tools.php'
];

// Create the direct form handler
$directFormHandlerContent = '<?php
// Direct Form Handler
// This code is injected at the top of each admin page to handle form submissions directly

// Check if this is a form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the content type from the current page
    $currentPage = basename($_SERVER["PHP_SELF"]);
    $contentType = str_replace(".php", "", $currentPage);
    
    // Get the action and ID from the URL
    $action = isset($_GET["action"]) ? $_GET["action"] : "";
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
    
    // Prepare the API endpoint
    $endpoint = "/api/v1/" . $contentType;
    if ($id > 0) {
        $endpoint .= "/" . $id;
    }
    
    // Determine the HTTP method
    $method = "POST";
    if ($action === "edit" && $id > 0) {
        $method = "PUT";
    } else if ($action === "delete" && $id > 0) {
        $method = "DELETE";
    }
    
    // Prepare the data
    $data = $_POST;
    
    // Convert checkbox values
    foreach ($data as $key => $value) {
        if ($value === "on" || $value === "1") {
            $data[$key] = true;
        } else if ($value === "off" || $value === "0") {
            $data[$key] = false;
        }
    }
    
    // Convert the data to JSON
    $jsonData = json_encode($data);
    
    // Make the API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.storiesfromtheweb.org" . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($method !== "GET" && $method !== "DELETE") {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Content-Length: " . strlen($jsonData)
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check if the request was successful
    if ($httpCode >= 200 && $httpCode < 300) {
        // Redirect back to the list page
        header("Location: " . $currentPage);
        exit;
    } else {
        // Display the error
        echo "<div style=\'background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 4px;\'>";
        echo "<h3>Error</h3>";
        echo "<p>Failed to save changes. HTTP status code: " . $httpCode . "</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        echo "<p><a href=\'" . $currentPage . "\'>Back to list</a></p>";
        echo "</div>";
    }
}
?>';

// Write the direct form handler to a file
$directFormHandlerFile = __DIR__ . '/admin/direct_form_handler.php';
if (file_put_contents($directFormHandlerFile, $directFormHandlerContent)) {
    if ($isWeb) output("<div class='success'>Created direct form handler: $directFormHandlerFile</div>", true);
    else output("Created direct form handler: $directFormHandlerFile");
} else {
    if ($isWeb) output("<div class='error'>Failed to create direct form handler</div>", true);
    else output("Error: Failed to create direct form handler");
}

// Create a script to inject the direct form handler into each admin page
$injectScript = '<?php
// This script injects the direct form handler into each admin page

// Include the direct form handler
require_once __DIR__ . "/direct_form_handler.php";

// Disable output buffering
ob_end_clean();

// Start output buffering
ob_start();
';

$injectScriptFile = __DIR__ . '/admin/inject_form_handler.php';
if (file_put_contents($injectScriptFile, $injectScript)) {
    if ($isWeb) output("<div class='success'>Created inject script: $injectScriptFile</div>", true);
    else output("Created inject script: $injectScriptFile");
} else {
    if ($isWeb) output("<div class='error'>Failed to create inject script</div>", true);
    else output("Error: Failed to create inject script");
}

// Create a .htaccess file to auto-prepend the inject script
$htaccessContent = '# Auto-prepend the inject script
php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/inject_form_handler.php"

# Disable JavaScript
<FilesMatch "\.js$">
    Header set Content-Type "text/plain"
</FilesMatch>
';

$htaccessFile = __DIR__ . '/admin/.htaccess';
if (file_exists($htaccessFile)) {
    // Backup the existing .htaccess file
    $backupFile = $htaccessFile . '.bak.' . date('YmdHis');
    if (!copy($htaccessFile, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of .htaccess file</div>", true);
        else output("Warning: Failed to create backup of .htaccess file");
    } else {
        output("Backup created: $backupFile");
    }
}

if (file_put_contents($htaccessFile, $htaccessContent)) {
    if ($isWeb) output("<div class='success'>Created .htaccess file: $htaccessFile</div>", true);
    else output("Created .htaccess file: $htaccessFile");
} else {
    if ($isWeb) output("<div class='error'>Failed to create .htaccess file</div>", true);
    else output("Error: Failed to create .htaccess file");
}

// Process each admin page
foreach ($adminPages as $page) {
    $pagePath = __DIR__ . '/admin/' . $page;
    
    if (!file_exists($pagePath)) {
        // Try to find the page in the server path
        $serverPath = '/home/stories/api.storiesfromtheweb.org/admin/' . $page;
        if (file_exists($serverPath)) {
            $pagePath = $serverPath;
        } else {
            if ($isWeb) output("<div class='warning'>Page not found: $page</div>", true);
            else output("Warning: Page not found: $page");
            continue;
        }
    }
    
    output("Processing page: $page");
    
    // Backup the original file
    $backupFile = $pagePath . '.bak.' . date('YmdHis');
    if (!copy($pagePath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of $page</div>", true);
        else output("Warning: Failed to create backup of $page");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the file content
    $content = file_get_contents($pagePath);
    
    // Modify the content
    $modifiedContent = $content;
    
    // 1. Remove the form-loading class from forms
    $modifiedContent = preg_replace('/class="([^"]*)\bform-loading\b([^"]*)"/', 'class="$1$2"', $modifiedContent);
    
    // 2. Remove the btn-loading class from buttons
    $modifiedContent = preg_replace('/class="([^"]*)\bbtn-loading\b([^"]*)"/', 'class="$1btn$2"', $modifiedContent);
    
    // 3. Remove the spinner elements
    $modifiedContent = preg_replace('/<span class="spinner-border[^>]*>.*?<\/span>/s', '', $modifiedContent);
    
    // 4. Remove the loading overlay
    $modifiedContent = preg_replace('/<div class="loading-overlay[^>]*>.*?<\/div>/s', '', $modifiedContent);
    
    // 5. Remove any JavaScript event handlers from the form
    $modifiedContent = preg_replace('/onsubmit="[^"]*"/', '', $modifiedContent);
    
    // 6. Add a direct action to the form that points to the same page
    $modifiedContent = preg_replace('/<form([^>]*)action="[^"]*"([^>]*)>/', '<form$1action="' . $page . '?action=$_GET[action]&id=$_GET[id]"$2 method="post">', $modifiedContent);
    
    // Write the modified content back to the file
    if (file_put_contents($pagePath, $modifiedContent)) {
        if ($isWeb) output("<div class='success'>Successfully modified $page</div>", true);
        else output("Successfully modified $page");
    } else {
        if ($isWeb) output("<div class='error'>Failed to modify $page</div>", true);
        else output("Error: Failed to modify $page");
    }
}

// Create a CSS file to hide the loading overlay
$cssContent = '/* Hide loading overlay */
.loading-overlay {
    display: none !important;
}

/* Hide spinner */
.spinner-border {
    display: none !important;
}

/* Show button text */
.button-text {
    display: inline !important;
}
';

$cssFile = __DIR__ . '/admin/assets/css/no-loading.css';
if (file_put_contents($cssFile, $cssContent)) {
    if ($isWeb) output("<div class='success'>Created CSS file: $cssFile</div>", true);
    else output("Created CSS file: $cssFile");
} else {
    if ($isWeb) output("<div class='error'>Failed to create CSS file</div>", true);
    else output("Error: Failed to create CSS file");
}

// Find the header file
$headerFile = __DIR__ . '/admin/views/header.php';
if (!file_exists($headerFile)) {
    // Try to find the header file
    $possibleHeaderFiles = [
        __DIR__ . '/admin/views/header.php',
        __DIR__ . '/admin/includes/header.php',
        __DIR__ . '/admin/header.php',
        '/home/stories/api.storiesfromtheweb.org/admin/views/header.php',
        '/home/stories/api.storiesfromtheweb.org/admin/includes/header.php',
        '/home/stories/api.storiesfromtheweb.org/admin/header.php'
    ];
    
    foreach ($possibleHeaderFiles as $file) {
        if (file_exists($file)) {
            $headerFile = $file;
            output("Found header file: $headerFile");
            break;
        }
    }
    
    if (!file_exists($headerFile)) {
        if ($isWeb) output("<div class='error'>Header file not found</div>", true);
        else output("Error: Header file not found");
    }
}

if (file_exists($headerFile)) {
    // Backup the header file
    $backupFile = $headerFile . '.bak.' . date('YmdHis');
    if (!copy($headerFile, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of header file</div>", true);
        else output("Warning: Failed to create backup of header file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the header file
    $headerContent = file_get_contents($headerFile);
    
    // Add the no-loading CSS
    $headerContent = str_replace('</head>', '
    <!-- No loading CSS -->
    <link href="/admin/assets/css/no-loading.css" rel="stylesheet">
</head>', $headerContent);
    
    // Write the modified content back to the file
    if (file_put_contents($headerFile, $headerContent)) {
        if ($isWeb) output("<div class='success'>Successfully modified header file</div>", true);
        else output("Successfully modified header file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to modify header file</div>", true);
        else output("Error: Failed to modify header file");
    }
}

output("");
output("Next Steps:");
output("1. Access the admin interface at: https://api.storiesfromtheweb.org/admin/");
output("2. Test the admin interface by creating or editing content");
output("3. The forms should now submit directly without JavaScript interference");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}