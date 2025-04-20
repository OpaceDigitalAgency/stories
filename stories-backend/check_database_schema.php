<?php
/**
 * Database Schema Check Script
 * 
 * This script checks the database schema for the tables used by the API endpoints.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/schema-check.log');

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
    
    echo "<h1>Database Schema Check</h1>";
    echo "<p>Connected to database {$config['name']} successfully.</p>";
    
    // Tables to check
    $tables = [
        'games',
        'directory_items',
        'directory_categories',
        'ai_tools',
        'ai_tool_categories'
    ];
    
    // Check each table
    foreach ($tables as $table) {
        echo "<h2>Table: $table</h2>";
        
        // Check if table exists
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            echo "<p class='error'>Table '$table' does not exist!</p>";
            
            // Create table if it doesn't exist
            echo "<h3>Creating table '$table'</h3>";
            
            switch ($table) {
                case 'games':
                    $db->exec("CREATE TABLE `games` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `title` varchar(255) NOT NULL,
                        `description` text,
                        `slug` varchar(255) NOT NULL,
                        `featured` tinyint(1) DEFAULT '0',
                        `is_published` tinyint(1) DEFAULT '0',
                        `published_at` datetime DEFAULT NULL,
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `slug` (`slug`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    echo "<p>Table 'games' created successfully.</p>";
                    break;
                    
                case 'directory_categories':
                    $db->exec("CREATE TABLE `directory_categories` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(255) NOT NULL,
                        `slug` varchar(255) NOT NULL,
                        `description` text,
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `slug` (`slug`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    echo "<p>Table 'directory_categories' created successfully.</p>";
                    break;
                    
                case 'directory_items':
                    $db->exec("CREATE TABLE `directory_items` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `title` varchar(255) NOT NULL,
                        `description` text,
                        `slug` varchar(255) NOT NULL,
                        `category_id` int(11) DEFAULT NULL,
                        `website_url` varchar(255) DEFAULT NULL,
                        `contact_email` varchar(255) DEFAULT NULL,
                        `contact_phone` varchar(50) DEFAULT NULL,
                        `address` text,
                        `featured` tinyint(1) DEFAULT '0',
                        `is_published` tinyint(1) DEFAULT '0',
                        `published_at` datetime DEFAULT NULL,
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `slug` (`slug`),
                        KEY `category_id` (`category_id`),
                        CONSTRAINT `directory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `directory_categories` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    echo "<p>Table 'directory_items' created successfully.</p>";
                    break;
                    
                case 'ai_tool_categories':
                    $db->exec("CREATE TABLE `ai_tool_categories` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `name` varchar(255) NOT NULL,
                        `slug` varchar(255) NOT NULL,
                        `description` text,
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `slug` (`slug`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    echo "<p>Table 'ai_tool_categories' created successfully.</p>";
                    break;
                    
                case 'ai_tools':
                    $db->exec("CREATE TABLE `ai_tools` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `title` varchar(255) NOT NULL,
                        `description` text,
                        `slug` varchar(255) NOT NULL,
                        `category_id` int(11) DEFAULT NULL,
                        `tool_url` varchar(255) DEFAULT NULL,
                        `pricing_type` enum('free','freemium','paid') DEFAULT 'free',
                        `price_info` text,
                        `features` text,
                        `rating` float DEFAULT '0',
                        `featured` tinyint(1) DEFAULT '0',
                        `is_published` tinyint(1) DEFAULT '0',
                        `published_at` datetime DEFAULT NULL,
                        `created_at` datetime NOT NULL,
                        `updated_at` datetime NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `slug` (`slug`),
                        KEY `category_id` (`category_id`),
                        CONSTRAINT `ai_tools_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `ai_tool_categories` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    echo "<p>Table 'ai_tools' created successfully.</p>";
                    break;
            }
            
            // Check if table was created
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                echo "<p class='error'>Failed to create table '$table'!</p>";
                continue;
            }
        }
        
        // Show table structure
        $stmt = $db->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        
        echo "<table border='1'>";
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
        
        // Check if table has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        
        echo "<p>Table '$table' has $count records.</p>";
        
        // If table is empty, add sample data
        if ($count === 0) {
            echo "<h3>Adding sample data to '$table'</h3>";
            
            switch ($table) {
                case 'games':
                    $db->exec("INSERT INTO `games` (`title`, `description`, `slug`, `featured`, `is_published`, `published_at`, `created_at`, `updated_at`)
                    VALUES
                    ('Word Scramble', 'A fun word game for kids to improve vocabulary', 'word-scramble', 1, 1, NOW(), NOW(), NOW()),
                    ('Math Challenge', 'Test your math skills with this interactive game', 'math-challenge', 0, 1, NOW(), NOW(), NOW()),
                    ('Memory Match', 'Classic memory matching game with colorful cards', 'memory-match', 0, 1, NOW(), NOW(), NOW())");
                    echo "<p>Added sample data to 'games'.</p>";
                    break;
                    
                case 'directory_categories':
                    $db->exec("INSERT INTO `directory_categories` (`name`, `slug`, `description`, `created_at`, `updated_at`)
                    VALUES
                    ('Schools', 'schools', 'Educational institutions for children', NOW(), NOW()),
                    ('Libraries', 'libraries', 'Public and private libraries with children\'s sections', NOW(), NOW()),
                    ('Bookstores', 'bookstores', 'Stores specializing in children\'s books', NOW(), NOW())");
                    echo "<p>Added sample data to 'directory_categories'.</p>";
                    break;
                    
                case 'directory_items':
                    // Check if directory_categories has data
                    $stmt = $db->query("SELECT COUNT(*) as count FROM directory_categories");
                    $categoryCount = $stmt->fetch()['count'];
                    
                    if ($categoryCount > 0) {
                        // Get first category ID
                        $stmt = $db->query("SELECT id FROM directory_categories LIMIT 1");
                        $categoryId = $stmt->fetch()['id'];
                        
                        $db->exec("INSERT INTO `directory_items` (`title`, `description`, `slug`, `category_id`, `website_url`, `contact_email`, `featured`, `is_published`, `published_at`, `created_at`, `updated_at`)
                        VALUES
                        ('City Central Library', 'A large public library with an extensive children\'s section', 'city-central-library', $categoryId, 'https://example.com/library', 'library@example.com', 1, 1, NOW(), NOW(), NOW()),
                        ('Kids Book Haven', 'Bookstore specializing in children\'s literature', 'kids-book-haven', $categoryId, 'https://example.com/bookstore', 'books@example.com', 0, 1, NOW(), NOW(), NOW()),
                        ('Sunshine Elementary School', 'A progressive elementary school with focus on literacy', 'sunshine-elementary', $categoryId, 'https://example.com/school', 'school@example.com', 0, 1, NOW(), NOW(), NOW())");
                        echo "<p>Added sample data to 'directory_items'.</p>";
                    } else {
                        echo "<p class='error'>Cannot add sample data to 'directory_items' because 'directory_categories' is empty.</p>";
                    }
                    break;
                    
                case 'ai_tool_categories':
                    $db->exec("INSERT INTO `ai_tool_categories` (`name`, `slug`, `description`, `created_at`, `updated_at`)
                    VALUES
                    ('Writing Assistants', 'writing-assistants', 'AI tools that help with writing and editing', NOW(), NOW()),
                    ('Image Generators', 'image-generators', 'AI tools that create images from text descriptions', NOW(), NOW()),
                    ('Educational Tools', 'educational-tools', 'AI tools designed for learning and education', NOW(), NOW())");
                    echo "<p>Added sample data to 'ai_tool_categories'.</p>";
                    break;
                    
                case 'ai_tools':
                    // Check if ai_tool_categories has data
                    $stmt = $db->query("SELECT COUNT(*) as count FROM ai_tool_categories");
                    $categoryCount = $stmt->fetch()['count'];
                    
                    if ($categoryCount > 0) {
                        // Get first category ID
                        $stmt = $db->query("SELECT id FROM ai_tool_categories LIMIT 1");
                        $categoryId = $stmt->fetch()['id'];
                        
                        $db->exec("INSERT INTO `ai_tools` (`title`, `description`, `slug`, `category_id`, `tool_url`, `pricing_type`, `price_info`, `features`, `rating`, `featured`, `is_published`, `published_at`, `created_at`, `updated_at`)
                        VALUES
                        ('StoryMaker AI', 'An AI tool that helps children create their own stories', 'storymaker-ai', $categoryId, 'https://example.com/storymaker', 'freemium', 'Free basic version, $5/month premium', 'Story templates\nCharacter creation\nIllustration generation', 4.5, 1, 1, NOW(), NOW(), NOW()),
                        ('PictureTeller', 'Generate illustrations for children\'s stories', 'pictureteller', $categoryId, 'https://example.com/pictureteller', 'free', 'Completely free', 'Character illustrations\nScene generation\nColoring book creation', 4.2, 0, 1, NOW(), NOW(), NOW()),
                        ('VocabBuilder', 'AI-powered vocabulary learning tool for kids', 'vocabbuilder', $categoryId, 'https://example.com/vocabbuilder', 'paid', '$3.99/month', 'Age-appropriate vocabulary\nInteractive exercises\nProgress tracking', 4.7, 0, 1, NOW(), NOW(), NOW())");
                        echo "<p>Added sample data to 'ai_tools'.</p>";
                    } else {
                        echo "<p class='error'>Cannot add sample data to 'ai_tools' because 'ai_tool_categories' is empty.</p>";
                    }
                    break;
            }
        }
    }
    
    echo "<h2>Testing API Endpoints</h2>";
    
    // Test API endpoints
    $endpoints = [
        'games',
        'directory-items',
        'ai-tools'
    ];
    
    echo "<table border='1'>";
    echo "<tr><th>Endpoint</th><th>Status</th><th>Response</th></tr>";
    
    foreach ($endpoints as $endpoint) {
        $url = "https://api.storiesfromtheweb.org/api/v1/$endpoint";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "<tr>";
        echo "<td>$endpoint</td>";
        echo "<td>$httpCode</td>";
        echo "<td>";
        
        if ($error) {
            echo "Error: $error";
        } else {
            // Try to decode JSON
            $json = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "Valid JSON: <pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . "</pre>";
            } else {
                echo "Invalid JSON: " . json_last_error_msg() . "<br>";
                echo "Raw response: <pre>" . htmlspecialchars($response) . "</pre>";
            }
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h1>Database Connection Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}