<?php
/**
 * Fix AI Tools Table
 * 
 * This script creates or fixes the ai_tools table and adds sample data if needed.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix AI Tools Table</h1>";

// Database connection parameters - adjust these to match your configuration
$host = 'localhost';
$dbname = 'stories';
$username = 'stories_user';
$password = 'stories_password';

echo "<h2>Database Connection</h2>";
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
    
    // Check if ai_tools table exists
    echo "<h2>Checking AI Tools Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_tools'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color:orange'>⚠️ AI tools table does not exist. Creating it...</p>";
        
        $sql = "CREATE TABLE ai_tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL,
            url VARCHAR(255),
            category VARCHAR(100),
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ AI tools table created successfully!</p>";
        
        // Add sample data
        echo "<h3>Adding Sample AI Tools</h3>";
        
        $sampleTools = [
            [
                'title' => 'Story Generator',
                'description' => 'AI-powered tool to generate story ideas and outlines',
                'slug' => 'story-generator',
                'url' => 'https://example.com/tools/story-generator',
                'category' => 'writing',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Character Creator',
                'description' => 'Create detailed character profiles with AI assistance',
                'slug' => 'character-creator',
                'url' => 'https://example.com/tools/character-creator',
                'category' => 'writing',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Plot Analyzer',
                'description' => 'AI tool to analyze and improve your story\'s plot',
                'slug' => 'plot-analyzer',
                'url' => 'https://example.com/tools/plot-analyzer',
                'category' => 'analysis',
                'featured' => 0,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $sql = "INSERT INTO ai_tools (title, description, slug, url, category, featured, is_published, published_at) 
                VALUES (:title, :description, :slug, :url, :category, :featured, :is_published, :published_at)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($sampleTools as $tool) {
            $stmt->execute($tool);
            echo "<p>Added AI tool: {$tool['title']}</p>";
        }
    } else {
        echo "<p style='color:green'>✅ AI tools table exists.</p>";
        
        // Check if the table has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM ai_tools");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            echo "<p>AI tools table is empty. Adding sample data...</p>";
            
            // Add sample data
            $sampleTools = [
                [
                    'title' => 'Story Generator',
                    'description' => 'AI-powered tool to generate story ideas and outlines',
                    'slug' => 'story-generator',
                    'url' => 'https://example.com/tools/story-generator',
                    'category' => 'writing',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Character Creator',
                    'description' => 'Create detailed character profiles with AI assistance',
                    'slug' => 'character-creator',
                    'url' => 'https://example.com/tools/character-creator',
                    'category' => 'writing',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Plot Analyzer',
                    'description' => 'AI tool to analyze and improve your story\'s plot',
                    'slug' => 'plot-analyzer',
                    'url' => 'https://example.com/tools/plot-analyzer',
                    'category' => 'analysis',
                    'featured' => 0,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $sql = "INSERT INTO ai_tools (title, description, slug, url, category, featured, is_published, published_at) 
                    VALUES (:title, :description, :slug, :url, :category, :featured, :is_published, :published_at)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($sampleTools as $tool) {
                $stmt->execute($tool);
                echo "<p>Added AI tool: {$tool['title']}</p>";
            }
        } else {
            echo "<p>AI tools table has $count records.</p>";
        }
    }
    
    // Check table structure
    echo "<h2>Checking Table Structure</h2>";
    
    $stmt = $pdo->query("DESCRIBE ai_tools");
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
    
    // Test query
    echo "<h2>Testing Query</h2>";
    
    $stmt = $pdo->query("SELECT * FROM ai_tools LIMIT 3");
    $tools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tools) > 0) {
        echo "<p>Sample data:</p>";
        echo "<pre>" . json_encode($tools, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>No data found in ai_tools table.</p>";
    }
    
    echo "<h2>Next Steps</h2>";
    echo "<p>Now run the <a href='fix_ai_tools_controller.php'>fix_ai_tools_controller.php</a> script to update the controller.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
    
    echo "<h2>Troubleshooting</h2>";
    echo "<ol>";
    echo "<li>Check that the database credentials are correct.</li>";
    echo "<li>Make sure the database exists and is accessible.</li>";
    echo "<li>Verify that the user has permission to create tables.</li>";
    echo "</ol>";
}