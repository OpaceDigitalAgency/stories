<?php
/**
 * Set up auth_tokens table for SimpleAuth
 */

require_once 'simple_auth.php';

// Database configuration
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
    // Create auth_tokens table
    if (SimpleAuth::setupTokensTable()) {
        echo "Auth tokens table created successfully\n";
    } else {
        echo "Failed to create auth tokens table\n";
    }
} else {
    echo "Failed to initialize database connection\n";
}