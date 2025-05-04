<?php
/**
 * Script to fix db-connect includes to use require_once instead of include
 */

// Get all PHP files in the content directory
$files = glob('stories-backend/admin/content/*.php');

foreach ($files as $filePath) {
    echo "Checking $filePath...\n";
    
    // Read the file
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Error: Could not read $filePath\n";
        continue;
    }
    
    // Check if the file includes the db-connect
    if (strpos($content, "include '../includes/db-connect.php';") !== false) {
        echo "Fixing db-connect include in $filePath...\n";
        
        // Replace include with require_once
        $content = str_replace(
            "include '../includes/db-connect.php';",
            "require_once '../includes/db-connect.php';",
            $content
        );
        
        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed db-connect include in $filePath\n";
        }
    }
    
    // Check if the file includes the db-connect with include_once
    if (strpos($content, "include_once '../includes/db-connect.php';") !== false) {
        echo "Fixing db-connect include_once in $filePath...\n";
        
        // Replace include_once with require_once
        $content = str_replace(
            "include_once '../includes/db-connect.php';",
            "require_once '../includes/db-connect.php';",
            $content
        );
        
        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed db-connect include_once in $filePath\n";
        }
    }
}

echo "All db-connect includes fixed!\n";
