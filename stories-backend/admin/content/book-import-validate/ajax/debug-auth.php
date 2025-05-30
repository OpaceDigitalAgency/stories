<?php
/**
 * Debug authentication path
 */

// Set JSON header first
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo json_encode([
    'step' => 'basic_php',
    'success' => true, 
    'message' => 'Basic PHP working',
    'current_dir' => __DIR__,
    'auth_path_check' => file_exists('../../../includes/auth-check.php') ? 'exists' : 'missing',
    'db_path_check' => file_exists('../../../includes/db-connect.php') ? 'exists' : 'missing'
]);
exit;
