<?php
/**
 * Test Simple Authentication System
 * 
 * This script tests the simple authentication system.
 */

// Include the simple auth file
require_once __DIR__ . '/simple_auth.php';

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

echo "Simple Auth Test\n";
echo "================\n\n";

// Initialize the database connection
if (SimpleAuth::initDB($config['db'])) {
    echo "Database connection successful.\n";
    
    // Test login with valid credentials
    echo "\nTesting login with valid credentials...\n";
    
    // Get a valid user from the database
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
        $config['db']['user'],
        $config['db']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $stmt = $db->query("SELECT email FROM users WHERE active = 1 LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found test user: {$user['email']}\n";
        echo "Please enter the password for this user: ";
        $password = trim(fgets(STDIN));
        
        $result = SimpleAuth::login($user['email'], $password);
        
        if ($result) {
            echo "Login successful!\n";
            echo "User data: " . print_r($result, true) . "\n";
            echo "Token: " . $_SESSION['auth_token'] . "\n";
            
            // Test token validation
            echo "\nTesting token validation...\n";
            $token = $_SESSION['auth_token'];
            $validationResult = SimpleAuth::validateSimpleToken($token);
            
            if ($validationResult) {
                echo "Token validation successful!\n";
                echo "Validated user data: " . print_r($validationResult, true) . "\n";
            } else {
                echo "Token validation failed.\n";
            }
            
            // Test logout
            echo "\nTesting logout...\n";
            SimpleAuth::logout();
            
            if (!isset($_SESSION['auth_user']) && !isset($_SESSION['auth_token'])) {
                echo "Logout successful!\n";
            } else {
                echo "Logout failed.\n";
            }
        } else {
            echo "Login failed. Invalid password.\n";
        }
    } else {
        echo "No active users found in the database.\n";
    }
} else {
    echo "Failed to connect to database.\n";
}

echo "\nTest complete.\n";