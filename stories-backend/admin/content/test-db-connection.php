<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

try {
    echo "<p>1. Testing database connection...</p>";
    
    // Include database connection
    require_once '../includes/db-connect.php';
    
    echo "<p>✅ Database connection file loaded successfully</p>";
    
    // Test if $db variable exists
    if (isset($db)) {
        echo "<p>✅ Database connection variable exists</p>";
        
        // Test a simple query
        $stmt = $db->query("SELECT 1 as test");
        $result = $stmt->fetchColumn();
        
        if ($result == 1) {
            echo "<p>✅ Database query successful</p>";
            
            // Check if age_ranges table exists
            $stmt = $db->query("SHOW TABLES LIKE 'age_ranges'");
            $tableExists = $stmt->rowCount() > 0;
            
            echo "<p>Age ranges table exists: " . ($tableExists ? "YES" : "NO") . "</p>";
            
            // Check if standard_reading_levels table exists
            $stmt = $db->query("SHOW TABLES LIKE 'standard_reading_levels'");
            $standardExists = $stmt->rowCount() > 0;
            
            echo "<p>Standard reading levels table exists: " . ($standardExists ? "YES" : "NO") . "</p>";
            
        } else {
            echo "<p>❌ Database query failed</p>";
        }
    } else {
        echo "<p>❌ Database connection variable not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<p><a href='check-age-ranges-table-usage.php'>Try Age Ranges Checker Again</a></p>";
?>
