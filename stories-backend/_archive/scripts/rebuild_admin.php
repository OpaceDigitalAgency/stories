<?php
/**
 * Rebuild Admin Interface
 * 
 * This script rebuilds the entire admin interface under admin-new,
 * keeping all UX/styling but removing JavaScript for simple functionality.
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
    <title>Rebuild Admin Interface</title>
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
        <h1>Rebuild Admin Interface</h1>
', true);
}

output("Rebuild Admin Interface");
output("=====================");
output("");

// Define the source and destination directories
$sourceDir = __DIR__ . '/admin';
$destDir = __DIR__ . '/admin-new';

// Check if the source directory exists
if (!is_dir($sourceDir)) {
    // Try to find the admin directory in the server path
    $serverPath = '/home/stories/api.storiesfromtheweb.org/admin';
    if (is_dir($serverPath)) {
        $sourceDir = $serverPath;
        $destDir = dirname($serverPath) . '/admin-new';
    } else {
        if ($isWeb) output("<div class='error'>Admin directory not found</div>", true);
        else output("Error: Admin directory not found");
        exit;
    }
}

output("Source directory: $sourceDir");
output("Destination directory: $destDir");

// Create the destination directory if it doesn't exist
if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0755, true)) {
        if ($isWeb) output("<div class='error'>Failed to create destination directory</div>", true);
        else output("Error: Failed to create destination directory");
        exit;
    }
    output("Created destination directory: $destDir");
}

// Function to copy a directory recursively
function copyDir($src, $dst, $skipJs = false) {
    $dir = opendir($src);
    @mkdir($dst);
    
    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $srcFile = $src . '/' . $file;
        $dstFile = $dst . '/' . $file;
        
        if (is_dir($srcFile)) {
            copyDir($srcFile, $dstFile, $skipJs);
        } else {
            // Skip JavaScript files if requested
            if ($skipJs && pathinfo($file, PATHINFO_EXTENSION) === 'js') {
                continue;
            }
            
            copy($srcFile, $dstFile);
        }
    }
    
    closedir($dir);
}

// Copy assets directory (CSS, images, etc.) but skip JavaScript files
$assetsDir = $sourceDir . '/assets';
$newAssetsDir = $destDir . '/assets';

if (is_dir($assetsDir)) {
    output("Copying assets directory (skipping JavaScript files)...");
    copyDir($assetsDir, $newAssetsDir, true);
    output("Assets directory copied");
    
    // Create empty JavaScript files to prevent 404 errors
    $jsDir = $newAssetsDir . '/js';
    if (!is_dir($jsDir)) {
        mkdir($jsDir, 0755, true);
    }
    
    // Create an empty admin.js file
    $emptyJs = "// Empty JavaScript file\n// All functionality is handled by PHP\n";
    file_put_contents($jsDir . '/admin.js', $emptyJs);
    output("Created empty admin.js file");
}

// Copy views directory
$viewsDir = $sourceDir . '/views';
$newViewsDir = $destDir . '/views';

if (is_dir($viewsDir)) {
    output("Copying views directory...");
    copyDir($viewsDir, $newViewsDir);
    output("Views directory copied");
    
    // Modify the header.php file to remove JavaScript
    $headerFile = $newViewsDir . '/header.php';
    if (file_exists($headerFile)) {
        output("Modifying header.php to remove JavaScript...");
        $headerContent = file_get_contents($headerFile);
        
        // Remove script tags
        $headerContent = preg_replace('/<script.*?<\/script>/s', '', $headerContent);
        
        // Add a simple CSS for the loading overlay
        $headerContent = str_replace('</head>', '
<style>
/* Simple CSS for the loading overlay */
.loading-overlay {
    display: none !important;
}
</style>
</head>', $headerContent);
        
        file_put_contents($headerFile, $headerContent);
        output("Header file modified");
    }
    
    // Modify the footer.php file to remove JavaScript
    $footerFile = $newViewsDir . '/footer.php';
    if (file_exists($footerFile)) {
        output("Modifying footer.php to remove JavaScript...");
        $footerContent = file_get_contents($footerFile);
        
        // Remove script tags
        $footerContent = preg_replace('/<script.*?<\/script>/s', '', $footerContent);
        
        file_put_contents($footerFile, $footerContent);
        output("Footer file modified");
    }
}

// Copy includes directory
$includesDir = $sourceDir . '/includes';
$newIncludesDir = $destDir . '/includes';

if (is_dir($includesDir)) {
    output("Copying includes directory...");
    copyDir($includesDir, $newIncludesDir);
    output("Includes directory copied");
}

// Define the admin pages to copy and modify
$adminPages = [
    'index.php',
    'stories.php',
    'authors.php',
    'tags.php',
    'blog-posts.php',
    'games.php',
    'directory-items.php',
    'ai-tools.php',
    'login.php',
    'logout.php'
];

// Process each admin page
foreach ($adminPages as $page) {
    $srcFile = $sourceDir . '/' . $page;
    $dstFile = $destDir . '/' . $page;
    
    if (!file_exists($srcFile)) {
        if ($isWeb) output("<div class='warning'>Page not found: $page</div>", true);
        else output("Warning: Page not found: $page");
        continue;
    }
    
    output("Processing page: $page");
    
    // Read the file content
    $content = file_get_contents($srcFile);
    
    // Modify the content
    $modifiedContent = $content;
    
    // 1. Remove the form-loading class from forms
    $modifiedContent = preg_replace('/class="([^"]*)\bform-loading\b([^"]*)"/', 'class="$1$2"', $modifiedContent);
    
    // 2. Remove the btn-loading class from buttons
    $modifiedContent = preg_replace('/class="([^"]*)\bbtn-loading\b([^"]*)"/', 'class="$1btn$2"', $modifiedContent);
    
    // 3. Remove the spinner elements
    $modifiedContent = preg_replace('/<span class="spinner-border[^>]*>.*?<\/span>/s', '', $modifiedContent);
    
    // 4. Remove the loading overlay
    $modifiedContent = preg_replace('/<div class="loading-overlay[^>]*>.*?<\/div>/s', '', $modifiedContent);
    
    // 5. Remove any JavaScript event handlers from the form
    $modifiedContent = preg_replace('/onsubmit="[^"]*"/', '', $modifiedContent);
    
    // 6. Add a direct action to the form that points to a PHP script
    $contentType = str_replace('.php', '', $page);
    $modifiedContent = preg_replace('/<form([^>]*)action="[^"]*"([^>]*)>/', '<form$1action="/admin-new/save.php?type=' . $contentType . '"$2>', $modifiedContent);
    
    // 7. Update all links to point to the new admin directory
    $modifiedContent = str_replace('href="/admin/', 'href="/admin-new/', $modifiedContent);
    $modifiedContent = str_replace('action="/admin/', 'action="/admin-new/', $modifiedContent);
    $modifiedContent = str_replace('src="/admin/', 'src="/admin-new/', $modifiedContent);
    
    // Write the modified content to the destination file
    if (file_put_contents($dstFile, $modifiedContent)) {
        if ($isWeb) output("<div class='success'>Successfully created modified $page</div>", true);
        else output("Successfully created modified $page");
    } else {
        if ($isWeb) output("<div class='error'>Failed to create modified $page</div>", true);
        else output("Error: Failed to create modified $page");
    }
}

// Create a save.php script to handle form submissions
$savePhpContent = '<?php
/**
 * Save Script
 * 
 * This script handles form submissions from the admin interface.
 * It saves the data to the database using the API.
 */

// Enable error reporting
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);
error_reporting(E_ALL);

// Get the content type from the URL
$contentType = isset($_GET[\'type\']) ? $_GET[\'type\'] : \'\';

// Get the action (create, edit, delete)
$action = isset($_GET[\'action\']) ? $_GET[\'action\'] : \'\';

// Get the ID if editing or deleting
$id = isset($_GET[\'id\']) ? (int)$_GET[\'id\'] : 0;

// Validate the content type
$validTypes = [\'stories\', \'authors\', \'tags\', \'blog-posts\', \'games\', \'directory-items\', \'ai-tools\'];
if (!in_array($contentType, $validTypes)) {
    die("Invalid content type");
}

// Prepare the API endpoint
$endpoint = \'/api/v1/\' . $contentType;
if ($id > 0) {
    $endpoint .= \'/\' . $id;
}

// Determine the HTTP method
$method = \'POST\';
if ($action === \'edit\' && $id > 0) {
    $method = \'PUT\';
} else if ($action === \'delete\' && $id > 0) {
    $method = \'DELETE\';
}

// Prepare the data
$data = $_POST;

// Convert checkbox values
foreach ($data as $key => $value) {
    if ($value === \'on\' || $value === \'1\') {
        $data[$key] = true;
    } else if ($value === \'off\' || $value === \'0\') {
        $data[$key] = false;
    }
}

// Convert the data to JSON
$jsonData = json_encode($data);

// Make the API request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, \'https://api.storiesfromtheweb.org\' . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

if ($method !== \'GET\' && $method !== \'DELETE\') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        \'Content-Type: application/json\',
        \'Content-Length: \' . strlen($jsonData)
    ]);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if the request was successful
if ($httpCode >= 200 && $httpCode < 300) {
    // Redirect back to the list page
    header(\'Location: /admin-new/\' . $contentType . \'.php\');
    exit;
} else {
    // Display the error
    echo "<h1>Error</h1>";
    echo "<p>Failed to save data. HTTP status code: $httpCode</p>";
    echo "<pre>$response</pre>";
    echo "<p><a href=\'/admin-new/\' . $contentType . \'.php\'>Back to list</a></p>";
}
';

$savePhpFile = $destDir . '/save.php';
if (file_put_contents($savePhpFile, $savePhpContent)) {
    if ($isWeb) output("<div class='success'>Created save.php script: $savePhpFile</div>", true);
    else output("Created save.php script: $savePhpFile");
} else {
    if ($isWeb) output("<div class='error'>Failed to create save.php script</div>", true);
    else output("Error: Failed to create save.php script");
}

// Create a .htaccess file to ensure PHP files are executed
$htaccessContent = '# Ensure PHP files are executed
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>

# Prevent access to .htaccess
<Files .htaccess>
    Order allow,deny
    Deny from all
</Files>

# Prevent directory listing
Options -Indexes
';

$htaccessFile = $destDir . '/.htaccess';
if (file_put_contents($htaccessFile, $htaccessContent)) {
    if ($isWeb) output("<div class='success'>Created .htaccess file: $htaccessFile</div>", true);
    else output("Created .htaccess file: $htaccessFile");
} else {
    if ($isWeb) output("<div class='error'>Failed to create .htaccess file</div>", true);
    else output("Error: Failed to create .htaccess file");
}

output("");
output("Next Steps:");
output("1. Access the new admin interface at: https://api.storiesfromtheweb.org/admin-new/");
output("2. Test the admin interface by creating or editing content");
output("3. The forms should now use standard HTML form submission without JavaScript interference");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}