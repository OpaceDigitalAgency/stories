<?php
/**
 * Fix Admin JS
 * 
 * This script directly modifies the admin.js file to fix the form submission issue.
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
    <title>Fix Admin JS</title>
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
        <h1>Fix Admin JS</h1>
', true);
}

output("Fix Admin JS");
output("===========");
output("");

// Find the admin.js file
$adminJsPath = __DIR__ . '/admin/assets/js/admin.js';
if (!file_exists($adminJsPath)) {
    // Try to find the admin.js file
    $possiblePaths = [
        __DIR__ . '/admin/assets/js/admin.js',
        __DIR__ . '/admin/js/admin.js',
        '/home/stories/api.storiesfromtheweb.org/admin/assets/js/admin.js',
        '/home/stories/api.storiesfromtheweb.org/admin/js/admin.js'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $adminJsPath = $path;
            output("Found admin.js file: $adminJsPath");
            break;
        }
    }
    
    if (!file_exists($adminJsPath)) {
        if ($isWeb) output("<div class='error'>Admin.js file not found</div>", true);
        else output("Error: Admin.js file not found");
        
        if ($isWeb) {
            output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
            output('</div></body></html>', true);
        }
        exit;
    }
}

output("Using admin.js file: $adminJsPath");

// Backup the admin.js file
$backupFile = $adminJsPath . '.bak.' . date('YmdHis');
if (!copy($adminJsPath, $backupFile)) {
    if ($isWeb) output("<div class='warning'>Failed to create backup of admin.js file</div>", true);
    else output("Warning: Failed to create backup of admin.js file");
} else {
    output("Backup created: $backupFile");
}

// Read the admin.js file
$adminJs = file_get_contents($adminJsPath);
output("Current admin.js file size: " . strlen($adminJs) . " bytes");

// Find the form submission handler in the admin.js file
$formSubmissionPattern = '/\$\(\'form\.form-loading\'\)\.on\(\'submit\',\s*function\s*\(event\)\s*\{.*?\}\);/s';
if (preg_match($formSubmissionPattern, $adminJs, $matches)) {
    output("Found form submission handler in admin.js");
    
    // Replace the form submission handler with our fixed version
    $fixedFormSubmissionHandler = '$(\'form.form-loading\').on(\'submit\', function(event) {
    event.preventDefault();
    
    var form = $(this);
    var submitButton = form.find(\'.btn-loading\');
    var buttonText = submitButton.find(\'.button-text\');
    var spinner = submitButton.find(\'.spinner-border\');
    var formMessages = form.find(\'.form-messages\');
    var successMessage = form.find(\'.success-message\');
    var errorMessage = form.find(\'.error-message\');
    
    // Show loading state
    buttonText.addClass(\'d-none\');
    spinner.removeClass(\'d-none\');
    
    // Get form data
    var formData = new FormData(form[0]);
    var jsonData = {};
    
    // Convert FormData to JSON
    formData.forEach(function(value, key) {
        jsonData[key] = value;
    });
    
    // Determine the API endpoint
    var action = form.attr(\'action\');
    var endpoint = \'\';
    var method = \'POST\';
    
    // Extract content type from URL
    var contentType = \'\';
    if (action.includes(\'stories.php\')) {
        contentType = \'stories\';
    } else if (action.includes(\'authors.php\')) {
        contentType = \'authors\';
    } else if (action.includes(\'tags.php\')) {
        contentType = \'tags\';
    } else if (action.includes(\'blog-posts.php\')) {
        contentType = \'blog-posts\';
    } else if (action.includes(\'games.php\')) {
        contentType = \'games\';
    } else if (action.includes(\'directory-items.php\')) {
        contentType = \'directory-items\';
    } else if (action.includes(\'ai-tools.php\')) {
        contentType = \'ai-tools\';
    }
    
    console.log(\'Content type:\', contentType);
    
    // Extract ID if editing
    var id = null;
    if (action.includes(\'action=edit\') || window.location.href.includes(\'action=edit\')) {
        var idMatch = window.location.href.match(/id=([0-9]+)/);
        if (idMatch) {
            id = idMatch[1];
            console.log(\'Extracted ID:\', id);
            endpoint = \'/api/v1/\' + contentType + \'/\' + id;
            method = \'PUT\';
        }
    } else if (action.includes(\'action=create\') || window.location.href.includes(\'action=create\')) {
        endpoint = \'/api/v1/\' + contentType;
        method = \'POST\';
    } else if (action.includes(\'action=delete\') || window.location.href.includes(\'action=delete\')) {
        var idMatch = window.location.href.match(/id=([0-9]+)/);
        if (idMatch) {
            id = idMatch[1];
            console.log(\'Extracted ID for deletion:\', id);
            endpoint = \'/api/v1/\' + contentType + \'/\' + id;
            method = \'DELETE\';
        }
    }
    
    console.log(\'API endpoint:\', endpoint);
    console.log(\'HTTP method:\', method);
    
    // Make the API call
    $.ajax({
        url: endpoint,
        type: method,
        data: JSON.stringify(jsonData),
        contentType: \'application/json\',
        success: function(response) {
            console.log(\'API response:\', response);
            
            // Hide loading state
            buttonText.removeClass(\'d-none\');
            spinner.addClass(\'d-none\');
            
            // Hide loading overlay
            $(\'.loading-overlay\').addClass(\'d-none\');
            
            // Show success message
            formMessages.show();
            successMessage.text(\'Changes saved successfully!\').show();
            errorMessage.hide();
            
            // Redirect to list page after a short delay
            setTimeout(function() {
                if (action.includes(\'action=create\') || action.includes(\'action=edit\')) {
                    // Extract the base URL without the query parameters
                    var baseUrl = window.location.href.split(\'?\')[0];
                    window.location.href = baseUrl;
                } else {
                    // Reload the current page
                    window.location.reload();
                }
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error(\'API error:\', error);
            
            // Hide loading state
            buttonText.removeClass(\'d-none\');
            spinner.addClass(\'d-none\');
            
            // Hide loading overlay
            $(\'.loading-overlay\').addClass(\'d-none\');
            
            // Show error message
            formMessages.show();
            errorMessage.text(\'Error saving changes: \' + error).show();
            successMessage.hide();
        }
    });
});';
    
    // Replace the form submission handler
    $newAdminJs = preg_replace($formSubmissionPattern, $fixedFormSubmissionHandler, $adminJs);
    
    // Write the modified admin.js file
    if (file_put_contents($adminJsPath, $newAdminJs)) {
        if ($isWeb) output("<div class='success'>Successfully updated admin.js file with fixed form submission handler</div>", true);
        else output("Successfully updated admin.js file with fixed form submission handler");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update admin.js file</div>", true);
        else output("Error: Failed to update admin.js file");
    }
} else {
    if ($isWeb) output("<div class='warning'>Could not find form submission handler in admin.js</div>", true);
    else output("Warning: Could not find form submission handler in admin.js");
    
    // Append our form submission handler to the end of the admin.js file
    $formSubmissionHandler = '
// Fixed form submission handler
$(document).ready(function() {
    $(\'form.form-loading\').on(\'submit\', function(event) {
        event.preventDefault();
        
        var form = $(this);
        var submitButton = form.find(\'.btn-loading\');
        var buttonText = submitButton.find(\'.button-text\');
        var spinner = submitButton.find(\'.spinner-border\');
        var formMessages = form.find(\'.form-messages\');
        var successMessage = form.find(\'.success-message\');
        var errorMessage = form.find(\'.error-message\');
        
        // Show loading state
        buttonText.addClass(\'d-none\');
        spinner.removeClass(\'d-none\');
        
        // Get form data
        var formData = new FormData(form[0]);
        var jsonData = {};
        
        // Convert FormData to JSON
        formData.forEach(function(value, key) {
            jsonData[key] = value;
        });
        
        // Determine the API endpoint
        var action = form.attr(\'action\');
        var endpoint = \'\';
        var method = \'POST\';
        
        // Extract content type from URL
        var contentType = \'\';
        if (action.includes(\'stories.php\')) {
            contentType = \'stories\';
        } else if (action.includes(\'authors.php\')) {
            contentType = \'authors\';
        } else if (action.includes(\'tags.php\')) {
            contentType = \'tags\';
        } else if (action.includes(\'blog-posts.php\')) {
            contentType = \'blog-posts\';
        } else if (action.includes(\'games.php\')) {
            contentType = \'games\';
        } else if (action.includes(\'directory-items.php\')) {
            contentType = \'directory-items\';
        } else if (action.includes(\'ai-tools.php\')) {
            contentType = \'ai-tools\';
        }
        
        console.log(\'Content type:\', contentType);
        
        // Extract ID if editing
        var id = null;
        if (action.includes(\'action=edit\') || window.location.href.includes(\'action=edit\')) {
            var idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log(\'Extracted ID:\', id);
                endpoint = \'/api/v1/\' + contentType + \'/\' + id;
                method = \'PUT\';
            }
        } else if (action.includes(\'action=create\') || window.location.href.includes(\'action=create\')) {
            endpoint = \'/api/v1/\' + contentType;
            method = \'POST\';
        } else if (action.includes(\'action=delete\') || window.location.href.includes(\'action=delete\')) {
            var idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log(\'Extracted ID for deletion:\', id);
                endpoint = \'/api/v1/\' + contentType + \'/\' + id;
                method = \'DELETE\';
            }
        }
        
        console.log(\'API endpoint:\', endpoint);
        console.log(\'HTTP method:\', method);
        
        // Make the API call
        $.ajax({
            url: endpoint,
            type: method,
            data: JSON.stringify(jsonData),
            contentType: \'application/json\',
            success: function(response) {
                console.log(\'API response:\', response);
                
                // Hide loading state
                buttonText.removeClass(\'d-none\');
                spinner.addClass(\'d-none\');
                
                // Hide loading overlay
                $(\'.loading-overlay\').addClass(\'d-none\');
                
                // Show success message
                formMessages.show();
                successMessage.text(\'Changes saved successfully!\').show();
                errorMessage.hide();
                
                // Redirect to list page after a short delay
                setTimeout(function() {
                    if (action.includes(\'action=create\') || action.includes(\'action=edit\')) {
                        // Extract the base URL without the query parameters
                        var baseUrl = window.location.href.split(\'?\')[0];
                        window.location.href = baseUrl;
                    } else {
                        // Reload the current page
                        window.location.reload();
                    }
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.error(\'API error:\', error);
                
                // Hide loading state
                buttonText.removeClass(\'d-none\');
                spinner.addClass(\'d-none\');
                
                // Hide loading overlay
                $(\'.loading-overlay\').addClass(\'d-none\');
                
                // Show error message
                formMessages.show();
                errorMessage.text(\'Error saving changes: \' + error).show();
                successMessage.hide();
            }
        });
    });
});';
    
    // Append the form submission handler to the admin.js file
    if (file_put_contents($adminJsPath, $adminJs . $formSubmissionHandler)) {
        if ($isWeb) output("<div class='success'>Successfully appended fixed form submission handler to admin.js file</div>", true);
        else output("Successfully appended fixed form submission handler to admin.js file");
    } else {
        if ($isWeb) output("<div class='error'>Failed to update admin.js file</div>", true);
        else output("Error: Failed to update admin.js file");
    }
}

// Also create a direct fix script that can be included in the admin interface
$directFixPath = __DIR__ . '/admin/assets/js/direct-form-fix.js';
$directFixContent = '// Direct form fix
$(document).ready(function() {
    // Disable the original form submission handler
    $(\'form.form-loading\').off(\'submit\');
    
    // Add our fixed form submission handler
    $(\'form.form-loading\').on(\'submit\', function(event) {
        event.preventDefault();
        
        var form = $(this);
        var submitButton = form.find(\'.btn-loading\');
        var buttonText = submitButton.find(\'.button-text\');
        var spinner = submitButton.find(\'.spinner-border\');
        var formMessages = form.find(\'.form-messages\');
        var successMessage = form.find(\'.success-message\');
        var errorMessage = form.find(\'.error-message\');
        
        // Show loading state
        buttonText.addClass(\'d-none\');
        spinner.removeClass(\'d-none\');
        
        // Get form data
        var formData = new FormData(form[0]);
        var jsonData = {};
        
        // Convert FormData to JSON
        formData.forEach(function(value, key) {
            jsonData[key] = value;
        });
        
        // Determine the API endpoint
        var action = form.attr(\'action\');
        var endpoint = \'\';
        var method = \'POST\';
        
        // Extract content type from URL
        var contentType = \'\';
        if (action.includes(\'stories.php\')) {
            contentType = \'stories\';
        } else if (action.includes(\'authors.php\')) {
            contentType = \'authors\';
        } else if (action.includes(\'tags.php\')) {
            contentType = \'tags\';
        } else if (action.includes(\'blog-posts.php\')) {
            contentType = \'blog-posts\';
        } else if (action.includes(\'games.php\')) {
            contentType = \'games\';
        } else if (action.includes(\'directory-items.php\')) {
            contentType = \'directory-items\';
        } else if (action.includes(\'ai-tools.php\')) {
            contentType = \'ai-tools\';
        }
        
        console.log(\'Content type:\', contentType);
        
        // Extract ID if editing
        var id = null;
        if (action.includes(\'action=edit\') || window.location.href.includes(\'action=edit\')) {
            var idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log(\'Extracted ID:\', id);
                endpoint = \'/api/v1/\' + contentType + \'/\' + id;
                method = \'PUT\';
            }
        } else if (action.includes(\'action=create\') || window.location.href.includes(\'action=create\')) {
            endpoint = \'/api/v1/\' + contentType;
            method = \'POST\';
        } else if (action.includes(\'action=delete\') || window.location.href.includes(\'action=delete\')) {
            var idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log(\'Extracted ID for deletion:\', id);
                endpoint = \'/api/v1/\' + contentType + \'/\' + id;
                method = \'DELETE\';
            }
        }
        
        console.log(\'API endpoint:\', endpoint);
        console.log(\'HTTP method:\', method);
        
        // Make the API call
        $.ajax({
            url: endpoint,
            type: method,
            data: JSON.stringify(jsonData),
            contentType: \'application/json\',
            success: function(response) {
                console.log(\'API response:\', response);
                
                // Hide loading state
                buttonText.removeClass(\'d-none\');
                spinner.addClass(\'d-none\');
                
                // Hide loading overlay
                $(\'.loading-overlay\').addClass(\'d-none\');
                
                // Show success message
                formMessages.show();
                successMessage.text(\'Changes saved successfully!\').show();
                errorMessage.hide();
                
                // Redirect to list page after a short delay
                setTimeout(function() {
                    if (action.includes(\'action=create\') || action.includes(\'action=edit\')) {
                        // Extract the base URL without the query parameters
                        var baseUrl = window.location.href.split(\'?\')[0];
                        window.location.href = baseUrl;
                    } else {
                        // Reload the current page
                        window.location.reload();
                    }
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.error(\'API error:\', error);
                
                // Hide loading state
                buttonText.removeClass(\'d-none\');
                spinner.addClass(\'d-none\');
                
                // Hide loading overlay
                $(\'.loading-overlay\').addClass(\'d-none\');
                
                // Show error message
                formMessages.show();
                errorMessage.text(\'Error saving changes: \' + error).show();
                successMessage.hide();
            }
        });
    });
    
    // Add a direct save button
    var directSaveButton = $(\'<button>\').attr({
        type: \'button\',
        class: \'btn btn-success ms-2\'
    }).text(\'Save (Direct)\');
    
    // Add the direct save button after the submit button
    $(\'.btn-loading\').after(directSaveButton);
    
    // Add click event to the direct save button
    directSaveButton.on(\'click\', function() {
        // Trigger the form submission
        $(\'form.form-loading\').submit();
    });
});';

if (file_put_contents($directFixPath, $directFixContent)) {
    if ($isWeb) output("<div class='success'>Created direct form fix script: $directFixPath</div>", true);
    else output("Created direct form fix script: $directFixPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create direct form fix script</div>", true);
    else output("Error: Failed to create direct form fix script");
}

// Create a script tag include file
$scriptTagPath = __DIR__ . '/admin/direct_form_fix_script.html';
$scriptTagContent = '<script src="/admin/assets/js/direct-form-fix.js"></script>';

if (file_put_contents($scriptTagPath, $scriptTagContent)) {
    if ($isWeb) output("<div class='success'>Created script tag file: $scriptTagPath</div>", true);
    else output("Created script tag file: $scriptTagPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create script tag file</div>", true);
    else output("Error: Failed to create script tag file");
}

// Find the footer file
$footerFile = __DIR__ . '/admin/views/footer.php';
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
    
    // Check if the direct form fix script is already included
    if (strpos($footerContent, 'direct-form-fix.js') !== false) {
        if ($isWeb) output("<div class='warning'>Direct form fix script already included in footer file</div>", true);
        else output("Warning: Direct form fix script already included in footer file");
    } else {
        // Find the closing </body> tag
        $bodyPos = strpos($footerContent, '</body>');
        if ($bodyPos !== false) {
            // Insert the script tag before the closing </body> tag
            $newFooterContent = substr($footerContent, 0, $bodyPos);
            $newFooterContent .= "\n<!-- Direct form fix script -->\n<script src=\"/admin/assets/js/direct-form-fix.js\"></script>\n";
            $newFooterContent .= substr($footerContent, $bodyPos);
            
            if (file_put_contents($footerFile, $newFooterContent)) {
                if ($isWeb) output("<div class='success'>Added direct form fix script to footer file</div>", true);
                else output("Added direct form fix script to footer file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
                else output("Error: Failed to update footer file");
            }
        } else {
            // Append the script tag to the end of the file
            $newFooterContent = $footerContent . "\n<!-- Direct form fix script -->\n<script src=\"/admin/assets/js/direct-form-fix.js\"></script>\n";
            
            if (file_put_contents($footerFile, $newFooterContent)) {
                if ($isWeb) output("<div class='success'>Added direct form fix script to the end of footer file</div>", true);
                else output("Added direct form fix script to the end of footer file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
                else output("Error: Failed to update footer file");
            }
        }
    }
} else {
    if ($isWeb) output("<div class='warning'>Footer file not found: $footerFile</div>", true);
    else output("Warning: Footer file not found: $footerFile");
}

output("");
output("Next Steps:");
output("1. Test the admin interface by creating or editing content");
output("2. Check the browser console for any JavaScript errors");
output("3. Verify that the form submission completes successfully");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}