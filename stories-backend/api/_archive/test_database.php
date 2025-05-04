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
    
    // Debug output for file loading
    error_log("Attempting to load class: $class from file: $file");
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
        error_log("Successfully loaded: $file");
    } else {
        error_log("File not found: $file");
    }
});

// Load configuration
$configPath = __DIR__ . '/v1/config/config.php';
if (!file_exists($configPath)) {
    // Try alternative config path
    $configPath = __DIR__ . '/v1/config.php';
    if (!file_exists($configPath)) {
        echo "<div style='color: red; margin-bottom: 20px;'>";
        echo "<strong>❌ Configuration file not found!</strong><br>";
        echo "<p>Paths checked: " . htmlspecialchars(__DIR__ . '/v1/config/config.php') . " and " . htmlspecialchars(__DIR__ . '/v1/config.php') . "</p>";
        echo "</div>";
        exit;
    }
}

$config = require $configPath;

echo "<h1>Database Connection Test</h1>";

// Debug information
echo "<h2>Debug Information</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Autoloader Path: " . __DIR__ . '/v1/' . "\n";
echo "Database Class: " . (class_exists('\\StoriesAPI\\Core\\Database') ? 'Found' : 'Not Found') . "\n";
echo "</pre>";

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
    
    // Check if the Database class exists
    if (!class_exists('\\StoriesAPI\\Core\\Database')) {
        echo "<div style='color: red; margin-bottom: 20px;'>";
        echo "<strong>❌ Database class not found!</strong><br>";
        echo "<p>Make sure the class file exists at: " . htmlspecialchars(__DIR__ . '/v1/core/Database.php') . "</p>";
        
        // Try to include the file directly
        $databaseClassFile = __DIR__ . '/v1/core/Database.php';
        if (file_exists($databaseClassFile)) {
            echo "<p>File exists, attempting to include directly...</p>";
            require_once $databaseClassFile;
            
            if (class_exists('\\StoriesAPI\\Core\\Database')) {
                echo "<p style='color: green;'>Successfully loaded Database class!</p>";
            } else {
                echo "<p>Still unable to load Database class after direct inclusion.</p>";
                echo "<p>Check for namespace issues or PHP errors in the file.</p>";
                throw new Exception("Database class could not be loaded");
            }
        } else {
            echo "<p>Database class file does not exist!</p>";
            throw new Exception("Database class file not found");
        }
    }
    
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