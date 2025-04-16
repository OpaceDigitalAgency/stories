<?php
/**
 * Admin UI Configuration
 * 
 * This file contains configuration settings for the admin UI.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

// Define the environment (development, testing, production)
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
}

// Set error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Define base paths
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', '/admin');
}
if (!defined('API_URL')) {
    // Use absolute URL for API server
    if (ENVIRONMENT === 'development') {
        // For local development
        define('API_URL', 'http://localhost:8000/api/v1');
    } else {
        // For production
        define('API_URL', 'https://api.storiesfromtheweb.org/api/v1');
    }
}

// Database configuration
$config['db'] = [
    'host'     => 'localhost',      // Database host
    'name'     => 'stories_db',     // Database name
    'user'     => 'stories_prod_user',   // Production database username
    'password' => 'Str0ng_Pr0d_P@ssw0rd!', // Production database password
    'charset'  => 'utf8mb4',        // Character set
    'port'     => 3306              // Database port
];

// Security configuration
$config['security'] = [
    'jwt_secret'   => 'a8f5e167d9f8b3c2e7b6d4a1c9e8d7f6', // Production JWT secret key
    'token_expiry' => 86400,                 // Token expiry time in seconds (24 hours)
];

// Media configuration
$config['media'] = [
    'upload_dir'   => BASE_PATH . '/uploads/',
    'max_file_size'=> 5242880, // 5MB
    'allowed_types'=> ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'base_url'     => ADMIN_URL . '/uploads/'
];

// Session configuration
$config['session'] = [
    'name'         => 'stories_admin_session',
    'lifetime'     => 86400, // 24 hours
    'path'         => '/',
    'domain'       => '',
    'secure'       => true, // Enabled for production with HTTPS
    'httponly'     => true
];

return $config;