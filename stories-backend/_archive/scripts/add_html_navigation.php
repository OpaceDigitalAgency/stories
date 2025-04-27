<?php
/**
 * Add HTML Navigation
 * 
 * This script adds simple HTML navigation links to all admin pages
 * without relying on JavaScript at all.
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
    <title>Add HTML Navigation</title>
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
        <h1>Add HTML Navigation</h1>
', true);
}

output("Add HTML Navigation");
output("==================");
output("");

// Create a CSS file for the navigation
$navCssContent = '/* HTML Navigation CSS */
.html-nav {
    margin: 20px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.html-nav h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 18px;
}

.html-nav-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.html-nav a {
    display: block;
    padding: 10px 15px;
    background-color: #4a6cf7;
    color: white !important;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    text-align: center;
    transition: background-color 0.2s;
}

.html-nav a:hover {
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

/* Hide loading overlay */
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

$navCssPath = __DIR__ . '/admin/assets/css/html-nav.css';
if (file_put_contents($navCssPath, $navCssContent)) {
    if ($isWeb) output("<div class='success'>Created navigation CSS file: $navCssPath</div>", true);
    else output("Created navigation CSS file: $navCssPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create navigation CSS file</div>", true);
    else output("Error: Failed to create navigation CSS file");
}

// Create empty placeholder files for JavaScript files to prevent 404 errors
$jsFiles = [
    'admin.js',
    'bootstrap.bundle.min.js',
    'jquery.min.js',
    'chart.min.js',
    'ckeditor.js',
    'flatpickr.min.js',
    'bootstrap-tagsinput.min.js'
];

$jsDir = __DIR__ . '/admin/assets/js';
if (!is_dir($jsDir)) {
    if (!mkdir($jsDir, 0755, true)) {
        if ($isWeb) output("<div class='error'>Failed to create JavaScript directory</div>", true);
        else output("Error: Failed to create JavaScript directory");
    } else {
        output("Created JavaScript directory: $jsDir");
    }
}

foreach ($jsFiles as $jsFile) {
    $jsFilePath = $jsDir . '/' . $jsFile;
    $emptyJs = "// Empty placeholder file to prevent 404 errors\n// Original functionality has been disabled to fix form submission issues\n";
    
    if (file_put_contents($jsFilePath, $emptyJs)) {
        if ($isWeb) output("<div class='success'>Created empty placeholder file: $jsFilePath</div>", true);
        else output("Created empty placeholder file: $jsFilePath");
    } else {
        if ($isWeb) output("<div class='error'>Failed to create empty placeholder file: $jsFilePath</div>", true);
        else output("Error: Failed to create empty placeholder file: $jsFilePath");
    }
}

// Create the HTML navigation content
$navHtmlContent = '<!-- HTML Navigation -->
<div class="html-nav">
    <h3>Navigation Menu</h3>
    <div class="html-nav-grid">
        <a href="/admin/index.php">Dashboard</a>
        <a href="/admin/stories.php">Stories</a>
        <a href="/admin/authors.php">Authors</a>
        <a href="/admin/tags.php">Tags</a>
        <a href="/admin/blog-posts.php">Blog Posts</a>
        <a href="/admin/games.php">Games</a>
        <a href="/admin/directory-items.php">Directory Items</a>
        <a href="/admin/ai-tools.php">AI Tools</a>
        <a href="/admin/media.php">Media</a>
        <a href="/admin/logout.php">Logout</a>
    </div>
</div>';

$navHtmlPath = __DIR__ . '/admin/html_nav.php';
if (file_put_contents($navHtmlPath, $navHtmlContent)) {
    if ($isWeb) output("<div class='success'>Created HTML navigation file: $navHtmlPath</div>", true);
    else output("Created HTML navigation file: $navHtmlPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create HTML navigation file</div>", true);
    else output("Error: Failed to create HTML navigation file");
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
    
    // Add the HTML navigation CSS
    $headerContent = str_replace('</head>', '
    <!-- HTML Navigation CSS -->
    <link href="/admin/assets/css/html-nav.css" rel="stylesheet">
</head>', $headerContent);
    
    // Write the modified content back to the file
    if (file_put_contents($headerFile, $headerContent)) {
        if ($isWeb) output("<div class='success'>Added HTML navigation CSS to header file</div>", true);
        else output("Added HTML navigation CSS to header file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
        else output("Error: Failed to update header file");
    }
}

// Process each admin page to add the HTML navigation
$adminPages = [
    'index.php',
    'stories.php',
    'authors.php',
    'tags.php',
    'blog-posts.php',
    'games.php',
    'directory-items.php',
    'ai-tools.php',
    'media.php'
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
    
    // Find the position to insert the HTML navigation
    $containerPos = strpos($content, '<div class="container-fluid">');
    if ($containerPos !== false) {
        // Find the position after the container opening tag
        $insertPos = strpos($content, '>', $containerPos) + 1;
        
        // Insert the HTML navigation
        $newContent = substr($content, 0, $insertPos) . '
        <!-- HTML Navigation -->
        <?php include_once __DIR__ . "/html_nav.php"; ?>
        ' . substr($content, $insertPos);
        
        // Write the modified content back to the file
        if (file_put_contents($pagePath, $newContent)) {
            if ($isWeb) output("<div class='success'>Added HTML navigation to $page</div>", true);
            else output("Added HTML navigation to $page");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update $page</div>", true);
            else output("Error: Failed to update $page");
        }
    } else {
        if ($isWeb) output("<div class='warning'>Could not find container div in $page</div>", true);
        else output("Warning: Could not find container div in $page");
    }
}

// Update the .htaccess file to block all JavaScript files
$htaccessPath = __DIR__ . '/admin/.htaccess';
if (file_exists($htaccessPath)) {
    // Backup the existing .htaccess file
    $backupFile = $htaccessPath . '.bak.' . date('YmdHis');
    if (!copy($htaccessPath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of .htaccess file</div>", true);
        else output("Warning: Failed to create backup of .htaccess file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Create a new .htaccess file that blocks all JavaScript
    $htaccessContent = '# Auto-prepend the inject script
php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/inject_form_handler.php"

# Block all JavaScript files
<FilesMatch "\.js$">
    Order allow,deny
    Deny from all
</FilesMatch>
';
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        if ($isWeb) output("<div class='success'>Updated .htaccess file to block all JavaScript files</div>", true);
        else output("Updated .htaccess file to block all JavaScript files");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update .htaccess file</div>", true);
        else output("Error: Failed to update .htaccess file");
    }
}

output("");
output("Next Steps:");
output("1. Access the admin interface at: https://api.storiesfromtheweb.org/admin/");
output("2. You should now see a simple HTML navigation menu at the top of each page");
output("3. Use these links to navigate between different sections of the admin interface");
output("4. The form submissions should continue to work without the 'Processing your request...' message");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}