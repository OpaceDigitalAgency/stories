<?php
/**
 * Setup Simple Authentication System
 * 
 * This script sets up the simple authentication system by creating the necessary database table.
 */

// Include the simple auth file
require_once __DIR__ . '/simple_auth.php';

// Include the database configuration
require_once __DIR__ . '/api/v1/config/config.php';

// Initialize the database connection
if (SimpleAuth::initDB($config['db'])) {
    echo "Database connection successful.\n";
    
    // Create the auth_tokens table
    if (SimpleAuth::setupTokensTable()) {
        echo "Auth tokens table created successfully.\n";
    } else {
        echo "Failed to create auth tokens table.\n";
    }
} else {
    echo "Failed to connect to database.\n";
}

echo "\nSetup complete. You can now use the SimpleAuth class for authentication.\n";