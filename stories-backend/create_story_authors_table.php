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
        
        // Populate story_authors table from existing stories
        $stories = $db->query("SELECT id, author_id FROM stories WHERE author_id IS NOT NULL")->fetchAll();
        if (!empty($stories)) {
            $insertValues = [];
            $insertParams = [];
            
            foreach ($stories as $story) {
                $insertValues[] = "(?, ?)";
                $insertParams[] = $story['id'];
                $insertParams[] = $story['author_id'];
            }
            
            $sql = "INSERT INTO story_authors (story_id, author_id) VALUES " . implode(', ', $insertValues);
            $stmt = $db->prepare($sql);
            $stmt->execute($insertParams);
            
            echo "Populated story_authors table with " . count($stories) . " relationships.\n";
        }
    } else {
        echo "story_authors table already exists.\n";
        
        // Check if there are any entries in the table
        $count = $db->query("SELECT COUNT(*) FROM story_authors")->fetchColumn();
        echo "There are " . $count . " entries in the story_authors table.\n";
        
        // List some entries for debugging
        $entries = $db->query("SELECT sa.story_id, s.title, sa.author_id, a.name 
                              FROM story_authors sa 
                              JOIN stories s ON sa.story_id = s.id 
                              JOIN authors a ON sa.author_id = a.id 
                              LIMIT 10")->fetchAll();
        
        if (!empty($entries)) {
            echo "Sample entries:\n";
            foreach ($entries as $entry) {
                echo "Story ID: " . $entry['story_id'] . ", Title: " . $entry['title'] . 
                     ", Author ID: " . $entry['author_id'] . ", Name: " . $entry['name'] . "\n";
            }
        } else {
            echo "No entries found in story_authors table.\n";
        }
    }
    
    // Check if there are any stories without authors in story_authors
    $orphanedStories = $db->query("
        SELECT s.id, s.title 
        FROM stories s 
        LEFT JOIN story_authors sa ON s.id = sa.story_id 
        WHERE sa.story_id IS NULL
    ")->fetchAll();
    
    if (!empty($orphanedStories)) {
        echo "Found " . count($orphanedStories) . " stories without authors in story_authors table:\n";
        foreach ($orphanedStories as $story) {
            echo "Story ID: " . $story['id'] . ", Title: " . $story['title'] . "\n";
        }
    } else {
        echo "All stories have authors in story_authors table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}