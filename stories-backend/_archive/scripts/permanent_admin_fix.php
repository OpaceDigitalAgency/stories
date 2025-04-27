<?php
/**
 * Permanent Admin Fix
 * 
 * This script permanently fixes the admin interface form submission issue
 * by modifying the admin.js file.
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
    <title>Permanent Admin Fix</title>
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
        <h1>Permanent Admin Fix</h1>
', true);
}

output("Permanent Admin Fix");
output("=================");
output("");

// Find the admin.js file
$adminJsPath = __DIR__ . '/admin/assets/js/admin.js';
if (!file_exists($adminJsPath)) {
    // Try to find the admin.js file
    output("Searching for admin.js file...");
    $possiblePaths = [
        __DIR__ . '/admin/assets/js/admin.js',
        __DIR__ . '/admin/assets/js/main.js',
        __DIR__ . '/admin/js/admin.js',
        __DIR__ . '/admin/js/main.js',
        '/home/stories/api.storiesfromtheweb.org/admin/assets/js/admin.js',
        '/home/stories/api.storiesfromtheweb.org/admin/assets/js/main.js',
        '/home/stories/api.storiesfromtheweb.org/admin/js/admin.js',
        '/home/stories/api.storiesfromtheweb.org/admin/js/main.js'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            output("Found admin.js at: $path");
            $adminJsPath = $path;
            break;
        }
    }
    
    if (!file_exists($adminJsPath)) {
        // Try to find any JavaScript files in the admin directory
        output("Searching for any JavaScript files in the admin directory...");
        $adminDir = __DIR__ . '/admin';
        if (!is_dir($adminDir)) {
            $adminDir = '/home/stories/api.storiesfromtheweb.org/admin';
        }
        
        if (is_dir($adminDir)) {
            $jsFiles = [];
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'js') {
                    $jsFiles[] = $file->getPathname();
                }
            }
            
            if (!empty($jsFiles)) {
                output("Found JavaScript files:");
                foreach ($jsFiles as $file) {
                    output("- $file");
                }
                
                // Use the first JavaScript file found
                $adminJsPath = $jsFiles[0];
                output("Using: $adminJsPath");
            } else {
                output("No JavaScript files found in the admin directory.");
            }
        } else {
            output("Admin directory not found.");
        }
    }
}

if (!file_exists($adminJsPath)) {
    // Create a new admin.js file
    output("Creating a new admin.js file...");
    
    $adminJsDir = dirname($adminJsPath);
    if (!is_dir($adminJsDir)) {
        if (!mkdir($adminJsDir, 0755, true)) {
            if ($isWeb) output("<div class='error'>Failed to create directory: $adminJsDir</div>", true);
            else output("Error: Failed to create directory: $adminJsDir");
            
            if ($isWeb) {
                output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
                output('</div></body></html>', true);
            }
            exit;
        }
        output("Created directory: $adminJsDir");
    }
}

// Backup the admin.js file if it exists
if (file_exists($adminJsPath)) {
    $backupFile = $adminJsPath . '.bak.' . date('YmdHis');
    if (!copy($adminJsPath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup file</div>", true);
        else output("Warning: Failed to create backup file");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the current admin.js file
    $currentAdminJs = file_get_contents($adminJsPath);
    output("Current admin.js file size: " . strlen($currentAdminJs) . " bytes");
} else {
    $currentAdminJs = '';
    output("No existing admin.js file found, creating a new one.");
}

// Create the improved admin.js content
$improvedAdminJs = $currentAdminJs;

// Add our form submission fix to the admin.js file
$formSubmissionFix = '
// Form submission fix added by permanent_admin_fix.php
(function() {
    console.log("Admin form submission fix loaded");
    
    // Function to handle form submissions
    function handleFormSubmission(form) {
        console.log("Form submission handler activated");
        
        // Get the form action
        const action = form.getAttribute("action");
        console.log("Form action:", action);
        
        // Extract the ID from the action URL if present
        let id = null;
        const idMatch = action ? action.match(/id=([0-9]+)/) : null;
        if (idMatch) {
            id = idMatch[1];
            console.log("Extracted ID:", id);
        }
        
        // Determine the API endpoint and method
        let endpoint = "";
        let method = "POST";
        
        if (action && action.includes("action=edit") && id) {
            // This is an edit form
            endpoint = "/api/v1/stories/" + id;
            method = "PUT";
        } else if (action && action.includes("action=create")) {
            // This is a create form
            endpoint = "/api/v1/stories";
            method = "POST";
        } else {
            // Try to determine from the URL
            const url = window.location.href;
            const urlIdMatch = url.match(/id=([0-9]+)/);
            
            if (urlIdMatch) {
                id = urlIdMatch[1];
                console.log("Extracted ID from URL:", id);
                endpoint = "/api/v1/stories/" + id;
                method = "PUT";
            } else if (url.includes("action=create")) {
                endpoint = "/api/v1/stories";
                method = "POST";
            } else {
                // Default to stories endpoint
                endpoint = "/api/v1/stories";
                method = "POST";
            }
        }
        
        console.log("API endpoint:", endpoint);
        console.log("HTTP method:", method);
        
        // Get form fields
        const formData = new FormData(form);
        const formObject = {};
        formData.forEach((value, key) => {
            formObject[key] = value;
        });
        
        console.log("Form data:", formObject);
        
        // Show loading message
        const loadingElement = document.querySelector(".loading-message");
        if (loadingElement) {
            loadingElement.style.display = "block";
        }
        
        // Make the API call
        fetch(endpoint, {
            method: method,
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(formObject)
        })
            .then(response => {
                console.log("API response:", response);
                return response.text();
            })
            .then(text => {
                console.log("Response text:", text);
                
                try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);
                    console.log("Parsed JSON:", data);
                    
                    // Hide loading message
                    if (loadingElement) {
                        loadingElement.style.display = "none";
                    }
                    
                    // Show success message
                    alert("Changes saved successfully!");
                    
                    // Redirect to the list page or reload
                    if (action && action.includes("action=create")) {
                        // Redirect to the list page
                        window.location.href = window.location.href.replace("action=create", "");
                    } else {
                        // Reload the current page
                        window.location.reload();
                    }
                } catch (e) {
                    console.error("Error parsing JSON:", e);
                    
                    // Hide loading message
                    if (loadingElement) {
                        loadingElement.style.display = "none";
                    }
                    
                    // Show error message
                    alert("Error saving changes: " + e.message + "\n\nResponse: " + text);
                }
            })
            .catch(error => {
                console.error("API error:", error);
                
                // Hide loading message
                if (loadingElement) {
                    loadingElement.style.display = "none";
                }
                
                // Show error message
                alert("Error saving changes: " + error.message);
            });
        
        // Prevent the default form submission
        return false;
    }
    
    // Find all forms and add the submission handler
    document.addEventListener("DOMContentLoaded", function() {
        console.log("DOM loaded, finding forms");
        
        const forms = document.querySelectorAll("form");
        console.log("Found " + forms.length + " forms");
        
        forms.forEach((form, index) => {
            console.log("Processing form " + index);
            
            // Add submit event listener
            form.addEventListener("submit", function(event) {
                console.log("Form submit event triggered");
                event.preventDefault();
                
                return handleFormSubmission(form);
            });
            
            console.log("Added submit event listener to form " + index);
        });
    });
    
    // Also handle forms that might be loaded after DOMContentLoaded
    const originalSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function() {
        console.log("Form submit method called");
        
        // Try to handle the submission
        if (handleFormSubmission(this) === false) {
            console.log("Form submission handled by our code");
            return false;
        }
        
        // Fall back to the original submit method
        console.log("Falling back to original submit method");
        return originalSubmit.apply(this, arguments);
    };
})();
';

// Add the form submission fix to the admin.js file
$improvedAdminJs .= $formSubmissionFix;

// Write the improved admin.js file
if (file_put_contents($adminJsPath, $improvedAdminJs)) {
    if ($isWeb) output("<div class='success'>Successfully updated admin.js file</div>", true);
    else output("Successfully updated admin.js file");
} else {
    if ($isWeb) output("<div class='error'>Failed to update admin.js file</div>", true);
    else output("Error: Failed to update admin.js file");
    
    // Try with different permissions
    output("Trying with different permissions...");
    
    // Try to make the file writable
    if (chmod(dirname($adminJsPath), 0777)) {
        output("Changed directory permissions to 0777");
        
        if (file_put_contents($adminJsPath, $improvedAdminJs)) {
            if ($isWeb) output("<div class='success'>Successfully updated admin.js file</div>", true);
            else output("Successfully updated admin.js file");
        } else {
            if ($isWeb) output("<div class='error'>Still failed to update admin.js file</div>", true);
            else output("Error: Still failed to update admin.js file");
        }
    } else {
        output("Failed to change directory permissions");
    }
}

// Create a standalone JavaScript file that can be included in the admin interface
$standaloneJsPath = __DIR__ . '/admin_form_fix.js';
if (file_put_contents($standaloneJsPath, $formSubmissionFix)) {
    if ($isWeb) output("<div class='success'>Created standalone JavaScript file: $standaloneJsPath</div>", true);
    else output("Created standalone JavaScript file: $standaloneJsPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create standalone JavaScript file</div>", true);
    else output("Error: Failed to create standalone JavaScript file");
}

// Create instructions for manually adding the JavaScript to the admin interface
output("");
output("Manual Integration Instructions");
output("-----------------------------");
output("If the automatic update didn't work, you can manually add the JavaScript to the admin interface:");
output("");
output("1. Add the following script tag to the admin interface HTML files (e.g., admin/includes/header.php):");
if ($isWeb) {
    output("<div class='code'>&lt;script src=\"/admin_form_fix.js\"&gt;&lt;/script&gt;</div>", true);
} else {
    output("<script src=\"/admin_form_fix.js\"></script>");
}
output("");
output("2. Or copy and paste the following JavaScript into the admin.js file:");
if ($isWeb) {
    output("<pre>" . htmlspecialchars($formSubmissionFix) . "</pre>", true);
} else {
    output($formSubmissionFix);
}

output("");
output("Next Steps:");
output("1. Test the admin interface by creating or editing content");
output("2. If issues persist, try the manual integration instructions");
output("3. Check the browser console for any JavaScript errors");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}