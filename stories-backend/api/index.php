<?php
/**
 * Stories API - Main Entry Point
 *
 * This file serves as the entry point for the Stories API.
 * It loads the configuration, sets up the router, and handles the request.
 *
 * @package Stories API
 * @version 1.0.0
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
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Load configuration
$config = require __DIR__ . '/v1/config/config.php';

// Initialize Auth utility
\StoriesAPI\Utils\Auth::init($config['security']);

// Create router
$router = new \StoriesAPI\Core\Router($config);

// Define routes

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

// Handle the request
$router->handle();

// Clean the output buffer and ensure only JSON is sent
$output = ob_get_clean();
if (!empty($output)) {
    // Log unexpected output for debugging
    error_log('Unexpected output before JSON response: ' . $output);
    
    // If headers haven't been sent yet, we can still send a proper JSON response
    if (!headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Unexpected output occurred. Please check the server logs.',
            'debug' => DEBUG_MODE ? $output : null
        ]);
        exit;
    }
} else {
    // If no output, the router didn't handle the request properly
    if (!headers_sent()) {
        error_log('No output from router. Request URI: ' . $_SERVER['REQUEST_URI']);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'API request not properly handled',
            'debug' => DEBUG_MODE ? [
                'uri' => $_SERVER['REQUEST_URI'],
                'method' => $_SERVER['REQUEST_METHOD']
            ] : null
        ]);
        exit;
    }
}