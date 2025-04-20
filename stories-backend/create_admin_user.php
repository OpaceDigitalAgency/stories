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
$db_name = 'stories_fromtheweb';
$db_user = 'stories_fromtheweb';
$db_pass = 'stories_fromtheweb';

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
    
    // Admin user details
    $admin = [
        'name' => 'Admin',
        'email' => 'admin@storiesfromtheweb.org',
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'role' => 'admin',
        'active' => 1
    ];
    
    // Check if admin user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin['email']]);
    
    if ($stmt->fetch()) {
        // Update existing admin user
        $stmt = $db->prepare("UPDATE users SET password = ?, role = ?, active = ? WHERE email = ?");
        $stmt->execute([
            $admin['password'],
            $admin['role'],
            $admin['active'],
            $admin['email']
        ]);
        output("Admin user updated successfully", 'success');
    } else {
        // Create new admin user
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role, active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $admin['name'],
            $admin['email'],
            $admin['password'],
            $admin['role'],
            $admin['active']
        ]);
        output("Admin user created successfully", 'success');
    }
    
    output("\nAdmin login details:", 'info');
    output("Email: admin@storiesfromtheweb.org", 'info');
    output("Password: admin123", 'info');
    
} catch (PDOException $e) {
    output("Database error: " . $e->getMessage(), 'error');
    exit(1);
}