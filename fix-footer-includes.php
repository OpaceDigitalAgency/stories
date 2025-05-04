<?php
/**
 * Script to fix footer includes to use require_once instead of include
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
    
    // Check if the file includes the footer
    if (strpos($content, "include '../includes/footer.php';") !== false) {
        echo "Fixing footer include in $filePath...\n";
        
        // Replace include with require_once
        $content = str_replace(
            "include '../includes/footer.php';",
            "require_once '../includes/footer.php';",
            $content
        );
        
        // Write the file
        if (file_put_contents($filePath, $content) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed footer include in $filePath\n";
        }
    }
}

echo "All footer includes fixed!\n";
