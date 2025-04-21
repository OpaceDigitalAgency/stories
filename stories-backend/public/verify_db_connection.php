<?php
/**
 * Database Connection Verification
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
    <title>Database Connection Verification</title>
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
    <h1>Database Connection Verification</h1>
    
    <div class="box">
        <?php
        try {
            // Load config
            $config = require __DIR__ . '/../api/v1/Config/config.php';
            
            echo "<h2>Database Configuration</h2>";
            echo "<pre>";
            echo "Host: " . $config['db']['host'] . "\n";
            echo "Database: " . $config['db']['name'] . "\n";
            echo "User: " . $config['db']['user'] . "\n";
            echo "Port: " . $config['db']['port'] . "\n";
            echo "Charset: " . $config['db']['charset'] . "\n";
            echo "</pre>";
            
            // Test MySQL server connection
            echo "<h2>MySQL Server Connection</h2>";
            $mysqli = new mysqli(
                $config['db']['host'],
                $config['db']['user'],
                $config['db']['password'],
                null,
                $config['db']['port']
            );
            
            if ($mysqli->connect_error) {
                throw new Exception("Failed to connect to MySQL server: " . $mysqli->connect_error);
            }
            
            echo "<p class='success'>✓ Successfully connected to MySQL server</p>";
            
            // Test database existence
            echo "<h2>Database Check</h2>";
            $result = $mysqli->query("SHOW DATABASES LIKE '" . $config['db']['name'] . "'");
            if ($result->num_rows === 0) {
                echo "<p class='error'>✗ Database '" . $config['db']['name'] . "' does not exist</p>";
                
                if (isset($_GET['create']) && $_GET['create'] === 'true') {
                    $mysqli->query("CREATE DATABASE IF NOT EXISTS " . $config['db']['name']);
                    echo "<p class='success'>✓ Created database '" . $config['db']['name'] . "'</p>";
                } else {
                    echo "<p><a href='verify_db_connection.php?create=true'>Create database</a></p>";
                }
            } else {
                echo "<p class='success'>✓ Database exists</p>";
            }
            
            // Test database connection
            echo "<h2>Database Connection</h2>";
            $mysqli->select_db($config['db']['name']);
            
            if ($mysqli->error) {
                throw new Exception("Failed to select database: " . $mysqli->error);
            }
            
            echo "<p class='success'>✓ Successfully connected to database</p>";
            
            // Test user privileges
            echo "<h2>User Privileges</h2>";
            $result = $mysqli->query("SHOW GRANTS FOR CURRENT_USER()");
            
            echo "<pre>";
            while ($row = $result->fetch_row()) {
                echo htmlspecialchars($row[0]) . "\n";
            }
            echo "</pre>";
            
            // Test creating a table
            echo "<h2>Table Creation Test</h2>";
            $result = $mysqli->query("
                CREATE TABLE IF NOT EXISTS connection_test (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    test_column VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            if ($result) {
                echo "<p class='success'>✓ Successfully created test table</p>";
                
                // Clean up
                $mysqli->query("DROP TABLE connection_test");
                echo "<p class='success'>✓ Successfully cleaned up test table</p>";
            } else {
                echo "<p class='error'>✗ Failed to create test table: " . $mysqli->error . "</p>";
            }
            
            // Close connection
            $mysqli->close();
            
            echo "<div class='box'>";
            echo "<h2>Next Steps</h2>";
            echo "<p>Database connection is working properly. You can now:</p>";
            echo "<ol>";
            echo "<li><a href='apply_database_changes.php'>Apply database schema</a></li>";
            echo "<li><a href='check_database.php'>Check database structure</a></li>";
            echo "</ol>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<h2>Error</h2>";
            echo "<p class='error'>Failed to verify database connection: " . htmlspecialchars($e->getMessage()) . "</p>";
            
            echo "<div class='box'>";
            echo "<h2>Troubleshooting</h2>";
            echo "<ol>";
            echo "<li>Check if MySQL server is running</li>";
            echo "<li>Verify database credentials in config.php</li>";
            echo "<li>Ensure database user has proper privileges</li>";
            echo "<li>Check if database exists and is accessible</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>