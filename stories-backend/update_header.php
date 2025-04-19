<?php
/**
 * Update Header
 * 
 * This script directly modifies the header file to include the admin fix JavaScript.
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
    <title>Update Header</title>
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
        <h1>Update Header</h1>
', true);
}

output("Update Header");
output("============");
output("");

// Find the header file
$headerFile = '/home/stories/api.storiesfromtheweb.org/admin/views/header.php';
if (!file_exists($headerFile)) {
    // Try to find the header file
    $possibleHeaderFiles = [
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
        
        if ($isWeb) {
            output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
            output('</div></body></html>', true);
        }
        exit;
    }
}

output("Using header file: $headerFile");

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

// Check if the script tag already exists
if (strpos($headerContent, 'admin_fix.js') !== false) {
    if ($isWeb) output("<div class='warning'>Script tag already exists in header file</div>", true);
    else output("Warning: Script tag already exists in header file");
} else {
    // Find the closing </head> tag
    $headPos = strpos($headerContent, '</head>');
    if ($headPos !== false) {
        // Insert the script tag before the closing </head> tag
        $newHeaderContent = substr($headerContent, 0, $headPos);
        $newHeaderContent .= "\n<!-- Admin fix script -->\n<script src=\"/admin/admin_fix.js\"></script>\n";
        $newHeaderContent .= substr($headerContent, $headPos);
        
        if (file_put_contents($headerFile, $newHeaderContent)) {
            if ($isWeb) output("<div class='success'>Added script tag to header file</div>", true);
            else output("Added script tag to header file");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
            else output("Error: Failed to update header file");
        }
    } else {
        // Try to find the closing </body> tag
        $bodyPos = strpos($headerContent, '</body>');
        if ($bodyPos !== false) {
            // Insert the script tag before the closing </body> tag
            $newHeaderContent = substr($headerContent, 0, $bodyPos);
            $newHeaderContent .= "\n<!-- Admin fix script -->\n<script src=\"/admin/admin_fix.js\"></script>\n";
            $newHeaderContent .= substr($headerContent, $bodyPos);
            
            if (file_put_contents($headerFile, $newHeaderContent)) {
                if ($isWeb) output("<div class='success'>Added script tag to header file (before </body>)</div>", true);
                else output("Added script tag to header file (before </body>)");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
                else output("Error: Failed to update header file");
            }
        } else {
            // Append the script tag to the end of the file
            $newHeaderContent = $headerContent . "\n<!-- Admin fix script -->\n<script src=\"/admin/admin_fix.js\"></script>\n";
            
            if (file_put_contents($headerFile, $newHeaderContent)) {
                if ($isWeb) output("<div class='success'>Added script tag to the end of header file</div>", true);
                else output("Added script tag to the end of header file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update header file</div>", true);
                else output("Error: Failed to update header file");
            }
        }
    }
}

// Also try to update the footer file
$footerFile = str_replace('header.php', 'footer.php', $headerFile);
if (file_exists($footerFile)) {
    output("Found footer file: $footerFile");
    
    // Backup the footer file
    $backupFile = $footerFile . '.bak.' . date('YmdHis');
    if (!copy($footerFile, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of footer file</div>", true);
        else output("Warning: Failed to create backup of footer file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the footer file
    $footerContent = file_get_contents($footerFile);
    
    // Check if the script tag already exists
    if (strpos($footerContent, 'admin_fix.js') !== false) {
        if ($isWeb) output("<div class='warning'>Script tag already exists in footer file</div>", true);
        else output("Warning: Script tag already exists in footer file");
    } else {
        // Find the closing </body> tag
        $bodyPos = strpos($footerContent, '</body>');
        if ($bodyPos !== false) {
            // Insert the script tag before the closing </body> tag
            $newFooterContent = substr($footerContent, 0, $bodyPos);
            $newFooterContent .= "\n<!-- Admin fix script -->\n<script src=\"/admin/admin_fix.js\"></script>\n";
            $newFooterContent .= substr($footerContent, $bodyPos);
            
            if (file_put_contents($footerFile, $newFooterContent)) {
                if ($isWeb) output("<div class='success'>Added script tag to footer file</div>", true);
                else output("Added script tag to footer file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
                else output("Error: Failed to update footer file");
            }
        } else {
            // Append the script tag to the end of the file
            $newFooterContent = $footerContent . "\n<!-- Admin fix script -->\n<script src=\"/admin/admin_fix.js\"></script>\n";
            
            if (file_put_contents($footerFile, $newFooterContent)) {
                if ($isWeb) output("<div class='success'>Added script tag to the end of footer file</div>", true);
                else output("Added script tag to the end of footer file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
                else output("Error: Failed to update footer file");
            }
        }
    }
}

// Try to find and update all PHP files in the admin directory
output("");
output("Searching for all PHP files in the admin directory...");
$adminDir = '/home/stories/api.storiesfromtheweb.org/admin';
if (is_dir($adminDir)) {
    $phpFiles = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles[] = $file->getPathname();
        }
    }
    
    output("Found " . count($phpFiles) . " PHP files");
    
    // Look for files that might be templates or include HTML
    $updatedFiles = 0;
    foreach ($phpFiles as $file) {
        $content = file_get_contents($file);
        if (strpos($content, '<!DOCTYPE html>') !== false || 
            strpos($content, '<html>') !== false || 
            strpos($content, '<head>') !== false || 
            strpos($content, '<body>') !== false) {
            
            // Check if the script tag already exists
            if (strpos($content, 'admin_fix.js') !== false) {
                continue;
            }
            
            // Find the closing </head> tag
            $headPos = strpos($content, '</head>');
            if ($headPos !== false) {
                // Insert the script tag before the closing </head> tag
                $newContent = substr($content, 0, $headPos);
                $newContent .= "\n<!-- Admin fix script -->\n<script src=\"/admin/admin_fix.js\"></script>\n";
                $newContent .= substr($content, $headPos);
                
                if (file_put_contents($file, $newContent)) {
                    output("Added script tag to file: $file");
                    $updatedFiles++;
                }
            } else {
                // Try to find the closing </body> tag
                $bodyPos = strpos($content, '</body>');
                if ($bodyPos !== false) {
                    // Insert the script tag before the closing </body> tag
                    $newContent = substr($content, 0, $bodyPos);
                    $newContent .= "\n<!-- Admin fix script -->\n<script src=\"/admin/admin_fix.js\"></script>\n";
                    $newContent .= substr($content, $bodyPos);
                    
                    if (file_put_contents($file, $newContent)) {
                        output("Added script tag to file: $file");
                        $updatedFiles++;
                    }
                }
            }
        }
    }
    
    if ($isWeb) output("<div class='success'>Updated $updatedFiles PHP files</div>", true);
    else output("Updated $updatedFiles PHP files");
}

output("");
output("Next Steps:");
output("1. Test the admin interface by creating or editing content");
output("2. Check the browser console for any JavaScript errors");
output("3. If issues persist, try the other methods from the previous script");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}