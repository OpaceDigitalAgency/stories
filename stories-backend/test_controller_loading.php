<?php
/**
 * Controller Loading Test Script
 * 
 * This script tests if the controllers can be loaded correctly.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/test-error.log');

// Define the base path
$basePath = __DIR__ . '/api/v1';

// Define the controllers to test
$controllers = [
    'GamesController.php',
    'DirectoryItemsController.php',
    'AiToolsController.php'
];

// Check if controllers exist in Endpoints directory
echo "<h1>Controller Loading Test</h1>";
echo "<h2>Checking controller files in Endpoints directory</h2>";
echo "<ul>";

foreach ($controllers as $controller) {
    $upperCasePath = $basePath . '/Endpoints/' . $controller;
    $lowerCasePath = $basePath . '/endpoints/' . $controller;
    
    echo "<li>$controller: ";
    
    if (file_exists($upperCasePath)) {
        echo "Found at <code>$upperCasePath</code>";
    } elseif (file_exists($lowerCasePath)) {
        echo "Found at <code>$lowerCasePath</code>";
    } else {
        echo "Not found";
    }
    
    echo "</li>";
}

echo "</ul>";

// Try to include the controllers
echo "<h2>Trying to include controllers</h2>";
echo "<ul>";

foreach ($controllers as $controller) {
    $upperCasePath = $basePath . '/Endpoints/' . $controller;
    $lowerCasePath = $basePath . '/endpoints/' . $controller;
    
    echo "<li>$controller: ";
    
    try {
        if (file_exists($upperCasePath)) {
            include_once $upperCasePath;
            echo "Included successfully from <code>$upperCasePath</code>";
        } elseif (file_exists($lowerCasePath)) {
            include_once $lowerCasePath;
            echo "Included successfully from <code>$lowerCasePath</code>";
        } else {
            echo "Not found";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    
    echo "</li>";
}

echo "</ul>";

// Check if autoloader is working
echo "<h2>Testing autoloader</h2>";
echo "<ul>";

try {
    // Include autoloader
    require_once $basePath . '/autoload.php';
    echo "<li>Autoloader included successfully</li>";
    
    // Try to load controllers using autoloader
    $controllerClasses = [
        'StoriesAPI\Endpoints\GamesController',
        'StoriesAPI\Endpoints\DirectoryItemsController',
        'StoriesAPI\Endpoints\AiToolsController'
    ];
    
    foreach ($controllerClasses as $class) {
        echo "<li>$class: ";
        
        try {
            if (class_exists($class)) {
                echo "Class exists";
            } else {
                echo "Class does not exist";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        
        echo "</li>";
    }
} catch (Exception $e) {
    echo "<li>Error loading autoloader: " . $e->getMessage() . "</li>";
}

echo "</ul>";

// Check API routes
echo "<h2>Testing API routes</h2>";
echo "<ul>";

$routes = [
    '/api/v1/games',
    '/api/v1/directory-items',
    '/api/v1/ai-tools'
];

foreach ($routes as $route) {
    echo "<li>$route: ";
    
    $url = 'https://api.storiesfromtheweb.org' . $route;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Status: $httpCode";
    
    if ($error) {
        echo ", Error: $error";
    } else {
        echo ", Response length: " . strlen($response);
        
        // Try to decode JSON
        $json = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo ", Valid JSON";
        } else {
            echo ", Invalid JSON: " . json_last_error_msg();
        }
    }
    
    echo "</li>";
}

echo "</ul>";

// Check file permissions
echo "<h2>Checking file permissions</h2>";
echo "<ul>";

foreach ($controllers as $controller) {
    $upperCasePath = $basePath . '/Endpoints/' . $controller;
    $lowerCasePath = $basePath . '/endpoints/' . $controller;
    
    echo "<li>$controller: ";
    
    if (file_exists($upperCasePath)) {
        $perms = fileperms($upperCasePath);
        echo "Permissions: " . substr(sprintf('%o', $perms), -4);
    } elseif (file_exists($lowerCasePath)) {
        $perms = fileperms($lowerCasePath);
        echo "Permissions: " . substr(sprintf('%o', $perms), -4);
    } else {
        echo "Not found";
    }
    
    echo "</li>";
}

echo "</ul>";

// Check PHP version and extensions
echo "<h2>PHP Information</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>PDO Enabled: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "</li>";
echo "<li>PDO MySQL Enabled: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "</li>";
echo "</ul>";

// Check database connection
echo "<h2>Database Connection Test</h2>";
echo "<ul>";

try {
    // Database configuration
    $config = [
        'host' => 'localhost',
        'name' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4',
        'port' => 3306
    ];
    
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "<li>Database connection: Success</li>";
    
    // Check tables
    $tables = ['games', 'directory_items', 'ai_tools'];
    
    foreach ($tables as $table) {
        echo "<li>$table table: ";
        
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "$count records found";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        
        echo "</li>";
    }
} catch (Exception $e) {
    echo "<li>Database connection error: " . $e->getMessage() . "</li>";
}

echo "</ul>";