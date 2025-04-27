<?php
/**
 * Fix Router and Config
 * 
 * This script fixes:
 * 1. The Router class to properly handle controller methods
 * 2. The config.php file to prevent duplicate ENVIRONMENT definition
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Router and Config</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$corePath = $apiPath . '/Core';
$configPath = $apiPath . '/config';

// Check paths
if (!is_dir($corePath)) {
    $corePath = $apiPath . '/core';
    if (!is_dir($corePath)) {
        echo "<p style='color:red'>❌ Core directory not found!</p>";
        exit;
    }
}

if (!is_dir($configPath)) {
    $configPath = $apiPath . '/config';
    if (!is_dir($configPath)) {
        echo "<p style='color:red'>❌ Config directory not found!</p>";
        exit;
    }
}

echo "<p>Using Core directory: $corePath</p>";
echo "<p>Using Config directory: $configPath</p>";

// Fix Router class
$routerFile = $corePath . '/Router.php';
if (!file_exists($routerFile)) {
    echo "<p style='color:red'>❌ Router class not found!</p>";
    exit;
}

echo "<p>Found Router class at: $routerFile</p>";

// Create a backup
$backupFile = $routerFile . '.bak.' . date('YmdHis');
if (copy($routerFile, $backupFile)) {
    echo "<p>Created backup at: $backupFile</p>";
}

// Get the namespace based on the core directory name
$coreNamespaceSuffix = basename($corePath);

// Update the Router class
$routerContent = <<<EOD
<?php
/**
 * Router Class
 * 
 * Handles routing requests to the appropriate controller and method
 */

namespace StoriesAPI\\$coreNamespaceSuffix;

class Router {
    private \$routes = [];
    private \$params = [];
    
    /**
     * Add a route
     * 
     * @param string \$method The HTTP method
     * @param string \$path The route path
     * @param string \$controller The controller class
     * @param string \$action The controller method
     */
    public function addRoute(\$method, \$path, \$controller, \$action) {
        \$this->routes[] = [
            'method' => \$method,
            'path' => \$path,
            'controller' => \$controller,
            'action' => \$action
        ];
    }
    
    /**
     * Handle the current request
     */
    public function handle() {
        \$method = \$_SERVER['REQUEST_METHOD'];
        \$path = parse_url(\$_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove API version prefix if present
        \$path = preg_replace('#^/api/v\d+#', '', \$path);
        
        foreach (\$this->routes as \$route) {
            \$pattern = \$this->getPattern(\$route['path']);
            
            if (\$method === \$route['method'] && preg_match(\$pattern, \$path, \$matches)) {
                // Get named parameters
                \$params = [];
                \$pathParts = explode('/', trim(\$route['path'], '/'));
                \$urlParts = explode('/', trim(\$path, '/'));
                
                foreach (\$pathParts as \$index => \$part) {
                    if (strpos(\$part, ':') === 0) {
                        \$paramName = substr(\$part, 1);
                        \$params[\$paramName] = \$urlParts[\$index];
                    }
                }
                
                // Create controller instance
                \$controllerClass = \$route['controller'];
                \$controller = new \$controllerClass();
                
                // Store parameters in controller
                \$controller->params = \$params;
                
                // Call the action
                \$action = \$route['action'];
                if (method_exists(\$controller, \$action)) {
                    if (!empty(\$params)) {
                        return \$controller->\$action(...array_values(\$params));
                    } else {
                        return \$controller->\$action();
                    }
                } else {
                    throw new \Exception("Action '\$action' not found in controller '\$controllerClass'");
                }
            }
        }
        
        // No route found
        header("HTTP/1.0 404 Not Found");
        echo json_encode(['error' => 'Route not found']);
        exit;
    }
    
    /**
     * Convert route path to regex pattern
     * 
     * @param string \$path The route path
     * @return string The regex pattern
     */
    private function getPattern(\$path) {
        return '#^' . preg_replace('#:[a-zA-Z]+#', '([^/]+)', \$path) . '\$#';
    }
}
EOD;

if (file_put_contents($routerFile, $routerContent)) {
    echo "<p style='color:green'>✅ Updated Router class successfully!</p>";
} else {
    echo "<p style='color:red'>❌ Failed to update Router class.</p>";
}

// Fix config.php
$configFile = $configPath . '/config.php';
if (!file_exists($configFile)) {
    echo "<p style='color:red'>❌ Config file not found!</p>";
    exit;
}

echo "<h2>Fixing Config File</h2>";
echo "<p>Found config file at: $configFile</p>";

// Create a backup
$backupFile = $configFile . '.bak.' . date('YmdHis');
if (copy($configFile, $backupFile)) {
    echo "<p>Created backup at: $backupFile</p>";
}

// Read the config file
$configContent = file_get_contents($configFile);

// Remove the ENVIRONMENT definition if it exists
$configContent = preg_replace(
    '/define\s*\(\s*[\'"]ENVIRONMENT[\'"]\s*,\s*[\'"][^\'"]+[\'"]\s*\)\s*;/',
    '',
    $configContent
);

// Add check for ENVIRONMENT constant
$configContent = <<<EOD
<?php
/**
 * Configuration file for the Stories API
 */

// Define the environment only if not already defined
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

// Set error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$configContent
EOD;

if (file_put_contents($configFile, $configContent)) {
    echo "<p style='color:green'>✅ Updated config file successfully!</p>";
    
    echo "<h2>Next Steps</h2>";
    echo "<p>The Router and config files have been fixed. Test the endpoints:</p>";
    echo "<ul>";
    echo "<li><a href='/api/v1/stories'>/api/v1/stories</a></li>";
    echo "<li><a href='/api/v1/authors'>/api/v1/authors</a></li>";
    echo "<li><a href='/api/v1/games'>/api/v1/games</a></li>";
    echo "<li><a href='/api/v1/directory-items'>/api/v1/directory-items</a></li>";
    echo "<li><a href='/api/v1/ai-tools'>/api/v1/ai-tools</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ Failed to update config file.</p>";
}