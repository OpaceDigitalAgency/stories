<?php
/**
 * Simple Navigation Fix
 * 
 * This script adds simple HTML links for navigation without using JavaScript.
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
    <title>Simple Navigation Fix</title>
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
        <h1>Simple Navigation Fix</h1>
', true);
}

output("Simple Navigation Fix");
output("==================");
output("");

// Create a simple navigation CSS file
$navCssContent = '/* Simple Navigation CSS */
.simple-nav {
    margin-top: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.simple-nav h5 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.simple-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.simple-nav li {
    margin-bottom: 8px;
}

.simple-nav a {
    display: block;
    padding: 8px 15px;
    background-color: #4a6cf7;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.2s;
}

.simple-nav a:hover {
    background-color: #3a5bd7;
}

/* Hide dropdown toggles */
.dropdown-toggle::after {
    display: none !important;
}

/* Hide dropdown menus */
.dropdown-menu {
    display: none !important;
}
';

$navCssPath = __DIR__ . '/admin/assets/css/simple-nav.css';
if (file_put_contents($navCssPath, $navCssContent)) {
    if ($isWeb) output("<div class='success'>Created simple navigation CSS file: $navCssPath</div>", true);
    else output("Created simple navigation CSS file: $navCssPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create simple navigation CSS file</div>", true);
    else output("Error: Failed to create simple navigation CSS file");
}

// Create a simple navigation HTML file
$navHtmlContent = '<!-- Simple Navigation -->
<div class="simple-nav">
    <h5>Navigation Menu</h5>
    <ul>
        <li><a href="/admin/index.php">Dashboard</a></li>
        <li><a href="/admin/stories.php">Stories</a></li>
        <li><a href="/admin/authors.php">Authors</a></li>
        <li><a href="/admin/tags.php">Tags</a></li>
        <li><a href="/admin/blog-posts.php">Blog Posts</a></li>
        <li><a href="/admin/games.php">Games</a></li>
        <li><a href="/admin/directory-items.php">Directory Items</a></li>
        <li><a href="/admin/ai-tools.php">AI Tools</a></li>
        <li><a href="/admin/logout.php">Logout</a></li>
    </ul>
</div>';

$navHtmlPath = __DIR__ . '/admin/simple_nav.html';
if (file_put_contents($navHtmlPath, $navHtmlContent)) {
    if ($isWeb) output("<div class='success'>Created simple navigation HTML file: $navHtmlPath</div>", true);
    else output("Created simple navigation HTML file: $navHtmlPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create simple navigation HTML file</div>", true);
    else output("Error: Failed to create simple navigation HTML file");
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
    
    // Add the simple navigation CSS
    $headerContent = str_replace('</head>', '
    <!-- Simple Navigation CSS -->
    <link href="/admin/assets/css/simple-nav.css" rel="stylesheet">
</head>', $headerContent);
    
    // Write the modified content back to the file
    if (file_put_contents($headerFile, $headerContent)) {
        if ($isWeb) output("<div class='success'>Added simple navigation CSS to header file</div>", true);
        else output("Added simple navigation CSS to header file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
        else output("Error: Failed to update header file");
    }
}

// Process each admin page to add the simple navigation
$adminPages = [
    'index.php',
    'stories.php',
    'authors.php',
    'tags.php',
    'blog-posts.php',
    'games.php',
    'directory-items.php',
    'ai-tools.php'
];

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
    
    // Find the position to insert the simple navigation
    $containerPos = strpos($content, '<div class="container-fluid">');
    if ($containerPos !== false) {
        // Find the position after the container opening tag
        $insertPos = strpos($content, '>', $containerPos) + 1;
        
        // Insert the simple navigation
        $newContent = substr($content, 0, $insertPos) . '
        <!-- Simple Navigation -->
        <?php include_once __DIR__ . "/simple_nav.html"; ?>
        ' . substr($content, $insertPos);
        
        // Write the modified content back to the file
        if (file_put_contents($pagePath, $newContent)) {
            if ($isWeb) output("<div class='success'>Added simple navigation to $page</div>", true);
            else output("Added simple navigation to $page");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update $page</div>", true);
            else output("Error: Failed to update $page");
        }
    } else {
        if ($isWeb) output("<div class='warning'>Could not find container div in $page</div>", true);
        else output("Warning: Could not find container div in $page");
    }
}

// Remove the navigation.js file if it exists
$navJsPath = __DIR__ . '/admin/assets/js/navigation.js';
if (file_exists($navJsPath)) {
    if (unlink($navJsPath)) {
        if ($isWeb) output("<div class='success'>Removed navigation.js file</div>", true);
        else output("Removed navigation.js file");
    } else {
        if ($isWeb) output("<div class='warning'>Failed to remove navigation.js file</div>", true);
        else output("Warning: Failed to remove navigation.js file");
    }
}

output("");
output("Next Steps:");
output("1. Access the admin interface at: https://api.storiesfromtheweb.org/admin/");
output("2. You should now see a simple navigation menu with links to all content types");
output("3. The form submissions should continue to work without the 'Processing your request...' message");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}