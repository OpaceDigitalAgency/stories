<?php
/**
 * Improve Admin Interface (Part 2)
 * 
 * This script creates the header and footer templates for the improved admin interface.
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
    <title>Improve Admin Interface (Part 2)</title>
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
        <h1>Improve Admin Interface (Part 2)</h1>
', true);
}

output("Improve Admin Interface (Part 2)");
output("============================");
output("");

// Create a new header file with improved navigation
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

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}