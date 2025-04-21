<?php
/**
 * Database Connection Verification
 * 
 * This script verifies all database connections across the application
 * to ensure they're using the correct credentials.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
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
            echo "<h2>API Configuration</h2>";
            $apiConfig = require __DIR__ . '/../api/v1/Config/config.php';
            echo "<pre>";
            echo "Host: " . $apiConfig['db']['host'] . "\n";
            echo "Database: " . $apiConfig['db']['name'] . "\n";
            echo "User: " . $apiConfig['db']['user'] . "\n";
            echo "Port: " . $apiConfig['db']['port'] . "\n";
            echo "Charset: " . $apiConfig['db']['charset'] . "\n";
            echo "</pre>";
            
            echo "<h2>Admin Configuration</h2>";
            $adminConfig = require __DIR__ . '/../admin/includes/config.php';
            echo "<pre>";
            echo "Host: " . $adminConfig['db']['host'] . "\n";
            echo "Database: " . $adminConfig['db']['name'] . "\n";
            echo "User: " . $adminConfig['db']['user'] . "\n";
            echo "Port: " . $adminConfig['db']['port'] . "\n";
            echo "Charset: " . $adminConfig['db']['charset'] . "\n";
            echo "</pre>";
            
            // Verify configurations match
            echo "<h2>Configuration Comparison</h2>";
            $matches = true;
            foreach (['host', 'name', 'user', 'password', 'charset', 'port'] as $key) {
                if ($apiConfig['db'][$key] !== $adminConfig['db'][$key]) {
                    echo "<p class='error'>✗ $key mismatch:</p>";
                    echo "<pre>";
                    echo "API: " . $apiConfig['db'][$key] . "\n";
                    echo "Admin: " . $adminConfig['db'][$key] . "\n";
                    echo "</pre>";
                    $matches = false;
                }
            }
            
            if ($matches) {
                echo "<p class='success'>✓ All configurations match</p>";
            }
            
            // Test API database connection
            echo "<h2>API Database Connection</h2>";
            require_once __DIR__ . '/../api/v1/Core/Database.php';
            $apiDb = new \StoriesAPI\Core\Database($apiConfig);
            $stmt = $apiDb->query("SELECT NOW() as time");
            $result = $stmt->fetch();
            echo "<p class='success'>✓ API database connection successful</p>";
            echo "<pre>Server time: " . $result['time'] . "</pre>";
            
            // Test Admin database connection
            echo "<h2>Admin Database Connection</h2>";
            require_once __DIR__ . '/../admin/includes/Database.php';
            $adminDb = \Admin\Database::getInstance();
            $stmt = $adminDb->query("SELECT NOW() as time");
            $result = $stmt->fetch();
            echo "<p class='success'>✓ Admin database connection successful</p>";
            echo "<pre>Server time: " . $result['time'] . "</pre>";
            
            // Check tables
            echo "<h2>Table Check</h2>";
            $tables = [
                'authors',
                'games',
                'directory_items',
                'admin_users',
                'auth_tokens'
            ];
            
            foreach ($tables as $table) {
                echo "<h3>$table</h3>";
                
                try {
                    $stmt = $adminDb->query("SHOW TABLES LIKE ?", [$table]);
                    if ($stmt->rowCount() > 0) {
                        echo "<p class='success'>✓ Table exists</p>";
                        
                        // Get row count
                        $stmt = $adminDb->query("SELECT COUNT(*) as count FROM $table");
                        $count = $stmt->fetch()['count'];
                        echo "<p>Row count: $count</p>";
                    } else {
                        echo "<p class='error'>✗ Table does not exist</p>";
                    }
                } catch (\Exception $e) {
                    echo "<p class='error'>✗ Error checking table: " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<div class='box'>";
            echo "<h2>Next Steps</h2>";
            if ($matches) {
                echo "<p class='success'>All database connections are properly configured!</p>";
                echo "<p>You can now:</p>";
                echo "<ol>";
                echo "<li><a href='check_database.php'>Check database structure</a></li>";
                echo "<li><a href='apply_database_changes.php'>Apply any pending changes</a></li>";
                echo "</ol>";
            } else {
                echo "<p class='error'>Database configurations do not match!</p>";
                echo "<p>Please update the configurations to match in:</p>";
                echo "<ol>";
                echo "<li>/api/v1/Config/config.php</li>";
                echo "<li>/admin/includes/config.php</li>";
                echo "</ol>";
            }
            echo "</div>";
            
        } catch (\Exception $e) {
            echo "<h2>Error</h2>";
            echo "<p class='error'>Verification failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            
            echo "<div class='box'>";
            echo "<h2>Troubleshooting</h2>";
            echo "<ol>";
            echo "<li>Check if MySQL server is running</li>";
            echo "<li>Verify database credentials in both config files</li>";
            echo "<li>Ensure database exists and is accessible</li>";
            echo "<li>Check if required tables exist</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>