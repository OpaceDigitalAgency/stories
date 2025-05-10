<?php
/**
 * Update Directory Items Schema
 * 
 * This script adds the 'type' column to the directory_items table
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

try {
    // Begin transaction
    $db->beginTransaction();

    // Add type column if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM directory_items LIKE 'type'");
    if ($stmt->rowCount() === 0) {
        $sql = "ALTER TABLE directory_items 
                ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'link' 
                AFTER category";
        $db->exec($sql);
        echo "<p style='color:green'>Successfully added 'type' column to directory_items table</p>";
    } else {
        echo "<p style='color:blue'>Type column already exists in directory_items table</p>";
    }

    // Commit transaction
    $db->commit();
    
    echo "<p><a href='direct_import.php'>Return to Import Tool</a></p>";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<p style='color:red'>Error updating directory_items table: " . $e->getMessage() . "</p>";
}