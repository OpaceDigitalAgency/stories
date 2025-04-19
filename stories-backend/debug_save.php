<?php
/**
 * Debug Save Functionality
 * 
 * This script helps debug issues with saving data in the admin interface.
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
    <title>Debug Save Functionality</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Save Functionality</h1>
', true);
}

output("Debug Save Functionality");
output("=======================");
output("");

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

// Test database connection
output("Testing Database Connection");
output("-------------------------");
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, $config['db']['user'], $config['db']['password'], $options);
    
    if ($isWeb) output("<div class='success'>Database connection successful</div>", true);
    else output("Database connection successful");
} catch (PDOException $e) {
    if ($isWeb) output("<div class='error'>Database connection failed: " . $e->getMessage() . "</div>", true);
    else output("Database connection failed: " . $e->getMessage());
}
output("");

// Test write permissions
output("Testing Write Permissions");
output("-----------------------");
try {
    // Create a test table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS debug_test (
        id INT AUTO_INCREMENT PRIMARY KEY,
        test_data VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert a test record
    $stmt = $db->prepare("INSERT INTO debug_test (test_data) VALUES (?)");
    $testData = "Test data " . date('Y-m-d H:i:s');
    $stmt->execute([$testData]);
    
    $lastId = $db->lastInsertId();
    
    if ($lastId) {
        if ($isWeb) output("<div class='success'>Successfully wrote to database. Insert ID: $lastId</div>", true);
        else output("Successfully wrote to database. Insert ID: $lastId");
        
        // Clean up
        $db->exec("DELETE FROM debug_test WHERE id = $lastId");
    } else {
        if ($isWeb) output("<div class='error'>Failed to write to database</div>", true);
        else output("Failed to write to database");
    }
} catch (PDOException $e) {
    if ($isWeb) output("<div class='error'>Database write test failed: " . $e->getMessage() . "</div>", true);
    else output("Database write test failed: " . $e->getMessage());
}
output("");

// Check authentication middleware
output("Checking Authentication Middleware");
output("-------------------------------");
$authMiddlewarePath = __DIR__ . '/api/v1/Middleware/AuthMiddleware.php';
if (file_exists($authMiddlewarePath)) {
    output("AuthMiddleware.php exists");
    
    // Check the content
    $authMiddlewareContent = file_get_contents($authMiddlewarePath);
    
    // Check if it's validating tokens correctly
    if (strpos($authMiddlewareContent, 'validateToken') !== false) {
        output("AuthMiddleware contains validateToken method");
    } else {
        if ($isWeb) output("<div class='warning'>AuthMiddleware does not contain validateToken method</div>", true);
        else output("Warning: AuthMiddleware does not contain validateToken method");
    }
    
    // Check if it's setting user data in the request
    if (strpos($authMiddlewareContent, '$_REQUEST[\'user\']') !== false) {
        output("AuthMiddleware sets user data in request");
    } else {
        if ($isWeb) output("<div class='warning'>AuthMiddleware does not set user data in request</div>", true);
        else output("Warning: AuthMiddleware does not set user data in request");
    }
} else {
    if ($isWeb) output("<div class='error'>AuthMiddleware.php not found</div>", true);
    else output("Error: AuthMiddleware.php not found");
}
output("");

// Check routes.php
output("Checking Routes Configuration");
output("---------------------------");
$routesPath = __DIR__ . '/api/v1/routes.php';
if (file_exists($routesPath)) {
    output("routes.php exists");
    
    // Check the content
    $routesContent = file_get_contents($routesPath);
    
    // Check if it's using AuthMiddleware
    if (strpos($routesContent, 'AuthMiddleware') !== false) {
        output("routes.php is using AuthMiddleware");
    } else if (strpos($routesContent, 'SimpleAuthMiddleware') !== false) {
        if ($isWeb) output("<div class='warning'>routes.php is using SimpleAuthMiddleware which might not exist</div>", true);
        else output("Warning: routes.php is using SimpleAuthMiddleware which might not exist");
    } else {
        if ($isWeb) output("<div class='warning'>routes.php is not using any authentication middleware</div>", true);
        else output("Warning: routes.php is not using any authentication middleware");
    }
    
    // Check PUT routes for stories
    if (strpos($routesContent, 'PUT') !== false && strpos($routesContent, 'stories') !== false) {
        output("routes.php has PUT routes for stories");
    } else {
        if ($isWeb) output("<div class='warning'>routes.php might not have PUT routes for stories</div>", true);
        else output("Warning: routes.php might not have PUT routes for stories");
    }
} else {
    if ($isWeb) output("<div class='error'>routes.php not found</div>", true);
    else output("Error: routes.php not found");
}
output("");

// Check StoriesController
output("Checking StoriesController");
output("------------------------");
$storiesControllerPath = __DIR__ . '/api/v1/Endpoints/StoriesController.php';
if (file_exists($storiesControllerPath)) {
    output("StoriesController.php exists");
    
    // Check the content
    $storiesControllerContent = file_get_contents($storiesControllerPath);
    
    // Check if it has an update method
    if (strpos($storiesControllerContent, 'update') !== false) {
        output("StoriesController has update method");
    } else {
        if ($isWeb) output("<div class='warning'>StoriesController might not have update method</div>", true);
        else output("Warning: StoriesController might not have update method");
    }
    
    // Check if it's using Response::sendSuccess
    if (strpos($storiesControllerContent, 'Response::sendSuccess') !== false) {
        output("StoriesController is using Response::sendSuccess");
    } else {
        if ($isWeb) output("<div class='warning'>StoriesController might not be using Response::sendSuccess</div>", true);
        else output("Warning: StoriesController might not be using Response::sendSuccess");
    }
} else {
    if ($isWeb) output("<div class='error'>StoriesController.php not found</div>", true);
    else output("Error: StoriesController.php not found");
}
output("");

// Create a test script to simulate a save request
output("Creating Test Save Request");
output("-----------------------");
output("To test saving a story, you can use the following curl command:");
output("");
$curlCommand = "curl -X PUT \\
  https://api.storiesfromtheweb.org/api/v1/stories/1 \\
  -H 'Content-Type: application/json' \\
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \\
  -d '{
    \"title\": \"Updated Story Title\",
    \"excerpt\": \"Updated story excerpt.\",
    \"content\": \"Updated story content.\"
  }'";

if ($isWeb) {
    output("<pre>$curlCommand</pre>", true);
} else {
    output($curlCommand);
}
output("");

// Create a fix for common save issues
output("Potential Fixes for Save Issues");
output("----------------------------");
output("1. Check if the user has write permissions in the database");
output("2. Ensure the authentication middleware is correctly validating tokens");
output("3. Verify that the routes.php file has PUT routes for stories");
output("4. Make sure the StoriesController has an update method that works correctly");
output("5. Check for any PHP errors in the server logs");
output("");

// Create a fix script
if ($isWeb) {
    output("<div class='section'>", true);
    output("<h3>Fix Authentication for Save</h3>", true);
    output("<p>This will modify the AuthMiddleware to always authenticate requests for testing purposes.</p>", true);
    output("<button class='fix-button' onclick='fixAuth()'>Apply Fix</button>", true);
    output("</div>", true);
    
    // Add JavaScript for the fix button
    output("<script>
function fixAuth() {
    if (confirm('This will modify the AuthMiddleware to always authenticate requests for testing purposes. Continue?')) {
        window.location.href = 'fix_auth_for_save.php';
    }
}
</script>", true);
}

// Close HTML if in web mode
if ($isWeb) {
    output('
    </div>
</body>
</html>', true);
}