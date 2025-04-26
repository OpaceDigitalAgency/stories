<?php
// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

try {
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

    echo "Connected to database successfully.\n";
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Read SQL file
        $sql = file_get_contents(__DIR__ . '/fix_story_authors.sql');
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map(
                'trim',
                explode(';', $sql)
            ),
            function($statement) {
                return !empty($statement) && strpos($statement, '--') !== 0;
            }
        );
        
        // Execute each statement
        foreach ($statements as $statement) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
        
        // Commit transaction
        $db->commit();
        echo "SQL executed successfully.\n";
        
        // Verify story_authors table
        $authors = $db->query("SELECT sa.story_id, s.title, sa.author_id, a.name 
                              FROM story_authors sa 
                              JOIN stories s ON sa.story_id = s.id 
                              JOIN authors a ON sa.author_id = a.id")->fetchAll();
        
        echo "\nStory-Author relationships:\n";
        foreach ($authors as $author) {
            echo "Story ID: " . $author['story_id'] . ", Title: " . $author['title'] . 
                 ", Author ID: " . $author['author_id'] . ", Name: " . $author['name'] . "\n";
        }
        
        // Verify story flags
        $stories = $db->query("SELECT id, title, featured, is_sponsored, is_self_published, is_ai_enhanced 
                              FROM stories")->fetchAll();
        
        echo "\nStory flags:\n";
        foreach ($stories as $story) {
            echo "Story ID: " . $story['id'] . ", Title: " . $story['title'] . 
                 ", Featured: " . $story['featured'] . 
                 ", Sponsored: " . $story['is_sponsored'] . 
                 ", Self-Published: " . $story['is_self_published'] . 
                 ", AI-Enhanced: " . $story['is_ai_enhanced'] . "\n";
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        echo "Error executing SQL: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}