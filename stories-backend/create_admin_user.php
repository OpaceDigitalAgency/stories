<?php
/**
 * Create Admin User
 * This script creates an admin user in the database
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to output messages
function output($message, $type = 'info') {
    $colors = [
        'info' => "\033[0m",
        'success' => "\033[32m",
        'error' => "\033[31m",
        'warning' => "\033[33m"
    ];
    
    $reset = "\033[0m";
    echo $colors[$type] . $message . $reset . "\n";
}

// Database configuration
$db_host = 'localhost';
$db_name = 'stories_db';     // Changed to match config
$db_user = 'stories_user';   // Changed to match config
$db_pass = '$tw1cac3*sOt';   // Changed to match config

try {
    // Connect to database
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Current timestamp
    $now = date('Y-m-d H:i:s');
    
    // Admin user details - using existing credentials
    $admin = [
        'name' => 'Admin',
        'email' => 'admin@example.com',  // Using existing admin email
        'password' => password_hash('Pa55word!', PASSWORD_BCRYPT),  // Using existing admin password
        'role' => 'admin',
        'active' => 1,
        'created_at' => $now,
        'updated_at' => $now
    ];
    
    // Check if admin user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin['email']]);
    
    if ($stmt->fetch()) {
        // Update existing admin user
        $stmt = $db->prepare("UPDATE users SET password = ?, role = ?, active = ?, updated_at = ? WHERE email = ?");
        $stmt->execute([
            $admin['password'],
            $admin['role'],
            $admin['active'],
            $admin['updated_at'],
            $admin['email']
        ]);
        output("Admin user updated successfully", 'success');
    } else {
        // Create new admin user
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $admin['name'],
            $admin['email'],
            $admin['password'],
            $admin['role'],
            $admin['active'],
            $admin['created_at'],
            $admin['updated_at']
        ]);
        output("Admin user created successfully", 'success');
    }
    
    output("\nAdmin login details:", 'info');
    output("Email: admin@example.com", 'info');
    output("Password: Pa55word!", 'info');
    
} catch (PDOException $e) {
    output("Database error: " . $e->getMessage(), 'error');
    exit(1);
}