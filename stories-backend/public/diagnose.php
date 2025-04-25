<?php
/**
 * API Diagnostic Script
 * 
 * This script checks each component of the API to identify issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Diagnostic</title>
    <style>
        body { font-family: Arial; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; }
    </style>
</head>
<body>
    <h1>API Diagnostic</h1>
    
    <?php
    // Check config
    echo "<h2>1. Configuration Check</h2>";
    try {
        $config = require __DIR__ . '/../api/v1/Config/config.php';
        echo "<p class='success'>✓ Config file loaded</p>";
        echo "<pre>";
        echo "Database: " . $config['db']['name'] . "\n";
        echo "Host: " . $config['db']['host'] . "\n";
        echo "User: " . $config['db']['user'] . "\n";
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Config error: " . $e->getMessage() . "</p>";
    }
    
    // Check database connection
    echo "<h2>2. Database Connection</h2>";
    try {
        require_once __DIR__ . '/../api/v1/Core/Database.php';
        $db = new StoriesAPI\Core\Database($config);
        $stmt = $db->query("SELECT NOW() as time");
        $result = $stmt->fetch();
        echo "<p class='success'>✓ Database connected</p>";
        echo "<pre>Server time: " . $result['time'] . "</pre>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
    }
    
    // Check tables
    echo "<h2>3. Table Check</h2>";
    $tables = ['stories', 'authors', 'games', 'directory_items', 'ai_tools'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<p class='success'>✓ Table '$table' exists</p>";
            echo "<pre>";
            foreach ($columns as $col) {
                echo $col['Field'] . " (" . $col['Type'] . ")\n";
            }
            echo "</pre>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Table '$table' error: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check autoloader
    echo "<h2>4. Class Autoloader</h2>";
    $classes = [
        'StoriesAPI\\Core\\BaseController',
        'StoriesAPI\\Core\\Router',
        'StoriesAPI\\Utils\\Response',
        'StoriesAPI\\Endpoints\\StoriesController',
        'StoriesAPI\\Endpoints\\AuthorsController',
        'StoriesAPI\\Endpoints\\GamesController',
        'StoriesAPI\\Endpoints\\DirectoryItemsController',
        'StoriesAPI\\Endpoints\\AiToolsController'
    ];
    
    foreach ($classes as $class) {
        try {
            if (class_exists($class)) {
                echo "<p class='success'>✓ Class '$class' loaded</p>";
            } else {
                echo "<p class='error'>✗ Class '$class' not found</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error loading '$class': " . $e->getMessage() . "</p>";
        }
    }
    
    // Test endpoint responses
    echo "<h2>5. Endpoint Response Format</h2>";
    $endpoints = [
        '/api/v1/stories',
        '/api/v1/authors',
        '/api/v1/games',
        '/api/v1/directory-items',
        '/api/v1/ai-tools'
    ];
    
    foreach ($endpoints as $endpoint) {
        try {
            $response = file_get_contents("http://" . $_SERVER['HTTP_HOST'] . $endpoint);
            $data = json_decode($response, true);
            
            if ($data === null) {
                echo "<p class='error'>✗ Invalid JSON from $endpoint</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            } else {
                echo "<p class='success'>✓ Valid JSON from $endpoint</p>";
                echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error testing $endpoint: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check error logs
    echo "<h2>6. Error Log Check</h2>";
    $logFile = __DIR__ . '/../logs/php-errors.log';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        if ($logs) {
            echo "<p>Recent errors:</p>";
            echo "<pre>" . htmlspecialchars($logs) . "</pre>";
        } else {
            echo "<p class='success'>✓ No recent errors</p>";
        }
    } else {
        echo "<p class='error'>✗ Error log file not found</p>";
    }
    ?>
    
    <h2>Next Steps</h2>
    <ul>
        <li>Fix any configuration issues</li>
        <li>Create missing database tables</li>
        <li>Fix any class loading issues</li>
        <li>Check error logs for details</li>
    </ul>
</body>
</html>