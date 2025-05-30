<?php
/**
 * Debug test for AJAX endpoint
 */

// Set JSON header first
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo json_encode([
    'success' => true, 
    'message' => 'Basic PHP test working',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION
]);
exit;
