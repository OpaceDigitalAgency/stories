<?php
/**
 * Database Connection Include
 *
 * This file establishes a connection to the database.
 * It should be included in any file that needs database access.
 *
 * Usage:
 * include '../includes/db-connect.php';
 * // Now $db is available for use
 */

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Connect to database
try {
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
} catch (PDOException $e) {
    // Log the error
    error_log("Database connection error: " . $e->getMessage());

    // Set error message
    $error = "Database connection error. Please try again.";
}
