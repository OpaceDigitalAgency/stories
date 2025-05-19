<?php
/**
 * Migrate Genres to Tags
 * 
 * This script migrates data from the genre field in the books table to tags.
 * It creates tags for each unique genre and associates them with the corresponding directory items.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Include tag functions
require_once '../includes/tag-functions.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Migrating Genres to Tags</h1>";

try {
    // Start transaction
    $db->beginTransaction();
    
    // Check if directory_item_tags table exists, create if not
    $stmt = $db->query("SHOW TABLES LIKE 'directory_item_tags'");
    if ($stmt->rowCount() === 0) {
        $db->exec("CREATE TABLE IF NOT EXISTS directory_item_tags (
            directory_item_id INT NOT NULL,
            tag_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (directory_item_id, tag_id),
            FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )");
        echo "<p>Created directory_item_tags table</p>";
    }
    
    // Check if tags table exists, create if not
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    if ($stmt->rowCount() === 0) {
        $db->exec("CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (slug)
        )");
        echo "<p>Created tags table</p>";
    }
    
    // Get all books with non-empty genres
    $stmt = $db->query("
        SELECT b.directory_item_id, b.genre
        FROM books b
        WHERE b.genre IS NOT NULL AND b.genre != ''
    ");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($books) . " books with genres</p>";
    
    // Process each book
    $processedGenres = [];
    $processedBooks = 0;
    
    foreach ($books as $book) {
        $directoryItemId = $book['directory_item_id'];
        $genre = trim($book['genre']);
        
        if (empty($genre)) {
            continue;
        }
        
        // Split genre by commas if it contains multiple genres
        $genres = explode(',', $genre);
        
        foreach ($genres as $genreName) {
            $genreName = trim($genreName);
            
            if (empty($genreName)) {
                continue;
            }
            
            // Create a slug for the genre
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $genreName));
            $slug = trim($slug, '-');
            
            // Check if this genre has already been processed
            if (!isset($processedGenres[$genreName])) {
                // Check if tag already exists
                $stmt = $db->prepare("SELECT id FROM tags WHERE LOWER(name) = LOWER(?)");
                $stmt->execute([$genreName]);
                $tag = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($tag) {
                    $tagId = $tag['id'];
                } else {
                    // Create a new tag
                    $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                    $stmt->execute([$genreName, $slug]);
                    $tagId = $db->lastInsertId();
                    echo "<p>Created new tag: $genreName (ID: $tagId)</p>";
                }
                
                $processedGenres[$genreName] = $tagId;
            } else {
                $tagId = $processedGenres[$genreName];
            }
            
            // Associate tag with directory item
            if (addTagToDirectoryItem($db, $directoryItemId, $tagId)) {
                echo "<p>Added tag '$genreName' to directory item #$directoryItemId</p>";
            }
        }
        
        $processedBooks++;
    }
    
    // Commit transaction
    $db->commit();
    
    echo "<h2>Migration Complete</h2>";
    echo "<p>Processed $processedBooks books</p>";
    echo "<p>Created " . count($processedGenres) . " unique genre tags</p>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "<h2>Error</h2>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
}
