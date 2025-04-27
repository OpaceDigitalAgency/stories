<?php
/**
 * Move files to properly capitalized directories
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');

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

// Function to recursively copy a directory
function rcopy($src, $dst) {
    if (is_dir($src)) {
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        $files = scandir($src);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                rcopy("$src/$file", "$dst/$file");
            }
        }
    } else if (file_exists($src)) {
        copy($src, $dst);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Move Files</title>
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
        pre {
            background: #fff;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Move Files to Capitalized Directories</h1>
    
    <div class="box">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            // Base directory
            $baseDir = dirname(__DIR__) . '/api/v1';
            
            // Directory mapping
            $directories = [
                'core' => 'Core',
                'middleware' => 'Middleware',
                'endpoints' => 'Endpoints',
                'utils' => 'Utils',
                'config' => 'Config'
            ];
            
            try {
                foreach ($directories as $old => $new) {
                    $oldPath = $baseDir . '/' . $old;
                    $newPath = $baseDir . '/' . $new;
                    $tempPath = $baseDir . '/' . $old . '_temp';
                    
                    if (is_dir($oldPath)) {
                        echo "<h3>Processing $old → $new</h3>";
                        
                        // Step 1: Move to temp directory
                        if (is_dir($tempPath)) {
                            rrmdir($tempPath);
                        }
                        if (rename($oldPath, $tempPath)) {
                            echo "<p class='success'>✓ Moved to temporary directory</p>";
                            
                            // Step 2: Create new directory
                            if (!is_dir($newPath)) {
                                mkdir($newPath, 0755, true);
                            }
                            
                            // Step 3: Copy files
                            rcopy($tempPath, $newPath);
                            echo "<p class='success'>✓ Copied files to new directory</p>";
                            
                            // Step 4: Remove temp directory
                            rrmdir($tempPath);
                            echo "<p class='success'>✓ Cleaned up temporary directory</p>";
                        } else {
                            echo "<p class='error'>Failed to move directory</p>";
                        }
                    }
                }
                
                echo "<p class='success'>✅ All files moved successfully!</p>";
                echo "<p><a href='check_namespaces.php'>Check Namespaces →</a></p>";
                
            } catch (Exception $e) {
                echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            // Show confirmation form
            ?>
            <h2>Current Structure</h2>
            <?php
            $baseDir = dirname(__DIR__) . '/api/v1';
            foreach ($directories as $old => $new) {
                $oldPath = $baseDir . '/' . $old;
                $newPath = $baseDir . '/' . $new;
                
                echo "<h3>$old → $new</h3>";
                if (is_dir($oldPath)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($oldPath, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    echo "<ul>";
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            echo "<li>" . str_replace($oldPath . '/', '', $file->getPathname()) . "</li>";
                        }
                    }
                    echo "</ul>";
                }
            }
            ?>
            <form method="post">
                <input type="hidden" name="confirm" value="true">
                <button type="submit">Move Files</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>