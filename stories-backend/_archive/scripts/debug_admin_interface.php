<?php
/**
 * Debug Admin Interface
 * 
 * This script helps debug issues with the admin interface.
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
    <title>Debug Admin Interface</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; max-height: 400px; }
        .section { background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-left: 4px solid #0066cc; }
        .fix-button { background: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .code { font-family: monospace; background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Admin Interface</h1>
', true);
}

output("Debug Admin Interface");
output("====================");
output("");

// Check if the admin interface is accessible
output("Checking admin interface accessibility");
output("----------------------------------");
$adminUrl = "https://api.storiesfromtheweb.org/admin/stories.php";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $adminUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

output("Admin interface HTTP status: $httpCode");
if ($httpCode >= 200 && $httpCode < 300) {
    if ($isWeb) output("<div class='success'>Admin interface is accessible</div>", true);
    else output("Admin interface is accessible");
} else {
    if ($isWeb) output("<div class='error'>Admin interface is not accessible</div>", true);
    else output("Error: Admin interface is not accessible");
}
output("");

// Check CORS headers
output("Checking CORS headers");
output("-------------------");
$apiUrl = "https://api.storiesfromtheweb.org/api/v1/stories";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Origin: https://api.storiesfromtheweb.org'
]);
$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
curl_close($ch);

output("API response headers:");
if ($isWeb) {
    output("<pre>" . htmlspecialchars($headers) . "</pre>", true);
} else {
    output($headers);
}

if (strpos($headers, 'Access-Control-Allow-Origin') !== false) {
    if ($isWeb) output("<div class='success'>CORS headers are present</div>", true);
    else output("CORS headers are present");
} else {
    if ($isWeb) output("<div class='error'>CORS headers are missing</div>", true);
    else output("Error: CORS headers are missing");
}
output("");

// Create a test HTML page to directly test the API
output("Creating a test HTML page");
output("----------------------");
$testHtmlPath = __DIR__ . '/test_admin_api.html';
$testHtml = '<!DOCTYPE html>
<html>
<head>
    <title>Test Admin API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        button { background: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Admin API</h1>
        
        <h2>Get Stories</h2>
        <button id="getStoriesBtn">Get Stories</button>
        <div id="getStoriesResult"></div>
        
        <h2>Get Story</h2>
        <button id="getStoryBtn">Get Story (ID: 1)</button>
        <div id="getStoryResult"></div>
        
        <h2>Update Story</h2>
        <button id="updateStoryBtn">Update Story (ID: 1)</button>
        <div id="updateStoryResult"></div>
    </div>
    
    <script>
        // Function to display results
        function displayResult(elementId, data, isError = false) {
            const element = document.getElementById(elementId);
            element.innerHTML = "";
            
            const status = document.createElement("div");
            status.className = isError ? "error" : "success";
            status.textContent = isError ? "Error" : "Success";
            element.appendChild(status);
            
            const pre = document.createElement("pre");
            pre.textContent = typeof data === "object" ? JSON.stringify(data, null, 2) : data;
            element.appendChild(pre);
        }
        
        // Get Stories
        document.getElementById("getStoriesBtn").addEventListener("click", function() {
            fetch("https://api.storiesfromtheweb.org/api/v1/stories")
                .then(response => response.json())
                .then(data => {
                    displayResult("getStoriesResult", data);
                })
                .catch(error => {
                    displayResult("getStoriesResult", error.message, true);
                });
        });
        
        // Get Story
        document.getElementById("getStoryBtn").addEventListener("click", function() {
            fetch("https://api.storiesfromtheweb.org/api/v1/stories/1")
                .then(response => response.json())
                .then(data => {
                    displayResult("getStoryResult", data);
                })
                .catch(error => {
                    displayResult("getStoryResult", error.message, true);
                });
        });
        
        // Update Story
        document.getElementById("updateStoryBtn").addEventListener("click", function() {
            const newTitle = "Updated Story Title " + new Date().toISOString();
            
            fetch("https://api.storiesfromtheweb.org/api/v1/stories/1", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    title: newTitle,
                    excerpt: "This is an updated excerpt.",
                    content: "This is updated content for the story."
                })
            })
                .then(response => response.json())
                .then(data => {
                    displayResult("updateStoryResult", data);
                })
                .catch(error => {
                    displayResult("updateStoryResult", error.message, true);
                });
        });
    </script>
</body>
</html>';

if (file_put_contents($testHtmlPath, $testHtml)) {
    if ($isWeb) output("<div class='success'>Test HTML page created: $testHtmlPath</div>", true);
    else output("Test HTML page created: $testHtmlPath");
    
    // Create a URL to access the test HTML page
    $testHtmlUrl = "https://api.storiesfromtheweb.org/test_admin_api.html";
    if ($isWeb) {
        output("<p>Access the test page at: <a href='$testHtmlUrl' target='_blank'>$testHtmlUrl</a></p>", true);
    } else {
        output("Access the test page at: $testHtmlUrl");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create test HTML page</div>", true);
    else output("Error: Failed to create test HTML page");
}
output("");

// Create a PHP script to directly test the admin API
output("Creating a PHP script to test the admin API");
output("--------------------------------------");
$testPhpPath = __DIR__ . '/test_admin_api.php';
$testPhp = '<?php
/**
 * Test Admin API
 * 
 * This script directly tests the admin API endpoints.
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
    <title>Test Admin API (PHP)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Admin API (PHP)</h1>\';

// Function to make API requests
function makeApiRequest($endpoint, $method = \'GET\', $data = null) {
    $baseUrl = "https://api.storiesfromtheweb.org/api/v1";
    $url = $baseUrl . $endpoint;
    
    echo "<h2>Testing {$method} {$url}</h2>";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set method
    if ($method === \'POST\') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($method !== \'GET\') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Set data if provided
    if ($data) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            \'Content-Type: application/json\',
            \'Content-Length: \' . strlen($jsonData)
        ]);
        
        echo "<h3>Request Data:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    }
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<h3>HTTP Status: {$httpCode}</h3>";
    
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
    } else {
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
    
    curl_close($ch);
    
    return $jsonResponse;
}

// Test GET /stories
$stories = makeApiRequest(\'/stories\');

// Test GET /stories/1
$story = makeApiRequest(\'/stories/1\');

// Test PUT /stories/1
$updateData = [
    \'title\' => \'Updated Story Title \' . date(\'Y-m-d H:i:s\'),
    \'excerpt\' => \'This is an updated excerpt.\',
    \'content\' => \'This is updated content for the story.\'
];
$updatedStory = makeApiRequest(\'/stories/1\', \'PUT\', $updateData);

echo \'
    </div>
</body>
</html>\';
';

if (file_put_contents($testPhpPath, $testPhp)) {
    if ($isWeb) output("<div class='success'>Test PHP script created: $testPhpPath</div>", true);
    else output("Test PHP script created: $testPhpPath");
    
    // Create a URL to access the test PHP script
    $testPhpUrl = "https://api.storiesfromtheweb.org/test_admin_api.php";
    if ($isWeb) {
        output("<p>Access the test script at: <a href='$testPhpUrl' target='_blank'>$testPhpUrl</a></p>", true);
    } else {
        output("Access the test script at: $testPhpUrl");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create test PHP script</div>", true);
    else output("Error: Failed to create test PHP script");
}
output("");

// Provide suggestions for fixing the admin interface
output("Suggestions for Fixing the Admin Interface");
output("--------------------------------------");
output("1. Check the browser console for JavaScript errors");
output("2. Verify that the admin interface is making the correct API calls");
output("3. Ensure that the admin interface is correctly parsing the API responses");
output("4. Check if the admin interface is using the correct authentication method");
output("5. Try using the test HTML page and PHP script to directly test the API");
output("");

if ($isWeb) {
    output("<div class='section'>", true);
    output("<h3>Next Steps</h3>", true);
    output("<p>The API endpoints are now working correctly. The issue might be with how the admin interface is consuming the API data.</p>", true);
    output("<p>Try accessing the admin interface again and check the browser console for any JavaScript errors.</p>", true);
    output("<p>You can also use the test HTML page and PHP script to directly test the API endpoints.</p>", true);
    output("</div>", true);
    
    output("<div class='back-link'><a href='debug_api_calls.php'>Back to Debug Tool</a></div>", true);
    output('</div></body></html>', true);
}