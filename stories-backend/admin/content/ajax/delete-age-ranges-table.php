<?php
/**
 * AJAX handler for deleting the deprecated age_ranges table
 */

// Include database connection
require_once '../../db-connect.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    if ($_POST['action'] !== 'delete_age_ranges_table') {
        throw new Exception('Invalid action');
    }
    
    // Double-check that it's safe to delete
    $safetyChecks = [];
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'age_ranges'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        throw new Exception('Table age_ranges does not exist');
    }
    
    // Check if standard_reading_levels exists
    $stmt = $db->query("SHOW TABLES LIKE 'standard_reading_levels'");
    $standardExists = $stmt->rowCount() > 0;
    
    if (!$standardExists) {
        throw new Exception('Cannot delete age_ranges table: standard_reading_levels table does not exist');
    }
    
    // Check for foreign key constraints
    $stmt = $db->query("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME = 'age_ranges'
    ");
    $foreignKeyCount = $stmt->fetchColumn();
    
    if ($foreignKeyCount > 0) {
        throw new Exception('Cannot delete age_ranges table: foreign key constraints exist');
    }
    
    // All safety checks passed - delete the table
    $db->exec("DROP TABLE age_ranges");
    
    echo json_encode([
        'success' => true,
        'message' => 'age_ranges table has been successfully deleted'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
