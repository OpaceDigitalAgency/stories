<?php
/**
 * Fix Database Schema
 * 
 * This script fixes database schema issues by checking and adding missing columns
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

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

try {
    // Connect to database
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    output("Connected to database successfully", 'success');
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        // Create users table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'user',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        output("Created users table", 'success');
        
        // Create default admin user if no users exist
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO users (name, email, password, role) VALUES ('Site Admin', 'admin@example.com', '$password', 'admin')");
        output("Created default admin user (email: admin@example.com, password: admin123)", 'success');
    }
    
    // Check if auth_tokens table exists
    $stmt = $db->query("SHOW TABLES LIKE 'auth_tokens'");
    if ($stmt->rowCount() === 0) {
        // Create auth_tokens table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            UNIQUE KEY (token)
        )");
        output("Created auth_tokens table", 'success');
    }
    
    // Check if stories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'stories'");
    if ($stmt->rowCount() === 0) {
        // Create stories table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS stories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
        output("Created stories table", 'success');
    }
    
    // Check if author column exists in stories table
    $hasAuthorColumn = false;
    $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author'");
    if ($stmt->rowCount() > 0) {
        $hasAuthorColumn = true;
        output("author column exists in stories table", 'info');
    }
    
    // Check if author_id column exists in stories table
    $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
    if ($stmt->rowCount() === 0) {
        // Add author_id column if it doesn't exist
        $db->exec("ALTER TABLE stories ADD COLUMN author_id INT NULL AFTER title");
        output("Added author_id column to stories table", 'success');
        
        // Update author_id based on author name if possible
        if ($hasAuthorColumn) {
            try {
                $db->exec("UPDATE stories s 
                          JOIN authors a ON s.author = a.name 
                          SET s.author_id = a.id 
                          WHERE s.author_id IS NULL");
                output("Updated author_id values where possible", 'success');
            } catch (PDOException $e) {
                output("Could not update author_id values: " . $e->getMessage(), 'warning');
            }
        }
    } else {
        output("author_id column already exists in stories table", 'info');
    }
    
    // Check if tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    if ($stmt->rowCount() === 0) {
        // Create tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
        output("Created tags table", 'success');
    }
    
    // Check if description column exists in tags table
    $stmt = $db->query("SHOW COLUMNS FROM tags LIKE 'description'");
    if ($stmt->rowCount() === 0) {
        // Add description column if it doesn't exist
        $db->exec("ALTER TABLE tags ADD COLUMN description TEXT NULL AFTER slug");
        output("Added description column to tags table", 'success');
    } else {
        output("description column already exists in tags table", 'info');
    }
    
    // Check if authors table exists
    $stmt = $db->query("SHOW TABLES LIKE 'authors'");
    if ($stmt->rowCount() === 0) {
        // Create authors table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            bio TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
        output("Created authors table", 'success');
    }
    
    // Check if blog_posts table exists
    $stmt = $db->query("SHOW TABLES LIKE 'blog_posts'");
    if ($stmt->rowCount() === 0) {
        // Create blog_posts table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author_id INT NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT NULL,
            status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
        output("Created blog_posts table", 'success');
    }
    
    // Check if story_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
    if ($stmt->rowCount() === 0) {
        // Create story_tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS story_tags (
            story_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (story_id, tag_id)
        )");
        output("Created story_tags table", 'success');
    }
    
    // Check if post_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
    if ($stmt->rowCount() === 0) {
        // Create post_tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (post_id, tag_id)
        )");
        output("Created post_tags table", 'success');
    }
    
    output("Database schema fixed successfully", 'success');
    
} catch (PDOException $e) {
    output("Database error: " . $e->getMessage(), 'error');
    exit(1);
}