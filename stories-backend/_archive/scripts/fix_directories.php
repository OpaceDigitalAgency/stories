<?php
/**
 * Directory Structure Fix
 * 
 * This script properly moves files from lowercase to uppercase directories
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

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Directory Structure</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Fix Directory Structure</h1>
    
    <div class="box">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
            foreach ($directories as $old => $new) {
                $oldPath = $baseDir . '/' . $old;
                $tempPath = $baseDir . '/' . $old . '_temp';
                $newPath = $baseDir . '/' . $new;
                
                if (is_dir($oldPath)) {
                    echo "<h3>Processing $old → $new</h3>";
                    
                    // Step 1: Move to temp directory
                    if (rename($oldPath, $tempPath)) {
                        echo "<p class='success'>✓ Moved to temporary directory</p>";
                        
                        // Step 2: Create new directory
                        if (!is_dir($newPath)) {
                            mkdir($newPath, 0755, true);
                            echo "<p class='success'>✓ Created new directory</p>";
                        }
                        
                        // Step 3: Move files
                        $files = glob($tempPath . '/*');
                        foreach ($files as $file) {
                            $filename = basename($file);
                            if (rename($file, $newPath . '/' . $filename)) {
                                echo "<p class='success'>✓ Moved $filename</p>";
                            } else {
                                echo "<p class='error'>Failed to move $filename</p>";
                            }
                        }
                        
                        // Step 4: Remove temp directory
                        rmdir($tempPath);
                        echo "<p class='success'>✓ Cleaned up temporary directory</p>";
                    } else {
                        echo "<p class='error'>Failed to process directory</p>";
                    }
                }
            }
            echo "<p class='success'>✅ Directory structure update complete!</p>";
            echo "<p><a href='check_namespaces.php'>Check Namespaces →</a></p>";
        } else {
            ?>
            <h2>Current Structure</h2>
            <?php
            foreach ($directories as $old => $new) {
                $oldPath = $baseDir . '/' . $old;
                $newPath = $baseDir . '/' . $new;
                
                echo "<h3>$old → $new</h3>";
                if (is_dir($oldPath)) {
                    echo "<p class='error'>Found lowercase directory: $oldPath</p>";
                } elseif (is_dir($newPath)) {
                    echo "<p class='success'>Correct directory: $newPath</p>";
                } else {
                    echo "<p>No directory found</p>";
                }
            }
            ?>
            <form method="post">
                <input type="hidden" name="fix" value="true">
                <button type="submit">Fix Directory Structure</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>