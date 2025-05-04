<?php
/**
 * Script to fix the author-delete.php file on the server
 * This script should be run on the server to fix the session_start() issue
 */

// Define the paths to check
$paths = [
    '/home/stories/api.storiesfromtheweb.org/admin/content/author-delete.php',
    '/home/stories/api.storiesfromtheweb.org/admin-new/content/author-delete.php'
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
    
    // Check if the file already has session_start() at the beginning
    $lines = explode("\n", $content);
    $hasSessionStart = false;
    $sessionStartLine = -1;
    
    // Find the session_start() line
    foreach ($lines as $i => $line) {
        if (strpos($line, 'session_start()') !== false) {
            $hasSessionStart = true;
            $sessionStartLine = $i;
            break;
        }
    }
    
    if (!$hasSessionStart) {
        echo "No session_start() found in $filePath\n";
        continue;
    }
    
    // If session_start() is not at the beginning, move it
    if ($sessionStartLine > 3) {
        echo "Moving session_start() to the beginning of $filePath\n";
        
        // Remove the session_start() line
        unset($lines[$sessionStartLine]);
        
        // Add session_start() after the opening PHP tag
        array_splice($lines, 1, 0, 'session_start(); // Moved to beginning to prevent headers already sent error');
        
        // Rebuild the content
        $newContent = implode("\n", $lines);
        
        // Write the file
        if (file_put_contents($filePath, $newContent) === false) {
            echo "Error: Could not write to $filePath\n";
        } else {
            echo "Successfully fixed session_start() in $filePath\n";
        }
    } else {
        echo "session_start() is already at the beginning of $filePath\n";
    }
}

echo "Fix complete!\n";
