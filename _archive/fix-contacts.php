<?php
/**
 * Script to fix the contacts.php file on the server
 * This script should be run on the server to fix the duplicate header/footer issue
 */

// Define the paths to check
$paths = [
    '/home/stories/api.storiesfromtheweb.org/admin/content/contacts.php',
    '/home/stories/api.storiesfromtheweb.org/admin-new/content/contacts.php'
];

foreach ($paths as $filePath) {
    echo "Checking file: $filePath\n";
    
    // Check if the file exists
    if (!file_exists($filePath)) {
        echo "File does not exist: $filePath\n";
        continue;
    }
    
    // Read the file
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Error: Could not read $filePath\n";
        continue;
    }
    
    // Create a backup of the file
    $backupPath = $filePath . '.bak.' . date('Y-m-d-H-i-s');
    if (file_put_contents($backupPath, $content) === false) {
        echo "Error: Could not create backup at $backupPath\n";
        continue;
    }
    
    echo "Created backup at $backupPath\n";
    
    // Count the number of header includes
    $headerCount = substr_count($content, "require_once '../includes/header.php'") + 
                  substr_count($content, "include '../includes/header.php'") + 
                  substr_count($content, "include_once '../includes/header.php'");
    
    // Count the number of footer includes
    $footerCount = substr_count($content, "require_once '../includes/footer.php'") + 
                  substr_count($content, "include '../includes/footer.php'") + 
                  substr_count($content, "include_once '../includes/footer.php'");
    
    echo "Found $headerCount header includes and $footerCount footer includes in $filePath\n";
    
    // If there are multiple header or footer includes, fix them
    if ($headerCount > 1 || $footerCount > 1) {
        echo "Fixing duplicate includes in $filePath\n";
        
        // Split the content into lines
        $lines = explode("\n", $content);
        
        // Track which includes we've seen
        $seenHeader = false;
        $seenFooter = false;
        
        // Process each line
        $newLines = [];
        foreach ($lines as $line) {
            // Check for header include
            if (strpos($line, '../includes/header.php') !== false) {
                if (!$seenHeader) {
                    // Keep the first header include and make it require_once
                    $newLines[] = "require_once '../includes/header.php';";
                    $seenHeader = true;
                }
                // Skip duplicate header includes
                continue;
            }
            
            // Check for footer include
            if (strpos($line, '../includes/footer.php') !== false) {
                if (!$seenFooter) {
                    // Keep the first footer include and make it require_once
                    $newLines[] = "require_once '../includes/footer.php';";
                    $seenFooter = true;
                }
                // Skip duplicate footer includes
                continue;
            }
            
            // Keep all other lines
            $newLines[] = $line;
        }
        
        // Rebuild the content
        $newContent = implode("\n", $newLines);
        
        // Write the file
        if (file_put_contents($filePath, $newContent) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed duplicate includes in $filePath\n";
        }
    } else {
        echo "No duplicate includes found in $filePath\n";
    }
}

echo "Fix complete!\n";
