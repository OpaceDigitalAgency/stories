<?php
// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Function to flush output buffer to ensure real-time progress display
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

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
    echo "<p>Database connection successful</p>";
    flushOutput();
} catch (PDOException $e) {
    echo "<p>Database connection failed: " . $e->getMessage() . "</p>";
    flushOutput();
    exit;
}

// Check if story_authors table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p>story_authors table exists</p>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE story_authors");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p>Table columns: " . implode(", ", $columns) . "</p>";
        
        // Check if there are any records
        $stmt = $db->query("SELECT COUNT(*) FROM story_authors");
        $count = $stmt->fetchColumn();
        
        echo "<p>Total records in story_authors: $count</p>";
        
        // Show some sample records
        if ($count > 0) {
            $stmt = $db->query("SELECT * FROM story_authors LIMIT 5");
            $records = $stmt->fetchAll();
            
            echo "<p>Sample records:</p>";
            echo "<pre>";
            print_r($records);
            echo "</pre>";
        }
    } else {
        echo "<p>story_authors table does not exist. Creating it now...</p>";
        
        // Create the table
        $db->exec("CREATE TABLE story_authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            story_id INT NOT NULL,
            author_id INT NOT NULL,
            UNIQUE KEY unique_story_author (story_id, author_id),
            FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
        )");
        
        echo "<p>story_authors table created successfully</p>";
    }
    
    // Check if there are any stories with author_id column
    try {
        $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
        $hasAuthorIdColumn = $stmt->rowCount() > 0;
        
        if ($hasAuthorIdColumn) {
            echo "<p>stories table has author_id column</p>";
            
            // Check if there are any stories with author_id set
            $stmt = $db->query("SELECT COUNT(*) FROM stories WHERE author_id IS NOT NULL");
            $count = $stmt->fetchColumn();
            
            echo "<p>Stories with author_id set: $count</p>";
            
            if ($count > 0 && !$tableExists) {
                echo "<p>Migrating author associations to junction table...</p>";
                
                // Migrate author associations to junction table
                $stmt = $db->query("INSERT INTO story_authors (story_id, author_id) 
                                    SELECT id, author_id FROM stories 
                                    WHERE author_id IS NOT NULL");
                
                echo "<p>Migration complete. " . $stmt->rowCount() . " records migrated.</p>";
            }
        } else {
            echo "<p>stories table does not have author_id column</p>";
        }
    } catch (PDOException $e) {
        echo "<p>Error checking stories table: " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error checking story_authors table: " . $e->getMessage() . "</p>";
}