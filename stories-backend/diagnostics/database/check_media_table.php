<?php
/**
 * Media Table Structure Diagnostic
 * 
 * This script checks the structure of the media table and reports any issues.
 */

// Set headers for plain text output
header('Content-Type: text/plain');

// Include database connection
require_once '../../admin/includes/db-connect.php';

// Check if database connection was successful
if (!isset($db)) {
    echo "ERROR: Database connection failed.\n";
    exit;
}

try {
    // Get table structure
    $stmt = $db->query("DESCRIBE media");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "MEDIA TABLE STRUCTURE:\n";
    echo "=====================\n\n";
    
    // Display column information
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']}";
        if ($column['Null'] === 'NO') {
            echo " (NOT NULL)";
        }
        if ($column['Key'] === 'PRI') {
            echo " (PRIMARY KEY)";
        }
        echo "\n";
    }
    
    echo "\n";
    
    // Check for required columns
    $requiredColumns = ['id', 'filename', 'file_path', 'file_type', 'file_size', 'alt_text', 'created_at', 'updated_at', 'width', 'height'];
    $missingColumns = [];
    
    $columnNames = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $required) {
        if (!in_array($required, $columnNames)) {
            $missingColumns[] = $required;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "MISSING COLUMNS: " . implode(', ', $missingColumns) . "\n\n";
        
        // Generate SQL to add missing columns
        echo "SQL TO ADD MISSING COLUMNS:\n";
        foreach ($missingColumns as $column) {
            $sql = "ALTER TABLE media ADD COLUMN ";
            
            switch ($column) {
                case 'width':
                case 'height':
                    $sql .= "$column INT DEFAULT NULL";
                    break;
                default:
                    $sql .= "$column VARCHAR(255) DEFAULT NULL";
            }
            
            echo $sql . ";\n";
        }
    } else {
        echo "All required columns are present.\n";
    }
    
    // Check for sample data
    $stmt = $db->query("SELECT COUNT(*) as count FROM media");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "\nTotal records in media table: $count\n";
    
    if ($count > 0) {
        // Show a sample record
        $stmt = $db->query("SELECT * FROM media ORDER BY id DESC LIMIT 1");
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nSAMPLE RECORD:\n";
        foreach ($sample as $key => $value) {
            echo "$key: " . (is_null($value) ? "NULL" : $value) . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
