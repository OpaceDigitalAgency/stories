<?php
/**
 * Fix Games Table Script
 * 
 * This script fixes the games table structure to match what the GamesController expects.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/fix-games-table.log');

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Games Table</title>
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
        <h1>Fix Games Table</h1>';

// Check if the form has been submitted
if (isset($_POST['fix_table'])) {
    echo '<div class="card">';
    echo '<h2>Fixing Games Table...</h2>';
    
    try {
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
        
        // Check if games table exists
        $stmt = $db->query("SHOW TABLES LIKE 'games'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p>Games table does not exist. Creating it...</p>';
            
            // Create games table
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
            
            echo '<p class="success">Games table created successfully.</p>';
            
            // Add sample data
            $db->exec("INSERT INTO `games` (`title`, `description`, `slug`, `featured`, `is_published`, `published_at`, `created_at`, `updated_at`)
            VALUES
            ('Word Scramble', 'A fun word game for kids to improve vocabulary', 'word-scramble', 1, 1, NOW(), NOW(), NOW()),
            ('Math Challenge', 'Test your math skills with this interactive game', 'math-challenge', 0, 1, NOW(), NOW(), NOW()),
            ('Memory Match', 'Classic memory matching game with colorful cards', 'memory-match', 0, 1, NOW(), NOW(), NOW())");
            
            echo '<p class="success">Sample data added to games table.</p>';
        } else {
            echo '<p>Games table exists. Checking structure...</p>';
            
            // Get table structure
            $stmt = $db->query("DESCRIBE games");
            $columns = $stmt->fetchAll();
            
            // Check if all required columns exist
            $requiredColumns = [
                'id' => false,
                'title' => false,
                'description' => false,
                'slug' => false,
                'featured' => false,
                'is_published' => false,
                'published_at' => false,
                'created_at' => false,
                'updated_at' => false
            ];
            
            foreach ($columns as $column) {
                if (isset($requiredColumns[$column['Field']])) {
                    $requiredColumns[$column['Field']] = true;
                }
            }
            
            // Display current structure
            echo '<h3>Current Table Structure</h3>';
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
            
            // Check if any required columns are missing
            $missingColumns = [];
            foreach ($requiredColumns as $column => $exists) {
                if (!$exists) {
                    $missingColumns[] = $column;
                }
            }
            
            if (!empty($missingColumns)) {
                echo '<p class="warning">Missing columns: ' . implode(', ', $missingColumns) . '</p>';
                echo '<p>Adding missing columns...</p>';
                
                // Add missing columns
                foreach ($missingColumns as $column) {
                    switch ($column) {
                        case 'id':
                            $db->exec("ALTER TABLE `games` ADD `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
                            break;
                        case 'title':
                            $db->exec("ALTER TABLE `games` ADD `title` varchar(255) NOT NULL AFTER `id`");
                            break;
                        case 'description':
                            $db->exec("ALTER TABLE `games` ADD `description` text AFTER `title`");
                            break;
                        case 'slug':
                            $db->exec("ALTER TABLE `games` ADD `slug` varchar(255) NOT NULL AFTER `description`");
                            $db->exec("ALTER TABLE `games` ADD UNIQUE KEY `slug` (`slug`)");
                            break;
                        case 'featured':
                            $db->exec("ALTER TABLE `games` ADD `featured` tinyint(1) DEFAULT '0' AFTER `slug`");
                            break;
                        case 'is_published':
                            $db->exec("ALTER TABLE `games` ADD `is_published` tinyint(1) DEFAULT '0' AFTER `featured`");
                            break;
                        case 'published_at':
                            $db->exec("ALTER TABLE `games` ADD `published_at` datetime DEFAULT NULL AFTER `is_published`");
                            break;
                        case 'created_at':
                            $db->exec("ALTER TABLE `games` ADD `created_at` datetime NOT NULL AFTER `published_at`");
                            break;
                        case 'updated_at':
                            $db->exec("ALTER TABLE `games` ADD `updated_at` datetime NOT NULL AFTER `created_at`");
                            break;
                    }
                }
                
                echo '<p class="success">Missing columns added successfully.</p>';
            } else {
                echo '<p class="success">All required columns exist.</p>';
            }
            
            // Check if table has data
            $stmt = $db->query("SELECT COUNT(*) as count FROM games");
            $count = $stmt->fetch()['count'];
            
            echo '<p>Games table has ' . $count . ' records.</p>';
            
            if ($count === 0) {
                echo '<p>Adding sample data...</p>';
                
                // Add sample data
                $db->exec("INSERT INTO `games` (`title`, `description`, `slug`, `featured`, `is_published`, `published_at`, `created_at`, `updated_at`)
                VALUES
                ('Word Scramble', 'A fun word game for kids to improve vocabulary', 'word-scramble', 1, 1, NOW(), NOW(), NOW()),
                ('Math Challenge', 'Test your math skills with this interactive game', 'math-challenge', 0, 1, NOW(), NOW(), NOW()),
                ('Memory Match', 'Classic memory matching game with colorful cards', 'memory-match', 0, 1, NOW(), NOW(), NOW())");
                
                echo '<p class="success">Sample data added to games table.</p>';
            }
        }
        
        // Do the same for directory_items and ai_tools tables
        
        // Check if directory_categories table exists
        $stmt = $db->query("SHOW TABLES LIKE 'directory_categories'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p>Directory categories table does not exist. Creating it...</p>';
            
            // Create directory_categories table
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
            
            echo '<p class="success">Directory categories table created successfully.</p>';
            
            // Add sample data
            $db->exec("INSERT INTO `directory_categories` (`name`, `slug`, `description`, `created_at`, `updated_at`)
            VALUES
            ('Schools', 'schools', 'Educational institutions for children', NOW(), NOW()),
            ('Libraries', 'libraries', 'Public and private libraries with children\'s sections', NOW(), NOW()),
            ('Bookstores', 'bookstores', 'Stores specializing in children\'s books', NOW(), NOW())");
            
            echo '<p class="success">Sample data added to directory categories table.</p>';
        }
        
        // Check if directory_items table exists
        $stmt = $db->query("SHOW TABLES LIKE 'directory_items'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p>Directory items table does not exist. Creating it...</p>';
            
            // Create directory_items table
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
            
            echo '<p class="success">Directory items table created successfully.</p>';
            
            // Add sample data
            $db->exec("INSERT INTO `directory_items` (`title`, `description`, `slug`, `category_id`, `website_url`, `contact_email`, `featured`, `is_published`, `published_at`, `created_at`, `updated_at`)
            VALUES
            ('City Central Library', 'A large public library with an extensive children\'s section', 'city-central-library', 2, 'https://example.com/library', 'library@example.com', 1, 1, NOW(), NOW(), NOW()),
            ('Kids Book Haven', 'Bookstore specializing in children\'s literature', 'kids-book-haven', 3, 'https://example.com/bookstore', 'books@example.com', 0, 1, NOW(), NOW(), NOW()),
            ('Sunshine Elementary School', 'A progressive elementary school with focus on literacy', 'sunshine-elementary', 1, 'https://example.com/school', 'school@example.com', 0, 1, NOW(), NOW(), NOW())");
            
            echo '<p class="success">Sample data added to directory items table.</p>';
        }
        
        // Check if ai_tool_categories table exists
        $stmt = $db->query("SHOW TABLES LIKE 'ai_tool_categories'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p>AI tool categories table does not exist. Creating it...</p>';
            
            // Create ai_tool_categories table
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
            
            echo '<p class="success">AI tool categories table created successfully.</p>';
            
            // Add sample data
            $db->exec("INSERT INTO `ai_tool_categories` (`name`, `slug`, `description`, `created_at`, `updated_at`)
            VALUES
            ('Writing Assistants', 'writing-assistants', 'AI tools that help with writing and editing', NOW(), NOW()),
            ('Image Generators', 'image-generators', 'AI tools that create images from text descriptions', NOW(), NOW()),
            ('Educational Tools', 'educational-tools', 'AI tools designed for learning and education', NOW(), NOW())");
            
            echo '<p class="success">Sample data added to AI tool categories table.</p>';
        }
        
        // Check if ai_tools table exists
        $stmt = $db->query("SHOW TABLES LIKE 'ai_tools'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo '<p>AI tools table does not exist. Creating it...</p>';
            
            // Create ai_tools table
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
            
            echo '<p class="success">AI tools table created successfully.</p>';
            
            // Add sample data
            $db->exec("INSERT INTO `ai_tools` (`title`, `description`, `slug`, `category_id`, `tool_url`, `pricing_type`, `price_info`, `features`, `rating`, `featured`, `is_published`, `published_at`, `created_at`, `updated_at`)
            VALUES
            ('StoryMaker AI', 'An AI tool that helps children create their own stories', 'storymaker-ai', 1, 'https://example.com/storymaker', 'freemium', 'Free basic version, $5/month premium', 'Story templates\nCharacter creation\nIllustration generation', 4.5, 1, 1, NOW(), NOW(), NOW()),
            ('PictureTeller', 'Generate illustrations for children\'s stories', 'pictureteller', 2, 'https://example.com/pictureteller', 'free', 'Completely free', 'Character illustrations\nScene generation\nColoring book creation', 4.2, 0, 1, NOW(), NOW(), NOW()),
            ('VocabBuilder', 'AI-powered vocabulary learning tool for kids', 'vocabbuilder', 3, 'https://example.com/vocabbuilder', 'paid', '$3.99/month', 'Age-appropriate vocabulary\nInteractive exercises\nProgress tracking', 4.7, 0, 1, NOW(), NOW(), NOW())");
            
            echo '<p class="success">Sample data added to AI tools table.</p>';
        }
        
        echo '<h2>Next Steps</h2>';
        echo '<p>The database tables have been fixed. You can now test the API endpoints:</p>';
        echo '<ul>';
        echo '<li><a href="/api/v1/games" target="_blank">Test Games Endpoint</a></li>';
        echo '<li><a href="/api/v1/directory-items" target="_blank">Test Directory Items Endpoint</a></li>';
        echo '<li><a href="/api/v1/ai-tools" target="_blank">Test AI Tools Endpoint</a></li>';
        echo '</ul>';
        
        echo '<p>Then run the API format test:</p>';
        echo '<ul>';
        echo '<li><a href="/test_api_format.php" target="_blank">Test API Format</a></li>';
        echo '</ul>';
        
    } catch (PDOException $e) {
        echo '<p class="error">Database error: ' . $e->getMessage() . '</p>';
    } catch (Exception $e) {
        echo '<p class="error">Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
} else {
    showForm();
}

echo '</div></body></html>';

/**
 * Show the form to fix the tables
 */
function showForm() {
    echo '<div class="card">';
    echo '<h2>Fix Database Tables</h2>';
    echo '<p>This script will fix the database tables to match what the controllers expect.</p>';
    echo '<p>The script will:</p>';
    echo '<ul>';
    echo '<li>Check if the tables exist and create them if they don\'t</li>';
    echo '<li>Check if the tables have the correct structure and fix them if they don\'t</li>';
    echo '<li>Add sample data if the tables are empty</li>';
    echo '</ul>';
    echo '<form method="post">';
    echo '<input type="hidden" name="fix_table" value="1">';
    echo '<button type="submit" class="btn">Fix Tables</button>';
    echo '</form>';
    echo '</div>';
}