<?php
/**
 * Auto Apply Admin Improvements
 * 
 * This script automatically applies all the admin improvements:
 * 1. Adds the CSS to the header file
 * 2. Adds the dropdown fix functions to the forms
 * 3. Adds the delete fix function to the delete confirmation pages
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
    <title>Auto Apply Admin Improvements</title>
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
        <h1>Auto Apply Admin Improvements</h1>
', true);
}

output("Auto Apply Admin Improvements");
output("===========================");
output("");

// First, run the simple_admin_improvement.php script to create the necessary files
output("Step 1: Creating necessary files...");
include_once __DIR__ . '/simple_admin_improvement.php';
output("Step 1 completed.");
output("");

// Step 2: Add the CSS to the header file
output("Step 2: Adding CSS to header file...");
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
    
    // Check if the CSS is already added
    if (strpos($headerContent, 'improved-admin.css') === false) {
        // Find the position to add the CSS
        $cssPosition = strpos($headerContent, '</head>');
        if ($cssPosition !== false) {
            // Add the CSS before the </head> tag
            $newHeaderContent = substr($headerContent, 0, $cssPosition) . 
                '    <!-- Improved Admin CSS -->
    <link href="/admin/assets/css/improved-admin.css" rel="stylesheet">
    
' . substr($headerContent, $cssPosition);
            
            // Write the new header content
            if (file_put_contents($headerFile, $newHeaderContent)) {
                if ($isWeb) output("<div class='success'>Added CSS to header file</div>", true);
                else output("Added CSS to header file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
                else output("Error: Failed to update header file");
            }
        } else {
            if ($isWeb) output("<div class='warning'>Could not find </head> tag in header file</div>", true);
            else output("Warning: Could not find </head> tag in header file");
        }
    } else {
        if ($isWeb) output("<div class='warning'>CSS already added to header file</div>", true);
        else output("Warning: CSS already added to header file");
    }
}

// Step 3: Add the dropdown fix to the forms
output("Step 3: Adding dropdown fix to forms...");

// Find all PHP files in the admin directory
$adminDir = __DIR__ . '/admin';
$phpFiles = glob($adminDir . '/*.php');

// Counter for modified files
$modifiedFiles = 0;

foreach ($phpFiles as $phpFile) {
    // Skip index.php, login.php, and logout.php
    if (basename($phpFile) == 'index.php' || basename($phpFile) == 'login.php' || basename($phpFile) == 'logout.php') {
        continue;
    }
    
    // Read the file content
    $content = file_get_contents($phpFile);
    
    // Check if the file contains a form with author_id or tags
    if (strpos($content, 'author_id') !== false || strpos($content, 'tags') !== false) {
        // Backup the file
        $backupFile = $phpFile . '.bak.' . date('YmdHis');
        if (!copy($phpFile, $backupFile)) {
            if ($isWeb) output("<div class='warning'>Failed to create backup of " . basename($phpFile) . "</div>", true);
            else output("Warning: Failed to create backup of " . basename($phpFile));
        } else {
            output("Backup created: $backupFile");
        }
        
        // Replace author_id select with renderAuthorDropdown
        if (strpos($content, 'author_id') !== false) {
            $pattern = '/<select[^>]*name=["\']author_id["\'][^>]*>.*?<\/select>/s';
            $replacement = '<?php renderAuthorDropdown(isset($item[\'author_id\']) ? $item[\'author_id\'] : null); ?>';
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Replace tags select with renderTagDropdown
        if (strpos($content, 'tags') !== false) {
            $pattern = '/<select[^>]*name=["\']tags\[\]["\'][^>]*>.*?<\/select>/s';
            $replacement = '<?php renderTagDropdown(isset($item[\'tags\']) ? $item[\'tags\'] : []); ?>';
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Write the modified content
        if (file_put_contents($phpFile, $content)) {
            if ($isWeb) output("<div class='success'>Added dropdown fix to " . basename($phpFile) . "</div>", true);
            else output("Added dropdown fix to " . basename($phpFile));
            $modifiedFiles++;
        } else {
            if ($isWeb) output("<div class='error'>Failed to update " . basename($phpFile) . "</div>", true);
            else output("Error: Failed to update " . basename($phpFile));
        }
    }
}

if ($modifiedFiles > 0) {
    if ($isWeb) output("<div class='success'>Added dropdown fix to $modifiedFiles files</div>", true);
    else output("Added dropdown fix to $modifiedFiles files");
} else {
    if ($isWeb) output("<div class='warning'>No files found with author_id or tags</div>", true);
    else output("Warning: No files found with author_id or tags");
}

// Step 4: Add the delete fix to the delete confirmation pages
output("Step 4: Adding delete fix to delete confirmation pages...");

// Reset counter for modified files
$modifiedFiles = 0;

foreach ($phpFiles as $phpFile) {
    // Skip index.php, login.php, and logout.php
    if (basename($phpFile) == 'index.php' || basename($phpFile) == 'login.php' || basename($phpFile) == 'logout.php') {
        continue;
    }
    
    // Read the file content
    $content = file_get_contents($phpFile);
    
    // Check if the file contains a delete confirmation
    if (strpos($content, 'delete') !== false && strpos($content, 'confirm') !== false) {
        // Backup the file
        $backupFile = $phpFile . '.bak.' . date('YmdHis');
        if (!copy($phpFile, $backupFile)) {
            if ($isWeb) output("<div class='warning'>Failed to create backup of " . basename($phpFile) . "</div>", true);
            else output("Warning: Failed to create backup of " . basename($phpFile));
        } else {
            output("Backup created: $backupFile");
        }
        
        // Determine the type based on the filename
        $type = str_replace('.php', '', basename($phpFile));
        if (substr($type, -1) == 's') {
            $type = substr($type, 0, -1);
        }
        
        // Replace delete confirmation with renderDeleteConfirmation
        $pattern = '/<div[^>]*class=["\']alert[^>]*>.*?<\/div>/s';
        $replacement = '<?php renderDeleteConfirmation(\'' . $type . '\', $id, isset($item[\'name\']) ? $item[\'name\'] : (isset($item[\'title\']) ? $item[\'title\'] : $id)); ?>';
        $content = preg_replace($pattern, $replacement, $content);
        
        // Write the modified content
        if (file_put_contents($phpFile, $content)) {
            if ($isWeb) output("<div class='success'>Added delete fix to " . basename($phpFile) . "</div>", true);
            else output("Added delete fix to " . basename($phpFile));
            $modifiedFiles++;
        } else {
            if ($isWeb) output("<div class='error'>Failed to update " . basename($phpFile) . "</div>", true);
            else output("Error: Failed to update " . basename($phpFile));
        }
    }
}

if ($modifiedFiles > 0) {
    if ($isWeb) output("<div class='success'>Added delete fix to $modifiedFiles files</div>", true);
    else output("Added delete fix to $modifiedFiles files");
} else {
    if ($isWeb) output("<div class='warning'>No files found with delete confirmation</div>", true);
    else output("Warning: No files found with delete confirmation");
}

output("");
output("All improvements have been automatically applied!");
output("1. CSS has been added to the header file");
output("2. Dropdown fix has been added to the forms");
output("3. Delete fix has been added to the delete confirmation pages");
output("");
output("You can now access the admin interface at: https://api.storiesfromtheweb.org/admin/");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}