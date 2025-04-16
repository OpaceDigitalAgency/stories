<?php
/**
 * PHP Syntax Check Script
 * 
 * This script checks all PHP files in the API directory for syntax errors.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Syntax Check</h1>";

// Function to check PHP syntax
function checkSyntax($file) {
    $output = null;
    $return_var = null;
    exec("php -l " . escapeshellarg($file), $output, $return_var);
    
    if ($return_var !== 0) {
        return [
            'status' => 'error',
            'message' => implode("\n", $output)
        ];
    }
    
    return [
        'status' => 'ok',
        'message' => "No syntax errors detected in $file"
    ];
}

// Function to scan directory recursively
function scanDirectory($dir) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            $files = array_merge($files, scanDirectory($path));
        } else if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
    
    return $files;
}

// Get all PHP files in the API directory
$apiDir = __DIR__;
$files = scanDirectory($apiDir);

// Check syntax of each file
$errors = [];
$checked = 0;

echo "<h2>Checking " . count($files) . " PHP files...</h2>";

foreach ($files as $file) {
    $result = checkSyntax($file);
    $checked++;
    
    if ($result['status'] === 'error') {
        $errors[] = [
            'file' => $file,
            'message' => $result['message']
        ];
        
        echo "<div style='color: red; margin-bottom: 10px;'>";
        echo "<strong>❌ " . htmlspecialchars($file) . "</strong><br>";
        echo "<pre>" . htmlspecialchars($result['message']) . "</pre>";
        echo "</div>";
    }
}

// Display summary
if (count($errors) === 0) {
    echo "<div style='color: green; margin-top: 20px;'>";
    echo "<h2>✅ All files passed syntax check!</h2>";
    echo "<p>Checked $checked PHP files.</p>";
    echo "</div>";
} else {
    echo "<div style='color: red; margin-top: 20px;'>";
    echo "<h2>❌ Found " . count($errors) . " files with syntax errors!</h2>";
    echo "<p>Checked $checked PHP files.</p>";
    echo "</div>";
    
    echo "<h2>Error Summary:</h2>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li><strong>" . htmlspecialchars($error['file']) . "</strong></li>";
    }
    echo "</ul>";
}