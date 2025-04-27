<?php
/**
 * Web-based deployment script for case sensitivity fix
 * Access this file through your browser to run the fix
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes

// Configuration
define('API_DIR', dirname(__FILE__));
define('BACKUP_DIR', API_DIR . '/backups/' . date('Ymd_His'));
define('SCRIPT_NAME', 'fix_case_once_and_for_all.php');

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Case Sensitivity Fix Deployment</title>
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
    <h1>Case Sensitivity Fix Deployment</h1>
    <div class="log">
<?php

function log_message($message, $type = 'info') {
    $timestamp = date('H:i:s');
    $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : '');
    echo "<div class='$class'>[$timestamp] $message</div>\n";
    flush();
    ob_flush();
}

try {
    // 1. Create backup directory
    log_message("Creating backup directory...");
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    
    // 2. Backup current state
    log_message("Backing up current API directory...");
    if (!is_dir(API_DIR . '/api')) {
        throw new Exception("API directory not found");
    }
    recurse_copy(API_DIR . '/api', BACKUP_DIR . '/api');
    
    // 3. Run the fix script
    log_message("Running case sensitivity fix...");
    require_once SCRIPT_NAME;
    
    // 4. Test API endpoints
    log_message("Testing API endpoints...");
    $endpoints = ['stories', 'authors', 'games', 'directory-items', 'ai-tools'];
    $failures = [];
    
    foreach ($endpoints as $endpoint) {
        $url = "https://api.storiesfromtheweb.org/api/v1/$endpoint";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            log_message("✓ $endpoint endpoint working", 'success');
        } else {
            $failures[] = "$endpoint ($httpCode)";
            log_message("✗ $endpoint endpoint failed: $httpCode", 'error');
        }
    }
    
    if (!empty($failures)) {
        throw new Exception("API endpoint tests failed: " . implode(", ", $failures));
    }
    
    log_message("\n✅ Deployment completed successfully!", 'success');
    log_message("\nNext steps:");
    log_message("1. Check API endpoints at https://api.storiesfromtheweb.org/test_api_format.php");
    log_message("2. Review logs in " . API_DIR . "/logs/api-error.log");
    log_message("3. Test frontend at https://storiesfromtheweb.netlify.app");
    
} catch (Exception $e) {
    log_message("\n❌ Error: " . $e->getMessage(), 'error');
    
    // Restore from backup
    log_message("\nRestoring from backup...");
    if (is_dir(BACKUP_DIR . '/api')) {
        if (is_dir(API_DIR . '/api')) {
            rename(API_DIR . '/api', API_DIR . '/api_failed_' . date('Ymd_His'));
        }
        recurse_copy(BACKUP_DIR . '/api', API_DIR . '/api');
        log_message("Restored from backup successfully", 'success');
    } else {
        log_message("Could not restore from backup - backup not found", 'error');
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
?>
    </div>
</body>
</html>