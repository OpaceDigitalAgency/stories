<?php
/**
 * Secure System Script
 * 
 * This script performs security improvements:
 * 1. Removes the direct_login.php backdoor
 * 2. Creates an .htaccess file to protect the admin/includes/ directory
 * 
 * Run this after successfully logging in with the new admin user.
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Secure System</h1>
    <div class="container">';

// Function to check if a file exists and is writable
function checkFileAccess($path) {
    if (!file_exists($path)) {
        return ["exists" => false, "writable" => false];
    }
    return ["exists" => true, "writable" => is_writable($path)];
}

// Function to check if a directory exists and is writable
function checkDirAccess($path) {
    if (!is_dir($path)) {
        return ["exists" => false, "writable" => false];
    }
    return ["exists" => true, "writable" => is_writable($path)];
}

try {
    // 1. Remove direct_login.php backdoor
    echo "<h2>1. Remove direct_login.php backdoor</h2>";
    
    $directLoginPath = __DIR__ . '/direct_login.php';
    $fileAccess = checkFileAccess($directLoginPath);
    
    if ($fileAccess["exists"]) {
        if ($fileAccess["writable"]) {
            // Backup the file first (optional)
            $backupPath = __DIR__ . '/direct_login.php.bak';
            if (copy($directLoginPath, $backupPath)) {
                echo "<p class='info'>Created backup at: " . htmlspecialchars($backupPath) . "</p>";
            } else {
                echo "<p class='warning'>Could not create backup, but will proceed with removal.</p>";
            }
            
            // Delete the file
            if (unlink($directLoginPath)) {
                echo "<p class='success'>Successfully removed direct_login.php backdoor.</p>";
            } else {
                echo "<p class='error'>Failed to remove direct_login.php. Please delete it manually.</p>";
            }
        } else {
            echo "<p class='error'>direct_login.php exists but is not writable. Please delete it manually.</p>";
        }
    } else {
        echo "<p class='info'>direct_login.php does not exist. No action needed.</p>";
    }
    
    // 2. Create .htaccess to protect admin/includes/ directory
    echo "<h2>2. Protect admin/includes/ directory</h2>";
    
    $includesDir = __DIR__ . '/admin/includes';
    $htaccessPath = $includesDir . '/.htaccess';
    $dirAccess = checkDirAccess($includesDir);
    
    if ($dirAccess["exists"]) {
        if ($dirAccess["writable"]) {
            // Create or update .htaccess file
            $htaccessContent = "# Protect PHP files from direct access\n";
            $htaccessContent .= "<FilesMatch \"\\.php$\">\n";
            $htaccessContent .= "    Order Deny,Allow\n";
            $htaccessContent .= "    Deny from all\n";
            $htaccessContent .= "</FilesMatch>\n\n";
            $htaccessContent .= "# Allow index.php\n";
            $htaccessContent .= "<Files index.php>\n";
            $htaccessContent .= "    Order Allow,Deny\n";
            $htaccessContent .= "    Allow from all\n";
            $htaccessContent .= "</Files>\n";
            
            if (file_put_contents($htaccessPath, $htaccessContent)) {
                echo "<p class='success'>Successfully created/updated .htaccess to protect admin/includes/ directory.</p>";
            } else {
                echo "<p class='error'>Failed to create .htaccess file. Please create it manually with the following content:</p>";
                echo "<pre>" . htmlspecialchars($htaccessContent) . "</pre>";
            }
        } else {
            echo "<p class='error'>admin/includes/ directory exists but is not writable. Please create .htaccess manually.</p>";
            echo "<p>Content for .htaccess:</p>";
            echo "<pre># Protect PHP files from direct access\n";
            echo "<FilesMatch \"\\.php$\">\n";
            echo "    Order Deny,Allow\n";
            echo "    Deny from all\n";
            echo "</FilesMatch>\n\n";
            echo "# Allow index.php\n";
            echo "<Files index.php>\n";
            echo "    Order Allow,Deny\n";
            echo "    Allow from all\n";
            echo "</Files></pre>";
        }
    } else {
        echo "<p class='error'>admin/includes/ directory does not exist. Please check your installation.</p>";
    }
    
    // 3. Create logs directory if it doesn't exist
    echo "<h2>3. Create logs directory</h2>";
    
    $logsDir = '/home/stories/api.storiesfromtheweb.org/logs';
    
    echo "<p class='info'>Attempting to create logs directory at: " . htmlspecialchars($logsDir) . "</p>";
    echo "<p class='info'>Note: This may fail if the script doesn't have permission to create directories at this location.</p>";
    
    $logsDirExists = is_dir($logsDir);
    if (!$logsDirExists) {
        if (@mkdir($logsDir, 0755, true)) {
            echo "<p class='success'>Successfully created logs directory.</p>";
            $logsDirExists = true;
        } else {
            echo "<p class='warning'>Could not create logs directory. You may need to create it manually.</p>";
        }
    } else {
        echo "<p class='info'>Logs directory already exists.</p>";
    }
    
    // Create error log file if logs directory exists
    if ($logsDirExists) {
        $errorLogPath = $logsDir . '/api-error.log';
        $errorLogExists = file_exists($errorLogPath);
        
        if (!$errorLogExists) {
            if (@touch($errorLogPath)) {
                echo "<p class='success'>Successfully created error log file.</p>";
                if (@chmod($errorLogPath, 0664)) {
                    echo "<p class='success'>Successfully set permissions on error log file.</p>";
                } else {
                    echo "<p class='warning'>Could not set permissions on error log file. You may need to do this manually.</p>";
                }
            } else {
                echo "<p class='warning'>Could not create error log file. You may need to create it manually.</p>";
            }
        } else {
            echo "<p class='info'>Error log file already exists.</p>";
            if (@chmod($errorLogPath, 0664)) {
                echo "<p class='success'>Successfully set permissions on existing error log file.</p>";
            } else {
                echo "<p class='warning'>Could not set permissions on existing error log file. You may need to do this manually.</p>";
            }
        }
    }
    
    // Security reminder
    echo "<h2>Security Reminders</h2>";
    echo "<p class='warning'><strong>IMPORTANT:</strong> For security, delete the following files after use:</p>";
    echo "<ul>";
    echo "<li>create_admin.php</li>";
    echo "<li>create_admin_user.sql</li>";
    echo "<li>secure_system.php (this file)</li>";
    echo "</ul>";
    
    // Link to login page
    echo "<p><a href='admin/login.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// HTML footer
echo '</div>
</body>
</html>';