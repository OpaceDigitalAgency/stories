<?php
/**
 * Move files to properly capitalized directories
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
                // Create capitalized directories
                foreach ($directories as $old => $new) {
                    $newPath = $baseDir . '/' . $new;
                    if (!is_dir($newPath)) {
                        if (mkdir($newPath, 0755, true)) {
                            echo "<p class='success'>✓ Created directory: $new</p>";
                        }
                    }
                }
                
                // Move files
                foreach ($directories as $old => $new) {
                    $oldPath = $baseDir . '/' . $old;
                    $newPath = $baseDir . '/' . $new;
                    
                    if (is_dir($oldPath)) {
                        echo "<h3>Moving files from $old to $new</h3>";
                        
                        $files = glob($oldPath . '/*');
                        foreach ($files as $file) {
                            $filename = basename($file);
                            $newFile = $newPath . '/' . $filename;
                            
                            if (rename($file, $newFile)) {
                                echo "<p class='success'>✓ Moved: $filename</p>";
                            } else {
                                echo "<p class='error'>Failed to move: $filename</p>";
                            }
                        }
                        
                        // Remove old directory if empty
                        if (count(glob($oldPath . '/*')) === 0) {
                            if (rmdir($oldPath)) {
                                echo "<p class='success'>✓ Removed old directory: $old</p>";
                            }
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
                    $files = glob($oldPath . '/*');
                    echo "<p>Files to move: " . count($files) . "</p>";
                    echo "<ul>";
                    foreach ($files as $file) {
                        echo "<li>" . basename($file) . "</li>";
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