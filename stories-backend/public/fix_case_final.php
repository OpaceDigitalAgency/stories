<?php
/**
 * Final Case Sensitivity Fix
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base directory
$baseDir = dirname(__DIR__) . '/api/v1';

// Function to recursively copy a directory
function rcopy($src, $dst) {
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    
    $dir = opendir($src);
    while(false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                rcopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Function to recursively delete a directory
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

// Directory mapping
$directories = [
    'core' => 'Core',
    'middleware' => 'Middleware',
    'endpoints' => 'Endpoints',
    'utils' => 'Utils',
    'config' => 'Config'
];

// Process each directory
foreach ($directories as $old => $new) {
    $oldPath = $baseDir . '/' . $old;
    $newPath = $baseDir . '/' . $new;
    $tempPath = $baseDir . '/' . $old . '_temp';
    
    if (is_dir($oldPath)) {
        echo "Processing $old → $new\n";
        
        // Move to temp directory
        rename($oldPath, $tempPath);
        echo "- Moved to temp directory\n";
        
        // Create new directory
        if (!is_dir($newPath)) {
            mkdir($newPath, 0755, true);
        }
        echo "- Created new directory\n";
        
        // Copy files
        rcopy($tempPath, $newPath);
        echo "- Copied files\n";
        
        // Remove temp directory
        rrmdir($tempPath);
        echo "- Cleaned up\n";
    }
}

echo "\nDone! Now run:\n";
echo "git add stories-backend/api/v1/Core/* stories-backend/api/v1/Middleware/* stories-backend/api/v1/Endpoints/* stories-backend/api/v1/Utils/* stories-backend/api/v1/Config/*\n";
echo "git commit -m \"Move files to capitalized directories\"\n";
echo "git push origin main\n";