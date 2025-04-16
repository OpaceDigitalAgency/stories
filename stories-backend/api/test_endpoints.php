<?php
/**
 * API Endpoints Test Script
 *
 * This script tests the API endpoints directly to check if they're returning
 * properly formatted JSON responses.
 */

// Start output buffering to capture any unexpected output
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path
define('BASE_PATH', __DIR__);

// Set content type to HTML
header('Content-Type: text/html; charset=UTF-8');

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

echo "<h1>API Endpoints Test</h1>";

// Function to test an API endpoint
function testEndpoint($endpoint, $method = 'GET', $data = null) {
    echo "<h2>Testing $method $endpoint</h2>";
    
    // Save output buffering state
    $obLevel = ob_get_level();
    
    // Start output buffering to capture any unexpected output
    ob_start();
    
    try {
        // Mock request
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = "/api/v1/$endpoint";
        $_GET = [];
        $_POST = [];
        
        if ($data && $method === 'POST') {
            $_POST = $data;
        } else if ($data && $method === 'GET') {
            $_GET = $data;
            $_SERVER['REQUEST_URI'] .= '?' . http_build_query($data);
        }
        
        // Create router instance
        $router = new \StoriesAPI\Core\Router($config);
        
        // Define routes
        switch ($endpoint) {
            case 'stories':
                $router->get('stories', '\StoriesAPI\Endpoints\StoriesController', 'index');
                break;
            case 'authors':
                $router->get('authors', '\StoriesAPI\Endpoints\AuthorsController', 'index');
                break;
            case 'tags':
                $router->get('tags', '\StoriesAPI\Endpoints\TagsController', 'index');
                break;
            // Add more endpoints as needed
        }
        
        // Capture the output
        ob_start();
        $router->handle();
        $output = ob_get_clean();
        
        // Check if the output is valid JSON
        $json = json_decode($output, true);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE) {
            echo "<div style='color: red; margin-bottom: 10px;'>";
            echo "<strong>❌ Invalid JSON response!</strong><br>";
            echo "<p>Error: " . json_last_error_msg() . "</p>";
            echo "<p>Raw output:</p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
            echo "</div>";
        } else {
            echo "<div style='color: green; margin-bottom: 10px;'>";
            echo "<strong>✅ Valid JSON response!</strong><br>";
            echo "<p>Response structure:</p>";
            echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . "</pre>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red; margin-bottom: 10px;'>";
        echo "<strong>❌ Exception occurred!</strong><br>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
    
    // Clean up any remaining output buffers
    while (ob_get_level() > $obLevel) {
        ob_end_clean();
    }
}

// Test stories endpoint
testEndpoint('stories', 'GET', ['pageSize' => 2]);

// Test authors endpoint
testEndpoint('authors', 'GET', ['pageSize' => 2]);

// Test tags endpoint
testEndpoint('tags', 'GET', ['pageSize' => 2]);

// Display troubleshooting information
echo "<h2>Troubleshooting Information</h2>";
echo "<p>If you're seeing invalid JSON responses, check for:</p>";
echo "<ul>";
echo "<li>PHP errors or warnings being output before the JSON response</li>";
echo "<li>Syntax errors in the controller files</li>";
echo "<li>Database connection issues</li>";
echo "<li>Missing or incorrect data in the database</li>";
echo "</ul>";

// Display PHP and server information
echo "<h2>System Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Environment: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'Not defined') . "</p>";