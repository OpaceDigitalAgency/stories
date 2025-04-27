<?php
/**
 * Fix Directory Items and AI Tools Config
 * 
 * This script updates both the directory items and AI tools tables
 * using the correct database configuration from config.php.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Directory Items and AI Tools Config</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';

// Load the config
$config = require $apiPath . '/config/config.php';
$dbConfig = $config['db'];

echo "<h2>Database Configuration</h2>";
echo "<p>Using configuration from config.php:</p>";
echo "<ul>";
echo "<li>Host: {$dbConfig['host']}</li>";
echo "<li>Database: {$dbConfig['name']}</li>";
echo "<li>Username: {$dbConfig['user']}</li>";
echo "<li>Password: " . str_repeat('*', strlen($dbConfig['password'])) . "</li>";
echo "<li>Port: {$dbConfig['port']}</li>";
echo "</ul>";

try {
    // Connect to database
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']};port={$dbConfig['port']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], $options);
    echo "<p style='color:green'>✅ Database connection successful!</p>";
    
    // Fix Directory Items Table
    echo "<h2>Fixing Directory Items Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'directory_items'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color:green'>✅ Directory items table exists.</p>";
        
        // Check if the table has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM directory_items");
        $count = $stmt->fetchColumn();
        
        echo "<p>Directory items table has $count records.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ Directory items table does not exist. Creating it...</p>";
        
        // Create the directory_items table
        $sql = "CREATE TABLE directory_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            url VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Directory items table created successfully!</p>";
        
        // Add sample data
        echo "<h3>Adding Sample Directory Items</h3>";
        
        $sampleItems = [
            [
                'title' => 'Writing Resources Hub',
                'description' => 'Collection of writing tools, guides, and resources',
                'url' => 'https://example.com/writing-resources',
                'category' => 'resources',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Story Writing Community',
                'description' => 'Active community for writers to share and discuss stories',
                'url' => 'https://example.com/writing-community',
                'category' => 'community',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $sql = "INSERT INTO directory_items (title, description, url, category, featured, is_published, published_at) 
                VALUES (:title, :description, :url, :category, :featured, :is_published, :published_at)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($sampleItems as $item) {
            $stmt->execute($item);
            echo "<p>Added directory item: {$item['title']}</p>";
        }
    }
    
    // Fix AI Tools Table
    echo "<h2>Fixing AI Tools Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_tools'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color:green'>✅ AI tools table exists.</p>";
        
        // Check if the table has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM ai_tools");
        $count = $stmt->fetchColumn();
        
        echo "<p>AI tools table has $count records.</p>";
    } else {
        echo "<p style='color:orange'>⚠️ AI tools table does not exist. Creating it...</p>";
        
        // Create the ai_tools table
        $sql = "CREATE TABLE ai_tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            url VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            pricing_type ENUM('free', 'freemium', 'paid') NOT NULL,
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
                'name' => 'Story Plot Generator',
                'description' => 'AI-powered tool to generate unique story plots',
                'url' => 'https://example.com/plot-generator',
                'category' => 'writing',
                'pricing_type' => 'freemium',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Character Creator AI',
                'description' => 'Create detailed character profiles using AI',
                'url' => 'https://example.com/character-creator',
                'category' => 'writing',
                'pricing_type' => 'free',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $sql = "INSERT INTO ai_tools (name, description, url, category, pricing_type, featured, is_published, published_at) 
                VALUES (:name, :description, :url, :category, :pricing_type, :featured, :is_published, :published_at)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($sampleTools as $tool) {
            $stmt->execute($tool);
            echo "<p>Added AI tool: {$tool['name']}</p>";
        }
    }
    
    echo "<h2>✅ All Tables Fixed Successfully!</h2>";
    echo "<p>The database is now properly configured with all required tables:</p>";
    echo "<ul>";
    echo "<li>Directory Items table - for storing directory listings</li>";
    echo "<li>AI Tools table - for storing AI tool listings</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
    echo "<p>Please check that the database credentials in config.php are correct:</p>";
    echo "<pre>";
    echo htmlspecialchars(<<<EOT
// In config.php:
\$config['db'] = [
    'host'     => '{$dbConfig['host']}',
    'name'     => '{$dbConfig['name']}',
    'user'     => '{$dbConfig['user']}',
    'password' => '********',
    'charset'  => '{$dbConfig['charset']}',
    'port'     => {$dbConfig['port']}
];
EOT
    );
    echo "</pre>";
}