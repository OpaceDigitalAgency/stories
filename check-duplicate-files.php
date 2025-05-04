<?php
/**
 * Script to check for duplicate files on the server
 * This script should be run on the server to identify duplicate files
 */

// Define the paths to check
$paths = [
    '/home/stories/api.storiesfromtheweb.org/admin/content/',
    '/home/stories/api.storiesfromtheweb.org/admin-new/content/',
    '/home/stories/api.storiesfromtheweb.org/admin/views/',
    '/home/stories/api.storiesfromtheweb.org/admin-new/views/'
];

// Files to check
$filesToCheck = [
    'author-delete.php',
    'contacts.php',
    'header.php',
    'footer.php',
    'db-connect.php',
    'auth-check.php'
];

// Check each path
foreach ($paths as $path) {
    echo "Checking path: $path\n";
    
    // Check if the path exists
    if (!file_exists($path)) {
        echo "Path does not exist: $path\n";
        continue;
    }
    
    // Check each file
    foreach ($filesToCheck as $file) {
        $filePath = $path . $file;
        
        if (file_exists($filePath)) {
            echo "File exists: $filePath\n";
            
            // Get the file size and modification time
            $size = filesize($filePath);
            $modTime = filemtime($filePath);
            
            echo "  Size: $size bytes\n";
            echo "  Last modified: " . date('Y-m-d H:i:s', $modTime) . "\n";
            
            // Get the first few lines of the file to help identify it
            $content = file_get_contents($filePath);
            $lines = explode("\n", $content);
            $firstFewLines = array_slice($lines, 0, 5);
            
            echo "  First few lines:\n";
            foreach ($firstFewLines as $line) {
                echo "    " . trim($line) . "\n";
            }
            
            echo "\n";
        } else {
            echo "File does not exist: $filePath\n";
        }
    }
    
    echo "\n";
}

echo "Check complete!\n";
