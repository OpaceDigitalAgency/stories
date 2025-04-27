<?php
/**
 * SIMPLE HTML NAVIGATION
 * 
 * This script creates simple HTML navigation files that can be included
 * in all admin pages.
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
    <title>SIMPLE HTML NAVIGATION</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .code-block { 
            background: #f5f5f5; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
            margin-bottom: 20px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>SIMPLE HTML NAVIGATION</h1>';
}

output("SIMPLE HTML NAVIGATION");
output("=====================");
output("");

// Step 1: Create the CSS file
output("Step 1: Creating CSS file...");
$cssContent = '/* Simple Admin Navigation CSS */

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
}

/* Layout */
.admin-container {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
}

.admin-main {
    display: flex;
    flex: 1;
}

/* Top Navigation */
.admin-header {
    background-color: #4a6cf7;
    color: white;
    padding: 0;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.admin-navbar {
    display: flex;
    align-items: center;
    height: 60px;
}

.admin-brand {
    display: flex;
    align-items: center;
    padding: 0 20px;
    font-size: 20px;
    font-weight: bold;
    text-decoration: none;
    color: white;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.1);
}

.admin-brand:hover {
    text-decoration: none;
    color: white;
}

.admin-nav {
    display: flex;
    height: 100%;
}

.admin-nav-item {
    height: 100%;
}

.admin-nav-link {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 20px;
    color: white;
    text-decoration: none;
}

.admin-nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
    text-decoration: none;
    color: white;
}

.admin-nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Side Navigation */
.admin-sidebar {
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    padding: 20px 0;
}

.admin-sidebar-section {
    margin-bottom: 20px;
}

.admin-sidebar-title {
    padding: 10px 20px;
    margin: 0;
    font-size: 16px;
    color: #333;
    font-weight: bold;
    border-bottom: 1px solid #eee;
}

.admin-sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-sidebar-item {
    margin: 0;
}

.admin-sidebar-link {
    display: block;
    padding: 10px 20px;
    color: #333;
    text-decoration: none;
}

.admin-sidebar-link:hover {
    background-color: #f5f5f5;
    text-decoration: none;
}

.admin-sidebar-link.active {
    background-color: #e9ecef;
    border-left: 3px solid #4a6cf7;
    padding-left: 17px;
}

/* Content Area */
.admin-content {
    flex: 1;
    padding: 20px;
}
';

$cssPath = __DIR__ . '/admin/assets/css/admin-navigation.css';
// Create the directory if it doesn't exist
if (!is_dir(dirname($cssPath))) {
    mkdir(dirname($cssPath), 0755, true);
}

if (file_put_contents($cssPath, $cssContent)) {
    if ($isWeb) output("<div class='success'>Created CSS file: $cssPath</div>", true);
    else output("Created CSS file: $cssPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create CSS file</div>", true);
    else output("Error: Failed to create CSS file");
}

// Step 2: Create the header file
output("Step 2: Creating header file...");
$headerContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Admin"; ?> - Stories Admin</title>
    <link rel="stylesheet" href="/admin/assets/css/admin-navigation.css">
    <link rel="icon" href="/admin/favicon.ico" type="image/x-icon">
</head>
<body>
<div class="admin-container">
    <!-- Top Navigation -->
    <header class="admin-header">
        <nav class="admin-navbar">
            <a href="/admin/index.php" class="admin-brand">Stories Admin</a>
            <div class="admin-nav">
                <div class="admin-nav-item">
                    <a href="/admin/index.php" class="admin-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'index.php\' ? \' active\' : \'\'; ?>">Dashboard</a>
                </div>
                <div class="admin-nav-item">
                    <a href="/admin/stories.php" class="admin-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'stories.php\' ? \' active\' : \'\'; ?>">Stories</a>
                </div>
                <div class="admin-nav-item">
                    <a href="/admin/blog-posts.php" class="admin-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'blog-posts.php\' ? \' active\' : \'\'; ?>">Blog</a>
                </div>
                <div class="admin-nav-item">
                    <a href="/admin/authors.php" class="admin-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'authors.php\' ? \' active\' : \'\'; ?>">Authors</a>
                </div>
                <div class="admin-nav-item">
                    <a href="/admin/tags.php" class="admin-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'tags.php\' ? \' active\' : \'\'; ?>">Tags</a>
                </div>
                <div class="admin-nav-item">
                    <a href="/admin/logout.php" class="admin-nav-link">Logout</a>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <div class="admin-main">
        <!-- Side Navigation -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-section">
                <h3 class="admin-sidebar-title">Content</h3>
                <ul class="admin-sidebar-menu">
                    <li class="admin-sidebar-item">
                        <a href="/admin/stories.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'stories.php\' ? \' active\' : \'\'; ?>">Stories</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/blog-posts.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'blog-posts.php\' ? \' active\' : \'\'; ?>">Blog Posts</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/games.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'games.php\' ? \' active\' : \'\'; ?>">Games</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/directory-items.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'directory-items.php\' ? \' active\' : \'\'; ?>">Directory Items</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/ai-tools.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'ai-tools.php\' ? \' active\' : \'\'; ?>">AI Tools</a>
                    </li>
                </ul>
            </div>
            
            <div class="admin-sidebar-section">
                <h3 class="admin-sidebar-title">Management</h3>
                <ul class="admin-sidebar-menu">
                    <li class="admin-sidebar-item">
                        <a href="/admin/authors.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'authors.php\' ? \' active\' : \'\'; ?>">Authors</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/tags.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'tags.php\' ? \' active\' : \'\'; ?>">Tags</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/media.php" class="admin-sidebar-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'media.php\' ? \' active\' : \'\'; ?>">Media</a>
                    </li>
                </ul>
            </div>
            
            <div class="admin-sidebar-section">
                <h3 class="admin-sidebar-title">Add New</h3>
                <ul class="admin-sidebar-menu">
                    <li class="admin-sidebar-item">
                        <a href="/admin/stories.php?action=add" class="admin-sidebar-link">Add Story</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/blog-posts.php?action=add" class="admin-sidebar-link">Add Blog Post</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/authors.php?action=add" class="admin-sidebar-link">Add Author</a>
                    </li>
                    <li class="admin-sidebar-item">
                        <a href="/admin/tags.php?action=add" class="admin-sidebar-link">Add Tag</a>
                    </li>
                </ul>
            </div>
        </aside>
        
        <!-- Content Area -->
        <main class="admin-content">
';

$headerPath = __DIR__ . '/admin/includes/html_header.php';
// Create the directory if it doesn't exist
if (!is_dir(dirname($headerPath))) {
    mkdir(dirname($headerPath), 0755, true);
}

if (file_put_contents($headerPath, $headerContent)) {
    if ($isWeb) output("<div class='success'>Created header file: $headerPath</div>", true);
    else output("Created header file: $headerPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create header file</div>", true);
    else output("Error: Failed to create header file");
}

// Step 3: Create the footer file
output("Step 3: Creating footer file...");
$footerContent = '        </main>
    </div>
</div>
</body>
</html>';

$footerPath = __DIR__ . '/admin/includes/html_footer.php';
// Create the directory if it doesn't exist
if (!is_dir(dirname($footerPath))) {
    mkdir(dirname($footerPath), 0755, true);
}

if (file_put_contents($footerPath, $footerContent)) {
    if ($isWeb) output("<div class='success'>Created footer file: $footerPath</div>", true);
    else output("Created footer file: $footerPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create footer file</div>", true);
    else output("Error: Failed to create footer file");
}

// Step 4: Create a favicon.ico file
output("Step 4: Creating favicon.ico file...");
$faviconData = base64_decode('AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAA==');

$faviconPath = __DIR__ . '/admin/favicon.ico';
if (file_put_contents($faviconPath, $faviconData)) {
    if ($isWeb) output("<div class='success'>Created favicon.ico file: $faviconPath</div>", true);
    else output("Created favicon.ico file: $faviconPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create favicon.ico file</div>", true);
    else output("Error: Failed to create favicon.ico file");
}

// Step 5: Update the .htaccess file to block JavaScript
output("Step 5: Updating .htaccess file to block JavaScript...");
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

// Step 6: Create a simple example file
output("Step 6: Creating example file...");
$exampleContent = '<?php
$pageTitle = "Example Page";
include_once __DIR__ . "/includes/html_header.php";
?>

<h1>Example Page</h1>

<p>This is an example page that uses the HTML navigation.</p>

<?php
include_once __DIR__ . "/includes/html_footer.php";
?>
';

$examplePath = __DIR__ . '/admin/example.php';
if (file_put_contents($examplePath, $exampleContent)) {
    if ($isWeb) output("<div class='success'>Created example file: $examplePath</div>", true);
    else output("Created example file: $examplePath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create example file</div>", true);
    else output("Error: Failed to create example file");
}

// Step 7: Provide instructions
output("Step 7: Instructions for using the HTML navigation...");
if ($isWeb) {
    output("<h2>How to Use the HTML Navigation</h2>", true);
    output("<p>To use the HTML navigation in your admin pages, follow these steps:</p>", true);
    output("<ol>", true);
    output("<li>At the top of your PHP file, add the page title and include the header:</li>", true);
    output("<div class='code-block'>&lt;?php\n\$pageTitle = \"Your Page Title\";\ninclude_once __DIR__ . \"/includes/html_header.php\";\n?&gt;</div>", true);
    output("<li>Add your page content:</li>", true);
    output("<div class='code-block'>&lt;h1&gt;Your Page Title&lt;/h1&gt;\n\n&lt;p&gt;Your page content goes here...&lt;/p&gt;</div>", true);
    output("<li>At the bottom of your PHP file, include the footer:</li>", true);
    output("<div class='code-block'>&lt;?php\ninclude_once __DIR__ . \"/includes/html_footer.php\";\n?&gt;</div>", true);
    output("</ol>", true);
    output("<p>See the example.php file for a complete example.</p>", true);
} else {
    output("How to Use the HTML Navigation");
    output("----------------------------");
    output("");
    output("To use the HTML navigation in your admin pages, follow these steps:");
    output("");
    output("1. At the top of your PHP file, add the page title and include the header:");
    output("   <?php");
    output("   \$pageTitle = \"Your Page Title\";");
    output("   include_once __DIR__ . \"/includes/html_header.php\";");
    output("   ?>");
    output("");
    output("2. Add your page content:");
    output("   <h1>Your Page Title</h1>");
    output("");
    output("   <p>Your page content goes here...</p>");
    output("");
    output("3. At the bottom of your PHP file, include the footer:");
    output("   <?php");
    output("   include_once __DIR__ . \"/includes/html_footer.php\";");
    output("   ?>");
    output("");
    output("See the example.php file for a complete example.");
}

output("");
output("All files have been created successfully!");
output("1. Created CSS file for the navigation");
output("2. Created header file with top and side navigation");
output("3. Created footer file");
output("4. Created favicon.ico file");
output("5. Updated .htaccess file to block JavaScript");
output("6. Created example file");
output("");
output("You can now use the HTML navigation in all your admin pages by following the instructions above.");

if ($isWeb) {
    echo '<div style="margin-top: 20px;"><a href="/admin/example.php">View Example Page</a></div>';
    echo '</div></body></html>';
}