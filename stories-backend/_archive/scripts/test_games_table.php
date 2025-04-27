<?php
/**
 * Direct test script for the games table
 * This script bypasses the API and directly queries the database
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Games Table Direct Test</h1>";

// Database connection parameters - adjust these to match your configuration
$host = 'localhost';
$dbname = 'stories';
$username = 'stories_user';
$password = 'stories_password';

echo "<h2>Database Connection Test</h2>";
echo "<p>Attempting to connect to database: $dbname on $host</p>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "<p style='color:green'>✅ Database connection successful!</p>";
    
    // Check if games table exists
    echo "<h2>Games Table Check</h2>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'games'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color:green'>✅ Games table exists</p>";
        
        // Get table structure
        echo "<h2>Games Table Structure</h2>";
        $stmt = $pdo->query("DESCRIBE games");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Query data
        echo "<h2>Games Table Data</h2>";
        $stmt = $pdo->query("SELECT * FROM games LIMIT 10");
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($games) > 0) {
            echo "<p>Found " . count($games) . " games:</p>";
            
            // Display raw data
            echo "<h3>Raw Data</h3>";
            echo "<pre>" . json_encode($games, JSON_PRETTY_PRINT) . "</pre>";
            
            // Format data in the expected structure
            echo "<h3>Formatted Data (API Format)</h3>";
            $formattedGames = [];
            foreach ($games as $game) {
                $attributes = [];
                foreach ($game as $key => $value) {
                    if ($key !== 'id') {
                        // Convert snake_case to camelCase for certain fields
                        if ($key === 'is_published') {
                            $attributes['isPublished'] = (bool)$value;
                        } elseif ($key === 'published_at') {
                            $attributes['publishedAt'] = $value;
                        } elseif ($key === 'created_at') {
                            $attributes['createdAt'] = $value;
                        } elseif ($key === 'updated_at') {
                            $attributes['updatedAt'] = $value;
                        } else {
                            $attributes[$key] = $value;
                        }
                    }
                }
                
                $formattedGames[] = [
                    'id' => $game['id'],
                    'attributes' => $attributes
                ];
            }
            
            $response = [
                'data' => $formattedGames,
                'meta' => [
                    'pagination' => [
                        'page' => 1,
                        'pageSize' => count($games),
                        'pageCount' => 1,
                        'total' => count($games)
                    ]
                ]
            ];
            
            echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p style='color:orange'>⚠️ No games found in the table</p>";
        }
        
        // Check for case sensitivity issues in column names
        echo "<h2>Case Sensitivity Check</h2>";
        $caseIssues = [];
        foreach ($columns as $column) {
            $field = $column['Field'];
            if ($field !== strtolower($field) && $field !== 'ID') {
                $caseIssues[] = $field;
            }
        }
        
        if (count($caseIssues) > 0) {
            echo "<p style='color:orange'>⚠️ Found columns with potential case sensitivity issues:</p>";
            echo "<ul>";
            foreach ($caseIssues as $field) {
                echo "<li>$field</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:green'>✅ No case sensitivity issues found in column names</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Games table does not exist!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li>If the connection failed, check your database credentials in the API configuration.</li>";
echo "<li>If the games table doesn't exist, you need to create it using the database schema.</li>";
echo "<li>If no games were found, you may need to add some test data.</li>";
echo "<li>If case sensitivity issues were found, consider standardizing your column names.</li>";
echo "</ul>";