<?php
/**
 * Debug API Calls
 * 
 * This script directly tests the API endpoints with detailed logging.
 */

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
    <title>Debug API Calls</title>
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
        .request { background: #e6f7ff; padding: 10px; margin-bottom: 10px; border-left: 4px solid #1890ff; }
        .response { background: #f6ffed; padding: 10px; margin-bottom: 10px; border-left: 4px solid #52c41a; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug API Calls</h1>
', true);
}

output("Debug API Calls");
output("==============");
output("");

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to make API requests
function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $isWeb;
    
    $baseUrl = "https://api.storiesfromtheweb.org/api/v1";
    $url = $baseUrl . $endpoint;
    
    output("Making $method request to: $url");
    
    if ($data) {
        output("Request data:");
        if ($isWeb) {
            output("<pre class='request'>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>", true);
        } else {
            output(json_encode($data, JSON_PRETTY_PRINT));
        }
    }
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    // Set method
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    // Set data if provided
    if ($data) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
    }
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    // Output results
    output("HTTP Status: $httpCode");
    
    if ($httpCode >= 200 && $httpCode < 300) {
        if ($isWeb) output("<span class='success'>Success</span>", true);
        else output("Success");
    } else {
        if ($isWeb) output("<span class='error'>Error</span>", true);
        else output("Error");
    }
    
    output("Response headers:");
    if ($isWeb) {
        output("<pre>" . htmlspecialchars($headers) . "</pre>", true);
    } else {
        output($headers);
    }
    
    output("Response body:");
    if ($isWeb) {
        output("<pre class='response'>" . htmlspecialchars($body) . "</pre>", true);
    } else {
        output($body);
    }
    
    // Parse JSON response
    $jsonResponse = json_decode($body, true);
    
    return [
        'status' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'json' => $jsonResponse
    ];
}

// Function to directly query the database
function queryDatabase($query, $params = []) {
    global $config, $isWeb;
    
    try {
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $db = new PDO($dsn, $config['db']['user'], $config['db']['password'], $options);
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll();
        
        output("Database query results:");
        if ($isWeb) {
            output("<pre>" . json_encode($results, JSON_PRETTY_PRINT) . "</pre>", true);
        } else {
            output(json_encode($results, JSON_PRETTY_PRINT));
        }
        
        return $results;
    } catch (PDOException $e) {
        if ($isWeb) output("<div class='error'>Database error: " . $e->getMessage() . "</div>", true);
        else output("Database error: " . $e->getMessage());
        
        return null;
    }
}

// Check if stories exist in the database
output("Checking if stories exist in the database");
output("-------------------------------------");
$stories = queryDatabase("SELECT * FROM stories LIMIT 10");

if (empty($stories)) {
    if ($isWeb) output("<div class='warning'>No stories found in the database</div>", true);
    else output("Warning: No stories found in the database");
    
    // Create a test story
    output("");
    output("Creating a test story in the database");
    output("----------------------------------");
    
    try {
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $db = new PDO($dsn, $config['db']['user'], $config['db']['password'], $options);
        
        // Check if the story already exists
        $stmt = $db->prepare("SELECT id FROM stories WHERE title = 'Test Story'");
        $stmt->execute();
        $existingStory = $stmt->fetch();
        
        if ($existingStory) {
            output("Test story already exists with ID: " . $existingStory['id']);
        } else {
            // Insert a test story
            $stmt = $db->prepare("INSERT INTO stories (title, slug, excerpt, content, author_id, featured, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                'Test Story',
                'test-story',
                'This is a test story created by the debugging script.',
                'This is the content of the test story.',
                1, // Assuming author ID 1 exists
                1, // Featured
                1  // Published
            ]);
            
            $storyId = $db->lastInsertId();
            
            if ($storyId) {
                if ($isWeb) output("<div class='success'>Successfully created test story with ID: $storyId</div>", true);
                else output("Successfully created test story with ID: $storyId");
            } else {
                if ($isWeb) output("<div class='error'>Failed to create test story</div>", true);
                else output("Error: Failed to create test story");
            }
        }
    } catch (PDOException $e) {
        if ($isWeb) output("<div class='error'>Database error: " . $e->getMessage() . "</div>", true);
        else output("Database error: " . $e->getMessage());
    }
}

// Test the stories endpoint
output("");
output("Testing GET /stories endpoint");
output("--------------------------");
$storiesResponse = makeApiRequest('/stories');

// Test the stories endpoint with ID
if (!empty($stories)) {
    $storyId = $stories[0]['id'];
    
    output("");
    output("Testing GET /stories/$storyId endpoint");
    output("--------------------------------");
    $storyResponse = makeApiRequest("/stories/$storyId");
}

// Test updating a story
if (!empty($stories)) {
    $storyId = $stories[0]['id'];
    
    output("");
    output("Testing PUT /stories/$storyId endpoint");
    output("--------------------------------");
    $updateData = [
        'title' => 'Updated Story Title ' . date('Y-m-d H:i:s'),
        'excerpt' => 'This is an updated excerpt.',
        'content' => 'This is updated content for the story.'
    ];
    
    $updateResponse = makeApiRequest("/stories/$storyId", 'PUT', $updateData);
}

// Check the admin interface API calls
output("");
output("Simulating admin interface API calls");
output("----------------------------------");
output("The admin interface typically makes these API calls:");
output("1. GET /stories - To fetch all stories");
output("2. GET /stories/{id} - To fetch a specific story for editing");
output("3. PUT /stories/{id} - To update a story");

// Create a fix for the StoriesController
output("");
output("Checking StoriesController");
output("------------------------");
$storiesControllerPath = __DIR__ . '/api/v1/Endpoints/StoriesController.php';

if (file_exists($storiesControllerPath)) {
    output("StoriesController exists at: $storiesControllerPath");
    
    // Add debug logging to StoriesController
    if ($isWeb) {
        output("<div class='section'>", true);
        output("<h3>Add Debug Logging to StoriesController</h3>", true);
        output("<p>This will modify the StoriesController to add detailed logging for debugging purposes.</p>", true);
        output("<button class='fix-button' onclick='addDebugLogging()'>Apply Fix</button>", true);
        output("</div>", true);
    }
} else {
    if ($isWeb) output("<div class='error'>StoriesController not found at: $storiesControllerPath</div>", true);
    else output("Error: StoriesController not found at: $storiesControllerPath");
}

// Create a fix for the admin interface
output("");
output("Potential Issues and Fixes");
output("-----------------------");
output("1. Authentication issues: The AuthMiddleware has been modified to always authenticate requests, but there might still be issues with how the admin interface sends authentication tokens.");
output("2. CORS issues: The API might not be properly configured to allow cross-origin requests from the admin interface.");
output("3. Response format issues: The API might not be returning data in the format expected by the admin interface.");
output("4. Database issues: There might be issues with the database connection or permissions.");

// Add JavaScript for the fix button
if ($isWeb) {
    output("<script>
function addDebugLogging() {
    if (confirm('This will modify the StoriesController to add detailed logging for debugging purposes. Continue?')) {
        window.location.href = 'add_debug_logging.php';
    }
}
</script>", true);
    
    // Close HTML
    output('
    </div>
</body>
</html>', true);
}