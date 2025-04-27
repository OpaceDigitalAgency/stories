<?php
/**
 * Final Case Sensitivity Fix
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Final Case Sensitivity Fix</title>
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
        .warning { color: orange; }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: #0056b3;
        }
        .progress {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            background: #fff;
        }
        .progress.done {
            background: #e8f5e9;
        }
        .checkmark {
            color: green;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <h1>Final Case Sensitivity Fix</h1>
    
    <div class="box">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
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
            
            echo "<h2>Processing Directories</h2>";
            
            // Process each directory
            foreach ($directories as $old => $new) {
                $oldPath = $baseDir . '/' . $old;
                $newPath = $baseDir . '/' . $new;
                $tempPath = $baseDir . '/' . $old . '_temp';
                
                echo "<div class='progress'>";
                echo "<h3>$old → $new</h3>";
                
                if (is_dir($oldPath)) {
                    // Move to temp directory
                    if (rename($oldPath, $tempPath)) {
                        echo "<p><span class='checkmark'>✓</span> Moved to temporary directory</p>";
                    }
                    
                    // Create new directory
                    if (!is_dir($newPath)) {
                        if (mkdir($newPath, 0755, true)) {
                            echo "<p><span class='checkmark'>✓</span> Created new directory</p>";
                        }
                    }
                    
                    // Copy files
                    rcopy($tempPath, $newPath);
                    echo "<p><span class='checkmark'>✓</span> Copied files</p>";
                    
                    // Remove temp directory
                    rrmdir($tempPath);
                    echo "<p><span class='checkmark'>✓</span> Cleaned up temporary files</p>";
                } else {
                    echo "<p class='success'>✓ Directory already correct</p>";
                }
                
                echo "</div>";
            }
            
            echo "<div class='box'>";
            echo "<h2>Next Steps</h2>";
            echo "<p>All directories have been fixed. Now run these commands:</p>";
            echo "<pre>";
            echo "git add stories-backend/api/v1/Core/* stories-backend/api/v1/Middleware/* stories-backend/api/v1/Endpoints/* stories-backend/api/v1/Utils/* stories-backend/api/v1/Config/*\n";
            echo "git commit -m \"Move files to capitalized directories\"\n";
            echo "git push origin main";
            echo "</pre>";
            echo "<p><a href='check_namespaces.php' class='button'>Check Namespaces →</a></p>";
            echo "</div>";
            
        } else {
            // Show confirmation form
            ?>
            <h2>Current Structure</h2>
            <?php
            $baseDir = dirname(__DIR__) . '/api/v1';
            $directories = [
                'core' => 'Core',
                'middleware' => 'Middleware',
                'endpoints' => 'Endpoints',
                'utils' => 'Utils',
                'config' => 'Config'
            ];
            
            foreach ($directories as $old => $new) {
                $oldPath = $baseDir . '/' . $old;
                $newPath = $baseDir . '/' . $new;
                
                echo "<div class='progress'>";
                echo "<h3>$old → $new</h3>";
                
                if (is_dir($oldPath)) {
                    echo "<p class='warning'>Found lowercase directory: $old</p>";
                    $files = glob($oldPath . '/*');
                    echo "<p>Files to move: " . count($files) . "</p>";
                } elseif (is_dir($newPath)) {
                    echo "<p class='success'>✓ Directory already correct</p>";
                } else {
                    echo "<p class='error'>No directory found</p>";
                }
                
                echo "</div>";
            }
            ?>
            <form method="post">
                <input type="hidden" name="fix" value="true">
                <button type="submit" class="button">Fix Directory Structure</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>