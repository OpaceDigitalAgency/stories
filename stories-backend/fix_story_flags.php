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

    // Get all stories
    $stories = $db->query("SELECT * FROM stories")->fetchAll();
    echo "Found " . count($stories) . " stories in the database.\n";
    
    // Update story flags to ensure they're properly set
    $storyUpdates = [];
    
    // Make sure we have at least one story with each flag
    if (count($stories) >= 2) {
        // First story: Featured and Sponsored
        $storyUpdates[] = [
            'id' => $stories[0]['id'],
            'featured' => 1,
            'is_sponsored' => 1,
            'is_self_published' => 0,
            'is_ai_enhanced' => 0
        ];
        
        // Second story: Self-Published and AI-Enhanced
        $storyUpdates[] = [
            'id' => $stories[1]['id'],
            'featured' => 0,
            'is_sponsored' => 0,
            'is_self_published' => 1,
            'is_ai_enhanced' => 1
        ];
    }
    
    // Apply updates
    if (!empty($storyUpdates)) {
        $db->beginTransaction();
        
        foreach ($storyUpdates as $update) {
            $sql = "UPDATE stories SET 
                    featured = :featured,
                    is_sponsored = :is_sponsored,
                    is_self_published = :is_self_published,
                    is_ai_enhanced = :is_ai_enhanced
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':featured' => $update['featured'],
                ':is_sponsored' => $update['is_sponsored'],
                ':is_self_published' => $update['is_self_published'],
                ':is_ai_enhanced' => $update['is_ai_enhanced'],
                ':id' => $update['id']
            ]);
            
            echo "Updated story ID " . $update['id'] . " with flags: " .
                 "featured=" . $update['featured'] . ", " .
                 "is_sponsored=" . $update['is_sponsored'] . ", " .
                 "is_self_published=" . $update['is_self_published'] . ", " .
                 "is_ai_enhanced=" . $update['is_ai_enhanced'] . "\n";
        }
        
        $db->commit();
        echo "Successfully updated story flags.\n";
    }
    
    // Display current story flags
    echo "\nCurrent story flags:\n";
    $stories = $db->query("SELECT id, title, featured, is_sponsored, is_self_published, is_ai_enhanced FROM stories")->fetchAll();
    
    foreach ($stories as $story) {
        echo "Story ID " . $story['id'] . " (" . $story['title'] . "): " .
             "featured=" . $story['featured'] . ", " .
             "is_sponsored=" . $story['is_sponsored'] . ", " .
             "is_self_published=" . $story['is_self_published'] . ", " .
             "is_ai_enhanced=" . $story['is_ai_enhanced'] . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
}