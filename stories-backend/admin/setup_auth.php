<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include simple auth
require_once '../simple_auth.php';

// Database configuration from auth-check.php
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
if (SimpleAuth::initDB($config)) {
    echo "Database connection successful<br>";
    
    // Create auth_tokens table
    if (SimpleAuth::setupTokensTable()) {
        echo "Auth tokens table created successfully<br>";
    } else {
        echo "Failed to create auth tokens table<br>";
    }
    
    // Create users table if it doesn't exist
    try {
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']};port={$config['port']}",
            $config['user'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'user',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($query);
        echo "Users table created successfully<br>";
        
        // Check if admin user exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = 'admin@storiesfromtheweb.org' LIMIT 1");
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Create default admin user
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Admin', 'admin@storiesfromtheweb.org', $password, 'admin']);
            echo "Default admin user created<br>";
            echo "Email: admin@storiesfromtheweb.org<br>";
            echo "Password: admin123<br>";
            echo "Please change this password after logging in!<br>";
        } else {
            echo "Admin user already exists<br>";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Failed to connect to database<br>";
}