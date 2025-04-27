<?php
/**
 * Verify Database Data Script
 * 
 * This script verifies the data in the database tables for games, directory items, and AI tools.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/verify-data.log');

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Database Data</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .success {
            color: #27ae60;
        }
        .error {
            color: #e74c3c;
        }
        .warning {
            color: #f39c12;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 300px;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verify Database Data</h1>';

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
try {
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
    
    echo '<p>Connected to database successfully.</p>';
    
    // Tables to verify
    $tables = [
        'games',
        'directory_items',
        'ai_tools'
    ];
    
    // Verify each table
    foreach ($tables as $table) {
        echo "<h2>Table: $table</h2>";
        
        // Check if table exists
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            echo "<p class='error'>Table '$table' does not exist!</p>";
            continue;
        }
        
        // Show table structure
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
        
        foreach ($columns as $column) {
            echo '<tr>';
            echo '<td>' . $column['Field'] . '</td>';
            echo '<td>' . $column['Type'] . '</td>';
            echo '<td>' . $column['Null'] . '</td>';
            echo '<td>' . $column['Key'] . '</td>';
            echo '<td>' . $column['Default'] . '</td>';
            echo '<td>' . $column['Extra'] . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Check if table has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        
        echo "<p>Table '$table' has $count records.</p>";
        
        // Show sample data
        $stmt = $db->query("SELECT * FROM $table LIMIT 5");
        $rows = $stmt->fetchAll();
        
        if ($rows) {
            echo '<h3>Sample Data</h3>';
            echo '<table>';
            echo '<tr>';
            foreach (array_keys($rows[0]) as $column) {
                echo '<th>' . $column . '</th>';
            }
            echo '</tr>';
            
            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . htmlspecialchars($value) . '</td>';
                }
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p>No data available in this table.</p>';
        }
    }
    
} catch (PDOException $e) {
    echo '<p class="error">Database error: ' . $e->getMessage() . '</p>';
}

echo '</div></body></html>';