<?php
/**
 * Update Authors Schema
 * 
 * This script adds the 'type' column to the authors table
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

try {
    // Begin transaction
    $db->beginTransaction();

    // Add type column if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM authors LIKE 'type'");
    if ($stmt->rowCount() === 0) {
        $sql = "ALTER TABLE authors 
                ADD COLUMN type ENUM('author', 'publisher', 'book_author') 
                NOT NULL DEFAULT 'author'";
        $db->exec($sql);
        echo "<p style='color:green'>Successfully added 'type' column to authors table</p>";
    } else {
        echo "<p style='color:blue'>Type column already exists in authors table</p>";
    }

    // Commit transaction
    $db->commit();
    
    echo "<p><a href='direct_import.php'>Return to Import Tool</a></p>";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<p style='color:red'>Error updating authors table: " . $e->getMessage() . "</p>";
}