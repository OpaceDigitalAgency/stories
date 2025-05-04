<?php
/**
 * Script to fix header includes to use require_once instead of include
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
    
    // Check if the file includes the header
    if (strpos($content, "include '../includes/header.php';") !== false) {
        echo "Fixing header include in $filePath...\n";
        
        // Replace include with require_once
        $content = str_replace(
            "include '../includes/header.php';",
            "require_once '../includes/header.php';",
            $content
        );
        
        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed header include in $filePath\n";
        }
    }
    
    // Check if the file includes the header with include_once
    if (strpos($content, "include_once '../includes/header.php';") !== false) {
        echo "Fixing header include_once in $filePath...\n";
        
        // Replace include_once with require_once
        $content = str_replace(
            "include_once '../includes/header.php';",
            "require_once '../includes/header.php';",
            $content
        );
        
        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed header include_once in $filePath\n";
        }
    }
}

echo "All header includes fixed!\n";
