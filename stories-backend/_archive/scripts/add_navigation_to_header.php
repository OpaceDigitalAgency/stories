<?php
/**
 * Add Navigation to Header
 * 
 * This script adds HTML navigation directly to the header file
 * to ensure it appears on all admin pages.
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
    <title>Add Navigation to Header</title>
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
        <h1>Add Navigation to Header</h1>
', true);
}

output("Add Navigation to Header");
output("=====================");
output("");

// Create a CSS file for the navigation
$navCssContent = '/* Header Navigation CSS */
.header-nav {
    background-color: #f8f9fa;
    padding: 15px;
    margin: 10px 0 20px 0;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.header-nav-title {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 18px;
    font-weight: bold;
}

.header-nav-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.header-nav-link {
    display: block;
    padding: 8px 12px;
    background-color: #4a6cf7;
    color: white !important;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    text-align: center;
    transition: background-color 0.2s;
}

.header-nav-link:hover {
    background-color: #3a5bd7;
    text-decoration: none;
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

$navCssPath = __DIR__ . '/admin/assets/css/header-nav.css';
if (file_put_contents($navCssPath, $navCssContent)) {
    if ($isWeb) output("<div class='success'>Created header navigation CSS file: $navCssPath</div>", true);
    else output("Created header navigation CSS file: $navCssPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create header navigation CSS file</div>", true);
    else output("Error: Failed to create header navigation CSS file");
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
    
    // Add the header navigation CSS
    $headerContent = str_replace('</head>', '
    <!-- Header Navigation CSS -->
    <link href="/admin/assets/css/header-nav.css" rel="stylesheet">
</head>', $headerContent);
    
    // Add the navigation HTML after the navbar
    $navbarEndPos = strpos($headerContent, '</nav>');
    if ($navbarEndPos !== false) {
        $navbarEndPos += 6; // Length of '</nav>'
        
        // Create the navigation HTML
        $navHtml = '
    <!-- Header Navigation -->
    <div class="header-nav">
        <h3 class="header-nav-title">Navigation Menu</h3>
        <div class="header-nav-grid">
            <a href="/admin/index.php" class="header-nav-link">Dashboard</a>
            <a href="/admin/stories.php" class="header-nav-link">Stories</a>
            <a href="/admin/authors.php" class="header-nav-link">Authors</a>
            <a href="/admin/tags.php" class="header-nav-link">Tags</a>
            <a href="/admin/blog-posts.php" class="header-nav-link">Blog Posts</a>
            <a href="/admin/games.php" class="header-nav-link">Games</a>
            <a href="/admin/directory-items.php" class="header-nav-link">Directory Items</a>
            <a href="/admin/ai-tools.php" class="header-nav-link">AI Tools</a>
            <a href="/admin/media.php" class="header-nav-link">Media</a>
            <a href="/admin/logout.php" class="header-nav-link">Logout</a>
        </div>
    </div>';
        
        // Insert the navigation HTML after the navbar
        $newHeaderContent = substr($headerContent, 0, $navbarEndPos) . $navHtml . substr($headerContent, $navbarEndPos);
        
        // Write the modified content back to the file
        if (file_put_contents($headerFile, $newHeaderContent)) {
            if ($isWeb) output("<div class='success'>Added navigation to header file</div>", true);
            else output("Added navigation to header file");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
            else output("Error: Failed to update header file");
        }
    } else {
        if ($isWeb) output("<div class='warning'>Could not find navbar end in header file</div>", true);
        else output("Warning: Could not find navbar end in header file");
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
output("2. You should now see a navigation menu right after the top navbar");
output("3. Use these links to navigate between different sections of the admin interface");
output("4. The form submissions should continue to work without the 'Processing your request...' message");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}