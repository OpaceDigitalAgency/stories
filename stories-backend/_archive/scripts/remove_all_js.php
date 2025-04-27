<?php
/**
 * Remove All JavaScript
 * 
 * This script completely removes all JavaScript references from the admin interface.
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
    <title>Remove All JavaScript</title>
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
        <h1>Remove All JavaScript</h1>
', true);
}

output("Remove All JavaScript");
output("===================");
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

/* Hide all JavaScript-dependent elements */
.dropdown-toggle,
.dropdown-menu,
.spinner-border,
.loading-overlay {
    display: none !important;
}

/* Show button text */
.button-text {
    display: inline !important;
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
    
    // Remove all script tags
    $headerContent = preg_replace('/<script.*?<\/script>/s', '', $headerContent);
    
    // Add the simple navigation CSS
    $headerContent = str_replace('</head>', '
    <!-- Simple Navigation CSS -->
    <link href="/admin/assets/css/simple-nav.css" rel="stylesheet">
</head>', $headerContent);
    
    // Write the modified content back to the file
    if (file_put_contents($headerFile, $headerContent)) {
        if ($isWeb) output("<div class='success'>Removed all script tags from header file</div>", true);
        else output("Removed all script tags from header file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
        else output("Error: Failed to update header file");
    }
}

// Find the footer file
$footerFile = __DIR__ . '/admin/views/footer.php';
if (!file_exists($footerFile)) {
    // Try to find the footer file
    $possibleFooterFiles = [
        __DIR__ . '/admin/views/footer.php',
        __DIR__ . '/admin/includes/footer.php',
        __DIR__ . '/admin/footer.php',
        '/home/stories/api.storiesfromtheweb.org/admin/views/footer.php',
        '/home/stories/api.storiesfromtheweb.org/admin/includes/footer.php',
        '/home/stories/api.storiesfromtheweb.org/admin/footer.php'
    ];
    
    foreach ($possibleFooterFiles as $file) {
        if (file_exists($file)) {
            $footerFile = $file;
            output("Found footer file: $footerFile");
            break;
        }
    }
    
    if (!file_exists($footerFile)) {
        if ($isWeb) output("<div class='error'>Footer file not found</div>", true);
        else output("Error: Footer file not found");
    }
}

if (file_exists($footerFile)) {
    // Backup the footer file
    $backupFile = $footerFile . '.bak.' . date('YmdHis');
    if (!copy($footerFile, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of footer file</div>", true);
        else output("Warning: Failed to create backup of footer file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the footer file
    $footerContent = file_get_contents($footerFile);
    
    // Remove all script tags
    $footerContent = preg_replace('/<script.*?<\/script>/s', '', $footerContent);
    
    // Write the modified content back to the file
    if (file_put_contents($footerFile, $footerContent)) {
        if ($isWeb) output("<div class='success'>Removed all script tags from footer file</div>", true);
        else output("Removed all script tags from footer file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
        else output("Error: Failed to update footer file");
    }
}

// Process each admin page to add the simple navigation and remove script tags
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
    
    // Remove all script tags
    $content = preg_replace('/<script.*?<\/script>/s', '', $content);
    
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
            if ($isWeb) output("<div class='success'>Added simple navigation to $page and removed script tags</div>", true);
            else output("Added simple navigation to $page and removed script tags");
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

// Remove all JavaScript files
$jsDir = __DIR__ . '/admin/assets/js';
if (is_dir($jsDir)) {
    output("Removing JavaScript files from $jsDir");
    
    // Get all JavaScript files
    $jsFiles = glob($jsDir . '/*.js');
    
    // Create a backup directory
    $backupDir = $jsDir . '/backup_' . date('YmdHis');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Move all JavaScript files to the backup directory
    foreach ($jsFiles as $jsFile) {
        $fileName = basename($jsFile);
        $backupFile = $backupDir . '/' . $fileName;
        
        if (rename($jsFile, $backupFile)) {
            if ($isWeb) output("<div class='success'>Moved $fileName to backup directory</div>", true);
            else output("Moved $fileName to backup directory");
        } else {
            if ($isWeb) output("<div class='warning'>Failed to move $fileName to backup directory</div>", true);
            else output("Warning: Failed to move $fileName to backup directory");
        }
    }
}

output("");
output("Next Steps:");
output("1. Access the admin interface at: https://api.storiesfromtheweb.org/admin/");
output("2. You should now see a simple navigation menu with links to all content types");
output("3. All JavaScript has been completely removed from the admin interface");
output("4. The form submissions should continue to work without the 'Processing your request...' message");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}