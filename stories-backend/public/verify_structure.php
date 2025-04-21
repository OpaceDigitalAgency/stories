<?php
/**
 * Verify and Fix Directory Structure
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
    <title>Verify Directory Structure</title>
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
    <h1>Verify Directory Structure</h1>
    
    <div class="box">
        <?php
        // Base directory
        $baseDir = dirname(__DIR__) . '/api/v1';
        
        // Expected structure
        $structure = [
            'Core' => [
                'Auth.php',
                'BaseController.php',
                'Database.php',
                'Router.php'
            ],
            'Middleware' => [
                'AuthMiddleware.php',
                'CorsMiddleware.php',
                'SimpleAuthMiddleware.php'
            ],
            'Endpoints' => [
                'AiToolsController.php',
                'AuthController.php',
                'AuthorsController.php',
                'BlogPostsController.php',
                'DirectoryItemsController.php',
                'GamesController.php',
                'StoriesController.php',
                'TagsController.php'
            ],
            'Utils' => [
                'Response.php',
                'Validator.php'
            ],
            'Config' => [
                'config.php'
            ]
        ];
        
        function fixPermissions($path) {
            chmod($path, 0755);
            if (is_dir($path)) {
                $files = scandir($path);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        $fullPath = $path . '/' . $file;
                        if (is_dir($fullPath)) {
                            chmod($fullPath, 0755);
                            fixPermissions($fullPath);
                        } else {
                            chmod($fullPath, 0644);
                        }
                    }
                }
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix'])) {
            echo "<h2>Fixing Structure</h2>";
            
            foreach ($structure as $dir => $files) {
                $dirPath = $baseDir . '/' . $dir;
                
                // Create directory if it doesn't exist
                if (!is_dir($dirPath)) {
                    if (mkdir($dirPath, 0755, true)) {
                        echo "<p class='success'>✓ Created directory: $dir</p>";
                    }
                }
                
                // Check files
                foreach ($files as $file) {
                    $filePath = $dirPath . '/' . $file;
                    $lowercaseDir = $baseDir . '/' . strtolower($dir);
                    $lowercaseFile = $lowercaseDir . '/' . $file;
                    
                    if (file_exists($lowercaseFile) && !file_exists($filePath)) {
                        if (rename($lowercaseFile, $filePath)) {
                            echo "<p class='success'>✓ Moved file: $file to $dir/</p>";
                        }
                    }
                }
                
                // Fix permissions
                fixPermissions($dirPath);
                echo "<p class='success'>✓ Fixed permissions for $dir/</p>";
            }
            
            // Remove old lowercase directories
            foreach ($structure as $dir => $files) {
                $lowercaseDir = $baseDir . '/' . strtolower($dir);
                if (is_dir($lowercaseDir)) {
                    if (rmdir($lowercaseDir)) {
                        echo "<p class='success'>✓ Removed old directory: " . strtolower($dir) . "</p>";
                    }
                }
            }
            
            echo "<p class='success'>✅ Structure verification complete!</p>";
            echo "<p><a href='check_namespaces.php'>Check Namespaces →</a></p>";
            
        } else {
            // Show current structure
            echo "<h2>Current Structure</h2>";
            
            $hasIssues = false;
            foreach ($structure as $dir => $files) {
                echo "<h3>$dir Directory</h3>";
                
                $dirPath = $baseDir . '/' . $dir;
                $lowercaseDir = $baseDir . '/' . strtolower($dir);
                
                if (!is_dir($dirPath)) {
                    if (is_dir($lowercaseDir)) {
                        echo "<p class='error'>Found lowercase directory: " . strtolower($dir) . "</p>";
                        $hasIssues = true;
                    } else {
                        echo "<p class='error'>Missing directory: $dir</p>";
                        $hasIssues = true;
                    }
                } else {
                    echo "<p class='success'>Directory exists with correct case</p>";
                    
                    foreach ($files as $file) {
                        $filePath = $dirPath . '/' . $file;
                        if (!file_exists($filePath)) {
                            echo "<p class='error'>Missing file: $file</p>";
                            $hasIssues = true;
                        }
                    }
                }
            }
            
            if ($hasIssues) {
                echo "<form method='post'>";
                echo "<input type='hidden' name='fix' value='true'>";
                echo "<button type='submit'>Fix Structure</button>";
                echo "</form>";
            } else {
                echo "<p class='success'>✅ All directories and files are correct!</p>";
            }
        }
        ?>
    </div>
</body>
</html>