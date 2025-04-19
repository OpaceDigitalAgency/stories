<?php
/**
 * Remove Processing Message
 * 
 * This script directly modifies the admin interface to remove the "Processing your request..." message
 * and replace the form submission with a direct API call.
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
    <title>Remove Processing Message</title>
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
        <h1>Remove Processing Message</h1>
', true);
}

output("Remove Processing Message");
output("======================");
output("");

// Create a direct JavaScript file to remove the processing message
$directJsPath = '/home/stories/api.storiesfromtheweb.org/admin/remove_processing.js';
$directJsContent = '// Remove Processing Message
(function() {
    console.log("Remove processing message script loaded");
    
    // Function to remove the processing message
    function removeProcessingMessage() {
        console.log("Removing processing message");
        
        // Find all elements with "Processing your request..." text
        const processingElements = [];
        const allElements = document.querySelectorAll("*");
        
        for (let i = 0; i < allElements.length; i++) {
            const element = allElements[i];
            if (element.textContent && element.textContent.includes("Processing your request")) {
                processingElements.push(element);
            }
        }
        
        console.log("Found " + processingElements.length + " processing elements");
        
        // Remove the processing elements
        processingElements.forEach(element => {
            console.log("Removing element:", element);
            element.style.display = "none";
        });
        
        // Also look for any loading spinners or overlays
        const spinners = document.querySelectorAll(".loading, .spinner, .overlay, .modal");
        console.log("Found " + spinners.length + " spinners/overlays");
        
        spinners.forEach(spinner => {
            console.log("Removing spinner:", spinner);
            spinner.style.display = "none";
        });
    }
    
    // Function to add a direct save button
    function addDirectSaveButton() {
        console.log("Adding direct save button");
        
        // Find the form
        const form = document.querySelector("form");
        if (!form) {
            console.error("Form not found");
            return;
        }
        
        // Create the direct save button
        const saveButton = document.createElement("button");
        saveButton.textContent = "Save Changes (Direct)";
        saveButton.type = "button"; // Prevent form submission
        saveButton.style.backgroundColor = "#4CAF50";
        saveButton.style.color = "white";
        saveButton.style.padding = "10px 20px";
        saveButton.style.border = "none";
        saveButton.style.borderRadius = "4px";
        saveButton.style.cursor = "pointer";
        saveButton.style.marginTop = "20px";
        
        // Add click event listener
        saveButton.addEventListener("click", function() {
            console.log("Direct save button clicked");
            
            // Get the current URL
            const url = window.location.href;
            console.log("Current URL:", url);
            
            // Extract the ID from the URL
            let id = null;
            const idMatch = url.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log("Extracted ID:", id);
            } else {
                console.error("Could not extract ID from URL");
                alert("Error: Could not extract ID from URL");
                return;
            }
            
            // Get form fields
            const formData = new FormData(form);
            const formObject = {};
            formData.forEach((value, key) => {
                formObject[key] = value;
            });
            
            console.log("Form data:", formObject);
            
            // Show loading message
            const loadingMessage = document.createElement("div");
            loadingMessage.textContent = "Saving changes...";
            loadingMessage.style.position = "fixed";
            loadingMessage.style.top = "50%";
            loadingMessage.style.left = "50%";
            loadingMessage.style.transform = "translate(-50%, -50%)";
            loadingMessage.style.padding = "20px";
            loadingMessage.style.backgroundColor = "#f9f9f9";
            loadingMessage.style.border = "1px solid #ddd";
            loadingMessage.style.borderRadius = "4px";
            loadingMessage.style.zIndex = "1001";
            document.body.appendChild(loadingMessage);
            
            // Make a direct API call
            fetch("/api/v1/stories/" + id, {
                method: "PUT",
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
                        
                        // Remove loading message
                        document.body.removeChild(loadingMessage);
                        
                        // Show success message
                        alert("Changes saved successfully!");
                        
                        // Reload the page
                        window.location.reload();
                    } catch (e) {
                        console.error("Error parsing JSON:", e);
                        
                        // Remove loading message
                        document.body.removeChild(loadingMessage);
                        
                        // Show error message
                        alert("Error saving changes: " + e.message + "\\n\\nResponse: " + text);
                    }
                })
                .catch(error => {
                    console.error("API error:", error);
                    
                    // Remove loading message
                    document.body.removeChild(loadingMessage);
                    
                    // Show error message
                    alert("Error saving changes: " + error.message);
                });
        });
        
        // Add the button to the form
        form.appendChild(saveButton);
        console.log("Direct save button added");
    }
    
    // Function to disable the original form submission
    function disableOriginalFormSubmission() {
        console.log("Disabling original form submission");
        
        // Find the form
        const form = document.querySelector("form");
        if (!form) {
            console.error("Form not found");
            return;
        }
        
        // Override the submit event
        form.addEventListener("submit", function(event) {
            console.log("Form submit event triggered");
            event.preventDefault();
            
            // Show a message to use the direct save button
            alert("Please use the \'Save Changes (Direct)\' button to save your changes.");
            
            return false;
        });
        
        console.log("Original form submission disabled");
    }
    
    // Run on page load
    document.addEventListener("DOMContentLoaded", function() {
        console.log("DOM loaded");
        
        // Remove the processing message
        removeProcessingMessage();
        
        // Add the direct save button
        addDirectSaveButton();
        
        // Disable the original form submission
        disableOriginalFormSubmission();
    });
    
    // Also run immediately in case the page is already loaded
    removeProcessingMessage();
    addDirectSaveButton();
    disableOriginalFormSubmission();
    
    // Set up a periodic check to remove the processing message
    setInterval(removeProcessingMessage, 1000);
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
$scriptTagPath = '/home/stories/api.storiesfromtheweb.org/admin/remove_processing_script.html';
$scriptTagContent = '<script src="/admin/remove_processing.js"></script>';

if (file_put_contents($scriptTagPath, $scriptTagContent)) {
    if ($isWeb) output("<div class='success'>Created script tag file: $scriptTagPath</div>", true);
    else output("Created script tag file: $scriptTagPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create script tag file</div>", true);
    else output("Error: Failed to create script tag file");
}

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
    }
}

if (file_exists($headerFile)) {
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
    if (strpos($headerContent, 'remove_processing.js') !== false) {
        if ($isWeb) output("<div class='warning'>Script tag already exists in header file</div>", true);
        else output("Warning: Script tag already exists in header file");
    } else {
        // Find the closing </head> tag
        $headPos = strpos($headerContent, '</head>');
        if ($headPos !== false) {
            // Insert the script tag before the closing </head> tag
            $newHeaderContent = substr($headerContent, 0, $headPos);
            $newHeaderContent .= "\n<!-- Remove processing message script -->\n<script src=\"/admin/remove_processing.js\"></script>\n";
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
                $newHeaderContent .= "\n<!-- Remove processing message script -->\n<script src=\"/admin/remove_processing.js\"></script>\n";
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
                $newHeaderContent = $headerContent . "\n<!-- Remove processing message script -->\n<script src=\"/admin/remove_processing.js\"></script>\n";
                
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
}

// Create a direct include script
$includeScriptPath = '/home/stories/api.storiesfromtheweb.org/admin/remove_processing_include.php';
$includeScriptContent = '<?php
// Remove processing message include script
// This script includes the remove processing message JavaScript

// Check if the JavaScript file exists
$jsFile = "/home/stories/api.storiesfromtheweb.org/admin/remove_processing.js";
if (file_exists($jsFile)) {
    echo "<script src=\"/admin/remove_processing.js\"></script>";
} else {
    // Inline the JavaScript if the file doesn\'t exist
    echo "<script>
// Remove Processing Message
(function() {
    console.log(\"Remove processing message script loaded\");
    
    // Function to remove the processing message
    function removeProcessingMessage() {
        console.log(\"Removing processing message\");
        
        // Find all elements with \"Processing your request...\" text
        const processingElements = [];
        const allElements = document.querySelectorAll(\"*\");
        
        for (let i = 0; i < allElements.length; i++) {
            const element = allElements[i];
            if (element.textContent && element.textContent.includes(\"Processing your request\")) {
                processingElements.push(element);
            }
        }
        
        console.log(\"Found \" + processingElements.length + \" processing elements\");
        
        // Remove the processing elements
        processingElements.forEach(element => {
            console.log(\"Removing element:\", element);
            element.style.display = \"none\";
        });
        
        // Also look for any loading spinners or overlays
        const spinners = document.querySelectorAll(\".loading, .spinner, .overlay, .modal\");
        console.log(\"Found \" + spinners.length + \" spinners/overlays\");
        
        spinners.forEach(spinner => {
            console.log(\"Removing spinner:\", spinner);
            spinner.style.display = \"none\";
        });
    }
    
    // Function to add a direct save button
    function addDirectSaveButton() {
        console.log(\"Adding direct save button\");
        
        // Find the form
        const form = document.querySelector(\"form\");
        if (!form) {
            console.error(\"Form not found\");
            return;
        }
        
        // Create the direct save button
        const saveButton = document.createElement(\"button\");
        saveButton.textContent = \"Save Changes (Direct)\";
        saveButton.type = \"button\"; // Prevent form submission
        saveButton.style.backgroundColor = \"#4CAF50\";
        saveButton.style.color = \"white\";
        saveButton.style.padding = \"10px 20px\";
        saveButton.style.border = \"none\";
        saveButton.style.borderRadius = \"4px\";
        saveButton.style.cursor = \"pointer\";
        saveButton.style.marginTop = \"20px\";
        
        // Add click event listener
        saveButton.addEventListener(\"click\", function() {
            console.log(\"Direct save button clicked\");
            
            // Get the current URL
            const url = window.location.href;
            console.log(\"Current URL:\", url);
            
            // Extract the ID from the URL
            let id = null;
            const idMatch = url.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log(\"Extracted ID:\", id);
            } else {
                console.error(\"Could not extract ID from URL\");
                alert(\"Error: Could not extract ID from URL\");
                return;
            }
            
            // Get form fields
            const formData = new FormData(form);
            const formObject = {};
            formData.forEach((value, key) => {
                formObject[key] = value;
            });
            
            console.log(\"Form data:\", formObject);
            
            // Show loading message
            const loadingMessage = document.createElement(\"div\");
            loadingMessage.textContent = \"Saving changes...\";
            loadingMessage.style.position = \"fixed\";
            loadingMessage.style.top = \"50%\";
            loadingMessage.style.left = \"50%\";
            loadingMessage.style.transform = \"translate(-50%, -50%)\";
            loadingMessage.style.padding = \"20px\";
            loadingMessage.style.backgroundColor = \"#f9f9f9\";
            loadingMessage.style.border = \"1px solid #ddd\";
            loadingMessage.style.borderRadius = \"4px\";
            loadingMessage.style.zIndex = \"1001\";
            document.body.appendChild(loadingMessage);
            
            // Make a direct API call
            fetch(\"/api/v1/stories/\" + id, {
                method: \"PUT\",
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
                        
                        // Remove loading message
                        document.body.removeChild(loadingMessage);
                        
                        // Show success message
                        alert(\"Changes saved successfully!\");
                        
                        // Reload the page
                        window.location.reload();
                    } catch (e) {
                        console.error(\"Error parsing JSON:\", e);
                        
                        // Remove loading message
                        document.body.removeChild(loadingMessage);
                        
                        // Show error message
                        alert(\"Error saving changes: \" + e.message + \"\\n\\nResponse: \" + text);
                    }
                })
                .catch(error => {
                    console.error(\"API error:\", error);
                    
                    // Remove loading message
                    document.body.removeChild(loadingMessage);
                    
                    // Show error message
                    alert(\"Error saving changes: \" + error.message);
                });
        });
        
        // Add the button to the form
        form.appendChild(saveButton);
        console.log(\"Direct save button added\");
    }
    
    // Function to disable the original form submission
    function disableOriginalFormSubmission() {
        console.log(\"Disabling original form submission\");
        
        // Find the form
        const form = document.querySelector(\"form\");
        if (!form) {
            console.error(\"Form not found\");
            return;
        }
        
        // Override the submit event
        form.addEventListener(\"submit\", function(event) {
            console.log(\"Form submit event triggered\");
            event.preventDefault();
            
            // Show a message to use the direct save button
            alert(\"Please use the \'Save Changes (Direct)\' button to save your changes.\");
            
            return false;
        });
        
        console.log(\"Original form submission disabled\");
    }
    
    // Run on page load
    document.addEventListener(\"DOMContentLoaded\", function() {
        console.log(\"DOM loaded\");
        
        // Remove the processing message
        removeProcessingMessage();
        
        // Add the direct save button
        addDirectSaveButton();
        
        // Disable the original form submission
        disableOriginalFormSubmission();
    });
    
    // Also run immediately in case the page is already loaded
    removeProcessingMessage();
    addDirectSaveButton();
    disableOriginalFormSubmission();
    
    // Set up a periodic check to remove the processing message
    setInterval(removeProcessingMessage, 1000);
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
$htaccessContent = '# Auto-prepend the remove processing message include script
php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/remove_processing_include.php"
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
        $newHtaccess = preg_replace('/php_value\s+auto_prepend_file\s+.*/', 'php_value auto_prepend_file "/home/stories/api.storiesfromtheweb.org/admin/remove_processing_include.php"', $currentHtaccess);
        
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
output("2. The processing message should be removed and a direct save button should be added");
output("3. Use the direct save button to save your changes");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}