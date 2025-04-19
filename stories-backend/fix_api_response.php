<?php
/**
 * Fix API Response
 * 
 * This script fixes the API response to ensure it returns proper JSON.
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
    <title>Fix API Response</title>
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
        button { background: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix API Response</h1>
', true);
}

output("Fix API Response");
output("===============");
output("");

// Create a proxy script to fix the API response
output("Creating a proxy script to fix the API response");
output("------------------------------------------");

// Create a PHP script to proxy API requests
$proxyApiPath = __DIR__ . '/api_proxy.php';
$proxyApiContent = '<?php
/**
 * API Proxy
 * 
 * This script proxies API requests and ensures proper JSON responses.
 */

// Enable error reporting
ini_set(\'display_errors\', 0);
ini_set(\'display_startup_errors\', 0);
error_reporting(0);

// Set content type to JSON
header(\'Content-Type: application/json\');

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");
header("Access-Control-Expose-Headers: X-Total-Count, X-Pagination-Total-Pages");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

// Get the request URI
$requestUri = $_SERVER[\'REQUEST_URI\'];

// Extract the endpoint from the request URI
$endpoint = str_replace(\'/api_proxy.php\', \'\', $requestUri);

// If no endpoint is specified, use the stories endpoint
if (empty($endpoint) || $endpoint === \'/\') {
    $endpoint = \'/api/v1/stories\';
}

// Build the API URL
$apiUrl = "https://api.storiesfromtheweb.org" . $endpoint;

// Get the request method
$method = $_SERVER[\'REQUEST_METHOD\'];

// Get the request body
$requestBody = file_get_contents(\'php://input\');

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Set the request method
if ($method === \'POST\') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
} else if ($method !== \'GET\') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if (!empty($requestBody)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    }
}

// Set headers
$headers = [];
foreach ($_SERVER as $key => $value) {
    if (substr($key, 0, 5) === \'HTTP_\') {
        $header = str_replace(\' \', \'-\', ucwords(str_replace(\'_\', \' \', strtolower(substr($key, 5)))));
        if ($header !== \'Host\' && $header !== \'Content-Length\') {
            $headers[] = "$header: $value";
        }
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Check if the response is HTML (error page)
$isHtml = strpos($contentType, \'text/html\') !== false || strpos($response, \'<!DOCTYPE html>\') !== false || strpos($response, \'<html>\') !== false;

// If the response is HTML, convert it to a JSON error response
if ($isHtml) {
    // Extract error message from HTML
    $errorMessage = "API Error";
    if (preg_match(\'/<b>(.+?)<\\/b>/\', $response, $matches)) {
        $errorMessage = $matches[1];
    }
    
    // Create a JSON error response
    $jsonResponse = json_encode([
        "error" => true,
        "message" => $errorMessage,
        "status" => $httpCode,
        "original_response" => base64_encode($response)
    ]);
    
    echo $jsonResponse;
} else {
    // Check if the response is valid JSON
    $jsonData = json_decode($response);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Not valid JSON, convert to a JSON error response
        $jsonResponse = json_encode([
            "error" => true,
            "message" => "Invalid JSON response",
            "status" => $httpCode,
            "original_response" => base64_encode($response)
        ]);
        
        echo $jsonResponse;
    } else {
        // Valid JSON, pass it through
        echo $response;
    }
}

curl_close($ch);
';

if (file_put_contents($proxyApiPath, $proxyApiContent)) {
    if ($isWeb) output("<div class='success'>API proxy script created: $proxyApiPath</div>", true);
    else output("API proxy script created: $proxyApiPath");
    
    // Create a URL to access the API proxy
    $proxyApiUrl = "https://api.storiesfromtheweb.org/api_proxy.php";
    if ($isWeb) {
        output("<p>To use the API proxy, replace the API URL with: <code>$proxyApiUrl</code></p>", true);
    } else {
        output("To use the API proxy, replace the API URL with: $proxyApiUrl");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create API proxy script</div>", true);
    else output("Error: Failed to create API proxy script");
}

// Create an updated form test page that uses the proxy
output("");
output("Creating an updated form test page");
output("------------------------------");

// Create an HTML file to test form submission with the proxy
$updatedFormTestPath = __DIR__ . '/form_test_proxy.html';
$updatedFormTestContent = '<!DOCTYPE html>
<html>
<head>
    <title>Form Submission Test (with Proxy)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        form { background: #f9f9f9; padding: 20px; border-radius: 4px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], textarea { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        #result { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Form Submission Test (with Proxy)</h1>
        
        <form id="storyForm">
            <div>
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="Test Story Title">
            </div>
            
            <div>
                <label for="excerpt">Excerpt:</label>
                <textarea id="excerpt" name="excerpt">This is a test excerpt.</textarea>
            </div>
            
            <div>
                <label for="content">Content:</label>
                <textarea id="content" name="content">This is test content for the story.</textarea>
            </div>
            
            <button type="submit">Submit</button>
        </form>
        
        <div id="result"></div>
    </div>
    
    <script>
        document.getElementById("storyForm").addEventListener("submit", function(event) {
            event.preventDefault();
            
            const resultDiv = document.getElementById("result");
            resultDiv.innerHTML = "<h2>Processing...</h2>";
            
            // Get the form data
            const title = document.getElementById("title").value;
            const excerpt = document.getElementById("excerpt").value;
            const content = document.getElementById("content").value;
            
            // Create the data object
            const data = {
                title: title,
                excerpt: excerpt,
                content: content
            };
            
            // Make the API call using the proxy
            fetch("https://api.storiesfromtheweb.org/api_proxy.php/api/v1/stories/1", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
                .then(response => {
                    console.log("API response:", response);
                    return response.json();
                })
                .then(data => {
                    console.log("API data:", data);
                    
                    if (data.error) {
                        resultDiv.innerHTML = "<h2 class=\'error\'>Error!</h2>";
                        resultDiv.innerHTML += "<p>" + data.message + "</p>";
                        
                        if (data.original_response) {
                            resultDiv.innerHTML += "<h3>Original Response:</h3>";
                            resultDiv.innerHTML += "<pre>" + atob(data.original_response) + "</pre>";
                        }
                    } else {
                        resultDiv.innerHTML = "<h2 class=\'success\'>Success!</h2>";
                        resultDiv.innerHTML += "<pre>" + JSON.stringify(data, null, 2) + "</pre>";
                    }
                })
                .catch(error => {
                    console.error("API error:", error);
                    
                    resultDiv.innerHTML = "<h2 class=\'error\'>Error!</h2>";
                    resultDiv.innerHTML += "<p>" + error.message + "</p>";
                });
        });
    </script>
</body>
</html>';

if (file_put_contents($updatedFormTestPath, $updatedFormTestContent)) {
    if ($isWeb) output("<div class='success'>Updated form test page created: $updatedFormTestPath</div>", true);
    else output("Updated form test page created: $updatedFormTestPath");
    
    // Create a URL to access the updated form test page
    $updatedFormTestUrl = "https://api.storiesfromtheweb.org/form_test_proxy.html";
    if ($isWeb) {
        output("<p>To test form submission with the proxy, visit: <a href='$updatedFormTestUrl' target='_blank'>$updatedFormTestUrl</a></p>", true);
    } else {
        output("To test form submission with the proxy, visit: $updatedFormTestUrl");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create updated form test page</div>", true);
    else output("Error: Failed to create updated form test page");
}

// Create an updated direct API call script that uses the proxy
output("");
output("Creating an updated direct API call script");
output("-------------------------------------");

// Create a PHP script to make a direct API call using the proxy
$updatedDirectApiPath = __DIR__ . '/direct_api_call_proxy.php';
$updatedDirectApiContent = '<?php
/**
 * Direct API Call (with Proxy)
 * 
 * This script makes a direct API call to update a story using the proxy.
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
    <title>Direct API Call (with Proxy)</title>
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
        <h1>Direct API Call (with Proxy)</h1>\';

// Get the story ID
$id = isset($_GET[\'id\']) ? (int)$_GET[\'id\'] : 1;

echo "<h2>Updating Story ID: $id</h2>";

// Create the data to update
$data = [
    \'title\' => \'Updated via Direct API Call (Proxy) \' . date(\'Y-m-d H:i:s\'),
    \'excerpt\' => \'This is an updated excerpt.\',
    \'content\' => \'This is updated content for the story.\'
];

echo "<h3>Update Data:</h3>";
echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";

// Make the API call using the proxy
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.storiesfromtheweb.org/api_proxy.php/api/v1/stories/$id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    \'Content-Type: application/json\',
    \'Content-Length: \' . strlen(json_encode($data))
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "<h3>HTTP Status: $httpCode</h3>";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "<div class=\'success\'>Success</div>";
} else {
    echo "<div class=\'error\'>Error</div>";
}

echo "<h3>Response:</h3>";

// Try to parse JSON
$jsonResponse = json_decode($response, true);
if ($jsonResponse !== null) {
    echo "<pre>" . htmlspecialchars(json_encode($jsonResponse, JSON_PRETTY_PRINT)) . "</pre>";
    
    // Check if there was an error
    if (isset($jsonResponse[\'error\']) && $jsonResponse[\'error\'] === true) {
        echo "<h3>Original Response:</h3>";
        echo "<pre>" . htmlspecialchars(base64_decode($jsonResponse[\'original_response\'])) . "</pre>";
    }
} else {
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

curl_close($ch);

echo "<h2>Next Steps</h2>";
echo "<p>1. Check if the story was updated by visiting the admin interface</p>";
echo "<p>2. If the story was updated, the API is working correctly</p>";
echo "<p>3. If the story was not updated, there might be an issue with the API</p>";

echo "<button onclick=\'window.location.reload()\'>Try Again</button>";
echo "<button onclick=\'window.location.href = \"https://api.storiesfromtheweb.org/admin/stories.php\"\'>Go to Admin Interface</button>";

echo \'
    </div>
</body>
</html>\';
';

if (file_put_contents($updatedDirectApiPath, $updatedDirectApiContent)) {
    if ($isWeb) output("<div class='success'>Updated direct API call script created: $updatedDirectApiPath</div>", true);
    else output("Updated direct API call script created: $updatedDirectApiPath");
    
    // Create a URL to access the updated direct API call script
    $updatedDirectApiUrl = "https://api.storiesfromtheweb.org/direct_api_call_proxy.php";
    if ($isWeb) {
        output("<p>To make a direct API call using the proxy, visit: <a href='$updatedDirectApiUrl' target='_blank'>$updatedDirectApiUrl</a></p>", true);
    } else {
        output("To make a direct API call using the proxy, visit: $updatedDirectApiUrl");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create updated direct API call script</div>", true);
    else output("Error: Failed to create updated direct API call script");
}

// Create a JavaScript file to fix the form submission using the proxy
output("");
output("Creating a JavaScript file to fix the form submission using the proxy");
output("----------------------------------------------------------");

// Create a JavaScript file to fix the form submission
$fixFormProxyJsPath = __DIR__ . '/fix_form_proxy.js';
$fixFormProxyJs = '// Fix for form submission using the proxy
console.log("Form submission fix (with proxy) loaded");

// Function to fix the form submission
function fixFormSubmission() {
    console.log("Fixing form submission with proxy");
    
    // Find all forms
    const forms = document.querySelectorAll("form");
    console.log("Found " + forms.length + " forms");
    
    forms.forEach((form, index) => {
        console.log("Processing form " + index);
        
        // Add a submit event listener
        form.addEventListener("submit", function(event) {
            console.log("Form submit event triggered");
            
            // Prevent the default form submission
            event.preventDefault();
            
            // Get the form data
            const formData = new FormData(form);
            const formObject = {};
            formData.forEach((value, key) => {
                formObject[key] = value;
            });
            
            console.log("Form data:", formObject);
            
            // Get the form action
            const action = form.getAttribute("action");
            console.log("Form action:", action);
            
            // Extract the ID from the action URL
            let id = null;
            const idMatch = action.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log("Extracted ID:", id);
            }
            
            // Determine the API endpoint
            let endpoint = "/api/v1/stories";
            if (id) {
                endpoint += "/" + id;
            }
            console.log("API endpoint:", endpoint);
            
            // Make a direct API call using the proxy
            fetch("/api_proxy.php" + endpoint, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formObject)
            })
                .then(response => {
                    console.log("API response:", response);
                    return response.json();
                })
                .then(data => {
                    console.log("API data:", data);
                    
                    if (data.error) {
                        console.error("API error:", data.message);
                        alert("Error saving changes: " + data.message);
                    } else {
                        // Show success message
                        alert("Changes saved successfully!");
                        
                        // Reload the page
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error("API error:", error);
                    
                    // Show error message
                    alert("Error saving changes: " + error.message);
                });
        });
        
        console.log("Submit event listener added to form " + index);
    });
    
    // Add a direct save button
    const saveButton = document.createElement("button");
    saveButton.textContent = "Save Changes (Proxy API)";
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
        console.log("Save button clicked");
        
        // Get the form
        const form = document.querySelector("form");
        if (form) {
            console.log("Form found, triggering submit event");
            
            // Create and dispatch a submit event
            const submitEvent = new Event("submit", { bubbles: true, cancelable: true });
            form.dispatchEvent(submitEvent);
        } else {
            console.error("Form not found");
            alert("Error: Form not found");
        }
    });
    
    document.body.appendChild(saveButton);
    console.log("Save button added");
    
    // Add a direct API call button for debugging
    const debugButton = document.createElement("button");
    debugButton.textContent = "Debug API Call (Proxy)";
    debugButton.style.position = "fixed";
    debugButton.style.bottom = "20px";
    debugButton.style.right = "200px";
    debugButton.style.zIndex = "1000";
    debugButton.style.padding = "10px 20px";
    debugButton.style.backgroundColor = "#2196F3";
    debugButton.style.color = "white";
    debugButton.style.border = "none";
    debugButton.style.borderRadius = "4px";
    debugButton.style.cursor = "pointer";
    debugButton.addEventListener("click", function() {
        console.log("Debug button clicked");
        
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
        
        // Make a direct API call to update the story using the proxy
        fetch("/api_proxy.php/api/v1/stories/" + id, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                title: "Updated via Debug Button (Proxy) " + new Date().toISOString(),
                excerpt: "This is an updated excerpt.",
                content: "This is updated content for the story."
            })
        })
            .then(response => {
                console.log("API response:", response);
                return response.json();
            })
            .then(data => {
                console.log("API data:", data);
                
                if (data.error) {
                    console.error("API error:", data.message);
                    alert("Error making debug API call: " + data.message);
                    
                    if (data.original_response) {
                        console.error("Original response:", atob(data.original_response));
                    }
                } else {
                    // Show success message
                    alert("Debug API call successful!");
                    
                    // Reload the page
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error("API error:", error);
                
                // Show error message
                alert("Error making debug API call: " + error.message);
            });
    });
    
    document.body.appendChild(debugButton);
    console.log("Debug button added");
}

// Run the fix when the page loads
window.addEventListener("load", fixFormSubmission);

// Run the fix now in case the page has already loaded
if (document.readyState === "complete") {
    fixFormSubmission();
}
';

if (file_put_contents($fixFormProxyJsPath, $fixFormProxyJs)) {
    if ($isWeb) output("<div class='success'>Fix form JavaScript (with proxy) created: $fixFormProxyJsPath</div>", true);
    else output("Fix form JavaScript (with proxy) created: $fixFormProxyJsPath");
} else {
    if ($isWeb) output("<div class='error'>Failed to create fix form JavaScript (with proxy)</div>", true);
    else output("Error: Failed to create fix form JavaScript (with proxy)");
}

// Create a bookmarklet to inject the fix
$bookmarklet = "javascript:(function(){var s=document.createElement('script');s.src='https://api.storiesfromtheweb.org/fix_form_proxy.js';document.head.appendChild(s);})();";

if ($isWeb) {
    output("<h3>Bookmarklet (with Proxy)</h3>", true);
    output("<p>Drag this link to your bookmarks bar: <a href=\"$bookmarklet\">Fix Form Submission (Proxy)</a></p>", true);
    output("<p>Then click the bookmark when you're on the admin interface to apply the fix.</p>", true);
} else {
    output("Bookmarklet (with Proxy):");
    output($bookmarklet);
    output("Drag this link to your bookmarks bar, then click it when you're on the admin interface to apply the fix.");
}

output("");
output("Next Steps:");
output("1. Try the updated form test page with the proxy");
output("2. If that works, try the bookmarklet with the proxy");
output("3. If you still have issues, try the updated direct API call script with the proxy");

if ($isWeb) {
    output("<div class='back-link'><a href='debug_admin_interface.php'>Back to Debug Tool</a></div>", true);
    output('</div></body></html>', true);
}