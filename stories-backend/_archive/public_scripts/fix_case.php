<?php
/**
 * Simple Case Sensitivity Fix Interface
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');

// Base directory for API
$baseDir = dirname(__DIR__) . '/api/v1';

// Expected directory structure
$expectedDirs = [
    'Core',
    'Middleware',
    'Endpoints',
    'Utils',
    'Config'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Case Sensitivity Fix</title>
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
    <h1>Case Sensitivity Fix</h1>
    
    <div class="box">
        <h2>Directory Structure</h2>
        <?php
        foreach ($expectedDirs as $dir) {
            echo "<h3>$dir</h3>";
            
            $path = "$baseDir/$dir";
            $lowercasePath = "$baseDir/" . strtolower($dir);
            
            if (!is_dir($path)) {
                if (is_dir($lowercasePath)) {
                    echo "<p class='error'>Found lowercase: $lowercasePath</p>";
                    if (isset($_POST['fix']) && $_POST['fix'] === 'true') {
                        rename($lowercasePath, $path);
                        echo "<p class='success'>Fixed: Renamed to $path</p>";
                    }
                } else {
                    echo "<p class='error'>Missing directory: $path</p>";
                    if (isset($_POST['fix']) && $_POST['fix'] === 'true') {
                        mkdir($path, 0755, true);
                        echo "<p class='success'>Fixed: Created $path</p>";
                    }
                }
            } else {
                echo "<p class='success'>Correct: $path</p>";
            }
        }
        ?>
    </div>
    
    <?php if (!isset($_POST['fix'])): ?>
    <form method="post">
        <input type="hidden" name="fix" value="true">
        <button type="submit">Fix Directory Structure</button>
    </form>
    <?php endif; ?>
    
    <div class="box">
        <h2>Next Steps</h2>
        <ol>
            <li>Check that all directories are properly capitalized</li>
            <li>Update any code references to use proper capitalization</li>
            <li>Test your application</li>
        </ol>
    </div>
</body>
</html>