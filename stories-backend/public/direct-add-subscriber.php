<?php
/**
 * Direct Add Subscriber Script
 * This script directly adds a subscriber to the database without going through the API
 */

// Set headers for plain text output
header('Content-Type: text/plain');

echo "Direct Add Subscriber Test Script\n";
echo "================================\n\n";

// Test data
$email = 'direct_test_' . time() . '@example.com';
$feature = 'premium stories';

echo "Attempting to add subscriber with:\n";
echo "Email: $email\n";
echo "Feature: $feature\n\n";

try {
    // Connect to database
    echo "Connecting to database...\n";
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

    // Check if subscribers table exists
    echo "Checking if subscribers table exists...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'subscribers'");
    if ($stmt->rowCount() === 0) {
        echo "Subscribers table doesn't exist. Creating it...\n";
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
        echo "Subscribers table already exists.\n\n";
    }

    // Insert subscriber
    echo "Inserting subscriber...\n";
    $stmt = $db->prepare("INSERT INTO subscribers (email, feature, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
    $stmt->execute([$email, $feature]);
    $newId = $db->lastInsertId();
    echo "Subscriber added successfully with ID: $newId\n\n";

    // Verify subscriber was added
    echo "Verifying subscriber was added...\n";
    $stmt = $db->prepare("SELECT * FROM subscribers WHERE id = ?");
    $stmt->execute([$newId]);
    $subscriber = $stmt->fetch();
    
    if ($subscriber) {
        echo "Verification successful! Subscriber details:\n";
        echo "ID: {$subscriber['id']}\n";
        echo "Email: {$subscriber['email']}\n";
        echo "Feature: {$subscriber['feature']}\n";
        echo "Created at: {$subscriber['created_at']}\n";
    } else {
        echo "Verification failed! Subscriber not found in database.\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nTest completed at " . date('Y-m-d H:i:s') . "\n";
