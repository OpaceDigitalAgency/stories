<?php
/**
 * Namespace Verification Script
 * 
 * This script verifies that all namespace references match the directory structure
 * and enforces strict case sensitivity.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes

// Configuration
define('API_DIR', dirname(__FILE__) . '/api/v1');

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Namespace Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .log {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            white-space: pre-wrap;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Namespace Verification</h1>
    <div class="log">
<?php

function log_message($message, $type = 'info') {
    $timestamp = date('H:i:s');
    $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : '');
    echo "<div class='$class'>[$timestamp] $message</div>\n";
    flush();
    ob_flush();
}

// Map of correct namespace capitalization
$correctNamespaces = [
    'core' => 'Core',
    'middleware' => 'Middleware',
    'endpoints' => 'Endpoints',
    'utils' => 'Utils',
    'config' => 'Config'
];

// Find all PHP files
$issues = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(API_DIR)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $relativePath = str_replace(API_DIR . '/', '', $file->getPath());
        
        // Check namespace declaration
        if (preg_match('/namespace\s+StoriesAPI\\\\([^;]+);/', $content, $matches)) {
            $declaredNamespace = $matches[1];
            $parts = explode('\\', $declaredNamespace);
            
            foreach ($parts as $part) {
                $lower = strtolower($part);
                if (isset($correctNamespaces[$lower]) && $correctNamespaces[$lower] !== $part) {
                    $issues[] = sprintf(
                        "Incorrect namespace capitalization in %s: Found '%s', should be '%s'",
                        $file->getPathname(),
                        $part,
                        $correctNamespaces[$lower]
                    );
                }
            }
        }
        
        // Check use statements
        if (preg_match_all('/use\s+StoriesAPI\\\\([^;]+);/', $content, $matches)) {
            foreach ($matches[1] as $usedNamespace) {
                $parts = explode('\\', $usedNamespace);
                
                foreach ($parts as $part) {
                    $lower = strtolower($part);
                    if (isset($correctNamespaces[$lower]) && $correctNamespaces[$lower] !== $part) {
                        $issues[] = sprintf(
                            "Incorrect namespace in use statement in %s: Found '%s', should be '%s'",
                            $file->getPathname(),
                            $part,
                            $correctNamespaces[$lower]
                        );
                    }
                }
            }
        }
        
        // Check class instantiations
        if (preg_match_all('/new\s+StoriesAPI\\\\([^(]+)/', $content, $matches)) {
            foreach ($matches[1] as $className) {
                $parts = explode('\\', $className);
                
                foreach ($parts as $part) {
                    $lower = strtolower($part);
                    if (isset($correctNamespaces[$lower]) && $correctNamespaces[$lower] !== $part) {
                        $issues[] = sprintf(
                            "Incorrect namespace in class instantiation in %s: Found '%s', should be '%s'",
                            $file->getPathname(),
                            $part,
                            $correctNamespaces[$lower]
                        );
                    }
                }
            }
        }
    }
}

if (empty($issues)) {
    log_message("✅ All namespace references are correct!", 'success');
} else {
    log_message("Found namespace issues:", 'error');
    foreach ($issues as $issue) {
        log_message($issue, 'error');
    }
    log_message("\nPlease fix these issues to ensure proper autoloading.", 'error');
}

?>
    </div>
</body>
</html>