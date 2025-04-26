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
        // 1. Fix story_authors table
        echo "\n=== Fixing story_authors table ===\n";
        
        // Check if story_authors table exists
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        if ($stmt->rowCount() === 0) {
            // Create story_authors table
            $db->exec("CREATE TABLE story_authors (
                story_id INT NOT NULL,
                author_id INT NOT NULL,
                PRIMARY KEY (story_id, author_id),
                FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
                FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            echo "Created story_authors table successfully.\n";
        } else {
            echo "story_authors table already exists.\n";
        }
        
        // Clear existing relationships
        $db->exec("DELETE FROM story_authors");
        echo "Cleared existing story_authors relationships.\n";
        
        // Get all stories
        $stories = $db->query("SELECT id, title FROM stories")->fetchAll();
        echo "Found " . count($stories) . " stories in the database.\n";
        
        // Get all authors
        $authors = $db->query("SELECT id, name FROM authors")->fetchAll();
        echo "Found " . count($authors) . " authors in the database.\n";
        
        // Assign authors to stories
        if (count($stories) >= 1 && count($authors) >= 1) {
            // First story gets first author
            $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
            $stmt->execute([$stories[0]['id'], $authors[0]['id']]);
            echo "Assigned author '{$authors[0]['name']}' to story '{$stories[0]['title']}'.\n";
            
            // Second story gets second author (if available)
            if (count($stories) >= 2 && count($authors) >= 2) {
                $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                $stmt->execute([$stories[1]['id'], $authors[1]['id']]);
                echo "Assigned author '{$authors[1]['name']}' to story '{$stories[1]['title']}'.\n";
            }
        }
        
        // 2. Fix story flags
        echo "\n=== Fixing story flags ===\n";
        
        if (count($stories) >= 1) {
            // First story: Featured and Sponsored
            $sql = "UPDATE stories SET 
                    featured = 1,
                    is_sponsored = 1,
                    is_self_published = 0,
                    is_ai_enhanced = 0
                    WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$stories[0]['id']]);
            
            echo "Updated story '{$stories[0]['title']}' with flags: " .
                 "featured=1, is_sponsored=1, is_self_published=0, is_ai_enhanced=0\n";
            
            // Second story: Self-Published and AI-Enhanced
            if (count($stories) >= 2) {
                $sql = "UPDATE stories SET 
                        featured = 0,
                        is_sponsored = 0,
                        is_self_published = 1,
                        is_ai_enhanced = 1
                        WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$stories[1]['id']]);
                
                echo "Updated story '{$stories[1]['title']}' with flags: " .
                     "featured=0, is_sponsored=0, is_self_published=1, is_ai_enhanced=1\n";
            }
        }
        
        // Commit transaction
        $db->commit();
        echo "\nAll fixes applied successfully.\n";
        
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
        
        echo "\nIMPORTANT: You need to rebuild the site for these changes to take effect.\n";
        echo "Run: npm run build && npm run preview\n";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        echo "Error executing fixes: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}