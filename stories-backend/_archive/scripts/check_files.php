<?php
/**
 * Check if files exist on the server
 */

// List of files to check
$files = [
    'simple_auth.php',
    'setup_simple_auth.php',
    'test_simple_auth.php',
    'test_admin_auth.php',
    'SIMPLE_AUTH_GUIDE.md',
    'DEPLOYMENT_GUIDE.md',
    'api/v1/Middleware/SimpleAuthMiddleware.php',
    'api/v1/Endpoints/SimpleAuthController.php',
    'api/v1/routes.php'
];

echo "File Check Results:\n";
echo "===================\n\n";

// Check each file
foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ " . $file . " exists\n";
        echo "   - Path: " . $fullPath . "\n";
        echo "   - Size: " . filesize($fullPath) . " bytes\n";
        echo "   - Permissions: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
        echo "   - Last modified: " . date('Y-m-d H:i:s', filemtime($fullPath)) . "\n";
    } else {
        echo "❌ " . $file . " does not exist\n";
        echo "   - Expected path: " . $fullPath . "\n";
    }
    echo "\n";
}

// Check PHP version and extensions
echo "PHP Environment:\n";
echo "===============\n\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "\n\n";

// Check server information
echo "Server Information:\n";
echo "=================\n\n";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Current Directory: " . getcwd() . "\n";