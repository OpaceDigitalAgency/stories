<?php
/**
 * Simplify Admin Forms
 * 
 * This script modifies the existing admin forms to use standard HTML form submission
 * without JavaScript interference.
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
    <title>Simplify Admin Forms</title>
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
        <h1>Simplify Admin Forms</h1>
', true);
}

output("Simplify Admin Forms");
output("==================");
output("");

// Define the admin pages to modify
$adminPages = [
    'stories.php',
    'authors.php',
    'tags.php',
    'blog-posts.php',
    'games.php',
    'directory-items.php',
    'ai-tools.php'
];

// Process each admin page
foreach ($adminPages as $page) {
    $pagePath = __DIR__ . '/admin/' . $page;
    
    if (!file_exists($pagePath)) {
        // Try to find the page in the server path
        $serverPath = '/home/stories/api.storiesfromtheweb.org/admin/' . $page;
        if (file_exists($serverPath)) {
            $pagePath = $serverPath;
        } else {
            if ($isWeb) output("<div class='warning'>Page not found: $page</div>", true);
            else output("Warning: Page not found: $page");
            continue;
        }
    }
    
    output("Processing page: $page");
    
    // Backup the original file
    $backupFile = $pagePath . '.bak.' . date('YmdHis');
    if (!copy($pagePath, $backupFile)) {
        if ($isWeb) output("<div class='warning'>Failed to create backup of $page</div>", true);
        else output("Warning: Failed to create backup of $page");
    } else {
        output("Backup created: $backupFile");
    }
    
    // Read the file content
    $content = file_get_contents($pagePath);
    
    // Modify the form to use standard HTML form submission
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
    $modifiedContent = preg_replace('/<form([^>]*)action="[^"]*"([^>]*)>/', '<form$1action="/admin/direct_save.php?type=' . $contentType . '"$2>', $modifiedContent);
    
    // Write the modified content back to the file
    if (file_put_contents($pagePath, $modifiedContent)) {
        if ($isWeb) output("<div class='success'>Successfully modified $page</div>", true);
        else output("Successfully modified $page");
    } else {
        if ($isWeb) output("<div class='error'>Failed to modify $page</div>", true);
        else output("Error: Failed to modify $page");
    }
}

// Create a direct save script
$directSavePath = __DIR__ . '/admin/direct_save.php';
$directSaveContent = '<?php
/**
 * Direct Save Script
 * 
 * This script handles direct form submissions from the admin interface.
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
    header(\'Location: /admin/\' . $contentType . \'.php\');
    exit;
} else {
    // Display the error
    echo "<h1>Error</h1>";
    echo "<p>Failed to save data. HTTP status code: $httpCode</p>";
    echo "<pre>$response</pre>";
    echo "<p><a href=\'/admin/\' . $contentType . \'.php\'>Back to list</a></p>";
}
';

if (file_put_contents($directSavePath, $directSaveContent)) {
    if ($isWeb) output("<div class='success'>Created direct save script: $directSavePath</div>", true);
    else output("Created direct save script: $directSavePath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create direct save script</div>", true);
    else output("Error: Failed to create direct save script");
}

// Create a script to disable JavaScript in the admin interface
$disableJsPath = __DIR__ . '/admin/assets/js/disable_js.js';
$disableJsContent = '// Disable JavaScript form handling
document.addEventListener("DOMContentLoaded", function() {
    // Find all forms
    var forms = document.querySelectorAll("form");
    
    // Remove any event listeners from the forms
    forms.forEach(function(form) {
        var clone = form.cloneNode(true);
        form.parentNode.replaceChild(clone, form);
    });
    
    // Find all submit buttons
    var submitButtons = document.querySelectorAll("button[type=\'submit\']");
    
    // Remove any event listeners from the submit buttons
    submitButtons.forEach(function(button) {
        var clone = button.cloneNode(true);
        button.parentNode.replaceChild(clone, button);
    });
    
    // Remove the loading overlay
    var loadingOverlay = document.querySelector(".loading-overlay");
    if (loadingOverlay) {
        loadingOverlay.style.display = "none";
    }
    
    // Remove any processing messages
    var processingMessages = document.querySelectorAll(".mt-2.loading-message");
    processingMessages.forEach(function(message) {
        message.style.display = "none";
    });
    
    console.log("JavaScript form handling disabled");
});';

if (file_put_contents($disableJsPath, $disableJsContent)) {
    if ($isWeb) output("<div class='success'>Created disable JavaScript script: $disableJsPath</div>", true);
    else output("Created disable JavaScript script: $disableJsPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create disable JavaScript script</div>", true);
    else output("Error: Failed to create disable JavaScript script");
}

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
    }
}

if (file_exists($footerFile)) {
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
    
    // Check if the disable JavaScript script is already included
    if (strpos($footerContent, 'disable_js.js') !== false) {
        if ($isWeb) output("<div class='warning'>Disable JavaScript script already included in footer file</div>", true);
        else output("Warning: Disable JavaScript script already included in footer file");
    } else {
        // Find the closing </body> tag
        $bodyPos = strpos($footerContent, '</body>');
        if ($bodyPos !== false) {
            // Insert the script tag before the closing </body> tag
            $newFooterContent = substr($footerContent, 0, $bodyPos);
            $newFooterContent .= "\n<!-- Disable JavaScript script -->\n<script src=\"/admin/assets/js/disable_js.js\"></script>\n";
            $newFooterContent .= substr($footerContent, $bodyPos);
            
            if (file_put_contents($footerFile, $newFooterContent)) {
                if ($isWeb) output("<div class='success'>Added disable JavaScript script to footer file</div>", true);
                else output("Added disable JavaScript script to footer file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
                else output("Error: Failed to update footer file");
            }
        } else {
            // Append the script tag to the end of the file
            $newFooterContent = $footerContent . "\n<!-- Disable JavaScript script -->\n<script src=\"/admin/assets/js/disable_js.js\"></script>\n";
            
            if (file_put_contents($footerFile, $newFooterContent)) {
                if ($isWeb) output("<div class='success'>Added disable JavaScript script to the end of footer file</div>", true);
                else output("Added disable JavaScript script to the end of footer file");
            } else {
                if ($isWeb) output("<div class='error'>Failed to update footer file</div>", true);
                else output("Error: Failed to update footer file");
            }
        }
    }
}

output("");
output("Next Steps:");
output("1. Test the admin interface by creating or editing content");
output("2. The forms should now use standard HTML form submission without JavaScript interference");
output("3. If you encounter any issues, check the browser console for error messages");

if ($isWeb) {
    output("<div class='back-link'><a href='javascript:history.back()'>Back</a></div>", true);
    output('</div></body></html>', true);
}