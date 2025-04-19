<?php
/**
 * Fix Admin Interface Emergency
 * 
 * This script fixes the admin interface by removing the changes made by the remove_processing_message.php script.
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
    <title>Fix Admin Interface Emergency</title>
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
        <h1>Fix Admin Interface Emergency</h1>
', true);
}

output("Fix Admin Interface Emergency");
output("===========================");
output("");

// 1. Restore the header file from backup
$headerFile = '/home/stories/api.storiesfromtheweb.org/admin/views/header.php';
$headerBackupFiles = glob('/home/stories/api.storiesfromtheweb.org/admin/views/header.php.bak.*');

if (!empty($headerBackupFiles)) {
    // Sort by modification time (newest first)
    usort($headerBackupFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestBackup = $headerBackupFiles[0];
    output("Found header backup file: $latestBackup");
    
    if (copy($latestBackup, $headerFile)) {
        if ($isWeb) output("<div class='success'>Restored header file from backup</div>", true);
        else output("Restored header file from backup");
    } else {
        if ($isWeb) output("<div class='error'>Failed to restore header file from backup</div>", true);
        else output("Error: Failed to restore header file from backup");
    }
} else {
    if ($isWeb) output("<div class='warning'>No header backup files found</div>", true);
    else output("Warning: No header backup files found");
}

// 2. Restore the footer file from backup
$footerFile = '/home/stories/api.storiesfromtheweb.org/admin/views/footer.php';
$footerBackupFiles = glob('/home/stories/api.storiesfromtheweb.org/admin/views/footer.php.bak.*');

if (!empty($footerBackupFiles)) {
    // Sort by modification time (newest first)
    usort($footerBackupFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestBackup = $footerBackupFiles[0];
    output("Found footer backup file: $latestBackup");
    
    if (copy($latestBackup, $footerFile)) {
        if ($isWeb) output("<div class='success'>Restored footer file from backup</div>", true);
        else output("Restored footer file from backup");
    } else {
        if ($isWeb) output("<div class='error'>Failed to restore footer file from backup</div>", true);
        else output("Error: Failed to restore footer file from backup");
    }
} else {
    if ($isWeb) output("<div class='warning'>No footer backup files found</div>", true);
    else output("Warning: No footer backup files found");
}

// 3. Restore the .htaccess file from backup
$htaccessFile = '/home/stories/api.storiesfromtheweb.org/admin/.htaccess';
$htaccessBackupFiles = glob('/home/stories/api.storiesfromtheweb.org/admin/.htaccess.bak.*');

if (!empty($htaccessBackupFiles)) {
    // Sort by modification time (newest first)
    usort($htaccessBackupFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestBackup = $htaccessBackupFiles[0];
    output("Found .htaccess backup file: $latestBackup");
    
    if (copy($latestBackup, $htaccessFile)) {
        if ($isWeb) output("<div class='success'>Restored .htaccess file from backup</div>", true);
        else output("Restored .htaccess file from backup");
    } else {
        if ($isWeb) output("<div class='error'>Failed to restore .htaccess file from backup</div>", true);
        else output("Error: Failed to restore .htaccess file from backup");
    }
} else {
    if ($isWeb) output("<div class='warning'>No .htaccess backup files found</div>", true);
    else output("Warning: No .htaccess backup files found");
    
    // Create a minimal .htaccess file
    $minimalHtaccess = "# Minimal .htaccess file\n";
    if (file_put_contents($htaccessFile, $minimalHtaccess)) {
        if ($isWeb) output("<div class='success'>Created minimal .htaccess file</div>", true);
        else output("Created minimal .htaccess file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to create minimal .htaccess file</div>", true);
        else output("Error: Failed to create minimal .htaccess file");
    }
}

// 4. Remove the JavaScript files
$jsFiles = [
    '/home/stories/api.storiesfromtheweb.org/admin/remove_processing.js',
    '/home/stories/api.storiesfromtheweb.org/admin/remove_processing_script.html',
    '/home/stories/api.storiesfromtheweb.org/admin/remove_processing_include.php',
    '/home/stories/api.storiesfromtheweb.org/admin/admin_fix.js',
    '/home/stories/api.storiesfromtheweb.org/admin/admin_fix_script.html',
    '/home/stories/api.storiesfromtheweb.org/admin/admin_fix_include.php'
];

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            if ($isWeb) output("<div class='success'>Removed file: $file</div>", true);
            else output("Removed file: $file");
        } else {
            if ($isWeb) output("<div class='error'>Failed to remove file: $file</div>", true);
            else output("Error: Failed to remove file: $file");
        }
    } else {
        if ($isWeb) output("<div class='warning'>File not found: $file</div>", true);
        else output("Warning: File not found: $file");
    }
}

// 5. Create a simple bookmarklet for the user to use
$bookmarkletCode = "javascript:(function(){
    console.log('Direct save bookmarklet loaded');
    
    // Find the form
    const form = document.querySelector('form');
    if (!form) {
        console.error('Form not found');
        alert('Error: Form not found');
        return;
    }
    
    // Get the current URL
    const url = window.location.href;
    console.log('Current URL:', url);
    
    // Extract the ID from the URL
    let id = null;
    const idMatch = url.match(/id=([0-9]+)/);
    if (idMatch) {
        id = idMatch[1];
        console.log('Extracted ID:', id);
    } else {
        console.error('Could not extract ID from URL');
        alert('Error: Could not extract ID from URL');
        return;
    }
    
    // Get form fields
    const formData = new FormData(form);
    const formObject = {};
    formData.forEach((value, key) => {
        formObject[key] = value;
    });
    
    console.log('Form data:', formObject);
    
    // Show loading message
    const loadingMessage = document.createElement('div');
    loadingMessage.textContent = 'Saving changes...';
    loadingMessage.style.position = 'fixed';
    loadingMessage.style.top = '50%';
    loadingMessage.style.left = '50%';
    loadingMessage.style.transform = 'translate(-50%, -50%)';
    loadingMessage.style.padding = '20px';
    loadingMessage.style.backgroundColor = '#f9f9f9';
    loadingMessage.style.border = '1px solid #ddd';
    loadingMessage.style.borderRadius = '4px';
    loadingMessage.style.zIndex = '1001';
    document.body.appendChild(loadingMessage);
    
    // Make a direct API call
    fetch('/api/v1/stories/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formObject)
    })
        .then(response => {
            console.log('API response:', response);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            
            try {
                // Try to parse as JSON
                const data = JSON.parse(text);
                console.log('Parsed JSON:', data);
                
                // Remove loading message
                document.body.removeChild(loadingMessage);
                
                // Show success message
                alert('Changes saved successfully!');
                
                // Reload the page
                window.location.reload();
            } catch (e) {
                console.error('Error parsing JSON:', e);
                
                // Remove loading message
                document.body.removeChild(loadingMessage);
                
                // Show error message
                alert('Error saving changes: ' + e.message + '\\n\\nResponse: ' + text);
            }
        })
        .catch(error => {
            console.error('API error:', error);
            
            // Remove loading message
            document.body.removeChild(loadingMessage);
            
            // Show error message
            alert('Error saving changes: ' + error.message);
        });
})();";

output("");
output("Bookmarklet for Direct Save");
output("=========================");
output("Drag this link to your bookmarks bar:");
if ($isWeb) {
    output("<div style='margin: 20px 0;'><a href=\"" . htmlspecialchars($bookmarkletCode) . "\" class='button'>Save Changes (Direct)</a></div>", true);
} else {
    output("Save Changes (Direct): " . $bookmarkletCode);
}
output("");
output("Instructions:");
output("1. Drag the 'Save Changes (Direct)' link to your bookmarks bar");
output("2. When editing a story, click the bookmark to save your changes directly");
output("3. This bypasses the problematic form submission without modifying the admin interface");

output("");
output("Next Steps:");
output("1. Clear your browser cache and cookies");
output("2. Reload the admin interface");
output("3. Use the bookmarklet to save changes when needed");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}