<?php
/**
 * Create Standalone Navigation
 * 
 * This script creates a standalone navigation page that doesn't rely on any JavaScript.
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
    <title>Create Standalone Navigation</title>
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
        <h1>Create Standalone Navigation</h1>
', true);
}

output("Create Standalone Navigation");
output("==========================");
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

// Create a redirect page for the admin index
$redirectPageContent = '<?php
// Redirect to the navigation page
header("Location: /admin/navigation.php");
exit;
';

$redirectPagePath = __DIR__ . '/admin/nav.php';
if (file_put_contents($redirectPagePath, $redirectPageContent)) {
    if ($isWeb) output("<div class='success'>Created redirect page: $redirectPagePath</div>", true);
    else output("Created redirect page: $redirectPagePath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create redirect page</div>", true);
    else output("Error: Failed to create redirect page");
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

// Create a simple link to the navigation page in the header
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
    
    // Add a link to the navigation page
    $navLinkHtml = '
    <!-- Navigation Link -->
    <div style="background-color: #4a6cf7; padding: 10px; text-align: center; margin-bottom: 20px;">
        <a href="/admin/navigation.php" style="color: white; font-weight: bold; text-decoration: none; font-size: 16px;">
            ⚡ Click here for a simple navigation page without JavaScript ⚡
        </a>
    </div>';
    
    // Find the position to insert the navigation link
    $bodyPos = strpos($headerContent, '<body');
    if ($bodyPos !== false) {
        // Find the position after the body tag
        $bodyEndPos = strpos($headerContent, '>', $bodyPos) + 1;
        
        // Insert the navigation link
        $newHeaderContent = substr($headerContent, 0, $bodyEndPos) . $navLinkHtml . substr($headerContent, $bodyEndPos);
        
        // Write the modified content back to the file
        if (file_put_contents($headerFile, $newHeaderContent)) {
            if ($isWeb) output("<div class='success'>Added navigation link to header file</div>", true);
            else output("Added navigation link to header file");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
            else output("Error: Failed to update header file");
        }
    } else {
        if ($isWeb) output("<div class='warning'>Could not find body tag in header file</div>", true);
        else output("Warning: Could not find body tag in header file");
    }
}

// Update the .htaccess file to allow JavaScript files but auto-prepend the form handler
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
    
    // Create a new .htaccess file that allows JavaScript files but auto-prepends the form handler
    $htaccessContent = '# Auto-prepend the inject script
php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/inject_form_handler.php"

# Set JavaScript MIME type
<FilesMatch "\.js$">
    ForceType application/javascript
</FilesMatch>
';
    
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        if ($isWeb) output("<div class='success'>Updated .htaccess file to allow JavaScript files</div>", true);
        else output("Updated .htaccess file to allow JavaScript files");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update .htaccess file</div>", true);
        else output("Error: Failed to update .htaccess file");
    }
}

output("");
output("Next Steps:");
output("1. Access the standalone navigation page at: https://api.storiesfromtheweb.org/admin/navigation.php");
output("2. You can also use the shortcut: https://api.storiesfromtheweb.org/admin/nav.php");
output("3. The navigation page is completely standalone and doesn't rely on any JavaScript");
output("4. You can use this page to navigate to different sections of the admin interface");
output("5. The form submissions should continue to work without the 'Processing your request...' message");
output("6. A link to the navigation page has been added to the top of all admin pages");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}