<?php
/**
 * Direct Fix for Routes.php
 * 
 * This script directly modifies the routes.php file to replace SimpleAuthMiddleware with AuthMiddleware.
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
    <title>Direct Fix for Routes.php</title>
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
        <h1>Direct Fix for Routes.php</h1>
', true);
}

output("Direct Fix for Routes.php");
output("=======================");
output("");

// Path to routes.php file
$routesPath = __DIR__ . '/api/v1/routes.php';

if (!file_exists($routesPath)) {
    if ($isWeb) output("<div class='error'>Routes file not found: $routesPath</div>", true);
    else output("Error: Routes file not found: $routesPath");
    
    // Try to find the routes.php file
    output("Searching for routes.php file...");
    $possiblePaths = [
        __DIR__ . '/api/v1/routes.php',
        __DIR__ . '/api/routes.php',
        '/home/stories/api.storiesfromtheweb.org/api/v1/routes.php',
        '/home/stories/api.storiesfromtheweb.org/routes.php'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            output("Found routes.php at: $path");
            $routesPath = $path;
            break;
        }
    }
    
    if (!file_exists($routesPath)) {
        if ($isWeb) {
            output("<div class='error'>Could not find routes.php file</div>", true);
            output("<div class='back-link'><a href='debug_api_calls.php'>Back to Debug Tool</a></div>", true);
            output('</div></body></html>', true);
        } else {
            output("Error: Could not find routes.php file");
        }
        exit;
    }
}

output("Routes file: $routesPath");
output("");

// Backup the routes file
$backupFile = $routesPath . '.bak.' . date('YmdHis');
if (!copy($routesPath, $backupFile)) {
    if ($isWeb) output("<div class='error'>Failed to create backup file</div>", true);
    else output("Error: Failed to create backup file");
    
    if ($isWeb) {
        output("<div class='back-link'><a href='debug_api_calls.php'>Back to Debug Tool</a></div>", true);
        output('</div></body></html>', true);
    }
    exit;
}

output("Backup created: $backupFile");
output("");

// Read the routes file
$content = file_get_contents($routesPath);

// Display the current content
output("Current content of routes.php:");
if ($isWeb) {
    output("<pre>" . htmlspecialchars($content) . "</pre>", true);
} else {
    output($content);
}
output("");

// Check if SimpleAuthMiddleware is being used
if (strpos($content, 'SimpleAuthMiddleware') !== false) {
    output("Found SimpleAuthMiddleware in routes.php");
    
    // Replace SimpleAuthMiddleware with AuthMiddleware
    $newContent = str_replace(
        'SimpleAuthMiddleware',
        'AuthMiddleware',
        $content
    );
    
    // Display the modified content
    output("Modified content of routes.php:");
    if ($isWeb) {
        output("<pre>" . htmlspecialchars($newContent) . "</pre>", true);
    } else {
        output($newContent);
    }
    output("");
    
    // Write the modified content back to the file
    if (file_put_contents($routesPath, $newContent)) {
        if ($isWeb) output("<div class='success'>Successfully replaced SimpleAuthMiddleware with AuthMiddleware</div>", true);
        else output("Successfully replaced SimpleAuthMiddleware with AuthMiddleware");
    } else {
        if ($isWeb) output("<div class='error'>Failed to write modified content to file</div>", true);
        else output("Error: Failed to write modified content to file");
        
        // Try with different permissions
        output("Trying with different permissions...");
        
        // Try to make the file writable
        if (chmod($routesPath, 0666)) {
            output("Changed file permissions to 0666");
            
            if (file_put_contents($routesPath, $newContent)) {
                if ($isWeb) output("<div class='success'>Successfully replaced SimpleAuthMiddleware with AuthMiddleware</div>", true);
                else output("Successfully replaced SimpleAuthMiddleware with AuthMiddleware");
            } else {
                if ($isWeb) output("<div class='error'>Still failed to write modified content to file</div>", true);
                else output("Error: Still failed to write modified content to file");
            }
        } else {
            output("Failed to change file permissions");
        }
    }
} else {
    if ($isWeb) output("<div class='warning'>SimpleAuthMiddleware not found in routes.php</div>", true);
    else output("Warning: SimpleAuthMiddleware not found in routes.php");
    
    // Check if there's any middleware being used
    if (preg_match('/\$router->use\(.*?Middleware/i', $content, $matches)) {
        output("Found middleware usage: " . $matches[0]);
    } else {
        output("No middleware usage found in routes.php");
    }
}

// Create a direct fix for the routes.php file
output("");
output("Creating a direct fix for routes.php");
output("--------------------------------");

// Create a new routes.php file with AuthMiddleware
$newRoutesContent = '<?php
/**
 * API Routes
 * 
 * This file defines the routes for the API.
 */

use StoriesAPI\Middleware\AuthMiddleware;
use StoriesAPI\Utils\Router;
use StoriesAPI\Utils\Response;

// Create router instance
$router = new Router();

// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");
header("Access-Control-Expose-Headers: X-Total-Count, X-Pagination-Total-Pages");

// Handle preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

// Use authentication middleware
$router->use(new AuthMiddleware($config));

// Define routes
$router->get("/stories", "StoriesController@index");
$router->get("/stories/:id", "StoriesController@show");
$router->post("/stories", "StoriesController@create");
$router->put("/stories/:id", "StoriesController@update");
$router->delete("/stories/:id", "StoriesController@delete");

$router->get("/authors", "AuthorsController@index");
$router->get("/authors/:id", "AuthorsController@show");
$router->post("/authors", "AuthorsController@create");
$router->put("/authors/:id", "AuthorsController@update");
$router->delete("/authors/:id", "AuthorsController@delete");

$router->get("/tags", "TagsController@index");
$router->get("/tags/:id", "TagsController@show");
$router->post("/tags", "TagsController@create");
$router->put("/tags/:id", "TagsController@update");
$router->delete("/tags/:id", "TagsController@delete");

$router->get("/games", "GamesController@index");
$router->get("/games/:id", "GamesController@show");
$router->post("/games", "GamesController@create");
$router->put("/games/:id", "GamesController@update");
$router->delete("/games/:id", "GamesController@delete");

$router->get("/directory-items", "DirectoryItemsController@index");
$router->get("/directory-items/:id", "DirectoryItemsController@show");
$router->post("/directory-items", "DirectoryItemsController@create");
$router->put("/directory-items/:id", "DirectoryItemsController@update");
$router->delete("/directory-items/:id", "DirectoryItemsController@delete");

$router->get("/blog-posts", "BlogPostsController@index");
$router->get("/blog-posts/:id", "BlogPostsController@show");
$router->post("/blog-posts", "BlogPostsController@create");
$router->put("/blog-posts/:id", "BlogPostsController@update");
$router->delete("/blog-posts/:id", "BlogPostsController@delete");

$router->get("/ai-tools", "AiToolsController@index");
$router->get("/ai-tools/:id", "AiToolsController@show");
$router->post("/ai-tools", "AiToolsController@create");
$router->put("/ai-tools/:id", "AiToolsController@update");
$router->delete("/ai-tools/:id", "AiToolsController@delete");

// Auth routes
$router->post("/auth/login", "AuthController@login");
$router->post("/auth/register", "AuthController@register");
$router->get("/auth/me", "AuthController@me");
$router->post("/auth/refresh", "AuthController@refresh");
$router->post("/auth/logout", "AuthController@logout");

// Handle the request
$router->handle();
';

// Create a new file with the fixed content
$newRoutesPath = __DIR__ . '/fixed_routes.php';
if (file_put_contents($newRoutesPath, $newRoutesContent)) {
    if ($isWeb) output("<div class='success'>Successfully created fixed routes.php file: $newRoutesPath</div>", true);
    else output("Successfully created fixed routes.php file: $newRoutesPath");
    
    output("To use this file, copy it to the server at: $routesPath");
    
    if ($isWeb) {
        output("<div class='code'>cp $newRoutesPath $routesPath</div>", true);
    } else {
        output("cp $newRoutesPath $routesPath");
    }
} else {
    if ($isWeb) output("<div class='error'>Failed to create fixed routes.php file</div>", true);
    else output("Error: Failed to create fixed routes.php file");
}

// Create a restore script
$restoreScript = '<?php
// Restore the original routes.php file
$backupFile = "' . $backupFile . '";
$routesPath = "' . $routesPath . '";

if (file_exists($backupFile)) {
    if (copy($backupFile, $routesPath)) {
        echo "Successfully restored original routes.php file";
    } else {
        echo "Failed to restore original routes.php file";
    }
} else {
    echo "Backup file not found: " . $backupFile;
}
';

$restoreScriptPath = __DIR__ . '/restore_routes.php';
file_put_contents($restoreScriptPath, $restoreScript);

output("");
output("A restore script has been created: restore_routes.php");
output("Run this script to restore the original routes.php file when you're done testing");

output("");
output("Next Steps:");
output("1. Try accessing the admin interface again");
output("2. If issues persist, try using the fixed_routes.php file");
output("3. Check the server logs for detailed debug information");

if ($isWeb) {
    output("<div class='back-link'><a href='debug_api_calls.php'>Back to Debug Tool</a></div>", true);
    output('</div></body></html>', true);
}