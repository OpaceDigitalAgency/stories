<?php
/**
 * Test Admin Authentication Integration
 * 
 * This script tests the integration of the simple authentication system with the admin interface.
 */

// Include the simple auth file
require_once __DIR__ . '/simple_auth.php';

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

echo "Admin Authentication Integration Test\n";
echo "===================================\n\n";

// Initialize the database connection
if (SimpleAuth::initDB($config['db'])) {
    echo "Database connection successful.\n";
    
    // Test login with admin credentials
    $email = "admin@example.com"; // Replace with an actual admin email
    $password = "admin123"; // Replace with the actual admin password
    
    echo "\nTesting login with admin credentials...\n";
    echo "Email: $email\n";
    echo "Password: [hidden]\n";
    
    $result = SimpleAuth::login($email, $password);
    
    if ($result) {
        echo "Login successful!\n";
        echo "User data: " . print_r($result, true) . "\n";
        echo "Token: " . $_SESSION['auth_token'] . "\n";
        
        // Test accessing a protected admin page
        echo "\nTesting access to protected admin page...\n";
        
        // Simulate a request to a protected admin page
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_SESSION['auth_token'];
        
        // Check if the user is authenticated
        $user = SimpleAuth::check();
        
        if ($user) {
            echo "Authentication check successful!\n";
            echo "User has access to admin pages.\n";
            
            // Test database write operation
            echo "\nTesting database write operation...\n";
            
            try {
                // Get PDO connection from SimpleAuth
                $db = new PDO(
                    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
                    $config['db']['user'],
                    $config['db']['password'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Create a test record
                $testTable = "auth_test";
                
                // Create test table if it doesn't exist
                $db->exec("CREATE TABLE IF NOT EXISTS $testTable (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    test_data VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                
                // Insert a test record
                $stmt = $db->prepare("INSERT INTO $testTable (test_data) VALUES (?)");
                $testData = "Test data " . date('Y-m-d H:i:s');
                $stmt->execute([$testData]);
                
                $lastId = $db->lastInsertId();
                
                if ($lastId) {
                    echo "Database write operation successful!\n";
                    echo "Inserted test record with ID: $lastId\n";
                    
                    // Clean up test data
                    $db->exec("DELETE FROM $testTable WHERE id = $lastId");
                    echo "Test record cleaned up.\n";
                } else {
                    echo "Database write operation failed.\n";
                }
            } catch (PDOException $e) {
                echo "Database error: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Authentication check failed.\n";
            echo "User does not have access to admin pages.\n";
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
        echo "Login failed. Invalid credentials.\n";
    }
} else {
    echo "Failed to connect to database.\n";
}

echo "\nTest complete.\n";