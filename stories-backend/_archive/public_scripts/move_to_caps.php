<?php
/**
 * Move files to capitalized directories on server
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
    <title>Move Files to Capitalized Directories</title>
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
    </style>
</head>
<body>
    <h1>Move Files to Capitalized Directories</h1>
    
    <div class="box">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            // Base directory
            $baseDir = dirname(__DIR__) . '/api/v1';
            
            // Function to recursively move files
            function moveFiles($src, $dst) {
                if (!is_dir($dst)) {
                    mkdir($dst, 0755, true);
                }
                
                $files = scandir($src);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        $srcPath = $src . '/' . $file;
                        $dstPath = $dst . '/' . $file;
                        
                        if (is_dir($srcPath)) {
                            moveFiles($srcPath, $dstPath);
                        } else {
                            copy($srcPath, $dstPath);
                        }
                    }
                }
            }
            
            // Function to recursively delete directory
            function rrmdir($dir) {
                if (is_dir($dir)) {
                    $files = scandir($dir);
                    foreach ($files as $file) {
                        if ($file != '.' && $file != '..') {
                            $path = $dir . '/' . $file;
                            if (is_dir($path)) {
                                rrmdir($path);
                            } else {
                                unlink($path);
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
            
            echo "<h2>Moving Files</h2>";
            
            try {
                // Process each directory
                foreach ($directories as $old => $new) {
                    $oldPath = $baseDir . '/' . $old;
                    $newPath = $baseDir . '/' . $new;
                    
                    if (is_dir($oldPath)) {
                        echo "<h3>$old → $new</h3>";
                        moveFiles($oldPath, $newPath);
                        rrmdir($oldPath);
                        echo "<p class='success'>✓ Files moved successfully</p>";
                    } else {
                        echo "<p class='warning'>Directory $old not found or already moved</p>";
                    }
                }
                
                echo "<div class='box'>";
                echo "<h2>Next Steps</h2>";
                echo "<p>All files have been moved. Now run these commands:</p>";
                echo "<pre>";
                echo "git add stories-backend/api/v1/Core/* stories-backend/api/v1/Middleware/* stories-backend/api/v1/Endpoints/* stories-backend/api/v1/Utils/* stories-backend/api/v1/Config/*\n";
                echo "git commit -m \"Move files to capitalized directories\"\n";
                echo "git push origin main";
                echo "</pre>";
                echo "<p><a href='check_namespaces.php' class='button'>Check Namespaces →</a></p>";
                echo "</div>";
            } catch (Exception $e) {
                echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
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
                
                echo "<h3>$old → $new</h3>";
                if (is_dir($oldPath)) {
                    $files = glob($oldPath . '/*');
                    echo "<p>Files to move: " . count($files) . "</p>";
                    echo "<ul>";
                    foreach ($files as $file) {
                        echo "<li>" . basename($file) . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='success'>✓ Already in correct directory</p>";
                }
            }
            ?>
            <form method="post">
                <input type="hidden" name="confirm" value="true">
                <button type="submit" class="button">Move Files to Capitalized Directories</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>