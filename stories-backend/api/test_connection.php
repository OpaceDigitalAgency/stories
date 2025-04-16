<?php
/**
 * API Connection Test Script
 * 
 * This script tests the database connection and API functionality.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the base path
define('BASE_PATH', __DIR__);

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

echo "<h1>API Connection Test</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    $db = \StoriesAPI\Core\Database::getInstance($config['db']);
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test a simple query
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✅ Database query successful!</p>";
    
    // Test stories table
    $stmt = $db->query("SELECT COUNT(*) as count FROM stories");
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✅ Stories table accessible! Found {$result['count']} stories.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test API routing
echo "<h2>API Routing Test</h2>";
echo "<p>Testing API routing with a mock request...</p>";

// Create a mock request to the stories endpoint
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/v1/stories';

// Initialize Router
try {
    $router = new \StoriesAPI\Core\Router($config);
    echo "<p style='color: green;'>✅ Router initialized successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Router initialization failed: " . $e->getMessage() . "</p>";
}

// Test CORS configuration
echo "<h2>CORS Configuration Test</h2>";
echo "<p>Allowed origins:</p>";
echo "<ul>";
foreach ($config['security']['cors']['allowed_origins'] as $origin) {
    echo "<li>{$origin}</li>";
}
echo "</ul>";

// Test API URL configuration
echo "<h2>API URL Configuration Test</h2>";
echo "<p>API Version: {$config['api']['version']}</p>";
echo "<p>Expected API URL format: /api/{$config['api']['version']}/endpoint</p>";

// Output server information
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Server Name: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";

// Output environment
echo "<h2>Environment</h2>";
echo "<p>Environment: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'Not defined') . "</p>";