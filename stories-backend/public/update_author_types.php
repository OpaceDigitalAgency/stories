<?php
/**
 * Update author types in the database
 */

// Database connection
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    echo "Connected to database successfully.<br>";
    
    // Check if author_type column exists
    $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'author_type'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add author_type column if it doesn't exist
        $db->exec("ALTER TABLE authors ADD COLUMN author_type ENUM('retail', 'parent', 'child', 'educator') DEFAULT 'retail' AFTER bio");
        echo "Added author_type column to authors table.<br>";
    } else {
        echo "author_type column already exists in authors table.<br>";
    }
    
    // Update author types
    $updates = [
        ['id' => 1, 'type' => 'retail'],
        ['id' => 2, 'type' => 'child'],
        ['id' => 3, 'type' => 'parent']
    ];
    
    $updateStmt = $db->prepare("UPDATE authors SET author_type = ? WHERE id = ?");
    
    foreach ($updates as $update) {
        $updateStmt->execute([$update['type'], $update['id']]);
        echo "Updated author ID {$update['id']} to type '{$update['type']}'.<br>";
    }
    
    // Verify the updates
    $authors = $db->query("SELECT id, name, author_type FROM authors")->fetchAll();
    
    echo "<h3>Current Author Types:</h3>";
    echo "<ul>";
    foreach ($authors as $author) {
        echo "<li>ID: {$author['id']}, Name: {$author['name']}, Type: " . 
             ($author['author_type'] ?? 'NULL') . "</li>";
    }
    echo "</ul>";
    
    echo "<p>Author types updated successfully!</p>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>