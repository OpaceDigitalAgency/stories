<?php
/**
 * Move files to properly capitalized directories
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base directory
$baseDir = __DIR__ . '/api/v1';

// Directory mapping
$directories = [
    'core' => 'Core',
    'middleware' => 'Middleware',
    'endpoints' => 'Endpoints',
    'utils' => 'Utils',
    'config' => 'Config'
];

// Create capitalized directories
foreach ($directories as $old => $new) {
    $newPath = $baseDir . '/' . $new;
    if (!is_dir($newPath)) {
        mkdir($newPath, 0755, true);
        echo "Created directory: $newPath\n";
    }
}

// Move files
foreach ($directories as $old => $new) {
    $oldPath = $baseDir . '/' . $old;
    $newPath = $baseDir . '/' . $new;
    
    if (is_dir($oldPath)) {
        $files = glob($oldPath . '/*');
        foreach ($files as $file) {
            $filename = basename($file);
            $newFile = $newPath . '/' . $filename;
            
            if (rename($file, $newFile)) {
                echo "Moved: $filename to $new/\n";
            } else {
                echo "Failed to move: $filename\n";
            }
        }
        
        // Remove old directory
        if (count(glob($oldPath . '/*')) === 0) {
            rmdir($oldPath);
            echo "Removed old directory: $oldPath\n";
        }
    }
}

echo "\nDone! Now run:\ngit add .\ngit commit -m \"Move files to capitalized directories\"\ngit push origin main\n";