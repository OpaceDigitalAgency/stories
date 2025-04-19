<?php
/**
 * API JWT Secret Test Script
 * 
 * This script tests if the JWT secret key is properly set in the API Auth class and if token validation is working correctly.
 */

// Include required files
require_once __DIR__ . '/api/v1/config/config.php';
require_once __DIR__ . '/api/v1/Core/Auth.php';

// Initialize Auth with config
\StoriesAPI\Core\Auth::init($config['security']);

// Output JWT secret information
echo "API JWT Secret Test\n";
echo "=================\n\n";

// Test JWT secret
echo "JWT Secret in config: " . (isset($config['security']['jwt_secret']) ? substr($config['security']['jwt_secret'], 0, 5) . '...' : 'Not set') . "\n";
echo "JWT Secret length: " . (isset($config['security']['jwt_secret']) ? strlen($config['security']['jwt_secret']) : 0) . "\n\n";

// Generate a test token
$payload = [
    'user_id' => 1,
    'role' => 'admin',
    'test' => true
];

echo "Generating test token with payload: " . json_encode($payload) . "\n";
$token = \StoriesAPI\Core\Auth::generateToken($payload);
echo "Generated token: " . $token . "\n\n";

// Validate the token
echo "Validating token...\n";
$validationResult = \StoriesAPI\Core\Auth::validateToken($token);
echo "Validation result: " . ($validationResult ? "Valid" : "Invalid") . "\n";

if ($validationResult) {
    echo "Token is valid\n";
} else {
    echo "Token validation failed\n";
}

// Test with a modified token
echo "\nTesting with modified token...\n";
$parts = explode('.', $token);
$parts[1] = base64_encode(json_encode(['user_id' => 999, 'role' => 'hacker']));
$modifiedToken = implode('.', $parts);
echo "Modified token: " . $modifiedToken . "\n";

$modifiedResult = \StoriesAPI\Core\Auth::validateToken($modifiedToken);
echo "Modified token validation result: " . ($modifiedResult ? "Valid (SECURITY ISSUE!)" : "Invalid (Expected)") . "\n";

// Test cross-validation between admin and API Auth classes
echo "\nTesting cross-validation between admin and API Auth classes...\n";

// Include admin Auth class
require_once __DIR__ . '/admin/includes/config.php';
require_once __DIR__ . '/admin/includes/Auth.php';

// Initialize admin Auth with config
Auth::init($GLOBALS['config']['security']);

// Generate token with admin Auth class
$adminToken = Auth::generateToken($payload);
echo "Admin-generated token: " . $adminToken . "\n";

// Validate admin token with API Auth class
$apiValidationResult = \StoriesAPI\Core\Auth::validateToken($adminToken);
echo "API validation of admin token: " . ($apiValidationResult ? "Valid" : "Invalid") . "\n";

// Generate token with API Auth class
$apiToken = \StoriesAPI\Core\Auth::generateToken($payload);
echo "API-generated token: " . $apiToken . "\n";

// Validate API token with admin Auth class
$adminValidationResult = Auth::validateToken($apiToken);
echo "Admin validation of API token: " . ($adminValidationResult ? "Valid" : "Invalid") . "\n";

// Output debug information
echo "\nDebug Information\n";
echo "================\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Error Reporting: " . ini_get('error_reporting') . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Log Errors: " . ini_get('log_errors') . "\n";
echo "Error Log: " . ini_get('error_log') . "\n";