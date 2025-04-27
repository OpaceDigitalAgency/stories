<?php
/**
 * JWT Secret Test Script
 * 
 * This script tests if the JWT secret key is properly set and if token validation is working correctly.
 */

// Include required files
require_once __DIR__ . '/admin/includes/config.php';
require_once __DIR__ . '/admin/includes/Auth.php';

// Initialize Auth with config
Auth::init($config['security']);

// Output JWT secret information
echo "JWT Secret Test\n";
echo "==============\n\n";

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
$token = Auth::generateToken($payload);
echo "Generated token: " . $token . "\n\n";

// Validate the token
echo "Validating token...\n";
$validationResult = Auth::validateToken($token);
echo "Validation result: " . ($validationResult ? "Valid" : "Invalid") . "\n";

if ($validationResult) {
    echo "Decoded payload: " . json_encode($validationResult) . "\n";
} else {
    echo "Token validation failed\n";
}

// Test with a modified token
echo "\nTesting with modified token...\n";
$parts = explode('.', $token);
$parts[1] = base64_encode(json_encode(['user_id' => 999, 'role' => 'hacker']));
$modifiedToken = implode('.', $parts);
echo "Modified token: " . $modifiedToken . "\n";

$modifiedResult = Auth::validateToken($modifiedToken);
echo "Modified token validation result: " . ($modifiedResult ? "Valid (SECURITY ISSUE!)" : "Invalid (Expected)") . "\n";

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