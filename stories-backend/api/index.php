<?php
ob_start(); // Start output buffering immediately after opening PHP tag
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

// Initialize and register the case-insensitive class loader
$classLoaderPaths = [
    __DIR__ . '/v1/Core/ClassLoader.php',
    __DIR__ . '/v1/core/ClassLoader.php',
    __DIR__ . '/v1/CORE/ClassLoader.php'
];

$loaded = false;
foreach ($classLoaderPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        if (DEBUG_MODE) {
            error_log("ClassLoader found at: " . $path);
        }
        break;
    }
}

if (!$loaded) {
    throw new Exception("ClassLoader not found in any of the expected locations: " . implode(", ", $classLoaderPaths));
}
$classLoader = \StoriesAPI\Core\ClassLoader::getInstance(__DIR__ . '/v1/', 'StoriesAPI\\');
$classLoader->register();

// Enable detailed error logging in debug mode
if (DEBUG_MODE) {
    error_log("Class loader initialized with base directory: " . __DIR__ . '/v1/');
}

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
\StoriesAPI\Core\Auth::init($config['security']);

// Set debug mode for Response class
\StoriesAPI\Utils\Response::$debugMode = DEBUG_MODE;

// Load Router class with case-insensitive approach
$routerFileResult = loadFileInsensitive(__DIR__, 'v1/Core/Router.php');

// --- GUARANTEED DEBUG LOGGING BEFORE ROUTER INSTANTIATION ---
$router_debug_message = "[GUARANTEE] loadFileInsensitive result: " . var_export($routerFileResult, true) . " | CWD: " . getcwd() . PHP_EOL;
file_put_contents(__DIR__ . '/router_load_debug.log', $router_debug_message, FILE_APPEND);
error_log($router_debug_message);
// ------------------------------------------------------------

// Debug output: log whether Router.php was found and loaded
if ($routerFileResult) {
    error_log("[DEBUG] Router.php loaded: " . $routerFileResult);
    file_put_contents(__DIR__ . '/router_load_debug.log', "[DEBUG] Router.php loaded: " . $routerFileResult . PHP_EOL, FILE_APPEND);
} else {
    error_log("[DEBUG] Router.php NOT found. Attempted path: " . __DIR__ . '/v1/Core/Router.php');
    file_put_contents(__DIR__ . '/router_load_debug.log', "[DEBUG] Router.php NOT found. Attempted path: " . __DIR__ . '/v1/Core/Router.php' . PHP_EOL, FILE_APPEND);
}

// Create router
$router = new \StoriesAPI\Core\Router($config);

// Load routes from routes file
if (file_exists(__DIR__ . '/v1/routes.php')) {
    // Pass the router to the routes file
    require __DIR__ . '/v1/routes.php';
    error_log("Routes loaded from routes.php");
} else {
    // Fallback to defining routes directly
    error_log("WARNING: routes.php not found, using hardcoded routes");
    
    // Auth routes
    $router->post('auth/login', '\StoriesAPI\Endpoints\AuthController', 'login');
    $router->post('auth/register', '\StoriesAPI\Endpoints\AuthController', 'register');
    $router->get('auth/me', '\StoriesAPI\Endpoints\AuthController', 'me', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    $router->post('auth/refresh', '\StoriesAPI\Endpoints\AuthController', 'refresh');
    
    // Tags routes (minimum required for testing)
    $router->get('tags', '\StoriesAPI\Endpoints\TagsController', 'index');
    $router->get('tags/{slug}', '\StoriesAPI\Endpoints\TagsController', 'show');
    $router->post('tags', '\StoriesAPI\Endpoints\TagsController', 'create', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    $router->put('tags/{id}', '\StoriesAPI\Endpoints\TagsController', 'update', [new \StoriesAPI\Middleware\AuthMiddleware()]);
    $router->delete('tags/{id}', '\StoriesAPI\Endpoints\TagsController', 'delete', [new \StoriesAPI\Middleware\AuthMiddleware()]);
}

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
    // Log unexpected output for debugging to a dedicated file
    file_put_contents(__DIR__ . '/api-error.log', date('c') . " Unexpected output before JSON response: " . $output . "\n", FILE_APPEND);

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