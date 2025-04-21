<?php
/**
 * Database Connection Check
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success { color: green; }
        .error { color: red; }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Database Connection Check</h1>
    
    <div class="box">
        <?php
        try {
            // Load config
            $config = require __DIR__ . '/../api/v1/Config/config.php';
            
            echo "<h2>Configuration</h2>";
            echo "<pre>";
            echo "Host: " . $config['db']['host'] . "\n";
            echo "Database: " . $config['db']['name'] . "\n";
            echo "User: " . $config['db']['user'] . "\n";
            echo "Port: " . $config['db']['port'] . "\n";
            echo "Charset: " . $config['db']['charset'] . "\n";
            echo "</pre>";
            
            // Create database connection
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s;port=%d',
                $config['db']['host'],
                $config['db']['name'],
                $config['db']['charset'],
                $config['db']['port']
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO(
                $dsn,
                $config['db']['user'],
                $config['db']['password'],
                $options
            );
            
            echo "<h2>Connection Test</h2>";
            echo "<p class='success'>✓ Successfully connected to database</p>";
            
            // Check tables
            echo "<h2>Table Check</h2>";
            $tables = [
                'authors' => [
                    'id', 'name', 'slug', 'bio', 'avatar_url',
                    'created_at', 'updated_at'
                ],
                'games' => [
                    'id', 'title', 'description', 'slug', 'genre',
                    'platform', 'developer', 'publisher', 'release_date',
                    'rating', 'price', 'created_at', 'updated_at'
                ],
                'directory_items' => [
                    'id', 'title', 'description', 'slug', 'url',
                    'category', 'rating', 'price_range',
                    'created_at', 'updated_at'
                ]
            ];
            
            foreach ($tables as $table => $columns) {
                echo "<h3>$table</h3>";
                
                // Check if table exists
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    echo "<p class='error'>✗ Table does not exist</p>";
                    continue;
                }
                
                echo "<p class='success'>✓ Table exists</p>";
                
                // Check columns
                $stmt = $pdo->query("SHOW COLUMNS FROM $table");
                $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $missingColumns = array_diff($columns, $existingColumns);
                if (!empty($missingColumns)) {
                    echo "<p class='error'>✗ Missing columns: " . implode(', ', $missingColumns) . "</p>";
                } else {
                    echo "<p class='success'>✓ All columns present</p>";
                }
                
                // Check row count
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "<p>Row count: $count</p>";
            }
            
            // Check indexes
            echo "<h2>Index Check</h2>";
            $indexes = [
                'authors' => ['idx_author_slug'],
                'games' => ['idx_game_slug'],
                'directory_items' => ['idx_directory_item_slug']
            ];
            
            foreach ($indexes as $table => $expectedIndexes) {
                echo "<h3>$table</h3>";
                
                $stmt = $pdo->query("SHOW INDEX FROM $table");
                $existingIndexes = array_column($stmt->fetchAll(), 'Key_name');
                
                foreach ($expectedIndexes as $index) {
                    if (in_array($index, $existingIndexes)) {
                        echo "<p class='success'>✓ Index $index exists</p>";
                    } else {
                        echo "<p class='error'>✗ Missing index: $index</p>";
                    }
                }
            }
            
            echo "<div class='box'>";
            echo "<h2>Next Steps</h2>";
            if (isset($_GET['fix']) && $_GET['fix'] === 'true') {
                echo "<p>Attempting to fix issues...</p>";
                
                // Get SQL file content
                $sql = file_get_contents(__DIR__ . '/../api/v1/database.sql');
                
                // Split into individual statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) { return !empty($stmt); }
                );
                
                // Execute each statement
                foreach ($statements as $statement) {
                    try {
                        $pdo->exec($statement);
                        echo "<p class='success'>✓ Successfully executed SQL statement</p>";
                    } catch (PDOException $e) {
                        if ($e->getCode() == '42S01') { // Table already exists
                            echo "<p class='success'>✓ Table already exists</p>";
                        } else {
                            throw $e;
                        }
                    }
                }
                
                echo "<p><a href='check_database.php'>Check again</a></p>";
            } else {
                echo "<p><a href='check_database.php?fix=true'>Fix issues</a></p>";
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<h2>Error</h2>";
            echo "<p class='error'>Failed to check database: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>