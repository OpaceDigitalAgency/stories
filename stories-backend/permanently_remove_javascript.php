<?php
/**
 * Permanently Remove JavaScript
 * 
 * This script permanently removes all JavaScript from the admin interface
 * and makes the standalone navigation page the default dashboard.
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
    <title>Permanently Remove JavaScript</title>
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
        <h1>Permanently Remove JavaScript</h1>
', true);
}

output("Permanently Remove JavaScript");
output("===========================");
output("");

// Create the standalone navigation page
$navPageContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories Admin Navigation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background-color: #4a6cf7;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .section {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .nav-link {
            display: block;
            padding: 15px;
            background-color: #4a6cf7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        
        .nav-link:hover {
            background-color: #3a5bd7;
        }
        
        .content-section {
            margin-bottom: 40px;
        }
        
        .content-section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #eee;
            margin-top: 30px;
        }
        
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .nav-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Stories Admin Navigation</h1>
    </div>
    
    <div class="container">
        <div class="warning-box">
            <strong>Important:</strong> JavaScript has been permanently disabled to fix form submission issues. Use this navigation page to access all admin features.
        </div>
        
        <div class="section">
            <h2>Main Navigation</h2>
            <div class="nav-grid">
                <a href="/admin/index.php" class="nav-link">Dashboard</a>
                <a href="/admin/stories.php" class="nav-link">Stories</a>
                <a href="/admin/authors.php" class="nav-link">Authors</a>
                <a href="/admin/tags.php" class="nav-link">Tags</a>
                <a href="/admin/blog-posts.php" class="nav-link">Blog Posts</a>
                <a href="/admin/games.php" class="nav-link">Games</a>
                <a href="/admin/directory-items.php" class="nav-link">Directory Items</a>
                <a href="/admin/ai-tools.php" class="nav-link">AI Tools</a>
                <a href="/admin/media.php" class="nav-link">Media</a>
                <a href="/admin/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
        
        <div class="section">
            <h2>Content Management</h2>
            
            <div class="content-section">
                <h3>Stories</h3>
                <div class="nav-grid">
                    <a href="/admin/stories.php" class="nav-link">All Stories</a>
                    <a href="/admin/stories.php?action=add" class="nav-link">Add New Story</a>
                </div>
            </div>
            
            <div class="content-section">
                <h3>Authors</h3>
                <div class="nav-grid">
                    <a href="/admin/authors.php" class="nav-link">All Authors</a>
                    <a href="/admin/authors.php?action=add" class="nav-link">Add New Author</a>
                </div>
            </div>
            
            <div class="content-section">
                <h3>Tags</h3>
                <div class="nav-grid">
                    <a href="/admin/tags.php" class="nav-link">All Tags</a>
                    <a href="/admin/tags.php?action=add" class="nav-link">Add New Tag</a>
                </div>
            </div>
            
            <div class="content-section">
                <h3>Blog Posts</h3>
                <div class="nav-grid">
                    <a href="/admin/blog-posts.php" class="nav-link">All Blog Posts</a>
                    <a href="/admin/blog-posts.php?action=add" class="nav-link">Add New Blog Post</a>
                </div>
            </div>
            
            <div class="content-section">
                <h3>Games</h3>
                <div class="nav-grid">
                    <a href="/admin/games.php" class="nav-link">All Games</a>
                    <a href="/admin/games.php?action=add" class="nav-link">Add New Game</a>
                </div>
            </div>
            
            <div class="content-section">
                <h3>Directory Items</h3>
                <div class="nav-grid">
                    <a href="/admin/directory-items.php" class="nav-link">All Directory Items</a>
                    <a href="/admin/directory-items.php?action=add" class="nav-link">Add New Directory Item</a>
                </div>
            </div>
            
            <div class="content-section">
                <h3>AI Tools</h3>
                <div class="nav-grid">
                    <a href="/admin/ai-tools.php" class="nav-link">All AI Tools</a>
                    <a href="/admin/ai-tools.php?action=add" class="nav-link">Add New AI Tool</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Stories Admin Navigation - A simple, JavaScript-free navigation page</p>
    </div>
</body>
</html>';

$navPagePath = __DIR__ . '/admin/navigation.php';
if (file_put_contents($navPagePath, $navPageContent)) {
    if ($isWeb) output("<div class='success'>Created standalone navigation page: $navPagePath</div>", true);
    else output("Created standalone navigation page: $navPagePath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create standalone navigation page</div>", true);
    else output("Error: Failed to create standalone navigation page");
}

// Make the navigation page the default dashboard by replacing index.php
$indexPageContent = '<?php
// Redirect to the navigation page
header("Location: /admin/navigation.php");
exit;
';

$indexPagePath = __DIR__ . '/admin/index.php';
if (file_exists($indexPagePath)) {
    // Backup the original index.php
    $backupFile = $indexPagePath . '.bak.' . date('YmdHis');
    if (!copy($indexPagePath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of index.php</div>", true);
        else output("Warning: Failed to create backup of index.php");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Replace index.php with a redirect to navigation.php
    if (file_put_contents($indexPagePath, $indexPageContent)) {
        if ($isWeb) output("<div class='success'>Replaced index.php with redirect to navigation.php</div>", true);
        else output("Replaced index.php with redirect to navigation.php");
    } else {
        if ($isWeb) output("<div class='error'>Failed to replace index.php</div>", true);
        else output("Error: Failed to replace index.php");
    }
} else {
    if ($isWeb) output("<div class='warning'>index.php not found, creating it</div>", true);
    else output("Warning: index.php not found, creating it");
    
    if (file_put_contents($indexPagePath, $indexPageContent)) {
        if ($isWeb) output("<div class='success'>Created index.php with redirect to navigation.php</div>", true);
        else output("Created index.php with redirect to navigation.php");
    } else {
        if ($isWeb) output("<div class='error'>Failed to create index.php</div>", true);
        else output("Error: Failed to create index.php");
    }
}

// Create a favicon.ico file to prevent 404 errors
$faviconPath = __DIR__ . '/admin/favicon.ico';
if (!file_exists($faviconPath)) {
    // Create a simple 1x1 transparent ICO file
    $faviconData = base64_decode('AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAA==');
    
    if (file_put_contents($faviconPath, $faviconData)) {
        if ($isWeb) output("<div class='success'>Created favicon.ico file: $faviconPath</div>", true);
        else output("Created favicon.ico file: $faviconPath");
    } else {
        if ($isWeb) output("<div class='error'>Failed to create favicon.ico file</div>", true);
        else output("Error: Failed to create favicon.ico file");
    }
}

// Find and delete all JavaScript files
$jsDir = __DIR__ . '/admin/assets/js';
if (is_dir($jsDir)) {
    output("Removing all JavaScript files from $jsDir");
    
    // Create a backup directory
    $backupDir = $jsDir . '/backup_' . date('YmdHis');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Get all JavaScript files
    $jsFiles = glob($jsDir . '/*.js');
    
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

// Update the .htaccess file to block all JavaScript
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

# Block inline JavaScript execution
<IfModule mod_headers.c>
    Header set Content-Security-Policy "script-src \'none\';"
</IfModule>
';
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        if ($isWeb) output("<div class='success'>Updated .htaccess file to block all JavaScript</div>", true);
        else output("Updated .htaccess file to block all JavaScript");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update .htaccess file</div>", true);
        else output("Error: Failed to update .htaccess file");
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
    if ($isWeb) output("<div class='success'>Created CSS file to hide loading overlay: $cssFile</div>", true);
    else output("Created CSS file to hide loading overlay: $cssFile");
} else {
    if ($isWeb) output("<div class='error'>Failed to create CSS file</div>", true);
    else output("Error: Failed to create CSS file");
}

// Add a warning message to all admin pages
$warningHtml = '
<!-- JavaScript Disabled Warning -->
<div style="background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
    <strong>Important:</strong> JavaScript has been permanently disabled to fix form submission issues. 
    <a href="/admin/navigation.php" style="color: #856404; text-decoration: underline; font-weight: bold;">
        Click here to return to the navigation page
    </a>
</div>';

// Find all PHP files in the admin directory
$adminDir = __DIR__ . '/admin';
$phpFiles = glob($adminDir . '/*.php');

foreach ($phpFiles as $phpFile) {
    // Skip index.php and navigation.php
    if (basename($phpFile) == 'index.php' || basename($phpFile) == 'navigation.php' || basename($phpFile) == 'nav.php') {
        continue;
    }
    
    output("Processing file: " . basename($phpFile));
    
    // Backup the original file
    $backupFile = $phpFile . '.bak.' . date('YmdHis');
    if (!copy($phpFile, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of " . basename($phpFile) . "</div>", true);
        else output("Warning: Failed to create backup of " . basename($phpFile));
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the file content
    $content = file_get_contents($phpFile);
    
    // Find the position to insert the warning
    $bodyPos = strpos($content, '<body');
    if ($bodyPos !== false) {
        // Find the position after the body tag
        $bodyEndPos = strpos($content, '>', $bodyPos) + 1;
        
        // Insert the warning
        $newContent = substr($content, 0, $bodyEndPos) . $warningHtml . substr($content, $bodyEndPos);
        
        // Write the modified content back to the file
        if (file_put_contents($phpFile, $newContent)) {
            if ($isWeb) output("<div class='success'>Added warning to " . basename($phpFile) . "</div>", true);
            else output("Added warning to " . basename($phpFile));
        } else {
            if ($isWeb) output("<div class='error'>Failed to update " . basename($phpFile) . "</div>", true);
            else output("Error: Failed to update " . basename($phpFile));
        }
    } else {
        // Try to find the container div
        $containerPos = strpos($content, '<div class="container');
        if ($containerPos !== false) {
            // Find the position after the container opening tag
            $containerEndPos = strpos($content, '>', $containerPos) + 1;
            
            // Insert the warning
            $newContent = substr($content, 0, $containerEndPos) . $warningHtml . substr($content, $containerEndPos);
            
            // Write the modified content back to the file
            if (file_put_contents($phpFile, $newContent)) {
                if ($isWeb) output("<div class='success'>Added warning to " . basename($phpFile) . "</div>", true);
                else output("Added warning to " . basename($phpFile));
            } else {
                if ($isWeb) output("<div class='error'>Failed to update " . basename($phpFile) . "</div>", true);
                else output("Error: Failed to update " . basename($phpFile));
            }
        } else {
            if ($isWeb) output("<div class='warning'>Could not find insertion point in " . basename($phpFile) . "</div>", true);
            else output("Warning: Could not find insertion point in " . basename($phpFile));
        }
    }
}

output("");
output("Next Steps:");
output("1. Access the admin interface at: https://api.storiesfromtheweb.org/admin/");
output("2. You will be automatically redirected to the navigation page");
output("3. JavaScript has been permanently disabled and all JavaScript files have been removed");
output("4. The form submissions should continue to work without the 'Processing your request...' message");
output("5. A warning message has been added to all admin pages with a link back to the navigation page");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}