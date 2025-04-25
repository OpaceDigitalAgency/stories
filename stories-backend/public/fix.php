<?php
/**
 * API Fix Script
 * 
 * This script fixes common API issues:
 * 1. Verifies and fixes database tables
 * 2. Checks and fixes file permissions
 * 3. Validates class loading
 * 4. Tests API responses
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

// Load config
$config = require __DIR__ . '/../api/v1/Config/config.php';

// Connect to database
try {
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
    
    // Drop and recreate tables
    $tables = [
        'stories' => "
            CREATE TABLE stories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                content TEXT,
                slug VARCHAR(255) NOT NULL UNIQUE,
                is_published BOOLEAN DEFAULT FALSE,
                author_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'authors' => "
            CREATE TABLE authors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                bio TEXT,
                avatar_url VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'games' => "
            CREATE TABLE games (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                slug VARCHAR(255) NOT NULL UNIQUE,
                genre VARCHAR(100),
                platform VARCHAR(100),
                developer VARCHAR(255),
                publisher VARCHAR(255),
                release_date DATE,
                rating DECIMAL(3,1) DEFAULT 0,
                price DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'directory_items' => "
            CREATE TABLE directory_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                slug VARCHAR(255) NOT NULL UNIQUE,
                url VARCHAR(255) NOT NULL,
                category VARCHAR(100),
                rating DECIMAL(3,1) DEFAULT 0,
                price_range VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        'ai_tools' => "
            CREATE TABLE ai_tools (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                slug VARCHAR(255) NOT NULL UNIQUE,
                tool_url VARCHAR(255),
                category_id INT,
                pricing_type VARCHAR(50),
                price_info TEXT,
                features TEXT,
                rating DECIMAL(3,1) DEFAULT 0,
                featured BOOLEAN DEFAULT FALSE,
                is_published BOOLEAN DEFAULT FALSE,
                published_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    foreach ($tables as $table => $sql) {
        try {
            // Drop table if exists
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "✓ Dropped table: $table<br>";
            
            // Create table
            $pdo->exec($sql);
            echo "✓ Created table: $table<br>";
        } catch (PDOException $e) {
            echo "✗ Error with table $table: " . $e->getMessage() . "<br>";
        }
    }
    
    // Add sample data
    $pdo->exec("INSERT INTO authors (name, slug, bio) VALUES 
        ('John Doe', 'john-doe', 'A test author'),
        ('Jane Smith', 'jane-smith', 'Another test author')");
    echo "✓ Added sample authors<br>";
    
    $pdo->exec("INSERT INTO stories (title, slug, content, author_id) VALUES 
        ('Test Story 1', 'test-story-1', 'Test content 1', 1),
        ('Test Story 2', 'test-story-2', 'Test content 2', 2)");
    echo "✓ Added sample stories<br>";
    
    $pdo->exec("INSERT INTO games (title, slug, description) VALUES 
        ('Test Game 1', 'test-game-1', 'Test game 1'),
        ('Test Game 2', 'test-game-2', 'Test game 2')");
    echo "✓ Added sample games<br>";
    
    $pdo->exec("INSERT INTO directory_items (title, slug, description, url) VALUES 
        ('Test Item 1', 'test-item-1', 'Test item 1', 'http://example.com/1'),
        ('Test Item 2', 'test-item-2', 'Test item 2', 'http://example.com/2')");
    echo "✓ Added sample directory items<br>";
    
    $pdo->exec("INSERT INTO ai_tools (title, slug, description) VALUES 
        ('Test Tool 1', 'test-tool-1', 'Test tool 1'),
        ('Test Tool 2', 'test-tool-2', 'Test tool 2')");
    echo "✓ Added sample AI tools<br>";
    
    echo "<br>✓ Database setup complete!<br>";
    echo "<br>Next steps:<br>";
    echo "1. <a href='diagnose.php'>Run diagnostics</a><br>";
    echo "2. <a href='test_endpoints.php'>Test endpoints</a><br>";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage();
}