<?php
/**
 * Script to fix auth-check includes to use require_once instead of include
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
    
    // Check if the file includes the auth-check
    if (strpos($content, "include '../includes/auth-check.php';") !== false) {
        echo "Fixing auth-check include in $filePath...\n";
        
        // Replace include with require_once
        $content = str_replace(
            "include '../includes/auth-check.php';",
            "require_once '../includes/auth-check.php';",
            $content
        );
        
        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed auth-check include in $filePath\n";
        }
    }
    
    // Check if the file includes the auth-check with include_once
    if (strpos($content, "include_once '../includes/auth-check.php';") !== false) {
        echo "Fixing auth-check include_once in $filePath...\n";
        
        // Replace include_once with require_once
        $content = str_replace(
            "include_once '../includes/auth-check.php';",
            "require_once '../includes/auth-check.php';",
            $content
        );
        
        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed auth-check include_once in $filePath\n";
        }
    }
}

echo "All auth-check includes fixed!\n";
