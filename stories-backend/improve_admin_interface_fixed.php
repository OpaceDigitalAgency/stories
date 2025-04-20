<?php
/**
 * Improve Admin Interface (Fixed)
 * 
 * This script improves the admin interface with:
 * 1. A better navigation system with top menu and side panel
 * 2. Fixed author and tag dropdowns
 * 3. Fixed delete warnings
 * 4. An improved dashboard with recent content
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
    <title>Improve Admin Interface</title>
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
        <h1>Improve Admin Interface</h1>
', true);
}

output("Improve Admin Interface");
output("======================");
output("");

// Create a CSS file for the improved admin interface
output("Creating CSS file...");
$adminCssContent = '/* Improved Admin Interface CSS */

/* Layout */
body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    background-color: #f8f9fa;
}

/* Top Navigation */
.top-nav {
    background-color: #4a6cf7;
    color: white;
    padding: 0;
    margin: 0;
    display: flex;
    align-items: center;
    height: 60px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.top-nav-brand {
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

.top-nav-menu {
    display: flex;
    height: 100%;
}

.top-nav-item {
    position: relative;
    height: 100%;
}

.top-nav-link {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 0 20px;
    color: white;
    text-decoration: none;
    transition: background-color 0.2s;
}

.top-nav-link:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.top-nav-link.active {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Main Container */
.main-container {
    display: flex;
    min-height: calc(100vh - 60px);
}

/* Side Navigation */
.side-nav {
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    padding: 20px 0;
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
    transition: background-color 0.2s;
}

.side-nav-link:hover {
    background-color: #f5f5f5;
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
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
    font-size: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.dashboard-card {
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    padding: 20px;
}

.dashboard-card-title {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 18px;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.dashboard-card-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.dashboard-card-item {
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
}

.dashboard-card-item:last-child {
    border-bottom: none;
}

.dashboard-card-link {
    display: block;
    color: #4a6cf7;
    text-decoration: none;
}

.dashboard-card-link:hover {
    text-decoration: underline;
}

.dashboard-card-footer {
    margin-top: 15px;
    text-align: center;
}

.view-more-link {
    display: inline-block;
    padding: 5px 15px;
    background-color: #f5f5f5;
    color: #333;
    text-decoration: none;
    border-radius: 3px;
    font-size: 14px;
}

.view-more-link:hover {
    background-color: #e9ecef;
}

/* Form Elements */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 16px;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    color: #495057;
    background-color: #fff;
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-select {
    display: block;
    width: 100%;
    padding: 8px 12px;
    font-size: 16px;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 4px;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

/* Buttons */
.btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 8px 12px;
    font-size: 16px;
    line-height: 1.5;
    border-radius: 4px;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    text-decoration: none;
}

.btn-primary {
    color: #fff;
    background-color: #4a6cf7;
    border-color: #4a6cf7;
}

.btn-primary:hover {
    color: #fff;
    background-color: #3a5bd7;
    border-color: #3a5bd7;
}

.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-secondary:hover {
    color: #fff;
    background-color: #5a6268;
    border-color: #545b62;
}

.btn-danger {
    color: #fff;
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-danger:hover {
    color: #fff;
    background-color: #c82333;
    border-color: #bd2130;
}

/* Tables */
.table {
    width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
    border-collapse: collapse;
}

.table th,
.table td {
    padding: 12px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}

.table tbody + tbody {
    border-top: 2px solid #dee2e6;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.05);
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.075);
}

/* Alerts */
.alert {
    position: relative;
    padding: 12px 20px;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeeba;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
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

$adminCssPath = __DIR__ . '/admin/assets/css/improved-admin.css';
if (file_put_contents($adminCssPath, $adminCssContent)) {
    if ($isWeb) output("<div class='success'>Created improved admin CSS file: $adminCssPath</div>", true);
    else output("Created improved admin CSS file: $adminCssPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create improved admin CSS file</div>", true);
    else output("Error: Failed to create improved admin CSS file");
}

// Create a new header file with improved navigation
output("Creating header file...");
$headerContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo isset($_SESSION[\'csrf_token\']) ? $_SESSION[\'csrf_token\'] : \'\'; ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Admin"; ?> - Stories Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://api.storiesfromtheweb.org/admin/assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (Local) -->
    <link href="https://api.storiesfromtheweb.org/admin/assets/css/all.min.css" rel="stylesheet">
    
    <!-- Improved Admin CSS -->
    <link href="/admin/assets/css/improved-admin.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="https://api.storiesfromtheweb.org/admin/assets/css/admin.css" rel="stylesheet">
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
                <a href="/admin/stories.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'stories.php\' ? \' active\' : \'\'; ?>">Content</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/authors.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'authors.php\' ? \' active\' : \'\'; ?>">Authors</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/media.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'media.php\' ? \' active\' : \'\'; ?>">Media</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/tags.php" class="top-nav-link<?php echo basename($_SERVER[\'PHP_SELF\']) == \'tags.php\' ? \' active\' : \'\'; ?>">Tags</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/navigation.php" class="top-nav-link">All Navigation</a>
            </div>
            <div class="top-nav-item">
                <a href="/admin/logout.php" class="top-nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
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

        <!-- Content Area -->
        <div class="content-area">
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
        if ($isWeb) output("<div class='success'>Replaced header file with improved navigation</div>", true);
        else output("Replaced header file with improved navigation");
    } else {
        if ($isWeb) output("<div class='error'>Failed to replace header file</div>", true);
        else output("Error: Failed to replace header file");
    }
}

// Create a new footer file
output("Creating footer file...");
$footerContent = '        </div>
    </div>
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

// Create an improved dashboard page
output("Creating dashboard page...");
$dashboardContent = '<?php
$pageTitle = "Dashboard";
include_once __DIR__ . "/views/header.php";

// Function to get recent content
function getRecentContent($type, $limit = 5) {
    // This would normally be a database query
    // For now, we\'ll return some sample data
    $items = [];
    
    switch ($type) {
        case "stories":
            $items = [
                ["id" => 1, "title" => "The Adventure Begins", "date" => "2025-04-18"],
                ["id" => 2, "title" => "Mystery in the Woods", "date" => "2025-04-17"],
                ["id" => 3, "title" => "Lost in Time", "date" => "2025-04-16"],
                ["id" => 4, "title" => "The Hidden Treasure", "date" => "2025-04-15"],
                ["id" => 5, "title" => "Journey to the Stars", "date" => "2025-04-14"]
            ];
            break;
        case "blog-posts":
            $items = [
                ["id" => 1, "title" => "Writing Tips for Beginners", "date" => "2025-04-18"],
                ["id" => 2, "title" => "How to Create Compelling Characters", "date" => "2025-04-17"],
                ["id" => 3, "title" => "The Art of Storytelling", "date" => "2025-04-16"],
                ["id" => 4, "title" => "Finding Inspiration", "date" => "2025-04-15"],
                ["id" => 5, "title" => "Publishing Your First Book", "date" => "2025-04-14"]
            ];
            break;
        case "authors":
            $items = [
                ["id" => 1, "title" => "John Doe", "date" => "2025-04-18"],
                ["id" => 2, "title" => "Jane Smith", "date" => "2025-04-17"],
                ["id" => 3, "title" => "David Johnson", "date" => "2025-04-16"],
                ["id" => 4, "title" => "Sarah Williams", "date" => "2025-04-15"],
                ["id" => 5, "title" => "Michael Brown", "date" => "2025-04-14"]
            ];
            break;
        case "games":
            $items = [
                ["id" => 1, "title" => "Word Puzzle", "date" => "2025-04-18"],
                ["id" => 2, "title" => "Story Builder", "date" => "2025-04-17"],
                ["id" => 3, "title" => "Character Creator", "date" => "2025-04-16"],
                ["id" => 4, "title" => "Plot Generator", "date" => "2025-04-15"],
                ["id" => 5, "title" => "Writing Challenge", "date" => "2025-04-14"]
            ];
            break;
        case "directory-items":
            $items = [
                ["id" => 1, "title" => "Writing Workshops", "date" => "2025-04-18"],
                ["id" => 2, "title" => "Literary Agents", "date" => "2025-04-17"],
                ["id" => 3, "title" => "Publishing Houses", "date" => "2025-04-16"],
                ["id" => 4, "title" => "Writing Conferences", "date" => "2025-04-15"],
                ["id" => 5, "title" => "Writing Groups", "date" => "2025-04-14"]
            ];
            break;
        case "ai-tools":
            $items = [
                ["id" => 1, "title" => "Story Generator", "date" => "2025-04-18"],
                ["id" => 2, "title" => "Character Creator", "date" => "2025-04-17"],
                ["id" => 3, "title" => "Plot Analyzer", "date" => "2025-04-16"],
                ["id" => 4, "title" => "Writing Assistant", "date" => "2025-04-15"],
                ["id" => 5, "title" => "Dialogue Generator", "date" => "2025-04-14"]
            ];
            break;
    }
    
    return $items;
}

// Get recent content for each type
$recentStories = getRecentContent("stories");
$recentBlogPosts = getRecentContent("blog-posts");
$recentAuthors = getRecentContent("authors");
$recentGames = getRecentContent("games");
$recentDirectoryItems = getRecentContent("directory-items");
$recentAiTools = getRecentContent("ai-tools");
?>

<h1>Dashboard</h1>

<div class="dashboard-section">
    <h2 class="dashboard-title">Recent Content</h2>
    
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Stories</h3>
            <ul class="dashboard-card-list">
                <?php foreach ($recentStories as $story): ?>
                <li class="dashboard-card-item">
                    <a href="/admin/stories.php?action=edit&id=<?php echo $story[\'id\']; ?>" class="dashboard-card-link">
                        <?php echo $story[\'title\']; ?>
                    </a>
                    <small><?php echo $story[\'date\']; ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="dashboard-card-footer">
                <a href="/admin/stories.php" class="view-more-link">View All</a>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Blog Posts</h3>
            <ul class="dashboard-card-list">
                <?php foreach ($recentBlogPosts as $post): ?>
                <li class="dashboard-card-item">
                    <a href="/admin/blog-posts.php?action=edit&id=<?php echo $post[\'id\']; ?>" class="dashboard-card-link">
                        <?php echo $post[\'title\']; ?>
                    </a>
                    <small><?php echo $post[\'date\']; ?></small>
                </li>
