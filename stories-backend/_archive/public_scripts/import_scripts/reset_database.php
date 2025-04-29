<?php
/**
 * Database Reset Script
 * 
 * This script drops and recreates all tables with proper structure
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Reset</title>
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
    <h1>Database Reset</h1>
    
    <div class="box">
        <?php
        try {
            $config = require __DIR__ . '/../api/v1/Config/config.php';
            
            // Connect to database
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
            
            echo "<h2>Dropping Tables</h2>";
            
            // Drop tables in correct order
            $tables = [
                'auth_tokens',
                'admin_users',
                'directory_items',
                'games',
                'authors'
            ];
            
            foreach ($tables as $table) {
                try {
                    $pdo->exec("DROP TABLE IF EXISTS $table");
                    echo "<p class='success'>✓ Dropped table: $table</p>";
                } catch (PDOException $e) {
                    echo "<p class='error'>✗ Failed to drop table $table: " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<h2>Creating Tables</h2>";
            
            // Create authors table
            $sql = "CREATE TABLE authors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                bio TEXT,
                avatar_url VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_author_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            echo "<p class='success'>✓ Created table: authors</p>";
            
            // Create games table
            $sql = "CREATE TABLE games (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                slug VARCHAR(255) NOT NULL,
                genre VARCHAR(100),
                platform VARCHAR(100),
                developer VARCHAR(255),
                publisher VARCHAR(255),
                release_date DATE,
                rating DECIMAL(3,1) DEFAULT 0,
                price DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_game_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            echo "<p class='success'>✓ Created table: games</p>";
            
            // Create directory_items table
            $sql = "CREATE TABLE directory_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                slug VARCHAR(255) NOT NULL,
                url VARCHAR(255) NOT NULL,
                category VARCHAR(100),
                rating DECIMAL(3,1) DEFAULT 0,
                price_range VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_directory_item_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            echo "<p class='success'>✓ Created table: directory_items</p>";
            
            // Create admin_users table
            $sql = "CREATE TABLE admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                UNIQUE KEY unique_username (username),
                UNIQUE KEY unique_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            echo "<p class='success'>✓ Created table: admin_users</p>";
            
            // Create auth_tokens table
            $sql = "CREATE TABLE auth_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                UNIQUE KEY unique_token (token),
                FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            echo "<p class='success'>✓ Created table: auth_tokens</p>";
            
            echo "<div class='box'>";
            echo "<h2>Next Steps</h2>";
            echo "<p class='success'>Database has been reset successfully!</p>";
            echo "<p>You can now:</p>";
            echo "<ol>";
            echo "<li><a href='check_database.php'>Check database structure</a></li>";
            echo "<li><a href='verify_all_connections.php'>Verify all connections</a></li>";
            echo "</ol>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<h2>Error</h2>";
            echo "<p class='error'>Reset failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            
            echo "<div class='box'>";
            echo "<h2>Troubleshooting</h2>";
            echo "<ol>";
            echo "<li>Check if you have proper permissions to drop/create tables</li>";
            echo "<li>Verify database credentials in config.php</li>";
            echo "<li>Ensure MySQL server is running</li>";
            echo "</ol>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>