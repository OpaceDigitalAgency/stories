<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to text/plain for easy reading
header('Content-Type: text/plain');

// Display basic path information
echo "Current script path: " . __FILE__ . "\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Server name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n\n";

// Check if the Core directory exists
$corePath = __DIR__ . '/v1/Core';
echo "Core directory path: " . $corePath . "\n";
echo "Core directory exists: " . (is_dir($corePath) ? 'Yes' : 'No') . "\n";

// Check if the Router.php file exists
$routerPath = $corePath . '/Router.php';
echo "Router.php path: " . $routerPath . "\n";
echo "Router.php exists: " . (file_exists($routerPath) ? 'Yes' : 'No') . "\n";

// Check if the Utils directory exists
$utilsPath = __DIR__ . '/v1/Utils';
echo "Utils directory path: " . $utilsPath . "\n";
echo "Utils directory exists: " . (is_dir($utilsPath) ? 'Yes' : 'No') . "\n";

// Check if the Response.php file exists
$responsePath = $utilsPath . '/Response.php';
echo "Response.php path: " . $responsePath . "\n";
echo "Response.php exists: " . (file_exists($responsePath) ? 'Yes' : 'No') . "\n";

// List all files in the v1 directory
echo "\nFiles in v1 directory:\n";
$v1Path = __DIR__ . '/v1';
if (is_dir($v1Path)) {
    $files = scandir($v1Path);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- " . $file . (is_dir($v1Path . '/' . $file) ? ' (directory)' : '') . "\n";
        }
    }
} else {
    echo "v1 directory does not exist\n";
}

// List all files in the Core directory
echo "\nFiles in Core directory:\n";
if (is_dir($corePath)) {
    $files = scandir($corePath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "- " . $file . (is_dir($corePath . '/' . $file) ? ' (directory)' : '') . "\n";
        }
    }
} else {
    echo "Core directory does not exist\n";
}
?>