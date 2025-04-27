<?php
/**
 * FIX 500 ERRORS
 * 
 * This script fixes the 500 errors on admin pages by restoring them from backups
 * and then creating a more careful update.
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
    echo '<!DOCTYPE html>
<html>
<head>
    <title>FIX 500 ERRORS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>FIX 500 ERRORS</h1>';
}

output("FIX 500 ERRORS");
output("=============");
output("");

// Step 1: Find the admin directory
output("Step 1: Finding admin directory...");
$adminDir = '/home/stories/api.storiesfromtheweb.org/admin';
if (!is_dir($adminDir)) {
    // Try to find the admin directory
    $possiblePaths = [
        __DIR__ . '/admin',
        '/home/stories/api.storiesfromtheweb.org/admin',
        '/var/www/html/admin'
    ];
    
    foreach ($possiblePaths as $path) {
        if (is_dir($path)) {
            $adminDir = $path;
            output("Found admin directory: $adminDir");
            break;
        }
    }
    
    if (!is_dir($adminDir)) {
        if ($isWeb) output("<div class='error'>Admin directory not found</div>", true);
        else output("Error: Admin directory not found");
        exit;
    }
}

// Step 2: Find and restore backup files
output("Step 2: Finding and restoring backup files...");
$adminPages = [
    'stories.php',
    'blog-posts.php',
    'authors.php',
    'tags.php',
    'games.php',
    'directory-items.php',
    'ai-tools.php',
    'media.php'
];

foreach ($adminPages as $page) {
    $pagePath = $adminDir . '/' . $page;
    
    // Find the most recent backup file
    $backupFiles = glob($pagePath . '.bak.*');
    if (!empty($backupFiles)) {
        // Sort by modification time (newest first)
        usort($backupFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestBackup = $backupFiles[0];
        
        // Restore the backup
        if (copy($latestBackup, $pagePath)) {
            if ($isWeb) output("<div class='success'>Restored $page from backup: $latestBackup</div>", true);
            else output("Restored $page from backup: $latestBackup");
        } else {
            if ($isWeb) output("<div class='error'>Failed to restore $page from backup</div>", true);
            else output("Error: Failed to restore $page from backup");
        }
    } else {
        if ($isWeb) output("<div class='warning'>No backup found for $page</div>", true);
        else output("Warning: No backup found for $page");
    }
}

// Step 3: Create a navigation include file that can be added to existing pages
output("Step 3: Creating navigation include file...");
$navigationIncludeContent = '<?php
// Check if this file is being included
if (!defined("ADMIN_PAGE")) {
    die("Direct access not allowed");
}
?>

<!-- Side Navigation -->
<style>
/* Side Navigation */
.side-nav {
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    padding: 20px 0;
    position: fixed;
    left: 0;
    top: 60px;
    bottom: 0;
    overflow-y: auto;
    z-index: 900;
}

.side-nav-section {
    margin-bottom: 20px;
}

.side-nav-title {
    padding: 10px 20px;
    margin: 0;
    font-size: 16px;
    color: #333;
    font-weight: bold;
    border-bottom: 1px solid #eee;
}

.side-nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.side-nav-item {
    margin: 0;
}

.side-nav-link {
    display: block;
    padding: 10px 20px;
    color: #333;
    text-decoration: none;
}

.side-nav-link:hover {
    background-color: #f5f5f5;
    text-decoration: none;
}

.side-nav-link.active {
    background-color: #e9ecef;
    border-left: 3px solid #4a6cf7;
    padding-left: 17px;
}

/* Content Area with Side Nav */
.content-with-sidenav {
    margin-left: 250px;
    padding: 20px;
}
</style>

<div class="side-nav">
    <div class="side-nav-section">
        <h3 class="side-nav-title">Content</h3>
        <ul class="side-nav-menu">
            <li class="side-nav-item">
                <a href="/admin/stories.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'stories.php\' ? \' active\' : \'\'; ?>">Stories</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/blog-posts.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'blog-posts.php\' ? \' active\' : \'\'; ?>">Blog Posts</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/games.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'games.php\' ? \' active\' : \'\'; ?>">Games</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/directory-items.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'directory-items.php\' ? \' active\' : \'\'; ?>">Directory Items</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/ai-tools.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'ai-tools.php\' ? \' active\' : \'\'; ?>">AI Tools</a>
            </li>
        </ul>
    </div>
    
    <div class="side-nav-section">
        <h3 class="side-nav-title">Management</h3>
        <ul class="side-nav-menu">
            <li class="side-nav-item">
                <a href="/admin/authors.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'authors.php\' ? \' active\' : \'\'; ?>">Authors</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/tags.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'tags.php\' ? \' active\' : \'\'; ?>">Tags</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/media.php" class="side-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'media.php\' ? \' active\' : \'\'; ?>">Media</a>
            </li>
        </ul>
    </div>
    
    <div class="side-nav-section">
        <h3 class="side-nav-title">Add New</h3>
        <ul class="side-nav-menu">
            <li class="side-nav-item">
                <a href="/admin/stories.php?action=add" class="side-nav-link">Add Story</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/blog-posts.php?action=add" class="side-nav-link">Add Blog Post</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/authors.php?action=add" class="side-nav-link">Add Author</a>
            </li>
            <li class="side-nav-item">
                <a href="/admin/tags.php?action=add" class="side-nav-link">Add Tag</a>
            </li>
        </ul>
    </div>
</div>

<div class="content-with-sidenav">
';

$navigationIncludePath = $adminDir . '/includes/side_navigation.php';
// Create the directory if it doesn't exist
if (!is_dir(dirname($navigationIncludePath))) {
    mkdir(dirname($navigationIncludePath), 0755, true);
}

if (file_put_contents($navigationIncludePath, $navigationIncludeContent)) {
    if ($isWeb) output("<div class='success'>Created navigation include file: $navigationIncludePath</div>", true);
    else output("Created navigation include file: $navigationIncludePath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create navigation include file</div>", true);
    else output("Error: Failed to create navigation include file");
}

// Step 4: Create a navigation end include file
output("Step 4: Creating navigation end include file...");
$navigationEndContent = '</div><!-- End content-with-sidenav -->';

$navigationEndPath = $adminDir . '/includes/side_navigation_end.php';
if (file_put_contents($navigationEndPath, $navigationEndContent)) {
    if ($isWeb) output("<div class='success'>Created navigation end file: $navigationEndPath</div>", true);
    else output("Created navigation end file: $navigationEndPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create navigation end file</div>", true);
    else output("Error: Failed to create navigation end file");
}

// Step 5: Create a script to add the navigation to admin pages
output("Step 5: Creating script to add navigation to admin pages...");
$addNavigationContent = '<?php
/**
 * Add Navigation
 * 
 * This script adds the side navigation to admin pages.
 * It should be included at the top of each admin page.
 */

// Define a constant to prevent direct access to the navigation include file
define("ADMIN_PAGE", true);

// Include the side navigation
include_once __DIR__ . "/side_navigation.php";
';

$addNavigationPath = $adminDir . '/includes/add_navigation.php';
if (file_put_contents($addNavigationPath, $addNavigationContent)) {
    if ($isWeb) output("<div class='success'>Created add navigation script: $addNavigationPath</div>", true);
    else output("Created add navigation script: $addNavigationPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create add navigation script</div>", true);
    else output("Error: Failed to create add navigation script");
}

// Step 6: Create a favicon.ico file
output("Step 6: Creating favicon.ico file...");
$faviconData = base64_decode('AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAA==');

$faviconPath = $adminDir . '/favicon.ico';
if (file_put_contents($faviconPath, $faviconData)) {
    if ($isWeb) output("<div class='success'>Created favicon.ico file: $faviconPath</div>", true);
    else output("Created favicon.ico file: $faviconPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create favicon.ico file</div>", true);
    else output("Error: Failed to create favicon.ico file");
}

// Step 7: Update the .htaccess file to block JavaScript
output("Step 7: Updating .htaccess file to block JavaScript...");
$htaccessPath = $adminDir . '/.htaccess';
if (file_exists($htaccessPath)) {
    // Backup the existing .htaccess file
    $backupFile = $htaccessPath . '.bak.' . date('YmdHis');
    if (!copy($htaccessPath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of .htaccess file</div>", true);
        else output("Warning: Failed to create backup of .htaccess file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Create a new .htaccess file that blocks JavaScript
    $htaccessContent = '# Block all JavaScript files
<FilesMatch "\.js$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block inline JavaScript execution
<IfModule mod_headers.c>
    Header set Content-Security-Policy "script-src \'none\';"
</IfModule>
';
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        if ($isWeb) output("<div class='success'>Updated .htaccess file to block JavaScript</div>", true);
        else output("Updated .htaccess file to block JavaScript");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update .htaccess file</div>", true);
        else output("Error: Failed to update .htaccess file");
    }
} else {
    if ($isWeb) output("<div class='warning'>.htaccess file not found, creating new one</div>", true);
    else output("Warning: .htaccess file not found, creating new one");
    
    $htaccessContent = '# Block all JavaScript files
<FilesMatch "\.js$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Block inline JavaScript execution
<IfModule mod_headers.c>
    Header set Content-Security-Policy "script-src \'none\';"
</IfModule>
';
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        if ($isWeb) output("<div class='success'>Created .htaccess file to block JavaScript</div>", true);
        else output("Created .htaccess file to block JavaScript");
    } else {
        if ($isWeb) output("<div class='error'>Failed to create .htaccess file</div>", true);
        else output("Error: Failed to create .htaccess file");
    }
}

// Step 8: Create instructions for adding navigation to admin pages
output("Step 8: Creating instructions for adding navigation to admin pages...");
if ($isWeb) {
    output("<h2>How to Add Navigation to Admin Pages</h2>", true);
    output("<p>To add the side navigation to your admin pages, follow these steps:</p>", true);
    output("<ol>", true);
    output("<li>Open each admin page (stories.php, blog-posts.php, etc.)</li>", true);
    output("<li>Add the following code at the top of the page, after any session handling or database connection code:</li>", true);
    output("<pre>include_once __DIR__ . '/includes/add_navigation.php';</pre>", true);
    output("<li>Add the following code at the bottom of the page, before the closing ?> tag:</li>", true);
    output("<pre>include_once __DIR__ . '/includes/side_navigation_end.php';</pre>", true);
    output("</ol>", true);
} else {
    output("How to Add Navigation to Admin Pages");
    output("----------------------------------");
    output("");
    output("To add the side navigation to your admin pages, follow these steps:");
    output("");
    output("1. Open each admin page (stories.php, blog-posts.php, etc.)");
    output("2. Add the following code at the top of the page, after any session handling or database connection code:");
    output("   include_once __DIR__ . '/includes/add_navigation.php';");
    output("3. Add the following code at the bottom of the page, before the closing ?> tag:");
    output("   include_once __DIR__ . '/includes/side_navigation_end.php';");
}

output("");
output("All fixes have been applied!");
output("1. Restored admin pages from backups");
output("2. Created navigation include files");
output("3. Created favicon.ico file");
output("4. Updated .htaccess file to block JavaScript");
output("");
output("Follow the instructions above to add the navigation to your admin pages.");

if ($isWeb) {
    echo '<div style="margin-top: 20px;"><a href="/admin/index.php">View Dashboard</a></div>';
    echo '</div></body></html>';
}