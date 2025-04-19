<?php
/**
 * Include Admin Fix
 * 
 * This script modifies the admin interface HTML files to include the standalone JavaScript fix.
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
    <title>Include Admin Fix</title>
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
        <h1>Include Admin Fix</h1>
', true);
}

output("Include Admin Fix");
output("===============");
output("");

// Find the admin header file
$headerFiles = [
    __DIR__ . '/admin/includes/header.php',
    __DIR__ . '/admin/views/header.php',
    __DIR__ . '/admin/header.php',
    '/home/stories/api.storiesfromtheweb.org/admin/includes/header.php',
    '/home/stories/api.storiesfromtheweb.org/admin/views/header.php',
    '/home/stories/api.storiesfromtheweb.org/admin/header.php'
];

$headerFile = null;
foreach ($headerFiles as $file) {
    if (file_exists($file)) {
        $headerFile = $file;
        output("Found header file: $headerFile");
        break;
    }
}

if (!$headerFile) {
    // Try to find any PHP files in the admin directory
    output("Searching for PHP files in the admin directory...");
    $adminDir = __DIR__ . '/admin';
    if (!is_dir($adminDir)) {
        $adminDir = '/home/stories/api.storiesfromtheweb.org/admin';
    }
    
    if (is_dir($adminDir)) {
        $phpFiles = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
        
        if (!empty($phpFiles)) {
            output("Found PHP files:");
            foreach ($phpFiles as $file) {
                output("- $file");
            }
            
            // Look for files that might be templates or include HTML
            $possibleTemplates = [];
            foreach ($phpFiles as $file) {
                $content = file_get_contents($file);
                if (strpos($content, '<!DOCTYPE html>') !== false || 
                    strpos($content, '<html>') !== false || 
                    strpos($content, '<head>') !== false) {
                    $possibleTemplates[] = $file;
                }
            }
            
            if (!empty($possibleTemplates)) {
                output("Found possible template files:");
                foreach ($possibleTemplates as $file) {
                    output("- $file");
                }
                
                // Use the first template file found
                $headerFile = $possibleTemplates[0];
                output("Using: $headerFile");
            } else {
                output("No template files found.");
            }
        } else {
            output("No PHP files found in the admin directory.");
        }
    } else {
        output("Admin directory not found.");
    }
}

// Create a direct include script
$includeScriptPath = '/home/stories/api.storiesfromtheweb.org/admin/admin_fix_include.php';
$includeScriptContent = '<?php
// Admin fix include script
// This script includes the admin form fix JavaScript

// Check if the standalone JavaScript file exists
$jsFile = "/home/stories/api.storiesfromtheweb.org/admin_form_fix.js";
if (file_exists($jsFile)) {
    echo "<script src=\"/admin_form_fix.js\"></script>";
} else {
    // Inline the JavaScript if the file doesn\'t exist
    echo "<script>
// Form submission fix
(function() {
    console.log(\"Admin form submission fix loaded\");
    
    // Function to handle form submissions
    function handleFormSubmission(form) {
        console.log(\"Form submission handler activated\");
        
        // Get the form action
        const action = form.getAttribute(\"action\");
        console.log(\"Form action:\", action);
        
        // Extract the ID from the action URL if present
        let id = null;
        const idMatch = action ? action.match(/id=([0-9]+)/) : null;
        if (idMatch) {
            id = idMatch[1];
            console.log(\"Extracted ID:\", id);
        }
        
        // Determine the API endpoint and method
        let endpoint = \"\";
        let method = \"POST\";
        
        if (action && action.includes(\"action=edit\") && id) {
            // This is an edit form
            endpoint = \"/api/v1/stories/\" + id;
            method = \"PUT\";
        } else if (action && action.includes(\"action=create\")) {
            // This is a create form
            endpoint = \"/api/v1/stories\";
            method = \"POST\";
        } else {
            // Try to determine from the URL
            const url = window.location.href;
            const urlIdMatch = url.match(/id=([0-9]+)/);
            
            if (urlIdMatch) {
                id = urlIdMatch[1];
                console.log(\"Extracted ID from URL:\", id);
                endpoint = \"/api/v1/stories/\" + id;
                method = \"PUT\";
            } else if (url.includes(\"action=create\")) {
                endpoint = \"/api/v1/stories\";
                method = \"POST\";
            } else {
                // Default to stories endpoint
                endpoint = \"/api/v1/stories\";
                method = \"POST\";
            }
        }
        
        console.log(\"API endpoint:\", endpoint);
        console.log(\"HTTP method:\", method);
        
        // Get form fields
        const formData = new FormData(form);
        const formObject = {};
        formData.forEach((value, key) => {
            formObject[key] = value;
        });
        
        console.log(\"Form data:\", formObject);
        
        // Show loading message
        const loadingElement = document.querySelector(\".loading-message\");
        if (loadingElement) {
            loadingElement.style.display = \"block\";
        }
        
        // Make the API call
        fetch(endpoint, {
            method: method,
            headers: {
                \"Content-Type\": \"application/json\"
            },
            body: JSON.stringify(formObject)
        })
            .then(response => {
                console.log(\"API response:\", response);
                return response.text();
            })
            .then(text => {
                console.log(\"Response text:\", text);
                
                try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);
                    console.log(\"Parsed JSON:\", data);
                    
                    // Hide loading message
                    if (loadingElement) {
                        loadingElement.style.display = \"none\";
                    }
                    
                    // Show success message
                    alert(\"Changes saved successfully!\");
                    
                    // Redirect to the list page or reload
                    if (action && action.includes(\"action=create\")) {
                        // Redirect to the list page
                        window.location.href = window.location.href.replace(\"action=create\", \"\");
                    } else {
                        // Reload the current page
                        window.location.reload();
                    }
                } catch (e) {
                    console.error(\"Error parsing JSON:\", e);
                    
                    // Hide loading message
                    if (loadingElement) {
                        loadingElement.style.display = \"none\";
                    }
                    
                    // Show error message
                    alert(\"Error saving changes: \" + e.message + \"\\n\\nResponse: \" + text);
                }
            })
            .catch(error => {
                console.error(\"API error:\", error);
                
                // Hide loading message
                if (loadingElement) {
                    loadingElement.style.display = \"none\";
                }
                
                // Show error message
                alert(\"Error saving changes: \" + error.message);
            });
        
        // Prevent the default form submission
        return false;
    }
    
    // Find all forms and add the submission handler
    document.addEventListener(\"DOMContentLoaded\", function() {
        console.log(\"DOM loaded, finding forms\");
        
        const forms = document.querySelectorAll(\"form\");
        console.log(\"Found \" + forms.length + \" forms\");
        
        forms.forEach((form, index) => {
            console.log(\"Processing form \" + index);
            
            // Add submit event listener
            form.addEventListener(\"submit\", function(event) {
                console.log(\"Form submit event triggered\");
                event.preventDefault();
                
                return handleFormSubmission(form);
            });
            
            console.log(\"Added submit event listener to form \" + index);
        });
    });
    
    // Also handle forms that might be loaded after DOMContentLoaded
    const originalSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function() {
        console.log(\"Form submit method called\");
        
        // Try to handle the submission
        if (handleFormSubmission(this) === false) {
            console.log(\"Form submission handled by our code\");
            return false;
        }
        
        // Fall back to the original submit method
        console.log(\"Falling back to original submit method\");
        return originalSubmit.apply(this, arguments);
    };
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
$htaccessPath = '/home/stories/api.storiesfromtheweb.org/admin/.htaccess';
$htaccessContent = '# Auto-prepend the admin fix include script
php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/admin_fix_include.php"
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
        $newHtaccess = preg_replace('/php_value\s+auto_prepend_file\s+.*/', 'php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/admin_fix_include.php"', $currentHtaccess);
        
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

// Create a direct JavaScript include in the admin directory
$directJsPath = '/home/stories/api.storiesfromtheweb.org/admin/admin_fix.js';
$directJsContent = '// Form submission fix
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

if (file_put_contents($directJsPath, $directJsContent)) {
    if ($isWeb) output("<div class='success'>Created direct JavaScript file: $directJsPath</div>", true);
    else output("Created direct JavaScript file: $directJsPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create direct JavaScript file</div>", true);
    else output("Error: Failed to create direct JavaScript file");
}

// Create a script tag include file
$scriptTagPath = '/home/stories/api.storiesfromtheweb.org/admin/admin_fix_script.html';
$scriptTagContent = '<script src="/admin/admin_fix.js"></script>';

if (file_put_contents($scriptTagPath, $scriptTagContent)) {
    if ($isWeb) output("<div class='success'>Created script tag file: $scriptTagPath</div>", true);
    else output("Created script tag file: $scriptTagPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create script tag file</div>", true);
    else output("Error: Failed to create script tag file");
}

// Provide instructions for manual inclusion
output("");
output("Manual Inclusion Instructions");
output("---------------------------");
output("If the automatic inclusion doesn't work, you can manually include the JavaScript in the admin interface:");
output("");
output("1. Add the following script tag to the admin interface HTML files (e.g., admin/includes/header.php):");
if ($isWeb) {
    output("<div class='code'>&lt;script src=\"/admin/admin_fix.js\"&gt;&lt;/script&gt;</div>", true);
} else {
    output("<script src=\"/admin/admin_fix.js\"></script>");
}
output("");
output("2. Or include the script tag file using PHP:");
if ($isWeb) {
    output("<div class='code'>&lt;?php include('/home/stories/api.storiesfromtheweb.org/admin/admin_fix_script.html'); ?&gt;</div>", true);
} else {
    output("<?php include('/home/stories/api.storiesfromtheweb.org/admin/admin_fix_script.html'); ?>");
}
output("");
output("3. Or use the auto_prepend_file directive in .htaccess (already attempted):");
if ($isWeb) {
    output("<div class='code'>php_value auto_prepend_file \"/home/stories/api.storiesfromtheweb.org/admin/admin_fix_include.php\"</div>", true);
} else {
    output("php_value auto_prepend_file \"/home/stories/api.storiesfromtheweb.org/admin/admin_fix_include.php\"");
}

output("");
output("Next Steps:");
output("1. Test the admin interface by creating or editing content");
output("2. If issues persist, try the manual inclusion instructions");
output("3. Check the browser console for any JavaScript errors");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}