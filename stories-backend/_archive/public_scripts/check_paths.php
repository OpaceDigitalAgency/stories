<?php
/**
 * Check File Paths
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
    <title>Check File Paths</title>
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
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Check File Paths</h1>
    
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
        
        $hasIssues = false;
        
        // Check each directory
        foreach ($structure as $dir => $files) {
            echo "<h2>$dir Directory</h2>";
            
            $dirPath = $baseDir . '/' . $dir;
            $lowercasePath = $baseDir . '/' . strtolower($dir);
            
            if (!is_dir($dirPath)) {
                if (is_dir($lowercasePath)) {
                    echo "<p class='error'>Found lowercase directory: " . strtolower($dir) . "</p>";
                    $hasIssues = true;
                } else {
                    echo "<p class='error'>Missing directory: $dir</p>";
                    $hasIssues = true;
                }
            } else {
                echo "<p class='success'>✓ Directory exists with correct case</p>";
                
                // Check files
                foreach ($files as $file) {
                    $filePath = $dirPath . '/' . $file;
                    if (!file_exists($filePath)) {
                        echo "<p class='error'>Missing file: $file</p>";
                        $hasIssues = true;
                    } else {
                        echo "<p class='success'>✓ Found file: $file</p>";
                    }
                }
            }
        }
        
        if (!$hasIssues) {
            echo "<div class='box'>";
            echo "<h2>All Good!</h2>";
            echo "<p class='success'>✓ All directories and files are in the correct location with proper capitalization.</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>