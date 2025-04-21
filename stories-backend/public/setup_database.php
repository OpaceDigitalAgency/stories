<?php
/**
 * Database Setup Script
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
    <title>Database Setup</title>
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
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Database Setup</h1>
    
    <div class="box">
        <?php
        try {
            // Load config
            $config = require __DIR__ . '/../api/v1/Config/config.php';
            
            // Connect to MySQL server (without selecting database)
            $root_mysqli = new mysqli(
                $config['db']['host'],
                'root',
                '',
                null,
                $config['db']['port']
            );
            
            if ($root_mysqli->connect_error) {
                throw new Exception("Failed to connect to MySQL server: " . $root_mysqli->connect_error);
            }
            
            echo "<h2>MySQL Server Connection</h2>";
            echo "<p class='success'>✓ Connected to MySQL server</p>";
            
            // Create database if it doesn't exist
            $dbname = $config['db']['name'];
            $result = $root_mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
            
            if (!$result) {
                throw new Exception("Failed to create database: " . $root_mysqli->error);
            }
            
            echo "<p class='success'>✓ Database '$dbname' created/verified</p>";
            
            // Create user if it doesn't exist
            $username = $config['db']['user'];
            $password = $config['db']['password'];
            
            $result = $root_mysqli->query("SELECT user FROM mysql.user WHERE user = '$username'");
            
            if ($result->num_rows === 0) {
                $result = $root_mysqli->query(
                    "CREATE USER '$username'@'localhost' IDENTIFIED BY '$password'"
                );
                
                if (!$result) {
                    throw new Exception("Failed to create user: " . $root_mysqli->error);
                }
                
                echo "<p class='success'>✓ User '$username' created</p>";
            } else {
                echo "<p class='success'>✓ User '$username' already exists</p>";
                
                // Update password
                $result = $root_mysqli->query(
                    "ALTER USER '$username'@'localhost' IDENTIFIED BY '$password'"
                );
                
                if (!$result) {
                    throw new Exception("Failed to update user password: " . $root_mysqli->error);
                }
                
                echo "<p class='success'>✓ User password updated</p>";
            }
            
            // Grant privileges
            $result = $root_mysqli->query(
                "GRANT ALL PRIVILEGES ON `$dbname`.* TO '$username'@'localhost'"
            );
            
            if (!$result) {
                throw new Exception("Failed to grant privileges: " . $root_mysqli->error);
            }
            
            echo "<p class='success'>✓ Granted all privileges to user</p>";
            
            // Flush privileges
            $result = $root_mysqli->query("FLUSH PRIVILEGES");
            
            if (!$result) {
                throw new Exception("Failed to flush privileges: " . $root_mysqli->error);
            }
            
            echo "<p class='success'>✓ Privileges updated</p>";
            
            // Close root connection
            $root_mysqli->close();
            
            // Test connection with new user
            $user_mysqli = new mysqli(
                $config['db']['host'],
                $config['db']['user'],
                $config['db']['password'],
                $config['db']['name'],
                $config['db']['port']
            );
            
            if ($user_mysqli->connect_error) {
                throw new Exception("Failed to connect with new user: " . $user_mysqli->connect_error);
            }
            
            echo "<h2>User Connection Test</h2>";
            echo "<p class='success'>✓ Successfully connected with new user</p>";
            
            // Close user connection
            $user_mysqli->close();
            
            echo "<div class='box'>";
            echo "<h2>Next Steps</h2>";
            echo "<p>Database and user have been set up successfully. You can now:</p>";
            echo "<ol>";
            echo "<li><a href='apply_database_changes.php'>Apply database schema</a></li>";
            echo "<li><a href='check_database.php'>Check database structure</a></li>";
            echo "<li><a href='verify_db_connection.php'>Verify database connection</a></li>";
            echo "</ol>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<h2>Error</h2>";
            echo "<p class='error'>Setup failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            
            echo "<div class='box'>";
            echo "<h2>Troubleshooting</h2>";
            echo "<ol>";
            echo "<li>Ensure you have root access to MySQL</li>";
            echo "<li>Check if MySQL server is running</li>";
            echo "<li>Verify port number in config.php</li>";
            echo "<li>Check if database name is valid</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>