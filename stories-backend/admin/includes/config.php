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
    define('ENVIRONMENT', 'development');
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
    define('API_URL', '/api/v1');
}

// Database configuration
$config['db'] = [
    'host'     => 'localhost',      // Database host
    'name'     => 'stories_db',     // Database name
    'user'     => 'stories_user',   // Database username
    'password' => 'your_secure_password', // Database password (use environment variables in production)
    'charset'  => 'utf8mb4',        // Character set
    'port'     => 3306              // Database port
];

// Security configuration
$config['security'] = [
    'jwt_secret'   => 'your_jwt_secret_key', // JWT secret key (use environment variables in production)
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
    'secure'       => false, // Set to true in production with HTTPS
    'httponly'     => true
];

return $config;