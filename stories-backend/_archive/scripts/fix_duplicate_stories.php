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
        // Check for duplicate stories
        $query = "SELECT id, title, COUNT(*) as count FROM stories GROUP BY id HAVING COUNT(*) > 1";
        $duplicates = $db->query($query)->fetchAll();
        
        if (empty($duplicates)) {
            echo "No duplicate stories found in the database.\n";
        } else {
            echo "Found " . count($duplicates) . " duplicate stories:\n";
            
            foreach ($duplicates as $duplicate) {
                echo "Story ID: " . $duplicate['id'] . ", Title: " . $duplicate['title'] . ", Count: " . $duplicate['count'] . "\n";
                
                // Keep only one copy of the story
                $deleteQuery = "DELETE FROM stories WHERE id = ? LIMIT " . ($duplicate['count'] - 1);
                $stmt = $db->prepare($deleteQuery);
                $stmt->execute([$duplicate['id']]);
                
                echo "Deleted " . ($duplicate['count'] - 1) . " duplicate(s) of story ID " . $duplicate['id'] . "\n";
            }
        }
        
        // Check for stories with the same title but different IDs
        $query = "SELECT title, COUNT(*) as count FROM stories GROUP BY title HAVING COUNT(*) > 1";
        $duplicateTitles = $db->query($query)->fetchAll();
        
        if (empty($duplicateTitles)) {
            echo "No stories with duplicate titles found.\n";
        } else {
            echo "Found " . count($duplicateTitles) . " titles with multiple stories:\n";
            
            foreach ($duplicateTitles as $duplicate) {
                echo "Title: " . $duplicate['title'] . ", Count: " . $duplicate['count'] . "\n";
                
                // Get all stories with this title
                $storiesQuery = "SELECT id, title, created_at FROM stories WHERE title = ? ORDER BY created_at DESC";
                $stmt = $db->prepare($storiesQuery);
                $stmt->execute([$duplicate['title']]);
                $stories = $stmt->fetchAll();
                
                // Keep the newest one, delete the rest
                echo "Keeping story ID " . $stories[0]['id'] . " (newest)\n";
                
                for ($i = 1; $i < count($stories); $i++) {
                    // Delete story_authors relationships
                    $deleteAuthorsQuery = "DELETE FROM story_authors WHERE story_id = ?";
                    $stmt = $db->prepare($deleteAuthorsQuery);
                    $stmt->execute([$stories[$i]['id']]);
                    
                    // Delete story_tags relationships
                    $deleteTagsQuery = "DELETE FROM story_tags WHERE story_id = ?";
                    $stmt = $db->prepare($deleteTagsQuery);
                    $stmt->execute([$stories[$i]['id']]);
                    
                    // Delete the story
                    $deleteStoryQuery = "DELETE FROM stories WHERE id = ?";
                    $stmt = $db->prepare($deleteStoryQuery);
                    $stmt->execute([$stories[$i]['id']]);
                    
                    echo "Deleted story ID " . $stories[$i]['id'] . "\n";
                }
            }
        }
        
        // Verify story_authors table
        $query = "SELECT sa.story_id, s.title, sa.author_id, a.name 
                 FROM story_authors sa 
                 JOIN stories s ON sa.story_id = s.id 
                 JOIN authors a ON sa.author_id = a.id";
        $relationships = $db->query($query)->fetchAll();
        
        echo "\nStory-Author relationships after cleanup:\n";
        foreach ($relationships as $rel) {
            echo "Story ID: " . $rel['story_id'] . ", Title: " . $rel['title'] . 
                 ", Author ID: " . $rel['author_id'] . ", Name: " . $rel['name'] . "\n";
        }
        
        // Verify story flags
        $query = "SELECT id, title, featured, is_sponsored, is_self_published, is_ai_enhanced 
                 FROM stories";
        $stories = $db->query($query)->fetchAll();
        
        echo "\nStory flags after cleanup:\n";
        foreach ($stories as $story) {
            echo "Story ID: " . $story['id'] . ", Title: " . $story['title'] . 
                 ", Featured: " . $story['featured'] . 
                 ", Sponsored: " . $story['is_sponsored'] . 
                 ", Self-Published: " . $story['is_self_published'] . 
                 ", AI-Enhanced: " . $story['is_ai_enhanced'] . "\n";
        }
        
        // Commit transaction
        $db->commit();
        echo "\nAll fixes applied successfully.\n";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        echo "Error executing fixes: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}