<?php
/**
 * UPDATE INDEX
 * 
 * This script updates the index.php file to use the HTML navigation.
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
    <title>UPDATE INDEX</title>
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
        <h1>UPDATE INDEX</h1>';
}

output("UPDATE INDEX");
output("============");
output("");

// Step 1: Find the index.php file
output("Step 1: Finding index.php file...");
$indexPath = '/home/stories/api.storiesfromtheweb.org/admin/index.php';
if (!file_exists($indexPath)) {
    // Try to find the index.php file
    $possiblePaths = [
        __DIR__ . '/admin/index.php',
        '/home/stories/api.storiesfromtheweb.org/admin/index.php',
        '/var/www/html/admin/index.php'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $indexPath = $path;
            output("Found index.php file: $indexPath");
            break;
        }
    }
    
    if (!file_exists($indexPath)) {
        if ($isWeb) output("<div class='error'>index.php file not found</div>", true);
        else output("Error: index.php file not found");
        exit;
    }
}

// Step 2: Backup the index.php file
output("Step 2: Backing up index.php file...");
$backupFile = $indexPath . '.bak.' . date('YmdHis');
if (!copy($indexPath, $backupFile)) {
    if ($isWeb) output("<div class='warning'>Failed to create backup of index.php file</div>", true);
    else output("Warning: Failed to create backup of index.php file");
} else {
    output("Backup created: $backupFile");
}

// Step 3: Create a new index.php file
output("Step 3: Creating new index.php file...");
$newIndexContent = '<?php
$pageTitle = "Dashboard";
include_once __DIR__ . "/includes/html_header.php";
?>

<h1>Dashboard</h1>

<div class="dashboard-welcome">
    <h2>Welcome to Stories Admin</h2>
    <p>Manage your content, authors, and more from this dashboard.</p>
</div>

<div class="dashboard-section">
    <h2>Content</h2>
    
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <h3>Stories</h3>
            <p><a href="/admin/stories.php">Manage Stories</a></p>
        </div>
        
        <div class="dashboard-card">
            <h3>Blog Posts</h3>
            <p><a href="/admin/blog-posts.php">Manage Blog Posts</a></p>
        </div>
        
        <div class="dashboard-card">
            <h3>Games</h3>
            <p><a href="/admin/games.php">Manage Games</a></p>
        </div>
        
        <div class="dashboard-card">
            <h3>Directory Items</h3>
            <p><a href="/admin/directory-items.php">Manage Directory</a></p>
        </div>
        
        <div class="dashboard-card">
            <h3>AI Tools</h3>
            <p><a href="/admin/ai-tools.php">Manage AI Tools</a></p>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h2>Management</h2>
    
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <h3>Authors</h3>
            <p><a href="/admin/authors.php">Manage Authors</a></p>
        </div>
        
        <div class="dashboard-card">
            <h3>Tags</h3>
            <p><a href="/admin/tags.php">Manage Tags</a></p>
        </div>
        
        <div class="dashboard-card">
            <h3>Media</h3>
            <p><a href="/admin/media.php">Manage Media</a></p>
        </div>
    </div>
</div>

<style>
.dashboard-welcome {
    background-color: #f8f9fa;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.dashboard-section {
    margin-bottom: 30px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.dashboard-card h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #4a6cf7;
}
</style>

<?php
include_once __DIR__ . "/includes/html_footer.php";
?>
';

if (file_put_contents($indexPath, $newIndexContent)) {
    if ($isWeb) output("<div class='success'>Created new index.php file</div>", true);
    else output("Created new index.php file");
} else {
    if ($isWeb) output("<div class='error'>Failed to create new index.php file</div>", true);
    else output("Error: Failed to create new index.php file");
}

// Step 4: Update all admin pages to use the HTML navigation
output("Step 4: Updating all admin pages to use the HTML navigation...");
$adminDir = dirname($indexPath);
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
    if (file_exists($pagePath)) {
        // Backup the page
        $backupFile = $pagePath . '.bak.' . date('YmdHis');
        if (!copy($pagePath, $backupFile)) {
            if ($isWeb) output("<div class='warning'>Failed to create backup of $page</div>", true);
            else output("Warning: Failed to create backup of $page");
        } else {
            output("Backup created: $backupFile");
        }
        
        // Read the page content
        $pageContent = file_get_contents($pagePath);
        
        // Check if the page already includes the HTML header
        if (strpos($pageContent, 'include_once __DIR__ . "/includes/html_header.php"') === false) {
            // Create a new page content
            $newPageContent = '<?php
$pageTitle = "' . ucfirst(str_replace(['-', '.php'], [' ', ''], $page)) . '";
include_once __DIR__ . "/includes/html_header.php";

// Original page content starts here
?>' . $pageContent;
            
            // Add the footer if it's not already included
            if (strpos($pageContent, 'include_once __DIR__ . "/includes/html_footer.php"') === false) {
                $newPageContent .= '
<?php
include_once __DIR__ . "/includes/html_footer.php";
?>';
            }
            
            // Write the new page content
            if (file_put_contents($pagePath, $newPageContent)) {
                if ($isWeb) output("<div class='success'>Updated $page to use HTML navigation</div>", true);
                else output("Updated $page to use HTML navigation");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update $page</div>", true);
                else output("Error: Failed to update $page");
            }
        } else {
            if ($isWeb) output("<div class='warning'>$page already uses HTML navigation</div>", true);
            else output("Warning: $page already uses HTML navigation");
        }
    } else {
        if ($isWeb) output("<div class='warning'>$page not found</div>", true);
        else output("Warning: $page not found");
    }
}

// Step 5: Create a CSS file for the dashboard
output("Step 5: Creating CSS file for the dashboard...");
$dashboardCssContent = '/* Dashboard CSS */

.dashboard-welcome {
    background-color: #f8f9fa;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.dashboard-section {
    margin-bottom: 30px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background-color: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.dashboard-card h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #4a6cf7;
}
';

$dashboardCssPath = $adminDir . '/assets/css/dashboard.css';
// Create the directory if it doesn't exist
if (!is_dir(dirname($dashboardCssPath))) {
    mkdir(dirname($dashboardCssPath), 0755, true);
}

if (file_put_contents($dashboardCssPath, $dashboardCssContent)) {
    if ($isWeb) output("<div class='success'>Created dashboard CSS file: $dashboardCssPath</div>", true);
    else output("Created dashboard CSS file: $dashboardCssPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create dashboard CSS file</div>", true);
    else output("Error: Failed to create dashboard CSS file");
}

output("");
output("All updates have been applied!");
output("1. Created new index.php file with HTML navigation");
output("2. Updated all admin pages to use HTML navigation");
output("3. Created dashboard CSS file");
output("");
output("You can now view the updated admin interface at:");
output("/admin/index.php");

if ($isWeb) {
    echo '<div style="margin-top: 20px;"><a href="/admin/index.php">View Updated Admin Interface</a></div>';
    echo '</div></body></html>';
}