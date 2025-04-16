<?php
/**
 * Database Connection Test Script
 *
 * This script tests the database connection and performs basic queries.
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

echo "<h1>Database Connection Test</h1>";

// Function to test a database query
function testQuery($db, $query, $params = [], $description = "Query") {
    try {
        $stmt = $db->query($query, $params);
        $result = $stmt->fetchAll();
        
        echo "<div style='color: green; margin-bottom: 10px;'>";
        echo "<strong>✅ $description successful!</strong><br>";
        echo "<p>Returned " . count($result) . " rows.</p>";
        echo "</div>";
        
        return $result;
    } catch (Exception $e) {
        echo "<div style='color: red; margin-bottom: 10px;'>";
        echo "<strong>❌ $description failed!</strong><br>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
        
        return null;
    }
}

// Test database connection
try {
    echo "<h2>Connection Test</h2>";
    $db = \StoriesAPI\Core\Database::getInstance($config['db']);
    echo "<div style='color: green; margin-bottom: 20px;'>";
    echo "<strong>✅ Database connection successful!</strong>";
    echo "</div>";
    
    // Display database configuration (with password hidden)
    echo "<h2>Database Configuration</h2>";
    echo "<pre>";
    $dbConfig = $config['db'];
    $dbConfig['password'] = '********'; // Hide password
    print_r($dbConfig);
    echo "</pre>";
    
    // Test basic query
    echo "<h2>Basic Query Test</h2>";
    testQuery($db, "SELECT 1 as test", [], "Basic query");
    
    // Test tables existence
    echo "<h2>Tables Existence Test</h2>";
    
    // List of tables to check
    $tables = [
        'stories',
        'authors',
        'story_authors',
        'tags',
        'story_tags',
        'media',
        'users'
    ];
    
    foreach ($tables as $table) {
        testQuery($db, "SELECT 1 FROM $table LIMIT 1", [], "Table '$table' check");
    }
    
    // Test stories query
    echo "<h2>Stories Query Test</h2>";
    $stories = testQuery($db, "SELECT id, title, slug FROM stories LIMIT 5", [], "Stories query");
    
    if ($stories && count($stories) > 0) {
        echo "<h3>Sample Stories:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Slug</th></tr>";
        
        foreach ($stories as $story) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($story['id']) . "</td>";
            echo "<td>" . htmlspecialchars($story['title']) . "</td>";
            echo "<td>" . htmlspecialchars($story['slug']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; margin-bottom: 20px;'>";
    echo "<strong>❌ Database connection failed!</strong><br>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    
    // Display troubleshooting information
    echo "<h2>Troubleshooting Information</h2>";
    echo "<p>Please check the following:</p>";
    echo "<ul>";
    echo "<li>Database server is running</li>";
    echo "<li>Database credentials are correct</li>";
    echo "<li>Database name exists</li>";
    echo "<li>Database user has proper permissions</li>";
    echo "<li>Database server allows connections from this host</li>";
    echo "</ul>";
}

// Display PHP and server information
echo "<h2>System Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PDO Drivers: " . implode(", ", PDO::getAvailableDrivers()) . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Environment: " . (defined('ENVIRONMENT') ? ENVIRONMENT : 'Not defined') . "</p>";