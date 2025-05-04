<?php
/**
 * Directory Structure Cleanup Script
 * 
 * This script helps clean up the directory structure on the server:
 * 1. Archives the /admin-new/ directory if it's not being used
 * 2. Removes all .bak files
 * 3. Standardizes the directory structure
 * 
 * Usage: php cleanup-directory-structure.php
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define paths
$rootPath = dirname(dirname(__FILE__)); // stories-backend directory
$adminPath = $rootPath . '/admin';
$adminNewPath = $rootPath . '/admin-new';
$archivePath = $rootPath . '/_archive';

// Create archive directory if it doesn't exist
if (!file_exists($archivePath)) {
    mkdir($archivePath, 0755, true);
    echo "Created archive directory: $archivePath\n";
}

// Function to recursively copy a directory
function copyDirectory($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $dir = opendir($source);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            $srcFile = $source . '/' . $file;
            $destFile = $destination . '/' . $file;
            
            if (is_dir($srcFile)) {
                copyDirectory($srcFile, $destFile);
            } else {
                copy($srcFile, $destFile);
            }
        }
    }
    closedir($dir);
}

// Function to recursively delete a directory
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
            if (is_dir($dir . "/" . $object)) {
                deleteDirectory($dir . "/" . $object);
            } else {
                unlink($dir . "/" . $object);
            }
        }
    }
    rmdir($dir);
}

// Function to find and remove .bak files
function removeBakFiles($dir) {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.bak(\.\d+)?$/', $file->getFilename())) {
            echo "Removing: " . $file->getPathname() . "\n";
            unlink($file->getPathname());
            $count++;
        }
    }
    
    return $count;
}

// Check if admin-new directory exists
if (is_dir($adminNewPath)) {
    echo "Found admin-new directory. Archiving...\n";
    
    // Create archive directory for admin-new
    $adminNewArchivePath = $archivePath . '/admin-new_' . date('Y-m-d_H-i-s');
    mkdir($adminNewArchivePath, 0755, true);
    
    // Copy admin-new to archive
    copyDirectory($adminNewPath, $adminNewArchivePath);
    echo "Copied admin-new to archive: $adminNewArchivePath\n";
    
    // Delete admin-new
    deleteDirectory($adminNewPath);
    echo "Deleted admin-new directory\n";
} else {
    echo "admin-new directory not found. Skipping...\n";
}

// Remove .bak files
echo "Removing .bak files...\n";
$bakFilesRemoved = removeBakFiles($rootPath);
echo "Removed $bakFilesRemoved .bak files\n";

// Create standard directories if they don't exist
$standardDirs = [
    $adminPath . '/content',
    $adminPath . '/includes',
    $adminPath . '/assets',
    $adminPath . '/assets/css',
    $adminPath . '/assets/js',
    $adminPath . '/assets/img',
    $adminPath . '/js'
];

foreach ($standardDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: $dir\n";
    }
}

echo "Directory structure cleanup complete!\n";
