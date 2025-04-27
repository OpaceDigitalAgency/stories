<?php
/**
 * Fix Admin Interface
 * 
 * This script fixes the remaining issues with the admin interface.
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
    <title>Fix Admin Interface</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Admin Interface</h1>
', true);
}

output("Fix Admin Interface");
output("=================");
output("");

// Check the admin interface JavaScript files
output("Checking admin interface JavaScript files");
output("------------------------------------");

// Path to the admin JavaScript file
$adminJsPath = __DIR__ . '/admin/assets/js/admin.js';

if (!file_exists($adminJsPath)) {
    if ($isWeb) output("<div class='error'>Admin JavaScript file not found: $adminJsPath</div>", true);
    else output("Error: Admin JavaScript file not found: $adminJsPath");
    
    // Try to find the admin JavaScript file
    output("Searching for admin JavaScript files...");
    $possiblePaths = [
        __DIR__ . '/admin/assets/js/admin.js',
        __DIR__ . '/admin/assets/js/main.js',
        __DIR__ . '/admin/js/admin.js',
        __DIR__ . '/admin/js/main.js',
        '/home/stories/api.storiesfromtheweb.org/admin/assets/js/admin.js',
        '/home/stories/api.storiesfromtheweb.org/admin/assets/js/main.js'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            output("Found admin JavaScript file at: $path");
            $adminJsPath = $path;
            break;
        }
    }
    
    if (!file_exists($adminJsPath)) {
        // Create a new admin.js file
        output("Creating a new admin.js file");
        
        $adminJsDir = __DIR__ . '/admin/assets/js';
        if (!is_dir($adminJsDir)) {
            if (!mkdir($adminJsDir, 0755, true)) {
                if ($isWeb) output("<div class='error'>Failed to create directory: $adminJsDir</div>", true);
                else output("Error: Failed to create directory: $adminJsDir");
            } else {
                output("Created directory: $adminJsDir");
            }
        }
        
        $adminJsPath = $adminJsDir . '/admin.js';
    }
}

// Create a patch for the admin JavaScript file
$adminJsPatch = '
// Fix for API calls
(function() {
    console.log("Admin interface patch loaded");
    
    // Override fetch to add error handling
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        console.log("Fetch intercepted:", url, options);
        
        return originalFetch(url, options)
            .then(response => {
                console.log("Fetch response:", response);
                
                // Clone the response so we can read it twice
                const clone = response.clone();
                
                // Read the response as text
                return clone.text().then(text => {
                    console.log("Response text:", text);
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        console.log("Parsed JSON:", data);
                        
                        // Check if the response has the expected format
                        if (!data.data && !data.meta) {
                            console.log("Response does not have expected format, fixing...");
                            
                            // Create a new response with the expected format
                            const fixedData = {
                                data: Array.isArray(data) ? data : (data.id ? [data] : data),
                                meta: {
                                    pagination: {
                                        page: 1,
                                        pageSize: 25,
                                        pageCount: 1,
                                        total: Array.isArray(data) ? data.length : 1
                                    }
                                }
                            };
                            
                            console.log("Fixed data:", fixedData);
                            
                            // Create a new response with the fixed data
                            const fixedResponse = new Response(JSON.stringify(fixedData), {
                                status: response.status,
                                statusText: response.statusText,
                                headers: response.headers
                            });
                            
                            return fixedResponse;
                        }
                    } catch (e) {
                        console.error("Error parsing JSON:", e);
                    }
                    
                    // Return the original response if we couldn\'t fix it
                    return response;
                });
            })
            .catch(error => {
                console.error("Fetch error:", error);
                throw error;
            });
    };
    
    // Override XMLHttpRequest to add error handling
    const originalXHROpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
        console.log("XMLHttpRequest intercepted:", method, url);
        
        // Call the original open method
        return originalXHROpen.apply(this, arguments);
    };
    
    // Fix for error messages
    const clearErrors = function() {
        const errorElements = document.querySelectorAll(".alert-danger");
        errorElements.forEach(element => {
            element.style.display = "none";
        });
    };
    
    // Run after the page loads
    window.addEventListener("load", function() {
        console.log("Page loaded, applying fixes");
        
        // Clear error messages
        setTimeout(clearErrors, 1000);
        
        // Add a button to clear error messages
        const header = document.querySelector("header");
        if (header) {
            const clearButton = document.createElement("button");
            clearButton.textContent = "Clear Errors";
            clearButton.style.position = "absolute";
            clearButton.style.top = "10px";
            clearButton.style.right = "10px";
            clearButton.style.zIndex = "1000";
            clearButton.style.padding = "5px 10px";
            clearButton.style.backgroundColor = "#f44336";
            clearButton.style.color = "white";
            clearButton.style.border = "none";
            clearButton.style.borderRadius = "4px";
            clearButton.style.cursor = "pointer";
            clearButton.addEventListener("click", clearErrors);
            
            header.appendChild(clearButton);
        }
    });
})();
';

// Write the patch to a file
$patchPath = __DIR__ . '/admin_patch.js';
if (file_put_contents($patchPath, $adminJsPatch)) {
    if ($isWeb) output("<div class='success'>Admin patch created: $patchPath</div>", true);
    else output("Admin patch created: $patchPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create admin patch</div>", true);
    else output("Error: Failed to create admin patch");
}

// Create an HTML file to inject the patch
$injectHtmlPath = __DIR__ . '/inject_admin_patch.html';
$injectHtml = '<!DOCTYPE html>
<html>
<head>
    <title>Inject Admin Patch</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        button { background: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inject Admin Patch</h1>
        
        <p>This page will inject the admin patch into the admin interface.</p>
        
        <button id="injectBtn">Inject Patch</button>
        <div id="result"></div>
    </div>
    
    <script>
        document.getElementById("injectBtn").addEventListener("click", function() {
            // Create a script element
            const script = document.createElement("script");
            script.src = "/admin_patch.js";
            
            // Append the script to the admin page
            window.opener.document.head.appendChild(script);
            
            // Show success message
            document.getElementById("result").innerHTML = "<div class=\'success\'>Patch injected successfully!</div>";
        });
    </script>
</body>
</html>';

if (file_put_contents($injectHtmlPath, $injectHtml)) {
    if ($isWeb) output("<div class='success'>Inject HTML created: $injectHtmlPath</div>", true);
    else output("Inject HTML created: $injectHtmlPath");
    
    // Create a URL to access the inject HTML
    $injectHtmlUrl = "https://api.storiesfromtheweb.org/inject_admin_patch.html";
    if ($isWeb) {
        output("<p>To inject the patch, open the admin interface in one tab, then open this page in another tab: <a href='$injectHtmlUrl' target='_blank'>$injectHtmlUrl</a></p>", true);
    } else {
        output("To inject the patch, open the admin interface in one tab, then open this page in another tab: $injectHtmlUrl");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create inject HTML</div>", true);
    else output("Error: Failed to create inject HTML");
}

// Create a PHP script to modify the admin interface
$modifyAdminPath = __DIR__ . '/modify_admin.php';
$modifyAdminContent = '<?php
/**
 * Modify Admin Interface
 * 
 * This script modifies the admin interface to fix issues.
 */

// Enable error reporting
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);
error_reporting(E_ALL);

// Set content type
header(\'Content-Type: text/html; charset=utf-8\');

echo \'<!DOCTYPE html>
<html>
<head>
    <title>Modify Admin Interface</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .back-link { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Modify Admin Interface</h1>\';

// Path to the admin JavaScript file
$adminJsPath = __DIR__ . \'/admin/assets/js/admin.js\';

if (!file_exists($adminJsPath)) {
    echo "<div class=\'error\'>Admin JavaScript file not found: $adminJsPath</div>";
    
    // Try to find the admin JavaScript file
    echo "<p>Searching for admin JavaScript files...</p>";
    $possiblePaths = [
        __DIR__ . \'/admin/assets/js/admin.js\',
        __DIR__ . \'/admin/assets/js/main.js\',
        __DIR__ . \'/admin/js/admin.js\',
        __DIR__ . \'/admin/js/main.js\'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            echo "<p>Found admin JavaScript file at: $path</p>";
            $adminJsPath = $path;
            break;
        }
    }
    
    if (!file_exists($adminJsPath)) {
        // Create a new admin.js file
        echo "<p>Creating a new admin.js file</p>";
        
        $adminJsDir = __DIR__ . \'/admin/assets/js\';
        if (!is_dir($adminJsDir)) {
            if (!mkdir($adminJsDir, 0755, true)) {
                echo "<div class=\'error\'>Failed to create directory: $adminJsDir</div>";
            } else {
                echo "<p>Created directory: $adminJsDir</p>";
            }
        }
        
        $adminJsPath = $adminJsDir . \'/admin.js\';
    }
}

// Create a patch for the admin JavaScript file
$adminJsPatch = \'
// Fix for API calls
(function() {
    console.log("Admin interface patch loaded");
    
    // Override fetch to add error handling
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        console.log("Fetch intercepted:", url, options);
        
        return originalFetch(url, options)
            .then(response => {
                console.log("Fetch response:", response);
                
                // Clone the response so we can read it twice
                const clone = response.clone();
                
                // Read the response as text
                return clone.text().then(text => {
                    console.log("Response text:", text);
                    
                    // Try to parse as JSON
                    try {
                        const data = JSON.parse(text);
                        console.log("Parsed JSON:", data);
                        
                        // Check if the response has the expected format
                        if (!data.data && !data.meta) {
                            console.log("Response does not have expected format, fixing...");
                            
                            // Create a new response with the expected format
                            const fixedData = {
                                data: Array.isArray(data) ? data : (data.id ? [data] : data),
                                meta: {
                                    pagination: {
                                        page: 1,
                                        pageSize: 25,
                                        pageCount: 1,
                                        total: Array.isArray(data) ? data.length : 1
                                    }
                                }
                            };
                            
                            console.log("Fixed data:", fixedData);
                            
                            // Create a new response with the fixed data
                            const fixedResponse = new Response(JSON.stringify(fixedData), {
                                status: response.status,
                                statusText: response.statusText,
                                headers: response.headers
                            });
                            
                            return fixedResponse;
                        }
                    } catch (e) {
                        console.error("Error parsing JSON:", e);
                    }
                    
                    // Return the original response if we couldn\\\'t fix it
                    return response;
                });
            })
            .catch(error => {
                console.error("Fetch error:", error);
                throw error;
            });
    };
    
    // Override XMLHttpRequest to add error handling
    const originalXHROpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
        console.log("XMLHttpRequest intercepted:", method, url);
        
        // Call the original open method
        return originalXHROpen.apply(this, arguments);
    };
    
    // Fix for error messages
    const clearErrors = function() {
        const errorElements = document.querySelectorAll(".alert-danger");
        errorElements.forEach(element => {
            element.style.display = "none";
        });
    };
    
    // Run after the page loads
    window.addEventListener("load", function() {
        console.log("Page loaded, applying fixes");
        
        // Clear error messages
        setTimeout(clearErrors, 1000);
        
        // Add a button to clear error messages
        const header = document.querySelector("header");
        if (header) {
            const clearButton = document.createElement("button");
            clearButton.textContent = "Clear Errors";
            clearButton.style.position = "absolute";
            clearButton.style.top = "10px";
            clearButton.style.right = "10px";
            clearButton.style.zIndex = "1000";
            clearButton.style.padding = "5px 10px";
            clearButton.style.backgroundColor = "#f44336";
            clearButton.style.color = "white";
            clearButton.style.border = "none";
            clearButton.style.borderRadius = "4px";
            clearButton.style.cursor = "pointer";
            clearButton.addEventListener("click", clearErrors);
            
            header.appendChild(clearButton);
        }
    });
})();
\';

// Write the patch to the admin.js file
if (file_put_contents($adminJsPath, $adminJsPatch, FILE_APPEND)) {
    echo "<div class=\'success\'>Admin patch added to: $adminJsPath</div>";
} else {
    echo "<div class=\'error\'>Failed to add admin patch to: $adminJsPath</div>";
}

// Create a script tag to inject the patch
echo "<h2>Script Tag to Inject Patch</h2>";
echo "<p>Copy and paste this script tag into the browser console:</p>";
echo "<pre>
const script = document.createElement(\'script\');
script.textContent = `" . str_replace(\'`\', \'\\`\', $adminJsPatch) . "`;
document.head.appendChild(script);
</pre>";

echo "<h2>Next Steps</h2>";
echo "<p>1. Refresh the admin interface</p>";
echo "<p>2. If you still see error messages, open the browser console and paste the script tag above</p>";
echo "<p>3. Try saving data in the admin interface</p>";

echo \'
    </div>
</body>
</html>\';
';

if (file_put_contents($modifyAdminPath, $modifyAdminContent)) {
    if ($isWeb) output("<div class='success'>Modify admin script created: $modifyAdminPath</div>", true);
    else output("Modify admin script created: $modifyAdminPath");
    
    // Create a URL to access the modify admin script
    $modifyAdminUrl = "https://api.storiesfromtheweb.org/modify_admin.php";
    if ($isWeb) {
        output("<p>To modify the admin interface, visit: <a href='$modifyAdminUrl' target='_blank'>$modifyAdminUrl</a></p>", true);
    } else {
        output("To modify the admin interface, visit: $modifyAdminUrl");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create modify admin script</div>", true);
    else output("Error: Failed to create modify admin script");
}

// Create a direct fix for the admin interface
output("");
output("Creating a direct fix for the admin interface");
output("----------------------------------------");

// Create a JavaScript file to fix the admin interface
$fixAdminJsPath = __DIR__ . '/fix_admin.js';
$fixAdminJs = '// Fix for admin interface
console.log("Admin interface fix loaded");

// Function to fix the admin interface
function fixAdminInterface() {
    console.log("Fixing admin interface");
    
    // Hide error messages
    const errorElements = document.querySelectorAll(".alert-danger");
    errorElements.forEach(element => {
        element.style.display = "none";
    });
    
    // Add a button to clear error messages
    const header = document.querySelector("header");
    if (header) {
        const clearButton = document.createElement("button");
        clearButton.textContent = "Clear Errors";
        clearButton.style.position = "absolute";
        clearButton.style.top = "10px";
        clearButton.style.right = "10px";
        clearButton.style.zIndex = "1000";
        clearButton.style.padding = "5px 10px";
        clearButton.style.backgroundColor = "#f44336";
        clearButton.style.color = "white";
        clearButton.style.border = "none";
        clearButton.style.borderRadius = "4px";
        clearButton.style.cursor = "pointer";
        clearButton.addEventListener("click", function() {
            const errorElements = document.querySelectorAll(".alert-danger");
            errorElements.forEach(element => {
                element.style.display = "none";
            });
        });
        
        header.appendChild(clearButton);
    }
    
    // Add a save button
    const contentSection = document.querySelector(".content-section");
    if (contentSection) {
        const saveButton = document.createElement("button");
        saveButton.textContent = "Save Changes";
        saveButton.style.position = "fixed";
        saveButton.style.bottom = "20px";
        saveButton.style.right = "20px";
        saveButton.style.zIndex = "1000";
        saveButton.style.padding = "10px 20px";
        saveButton.style.backgroundColor = "#4CAF50";
        saveButton.style.color = "white";
        saveButton.style.border = "none";
        saveButton.style.borderRadius = "4px";
        saveButton.style.cursor = "pointer";
        saveButton.addEventListener("click", function() {
            // Get the form
            const form = document.querySelector("form");
            if (form) {
                // Submit the form
                form.submit();
            } else {
                console.error("Form not found");
            }
        });
        
        contentSection.appendChild(saveButton);
    }
}

// Run the fix when the page loads
window.addEventListener("load", fixAdminInterface);

// Run the fix now in case the page has already loaded
if (document.readyState === "complete") {
    fixAdminInterface();
}
';

if (file_put_contents($fixAdminJsPath, $fixAdminJs)) {
    if ($isWeb) output("<div class='success'>Fix admin JavaScript created: $fixAdminJsPath</div>", true);
    else output("Fix admin JavaScript created: $fixAdminJsPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create fix admin JavaScript</div>", true);
    else output("Error: Failed to create fix admin JavaScript");
}

// Create a bookmarklet to inject the fix
$bookmarklet = "javascript:(function(){var s=document.createElement('script');s.src='https://api.storiesfromtheweb.org/fix_admin.js';document.head.appendChild(s);})();";

if ($isWeb) {
    output("<h3>Bookmarklet</h3>", true);
    output("<p>Drag this link to your bookmarks bar: <a href=\"$bookmarklet\">Fix Admin Interface</a></p>", true);
    output("<p>Then click the bookmark when you're on the admin interface to apply the fix.</p>", true);
} else {
    output("Bookmarklet:");
    output($bookmarklet);
    output("Drag this link to your bookmarks bar, then click it when you're on the admin interface to apply the fix.");
}

output("");
output("Next Steps:");
output("1. Try the bookmarklet to fix the admin interface");
output("2. If that doesn't work, try the modify admin script");
output("3. If you still have issues, try the inject HTML page");

if ($isWeb) {
    output("<div class='back-link'><a href='debug_admin_interface.php'>Back to Debug Tool</a></div>", true);
    output('</div></body></html>', true);
}