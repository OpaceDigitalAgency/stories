<?php
/**
 * Update Directory Items Schema
 * 
 * This script updates the directory_items table to add missing columns
 * and rename existing columns to match the expected schema
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

try {
    // Begin transaction
    $db->beginTransaction();

    // 1. First rename existing columns
    $db->exec("ALTER TABLE directory_items 
               CHANGE name title VARCHAR(100) NOT NULL,
               CHANGE url website_url VARCHAR(255) NOT NULL,
               CHANGE category category_id INT NOT NULL DEFAULT 1");

    echo "<p style='color:green'>Renamed existing columns</p>";

    // 2. Add new columns
    $db->exec("ALTER TABLE directory_items 
               ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'link' AFTER category_id,
               ADD COLUMN slug VARCHAR(255) NOT NULL AFTER type,
               ADD COLUMN cover_url VARCHAR(255) DEFAULT NULL AFTER slug,
               ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 0 AFTER cover_url,
               ADD COLUMN published_at DATETIME DEFAULT NULL AFTER is_published");

    echo "<p style='color:green'>Added new columns to directory_items table</p>";

    // 3. Add unique index on slug
    $db->exec("ALTER TABLE directory_items ADD UNIQUE INDEX idx_directory_items_slug (slug)");
    
    echo "<p style='color:green'>Added unique index on slug column</p>";

    // Commit transaction
    $db->commit();
    
    echo "<p style='color:green'>Successfully updated directory_items table schema</p>";
    echo "<p><a href='direct_import.php'>Return to Import Tool</a></p>";

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<p style='color:red'>Error updating directory_items table: " . $e->getMessage() . "</p>";
}