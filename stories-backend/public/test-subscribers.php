<?php
/**
 * Test Script for Subscribers
 * This script tests the database connection and adds a test subscriber
 */

// Set headers for plain text output
header('Content-Type: text/plain; charset=utf-8');

echo "Subscriber Database Test Script\n";
echo "==============================\n\n";

// Connect to database
try {
    echo "Attempting to connect to database...\n";
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "Database connection successful!\n\n";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Check if subscribers table exists
try {
    echo "Checking if subscribers table exists...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'subscribers'");
    if ($stmt->rowCount() === 0) {
        echo "Subscribers table does not exist. Creating it now...\n";
        $db->exec("CREATE TABLE subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255),
            feature VARCHAR(100) NOT NULL,
            message TEXT,
            is_contacted TINYINT(1) DEFAULT 0,
            admin_notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "Subscribers table created successfully!\n\n";
    } else {
        echo "Subscribers table exists.\n\n";
    }
} catch (PDOException $e) {
    echo "Error checking/creating subscribers table: " . $e->getMessage() . "\n";
    exit;
}

// Check table structure
try {
    echo "Checking subscribers table structure...\n";
    $stmt = $db->query("DESCRIBE subscribers");
    $columns = $stmt->fetchAll();
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    echo "\n";
} catch (PDOException $e) {
    echo "Error checking table structure: " . $e->getMessage() . "\n";
    exit;
}

// Add a test subscriber
try {
    echo "Adding a test subscriber...\n";
    $email = "test_" . time() . "@example.com";
    $feature = "premium stories";
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo "Email already exists. Skipping insertion.\n";
    } else {
        $stmt = $db->prepare("INSERT INTO subscribers (email, feature, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$email, $feature]);
        echo "Test subscriber added with email: " . $email . "\n\n";
    }
} catch (PDOException $e) {
    echo "Error adding test subscriber: " . $e->getMessage() . "\n";
    exit;
}

// List all subscribers
try {
    echo "Listing all subscribers...\n";
    $stmt = $db->query("SELECT * FROM subscribers ORDER BY created_at DESC");
    $subscribers = $stmt->fetchAll();
    
    if (count($subscribers) === 0) {
        echo "No subscribers found in the database.\n";
    } else {
        echo "Found " . count($subscribers) . " subscribers:\n";
        foreach ($subscribers as $subscriber) {
            echo "- ID: " . $subscriber['id'] . ", Email: " . $subscriber['email'] . ", Feature: " . $subscriber['feature'] . ", Created: " . $subscriber['created_at'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Error listing subscribers: " . $e->getMessage() . "\n";
    exit;
}

echo "\nTest completed successfully!\n";
