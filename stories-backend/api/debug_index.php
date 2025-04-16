<?php
/**
 * Stories API - Debug Entry Point
 *
 * This file serves as a debugging entry point for the Stories API.
 * It loads the configuration, sets up the router, and handles the request,
 * but displays errors directly in the browser.
 */

// Start output buffering to capture any unexpected output
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path
define('BASE_PATH', __DIR__);

// Define debug mode (should be true for debugging)
define('DEBUG_MODE', true);

// Set content type to HTML for debugging
header('Content-Type: text/html; charset=UTF-8');

echo "<h1>API Debug Mode</h1>";
echo "<p>This page shows detailed debugging information for the API.</p>";

// Enable CORS for preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

// Autoload classes
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'StoriesAPI\\';
    $base_dir = __DIR__ . '/v1/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Convert namespace separators to directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Debug output
    echo "<p>Attempting to load class: " . htmlspecialchars($class) . " from file: " . htmlspecialchars($file) . "</p>";
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
        echo "<p style='color: green;'>Successfully loaded: " . htmlspecialchars($file) . "</p>";
        return;
    } else {
        echo "<p style='color: red;'>File not found: " . htmlspecialchars($file) . "</p>";
    }
    
    // Case-insensitive approach - try lowercase path
    $lowercase_path = strtolower($base_dir . str_replace('\\', '/', $relative_class)) . '.php';
    $actual_path = '';
    
    // Get the actual path with correct case
    $parts = explode('/', str_replace('\\', '/', $relative_class));
    $current_path = $base_dir;
    
    foreach ($parts as $part) {
        if (!is_dir($current_path)) {
            break;
        }
        
        $found = false;
        $items = scandir($current_path);
        
        foreach ($items as $item) {
            if (strtolower($item) === strtolower($part)) {
                $current_path .= $item . '/';
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            break;
        }
    }
    
    // Remove trailing slash and add .php
    $actual_path = substr($current_path, 0, -1) . '.php';
    
    // Debug output for case-insensitive approach
    echo "<p>Trying case-insensitive approach. Actual path: " . htmlspecialchars($actual_path) . "</p>";
    
    // If the file exists with the correct case, require it
    if (file_exists($actual_path)) {
        require $actual_path;
        echo "<p style='color: green;'>Successfully loaded with case-insensitive approach: " . htmlspecialchars($actual_path) . "</p>";
        return;
    }
    
    // Direct approach - try to find the file in the utils directory
    if (strpos($relative_class, 'Utils') === 0) {
        $utils_file = $base_dir . 'utils/' . substr($relative_class, 6) . '.php';
        echo "<p>Trying direct utils approach. Path: " . htmlspecialchars($utils_file) . "</p>";
        if (file_exists($utils_file)) {
            require $utils_file;
            echo "<p style='color: green;'>Successfully loaded from utils directory: " . htmlspecialchars($utils_file) . "</p>";
            return;
        }
    }
    
    // Log the error
    echo "<p style='color: red;'>Failed to load class: " . htmlspecialchars($class) . ". Tried paths: " . htmlspecialchars($file) . ", " . htmlspecialchars($lowercase_path) . ", " . htmlspecialchars($actual_path) . "</p>";
    error_log("Failed to load class: $class. Tried paths: $file, $lowercase_path, $actual_path");
});

try {
    // Load configuration
    echo "<h2>Loading Configuration</h2>";
    $configPath = __DIR__ . '/v1/config/config.php';
    if (file_exists($configPath)) {
        echo "<p style='color: green;'>Configuration file found at: " . htmlspecialchars($configPath) . "</p>";
        $config = require $configPath;
        echo "<pre>";
        $configCopy = $config;
        // Hide sensitive information
        if (isset($configCopy['db']['password'])) {
            $configCopy['db']['password'] = '********';
        }
        if (isset($configCopy['security']['jwt_secret'])) {
            $configCopy['security']['jwt_secret'] = '********';
        }
        print_r($configCopy);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>Configuration file not found at: " . htmlspecialchars($configPath) . "</p>";
        throw new Exception("Configuration file not found");
    }

    // Initialize Auth utility
    echo "<h2>Initializing Auth Utility</h2>";
    \StoriesAPI\Utils\Auth::init($config['security']);
    echo "<p style='color: green;'>Auth utility initialized</p>";

    // Create router
    echo "<h2>Creating Router</h2>";
    $router = new \StoriesAPI\Core\Router($config);
    echo "<p style='color: green;'>Router created</p>";

    // Define routes
    echo "<h2>Defining Routes</h2>";
    
    // Auth routes
    $router->post('auth/login', '\StoriesAPI\Endpoints\AuthController', 'login');
    $router->post('auth/register', '\StoriesAPI\Endpoints\AuthController', 'register');
    $router->get('auth/me', '\StoriesAPI\Endpoints\AuthController', 'me', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    
    // Stories routes
    $router->get('stories', '\StoriesAPI\Endpoints\StoriesController', 'index');
    $router->get('stories/{slug}', '\StoriesAPI\Endpoints\StoriesController', 'show');
    $router->post('stories', '\StoriesAPI\Endpoints\StoriesController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    $router->put('stories/{id}', '\StoriesAPI\Endpoints\StoriesController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    $router->delete('stories/{id}', '\StoriesAPI\Endpoints\StoriesController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    
    // Authors routes
    $router->get('authors', '\StoriesAPI\Endpoints\AuthorsController', 'index');
    $router->get('authors/{slug}', '\StoriesAPI\Endpoints\AuthorsController', 'show');
    $router->put('authors/{id}', '\StoriesAPI\Endpoints\AuthorsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    
    // Blog posts routes
    $router->get('blog-posts', '\StoriesAPI\Endpoints\BlogPostsController', 'index');
    $router->get('blog-posts/{slug}', '\StoriesAPI\Endpoints\BlogPostsController', 'show');
    $router->post('blog-posts', '\StoriesAPI\Endpoints\BlogPostsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->put('blog-posts/{id}', '\StoriesAPI\Endpoints\BlogPostsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->delete('blog-posts/{id}', '\StoriesAPI\Endpoints\BlogPostsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    
    // Directory items routes
    $router->get('directory-items', '\StoriesAPI\Endpoints\DirectoryItemsController', 'index');
    $router->get('directory-items/{id}', '\StoriesAPI\Endpoints\DirectoryItemsController', 'show');
    $router->post('directory-items', '\StoriesAPI\Endpoints\DirectoryItemsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->put('directory-items/{id}', '\StoriesAPI\Endpoints\DirectoryItemsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->delete('directory-items/{id}', '\StoriesAPI\Endpoints\DirectoryItemsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    
    // Games routes
    $router->get('games', '\StoriesAPI\Endpoints\GamesController', 'index');
    $router->get('games/{id}', '\StoriesAPI\Endpoints\GamesController', 'show');
    $router->post('games', '\StoriesAPI\Endpoints\GamesController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->put('games/{id}', '\StoriesAPI\Endpoints\GamesController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->delete('games/{id}', '\StoriesAPI\Endpoints\GamesController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    
    // AI tools routes
    $router->get('ai-tools', '\StoriesAPI\Endpoints\AiToolsController', 'index');
    $router->get('ai-tools/{id}', '\StoriesAPI\Endpoints\AiToolsController', 'show');
    $router->post('ai-tools', '\StoriesAPI\Endpoints\AiToolsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->put('ai-tools/{id}', '\StoriesAPI\Endpoints\AiToolsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->delete('ai-tools/{id}', '\StoriesAPI\Endpoints\AiToolsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    
    // Tags routes
    $router->get('tags', '\StoriesAPI\Endpoints\TagsController', 'index');
    $router->get('tags/{slug}', '\StoriesAPI\Endpoints\TagsController', 'show');
    $router->post('tags', '\StoriesAPI\Endpoints\TagsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->put('tags/{id}', '\StoriesAPI\Endpoints\TagsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    $router->delete('tags/{id}', '\StoriesAPI\Endpoints\TagsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
    
    echo "<p style='color: green;'>Routes defined</p>";
    
    // Request information
    echo "<h2>Request Information</h2>";
    echo "<p>Method: " . htmlspecialchars($_SERVER['REQUEST_METHOD']) . "</p>";
    echo "<p>URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
    
    // Handle the request
    echo "<h2>Handling Request</h2>";
    
    // Override the Response class's json method to display the response instead of exiting
    \StoriesAPI\Utils\Response::$debugMode = true;
    
    // Handle the request
    $router->handle();
    
} catch (Exception $e) {
    echo "<div style='color: red; margin: 20px; padding: 20px; border: 1px solid red;'>";
    echo "<h2>Exception Caught</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

// Clean the output buffer and display it
$output = ob_get_clean();
echo $output;

// Display PHP and server information
echo "<h2>System Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Host: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
?>