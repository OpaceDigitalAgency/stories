<?php
/**
 * Create Books Table
 * 
 * This script creates the books table and its relationships
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

try {
    // Begin transaction
    $db->beginTransaction();

    // Create books table
    $sql = "CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        directory_item_id INT NOT NULL,
        isbn VARCHAR(13),
        isbn13 VARCHAR(13),
        author VARCHAR(255),
        publisher VARCHAR(255),
        publication_date DATE,
        page_count INT,
        age_range VARCHAR(50),
        reading_level VARCHAR(50),
        cover_url VARCHAR(255),
        purchase_links JSON,
        metadata JSON,
        genre VARCHAR(100),
        series VARCHAR(255),
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);
    echo "<p style='color:green'>Created books table</p>";

    // Create book_authors table for relationships
    $sql = "CREATE TABLE IF NOT EXISTS book_authors (
        directory_item_id INT NOT NULL,
        author_id INT NOT NULL,
        role ENUM('author', 'publisher') NOT NULL DEFAULT 'author',
        PRIMARY KEY (directory_item_id, author_id, role),
        FOREIGN KEY (directory_item_id) REFERENCES directory_items(id) ON DELETE CASCADE,
        FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $db->exec($sql);
    echo "<p style='color:green'>Created book_authors table</p>";

    // Commit transaction
    $db->commit();
    
    echo "<p style='color:green'>Successfully created books tables</p>";
    echo "<p><a href='direct_import.php'>Return to Import Tool</a></p>";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<p style='color:red'>Error creating books tables: " . $e->getMessage() . "</p>";
}