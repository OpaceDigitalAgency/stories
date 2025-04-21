<?php
/**
 * Permanent Case Sensitivity Fix
 * 
 * This script:
 * 1. Creates properly capitalized directories
 * 2. Moves files to correct directories
 * 3. Updates namespace declarations
 * 4. Removes old lowercase directories
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes

// Configuration
define('API_DIR', __DIR__ . '/api/v1');
define('BACKUP_DIR', __DIR__ . '/backups/' . date('Ymd_His'));

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Permanent Case Fix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .log {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            white-space: pre-wrap;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Permanent Case Fix</h1>
    <div class="log">
<?php

function log_message($message, $type = 'info') {
    $timestamp = date('H:i:s');
    $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : '');
    echo "<div class='$class'>[$timestamp] $message</div>\n";
    flush();
    ob_flush();
}

// Directory mapping
$directoryMap = [
    'core' => 'Core',
    'middleware' => 'Middleware',
    'endpoints' => 'Endpoints',
    'utils' => 'Utils',
    'config' => 'Config'
];

try {
    // 1. Create backup
    log_message("Creating backup...");
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    
    // Backup current state
    foreach ($directoryMap as $old => $new) {
        $oldPath = API_DIR . '/' . $old;
        $backupPath = BACKUP_DIR . '/' . $old;
        if (is_dir($oldPath)) {
            recurse_copy($oldPath, $backupPath);
            log_message("Backed up: $old → $backupPath");
        }
    }
    
    // 2. Create new directories
    foreach ($directoryMap as $old => $new) {
        $newPath = API_DIR . '/' . $new;
        if (!is_dir($newPath)) {
            mkdir($newPath, 0755, true);
            log_message("Created directory: $new");
        }
    }
    
    // 3. Move files and update namespaces
    foreach ($directoryMap as $old => $new) {
        $oldPath = API_DIR . '/' . $old;
        $newPath = API_DIR . '/' . $new;
        
        if (is_dir($oldPath)) {
            $files = glob($oldPath . '/*.php');
            foreach ($files as $file) {
                $filename = basename($file);
                $newFile = $newPath . '/' . $filename;
                
                // Read and update namespace
                $content = file_get_contents($file);
                $content = preg_replace(
                    "/namespace\s+StoriesAPI\\\\" . preg_quote($old, '/') . "\s*;/i",
                    "namespace StoriesAPI\\$new;",
                    $content
                );
                
                // Write to new location
                file_put_contents($newFile, $content);
                log_message("Moved and updated: $filename");
                
                // Remove old file
                unlink($file);
            }
            
            // Remove old directory if empty
            if (is_dir_empty($oldPath)) {
                rmdir($oldPath);
                log_message("Removed old directory: $old");
            }
        }
    }
    
    // 4. Update references in all PHP files
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(API_DIR)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $modified = false;
            
            // Update namespace references
            foreach ($directoryMap as $old => $new) {
                $pattern = "/StoriesAPI\\\\" . preg_quote($old, '/') . "\\\\/i";
                $replacement = "StoriesAPI\\$new\\";
                
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                    $modified = true;
                }
            }
            
            if ($modified) {
                file_put_contents($file->getPathname(), $content);
                log_message("Updated references in: " . basename($file->getPathname()));
            }
        }
    }
    
    log_message("\n✅ Case sensitivity fix completed successfully!", 'success');
    
} catch (Exception $e) {
    log_message("\n❌ Error: " . $e->getMessage(), 'error');
    
    // Restore from backup
    log_message("\nRestoring from backup...");
    foreach ($directoryMap as $old => $new) {
        $backupPath = BACKUP_DIR . '/' . $old;
        $oldPath = API_DIR . '/' . $old;
        
        if (is_dir($backupPath)) {
            if (is_dir($oldPath)) {
                rename($oldPath, $oldPath . '_failed_' . date('Ymd_His'));
            }
            recurse_copy($backupPath, $oldPath);
            log_message("Restored: $old");
        }
    }
}

function recurse_copy($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    while(false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function is_dir_empty($dir) {
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            closedir($handle);
            return false;
        }
    }
    closedir($handle);
    return true;
}
?>
    </div>
</body>
</html>