<?php
/**
 * Update Admin Footer
 * 
 * This script updates the admin footer to include the form submission fix script.
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
    <title>Update Admin Footer</title>
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
        <h1>Update Admin Footer</h1>
', true);
}

output("Update Admin Footer");
output("=================");
output("");

// Find the footer file
$footerFile = __DIR__ . '/admin/views/footer.php';
if (!file_exists($footerFile)) {
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
            $footerFile = $file;
            output("Found footer file: $footerFile");
            break;
        }
    }
    
    if (!file_exists($footerFile)) {
        if ($isWeb) output("<div class='error'>Footer file not found</div>", true);
        else output("Error: Footer file not found");
        
        if ($isWeb) {
            output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
            output('</div></body></html>', true);
        }
        exit;
    }
}

output("Using footer file: $footerFile");

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

// Check if the form submission fix script is already included
if (strpos($footerContent, 'form-submission-fix.js') !== false) {
    if ($isWeb) output("<div class='warning'>Form submission fix script already included in footer file</div>", true);
    else output("Warning: Form submission fix script already included in footer file");
} else {
    // Find the closing </body> tag
    $bodyPos = strpos($footerContent, '</body>');
    if ($bodyPos !== false) {
        // Insert the script tag before the closing </body> tag
        $newFooterContent = substr($footerContent, 0, $bodyPos);
        $newFooterContent .= "\n<!-- Form submission fix script -->\n<script src=\"/admin/assets/js/form-submission-fix.js\"></script>\n";
        $newFooterContent .= substr($footerContent, $bodyPos);
        
        if (file_put_contents($footerFile, $newFooterContent)) {
            if ($isWeb) output("<div class='success'>Added form submission fix script to footer file</div>", true);
            else output("Added form submission fix script to footer file");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
            else output("Error: Failed to update footer file");
        }
    } else {
        // Append the script tag to the end of the file
        $newFooterContent = $footerContent . "\n<!-- Form submission fix script -->\n<script src=\"/admin/assets/js/form-submission-fix.js\"></script>\n";
        
        if (file_put_contents($footerFile, $newFooterContent)) {
            if ($isWeb) output("<div class='success'>Added form submission fix script to the end of footer file</div>", true);
            else output("Added form submission fix script to the end of footer file");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
            else output("Error: Failed to update footer file");
        }
    }
}

// Create a direct include script
$includeScriptPath = __DIR__ . '/admin/form_submission_fix_include.php';
$includeScriptContent = '<?php
// Form submission fix include script
// This script includes the form submission fix JavaScript

// Check if the JavaScript file exists
$jsFile = __DIR__ . "/assets/js/form-submission-fix.js";
if (file_exists($jsFile)) {
    echo "<script src=\"/admin/assets/js/form-submission-fix.js\"></script>";
} else {
    // Inline the JavaScript if the file doesn\'t exist
    echo "<script>
/**
 * Form Submission Fix
 * 
 * This script fixes the form submission issue in the admin interface.
 * It replaces the problematic form submission handler with a direct API call.
 */

(function() {
    console.log(\'[FORM FIX] Form submission fix loaded\');

    // Function to handle form submissions
    function handleFormSubmission(form) {
        console.log(\'[FORM FIX] Form submission handler activated\');
        
        // Get the form action
        const action = form.getAttribute(\'action\');
        console.log(\'[FORM FIX] Form action:\', action);
        
        // Determine the API endpoint and method
        let endpoint = \'\';
        let method = \'POST\';
        
        // Extract content type from URL
        let contentType = \'\';
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
        
        console.log(\'[FORM FIX] Content type:\', contentType);
        
        // Extract ID if editing
        let id = null;
        if (action.includes(\'action=edit\') || window.location.href.includes(\'action=edit\')) {
            const idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log(\'[FORM FIX] Extracted ID:\', id);
                endpoint = \'/api/v1/\' + contentType + \'/\' + id;
                method = \'PUT\';
            }
        } else if (action.includes(\'action=create\') || window.location.href.includes(\'action=create\')) {
            endpoint = \'/api/v1/\' + contentType;
            method = \'POST\';
        } else if (action.includes(\'action=delete\') || window.location.href.includes(\'action=delete\')) {
            const idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log(\'[FORM FIX] Extracted ID for deletion:\', id);
                endpoint = \'/api/v1/\' + contentType + \'/\' + id;
                method = \'DELETE\';
            }
        }
        
        console.log(\'[FORM FIX] API endpoint:\', endpoint);
        console.log(\'[FORM FIX] HTTP method:\', method);
        
        // If we couldn\'t determine the endpoint, fall back to the original form submission
        if (!endpoint) {
            console.log(\'[FORM FIX] Could not determine API endpoint, falling back to original form submission\');
            return true;
        }
        
        // Get form data
        const formData = new FormData(form);
        const formObject = {};
        formData.forEach((value, key) => {
            formObject[key] = value;
        });
        
        console.log(\'[FORM FIX] Form data:\', formObject);
        
        // Show loading message (already shown by the original form handler)
        
        // Make a direct API call
        fetch(endpoint, {
            method: method,
            headers: {
                \'Content-Type\': \'application/json\'
            },
            body: JSON.stringify(formObject)
        })
            .then(response => {
                console.log(\'[FORM FIX] API response:\', response);
                return response.text();
            })
            .then(text => {
                console.log(\'[FORM FIX] Response text:\', text);
                
                try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);
                    console.log(\'[FORM FIX] Parsed JSON:\', data);
                    
                    // Hide the loading overlay
                    const loadingOverlay = document.querySelector(\'.loading-overlay\');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = \'none\';
                    }
                    
                    // Hide any processing messages
                    const processingMessages = document.querySelectorAll(\'.mt-2.loading-message\');
                    processingMessages.forEach(message => {
                        message.style.display = \'none\';
                    });
                    
                    // Show success message
                    alert(\'Changes saved successfully!\');
                    
                    // Redirect to the list page
                    if (action.includes(\'action=create\') || action.includes(\'action=edit\')) {
                        // Extract the base URL without the query parameters
                        const baseUrl = window.location.href.split(\'?\')[0];
                        window.location.href = baseUrl;
                    } else {
                        // Reload the current page
                        window.location.reload();
                    }
                } catch (e) {
                    console.error(\'[FORM FIX] Error parsing JSON:\', e);
                    
                    // Hide the loading overlay
                    const loadingOverlay = document.querySelector(\'.loading-overlay\');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = \'none\';
                    }
                    
                    // Hide any processing messages
                    const processingMessages = document.querySelectorAll(\'.mt-2.loading-message\');
                    processingMessages.forEach(message => {
                        message.style.display = \'none\';
                    });
                    
                    // Show error message
                    alert(\'Error saving changes: \' + e.message + \'\\n\\nResponse: \' + text);
                }
            })
            .catch(error => {
                console.error(\'[FORM FIX] API error:\', error);
                
                // Hide the loading overlay
                const loadingOverlay = document.querySelector(\'.loading-overlay\');
                if (loadingOverlay) {
                    loadingOverlay.style.display = \'none\';
                }
                
                // Hide any processing messages
                const processingMessages = document.querySelectorAll(\'.mt-2.loading-message\');
                processingMessages.forEach(message => {
                    message.style.display = \'none\';
                });
                
                // Show error message
                alert(\'Error saving changes: \' + error.message);
            });
        
        // Prevent the default form submission
        return false;
    }
    
    // Function to override the form submission
    function overrideFormSubmission() {
        console.log(\'[FORM FIX] Overriding form submission\');
        
        // Find all forms
        const forms = document.querySelectorAll(\'form\');
        console.log(\'[FORM FIX] Found \' + forms.length + \' forms\');
        
        forms.forEach((form, index) => {
            console.log(\'[FORM FIX] Processing form \' + index);
            
            // Add submit event listener
            form.addEventListener(\'submit\', function(event) {
                console.log(\'[FORM FIX] Form submit event triggered\');
                
                // Prevent the default form submission
                event.preventDefault();
                
                // Handle the form submission
                if (handleFormSubmission(form) === true) {
                    // If handleFormSubmission returns true, submit the form normally
                    form.submit();
                }
            });
            
            console.log(\'[FORM FIX] Added submit event listener to form \' + index);
        });
    }
    
    // Run when the DOM is loaded
    if (document.readyState === \'loading\') {
        document.addEventListener(\'DOMContentLoaded\', overrideFormSubmission);
    } else {
        // DOM is already loaded
        overrideFormSubmission();
    }
})();
</script>";
}
';

if (file_put_contents($includeScriptPath, $includeScriptContent)) {
    if ($isWeb) output("<div class='success'>Created include script: $includeScriptPath</div>", true);
    else output("Created include script: $includeScriptPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create include script</div>", true);
    else output("Error: Failed to create include script");
}

// Create a direct .htaccess file to auto-prepend the include script
$htaccessPath = __DIR__ . '/admin/.htaccess';
$htaccessContent = '# Auto-prepend the form submission fix include script
php_value auto_prepend_file "' . __DIR__ . '/admin/form_submission_fix_include.php"
';

if (file_exists($htaccessPath)) {
    // Backup the existing .htaccess file
    $backupFile = $htaccessPath . '.bak.' . date('YmdHis');
    if (!copy($htaccessPath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of .htaccess file</div>", true);
        else output("Warning: Failed to create backup of .htaccess file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the current .htaccess file
    $currentHtaccess = file_get_contents($htaccessPath);
    
    // Check if the auto_prepend_file directive already exists
    if (strpos($currentHtaccess, 'auto_prepend_file') !== false) {
        // Replace the existing auto_prepend_file directive
        $newHtaccess = preg_replace('/php_value\s+auto_prepend_file\s+.*/', 'php_value auto_prepend_file "' . __DIR__ . '/admin/form_submission_fix_include.php"', $currentHtaccess);
        
        if (file_put_contents($htaccessPath, $newHtaccess)) {
            if ($isWeb) output("<div class='success'>Updated .htaccess file with auto_prepend_file directive</div>", true);
            else output("Updated .htaccess file with auto_prepend_file directive");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update .htaccess file</div>", true);
            else output("Error: Failed to update .htaccess file");
        }
    } else {
        // Append the auto_prepend_file directive
        $newHtaccess = $currentHtaccess . "\n" . $htaccessContent;
        
        if (file_put_contents($htaccessPath, $newHtaccess)) {
            if ($isWeb) output("<div class='success'>Added auto_prepend_file directive to .htaccess file</div>", true);
            else output("Added auto_prepend_file directive to .htaccess file");
        } else {
            if ($isWeb) output("<div class='error'>Failed to update .htaccess file</div>", true);
            else output("Error: Failed to update .htaccess file");
        }
    }
} else {
    // Create a new .htaccess file
    if (file_put_contents($htaccessPath, $htaccessContent)) {
        if ($isWeb) output("<div class='success'>Created .htaccess file with auto_prepend_file directive</div>", true);
        else output("Created .htaccess file with auto_prepend_file directive");
    } else {
        if ($isWeb) output("<div class='error'>Failed to create .htaccess file</div>", true);
        else output("Error: Failed to create .htaccess file");
    }
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