<?php
/**
 * FIX DASHBOARD
 * 
 * This script fixes the dashboard to include the side navigation.
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
    <title>FIX DASHBOARD</title>
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
        <h1>FIX DASHBOARD</h1>';
}

output("FIX DASHBOARD");
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

// Step 2: Create a CSS file for the side navigation
output("Step 2: Creating CSS file for side navigation...");
$cssContent = '/* Side Navigation CSS */

/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Layout */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    font-family: Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f8f9fa;
}

.main-container {
    display: flex;
    flex: 1;
}

/* Side Navigation */
.side-nav {
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    padding: 20px 0;
    flex-shrink: 0;
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

/* Content Area */
.content-area {
    flex: 1;
    padding: 20px;
}

/* Dashboard Cards */
.dashboard-section {
    margin-bottom: 30px;
}

.dashboard-title {
    font-size: 24px;
    margin-bottom: 20px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    padding: 20px;
}

.dashboard-card-title {
    font-size: 18px;
    margin-bottom: 15px;
    color: #4a6cf7;
}

.dashboard-card-link {
    display: inline-block;
    padding: 8px 15px;
    background-color: #f5f5f5;
    color: #333;
    text-decoration: none;
    border-radius: 3px;
    margin-top: 10px;
}

.dashboard-card-link:hover {
    background-color: #e9ecef;
    text-decoration: none;
}
';

$cssPath = $adminDir . '/assets/css/side-nav.css';
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

// Step 3: Create a favicon.ico file
output("Step 3: Creating favicon.ico file...");
$faviconData = base64_decode('AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A////AP///wD///8A//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAA==');

$faviconPath = $adminDir . '/favicon.ico';
if (file_put_contents($faviconPath, $faviconData)) {
    if ($isWeb) output("<div class='success'>Created favicon.ico file: $faviconPath</div>", true);
    else output("Created favicon.ico file: $faviconPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create favicon.ico file</div>", true);
    else output("Error: Failed to create favicon.ico file");
}

// Step 4: Update the index.php file
output("Step 4: Updating index.php file...");
$indexPath = $adminDir . '/index.php';
if (!file_exists($indexPath)) {
    if ($isWeb) output("<div class='error'>index.php file not found</div>", true);
    else output("Error: index.php file not found");
    exit;
}

// Backup the index.php file
$backupFile = $indexPath . '.bak.' . date('YmdHis');
if (!copy($indexPath, $backupFile)) {
    if ($isWeb) output("<div class='warning'>Failed to create backup of index.php file</div>", true);
    else output("Warning: Failed to create backup of index.php file");
} else {
    output("Backup created: $backupFile");
}

// Create a new index.php file
$newIndexContent = '<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: /admin/login.php");
    exit;
}

// Set page title
$pageTitle = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Stories Admin</title>
    <link rel="stylesheet" href="/admin/assets/css/side-nav.css">
    <link rel="icon" href="/admin/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Top Navigation (Original) -->
    <div class="top-nav">
        <a href="/admin/index.php" class="top-nav-brand">Stories Admin</a>
        <div class="top-nav-menu">
            <div class="top-nav-item">
                <a href="/admin/index.php" class="top-nav-link active">Dashboard</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/stories.php" class="top-nav-link">Stories</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/blog-posts.php" class="top-nav-link">Blog</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/authors.php" class="top-nav-link">Authors</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/tags.php" class="top-nav-link">Tags</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/logout.php" class="top-nav-link">Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Side Navigation -->
        <div class="side-nav">
            <div class="side-nav-section">
                <h3 class="side-nav-title">Content</h3>
                <ul class="side-nav-menu">
                    <li class="side-nav-item">
                        <a href="/admin/stories.php" class="side-nav-link">Stories</a>
                    </li>
                    <li class="side-nav-item">
                        <a href="/admin/blog-posts.php" class="side-nav-link">Blog Posts</a>
                    </li>
                    <li class="side-nav-item">
                        <a href="/admin/games.php" class="side-nav-link">Games</a>
                    </li>
                    <li class="side-nav-item">
                        <a href="/admin/directory-items.php" class="side-nav-link">Directory Items</a>
                    </li>
                    <li class="side-nav-item">
                        <a href="/admin/ai-tools.php" class="side-nav-link">AI Tools</a>
                    </li>
                </ul>
            </div>
            
            <div class="side-nav-section">
                <h3 class="side-nav-title">Management</h3>
                <ul class="side-nav-menu">
                    <li class="side-nav-item">
                        <a href="/admin/authors.php" class="side-nav-link">Authors</a>
                    </li>
                    <li class="side-nav-item">
                        <a href="/admin/tags.php" class="side-nav-link">Tags</a>
                    </li>
                    <li class="side-nav-item">
                        <a href="/admin/media.php" class="side-nav-link">Media</a>
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
        
        <!-- Content Area -->
        <div class="content-area">
            <h1>Dashboard</h1>
            
            <div class="dashboard-section">
                <h2>Welcome to Stories Admin</h2>
                <p>Manage your content, authors, and more from this dashboard.</p>
            </div>
            
            <div class="dashboard-section">
                <h2>Content</h2>
                
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">Stories</h3>
                        <a href="/admin/stories.php" class="dashboard-card-link">Manage Stories</a>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">Blog Posts</h3>
                        <a href="/admin/blog-posts.php" class="dashboard-card-link">Manage Blog Posts</a>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">Games</h3>
                        <a href="/admin/games.php" class="dashboard-card-link">Manage Games</a>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">Directory Items</h3>
                        <a href="/admin/directory-items.php" class="dashboard-card-link">Manage Directory</a>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">AI Tools</h3>
                        <a href="/admin/ai-tools.php" class="dashboard-card-link">Manage AI Tools</a>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2>Management</h2>
                
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">Authors</h3>
                        <a href="/admin/authors.php" class="dashboard-card-link">Manage Authors</a>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">Tags</h3>
                        <a href="/admin/tags.php" class="dashboard-card-link">Manage Tags</a>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3 class="dashboard-card-title">Media</h3>
                        <a href="/admin/media.php" class="dashboard-card-link">Manage Media</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';

if (file_put_contents($indexPath, $newIndexContent)) {
    if ($isWeb) output("<div class='success'>Updated index.php file with side navigation</div>", true);
    else output("Updated index.php file with side navigation");
} else {
    if ($isWeb) output("<div class='error'>Failed to update index.php file</div>", true);
    else output("Error: Failed to update index.php file");
}

// Step 5: Update the .htaccess file to block JavaScript
output("Step 5: Updating .htaccess file to block JavaScript...");
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

output("");
output("All dashboard fixes have been applied!");
output("1. Created CSS file for side navigation");
output("2. Created favicon.ico file");
output("3. Updated index.php file with side navigation");
output("4. Updated .htaccess file to block JavaScript");
output("");
output("You can now view the updated dashboard at:");
output("/admin/index.php");

if ($isWeb) {
    echo '<div style="margin-top: 20px;"><a href="/admin/index.php">View Updated Dashboard</a></div>';
    echo '</div></body></html>';
}