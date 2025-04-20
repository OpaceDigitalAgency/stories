<?php
/**
 * Simple Fix
 * 
 * This script:
 * 1. Blocks all JavaScript
 * 2. Creates a favicon.ico file
 * 3. Adds a simple HTML header with navigation
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
    <title>Simple Fix</title>
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
        <h1>Simple Fix</h1>';
}

output("Simple Fix");
output("==========");
output("");

// Step 1: Create a favicon.ico file
output("Step 1: Creating favicon.ico file...");
$faviconData = base64_decode('AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAA==');

$faviconPath = __DIR__ . '/admin/favicon.ico';
if (file_put_contents($faviconPath, $faviconData)) {
    if ($isWeb) output("<div class='success'>Created favicon.ico file: $faviconPath</div>", true);
    else output("Created favicon.ico file: $faviconPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create favicon.ico file</div>", true);
    else output("Error: Failed to create favicon.ico file");
}

// Step 2: Update the .htaccess file to block JavaScript
output("Step 2: Updating .htaccess file to block JavaScript...");
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
    
    // Create a new .htaccess file that blocks JavaScript
    $htaccessContent = '# Auto-prepend the inject script
php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/inject_form_handler.php"

# Block all JavaScript files
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
    if ($isWeb) output("<div class='error'>.htaccess file not found</div>", true);
    else output("Error: .htaccess file not found");
}

// Step 3: Create a simple HTML header with navigation
output("Step 3: Creating simple HTML header with navigation...");
$headerContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Admin"; ?> - Stories Admin</title>
    <link rel="icon" href="/admin/favicon.ico" type="image/x-icon">
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Base */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            padding-top: 60px;
            display: flex;
            min-height: 100vh;
        }
        
        a {
            color: #4a6cf7;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        /* Top Navigation */
        .top-nav {
            background-color: #4a6cf7;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            display: flex;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .top-nav-brand {
            display: flex;
            align-items: center;
            font-size: 20px;
            font-weight: bold;
            padding: 0 20px;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.1);
            color: white;
            text-decoration: none;
        }
        
        .top-nav-brand:hover {
            text-decoration: none;
        }
        
        .top-nav-menu {
            display: flex;
            height: 100%;
        }
        
        .top-nav-item {
            height: 100%;
        }
        
        .top-nav-link {
            display: flex;
            align-items: center;
            height: 100%;
            padding: 0 20px;
            color: white;
            text-decoration: none;
        }
        
        .top-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }
        
        .top-nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
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
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <a href="/admin/index.php" class="top-nav-brand">Stories Admin</a>
        <div class="top-nav-menu">
            <div class="top-nav-item">
                <a href="/admin/index.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'index.php\' ? \' active\' : \'\'; ?>">Dashboard</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/stories.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'stories.php\' ? \' active\' : \'\'; ?>">Stories</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/blog-posts.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'blog-posts.php\' ? \' active\' : \'\'; ?>">Blog</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/authors.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'authors.php\' ? \' active\' : \'\'; ?>">Authors</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/tags.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'tags.php\' ? \' active\' : \'\'; ?>">Tags</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/logout.php" class="top-nav-link">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Side Navigation -->
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
    
    <!-- Main Content -->
    <div class="main-content">
';

$headerPath = __DIR__ . '/admin/views/header.php';
if (!file_exists($headerPath)) {
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
            $headerPath = $file;
            output("Found header file: $headerPath");
            break;
        }
    }
    
    if (!file_exists($headerPath)) {
        if ($isWeb) output("<div class='error'>Header file not found</div>", true);
        else output("Error: Header file not found");
    }
}

if (file_exists($headerPath)) {
    // Backup the header file
    $backupFile = $headerPath . '.bak.' . date('YmdHis');
    if (!copy($headerPath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of header file</div>", true);
        else output("Warning: Failed to create backup of header file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Write the new header content
    if (file_put_contents($headerPath, $headerContent)) {
        if ($isWeb) output("<div class='success'>Replaced header file with plain HTML navigation</div>", true);
        else output("Replaced header file with plain HTML navigation");
    } else {
        if ($isWeb) output("<div class='error'>Failed to replace header file</div>", true);
        else output("Error: Failed to replace header file");
    }
}

// Step 4: Create a simple footer file
output("Step 4: Creating simple footer file...");
$footerContent = '    </div>
</body>
</html>';

$footerPath = __DIR__ . '/admin/views/footer.php';
if (!file_exists($footerPath)) {
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
            $footerPath = $file;
            output("Found footer file: $footerPath");
            break;
        }
    }
    
    if (!file_exists($footerPath)) {
        if ($isWeb) output("<div class='error'>Footer file not found</div>", true);
        else output("Error: Footer file not found");
    }
}

if (file_exists($footerPath)) {
    // Backup the footer file
    $backupFile = $footerPath . '.bak.' . date('YmdHis');
    if (!copy($footerPath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of footer file</div>", true);
        else output("Warning: Failed to create backup of footer file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Write the new footer content
    if (file_put_contents($footerPath, $footerContent)) {
        if ($isWeb) output("<div class='success'>Replaced footer file</div>", true);
        else output("Replaced footer file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to replace footer file</div>", true);
        else output("Error: Failed to replace footer file");
    }
}

output("");
output("All fixes have been applied!");
output("1. JavaScript has been blocked at the server level");
output("2. A favicon.ico file has been created to prevent 404 errors");
output("3. The header has been replaced with a plain HTML navigation");
output("4. The footer has been replaced with a simple HTML footer");
output("");
output("IMPORTANT: If you still see JavaScript loading or 404 errors, try clearing your browser cache or opening the page in a private/incognito window.");

if ($isWeb) {
    echo '<div style="margin-top: 20px;"><a href="javascript:history.back()">Back</a></div>';
    echo '</div></body></html>';
}