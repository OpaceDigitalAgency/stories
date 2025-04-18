<?php
// Comment out debug file writing to prevent output before JSON
// file_put_contents('/home/stories/tmp_autoload_test.txt', date('c')." index.php executed\n", FILE_APPEND);
header("Cache-Control: no-cache");
header("Content-Type: application/json; charset=UTF-8");
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

// Set error reporting - don't display errors in output to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 1); // TEMPORARY DEBUG: Show errors on screen
ini_set('log_errors', 1);

// Define the base path
define('BASE_PATH', __DIR__);

// Define debug mode (should be false for production)
define('DEBUG_MODE', true); // TEMPORARY DEBUG: Enable debug features

// Enable CORS for preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

// Autoload classes with case-insensitive directory handling
spl_autoload_register(function($class) {
  $prefix   = 'StoriesAPI\\';
  $base_dir = __DIR__ . '/v1/';
  
  // Check if the class uses our namespace
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
    return;
  }
  
  // Get the relative class name
  $relative = substr($class, strlen($prefix));
  
  // Convert namespace separators to directory separators
  $path = str_replace('\\', '/', $relative);
  
  // Try the exact case first
  $file = $base_dir . $path . '.php';
  if (file_exists($file)) {
    require_once $file;
    return;
  }
  
  // If exact case doesn't work, try case-insensitive approach
  $parts = explode('/', $path);
  $current_path = $base_dir;
  
  foreach ($parts as $i => $part) {
    // If this is the last part (the file itself)
    if ($i === count($parts) - 1) {
      $file_name = $part . '.php';
      $dir_contents = scandir($current_path);
      
      foreach ($dir_contents as $item) {
        if (strtolower($item) === strtolower($file_name)) {
          require_once $current_path . $item;
          return;
        }
      }
    } else {
      // This is a directory part
      $dir_contents = scandir($current_path);
      $found = false;
      
      foreach ($dir_contents as $item) {
        if (is_dir($current_path . $item) && strtolower($item) === strtolower($part)) {
          $current_path .= $item . '/';
          $found = true;
          break;
        }
      }
      
      if (!$found) {
        // Directory not found, can't proceed
        return;
      }
    }
  }
  
  // If we get here, the file wasn't found
  error_log("Autoloader: Could not find file for class $class");
});

/**
 * Case-insensitive file loader
 *
 * This function attempts to load a file regardless of case sensitivity.
 * It will try the exact path first, then try to find the file with case-insensitive matching.
 *
 * @param string $basePath The base directory path
 * @param string $relativePath The relative path to the file
 * @param bool $requireFile Whether to require the file or just return the path
 * @return string|bool The file path if found, false otherwise
 */
function loadFileInsensitive($basePath, $relativePath, $requireFile = true) {
    // Try the exact path first
    $exactPath = $basePath . '/' . $relativePath;
    if (file_exists($exactPath)) {
        if ($requireFile) {
            require_once $exactPath;
        }
        return $exactPath;
    }
    
    // Split the relative path into parts
    $parts = explode('/', $relativePath);
    $currentPath = $basePath;
    
    // Traverse the path parts
    foreach ($parts as $i => $part) {
        // If this is the last part (the file itself)
        if ($i === count($parts) - 1) {
            $fileName = $part;
            $dirContents = scandir($currentPath);
            
            foreach ($dirContents as $item) {
                if (strtolower($item) === strtolower($fileName)) {
                    $filePath = $currentPath . '/' . $item;
                    if ($requireFile) {
                        require_once $filePath;
                    }
                    return $filePath;
                }
            }
            
            // File not found
            error_log("File not found: $relativePath in $basePath");
            return false;
        } else {
            // This is a directory part
            $dirContents = scandir($currentPath);
            $found = false;
            
            foreach ($dirContents as $item) {
                if (is_dir($currentPath . '/' . $item) && strtolower($item) === strtolower($part)) {
                    $currentPath .= '/' . $item;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // Directory not found
                error_log("Directory not found: $part in $currentPath");
                return false;
            }
        }
    }
    
    // Should not reach here
    return false;
}

// Load configuration
$config = require __DIR__ . '/v1/config/config.php';

// Initialize Auth utility
\StoriesAPI\Utils\Auth::init($config['security']);

// Set debug mode for Response class
\StoriesAPI\Utils\Response::$debugMode = DEBUG_MODE;

// Load Router class with case-insensitive approach
loadFileInsensitive(__DIR__, 'v1/Core/Router.php');

// Create router
$router = new \StoriesAPI\Core\Router($config);

// Define routes

// Auth routes
$router->post('auth/login', '\StoriesAPI\Endpoints\AuthController', 'login');
$router->post('auth/register', '\StoriesAPI\Endpoints\AuthController', 'register');
$router->get('auth/me', '\StoriesAPI\Endpoints\AuthController', 'me', [new \StoriesAPI\Middleware\AuthMiddleware()]);
$router->post('auth/refresh', '\StoriesAPI\Endpoints\AuthController', 'refresh');

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
$router->get('directory-items/{slug}', '\StoriesAPI\Endpoints\DirectoryItemsController', 'show');
$router->post('directory-items', '\StoriesAPI\Endpoints\DirectoryItemsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->put('directory-items/{id}', '\StoriesAPI\Endpoints\DirectoryItemsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->delete('directory-items/{id}', '\StoriesAPI\Endpoints\DirectoryItemsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);

// Games routes
$router->get('games', '\StoriesAPI\Endpoints\GamesController', 'index');
$router->get('games/{slug}', '\StoriesAPI\Endpoints\GamesController', 'show');
$router->post('games', '\StoriesAPI\Endpoints\GamesController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->put('games/{id}', '\StoriesAPI\Endpoints\GamesController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->delete('games/{id}', '\StoriesAPI\Endpoints\GamesController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);

// AI tools routes
$router->get('ai-tools', '\StoriesAPI\Endpoints\AiToolsController', 'index');
$router->get('ai-tools/{slug}', '\StoriesAPI\Endpoints\AiToolsController', 'show');
$router->post('ai-tools', '\StoriesAPI\Endpoints\AiToolsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->put('ai-tools/{id}', '\StoriesAPI\Endpoints\AiToolsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->delete('ai-tools/{id}', '\StoriesAPI\Endpoints\AiToolsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);

// Tags routes
$router->get('tags', '\StoriesAPI\Endpoints\TagsController', 'index');
$router->get('tags/{slug}', '\StoriesAPI\Endpoints\TagsController', 'show');
$router->post('tags', '\StoriesAPI\Endpoints\TagsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->put('tags/{id}', '\StoriesAPI\Endpoints\TagsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);
$router->delete('tags/{id}', '\StoriesAPI\Endpoints\TagsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware(['admin', 'editor'])]);

// Handle the request with try/catch to squash any PHP fatal into JSON
try {
    $router->handle();
} catch (Throwable $e) {
    \StoriesAPI\Utils\Response::sendError(
        'Internal server error',
        500,
        ['exception' => $e->getMessage()]
    );
}

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