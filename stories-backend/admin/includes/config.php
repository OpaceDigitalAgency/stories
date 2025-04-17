<?php
/**
 * Admin UI Configuration
 *
 * This file contains configuration settings for the admin UI.
 *
 * @package Stories Admin
 * @version 1.0.0
 */

// Prevent any output before headers are sent
if (ob_get_level() == 0) ob_start();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1);
ini_set('error_log', '/home/stories/api.storiesfromtheweb.org/logs/api-error.log');

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
    // Use absolute URL for admin interface
    if (ENVIRONMENT === 'development') {
        // For local development
        define('ADMIN_URL', '/admin');
    } else {
        // For production - use the correct domain, not the API domain
        define('ADMIN_URL', '/admin');
    }
}

// Define the admin assets URL (always relative to ensure assets load from the same domain)
if (!defined('ADMIN_ASSETS_URL')) {
    define('ADMIN_ASSETS_URL', '/admin');
}
if (!defined('API_URL')) {
    // Use absolute URL for API server
    if (ENVIRONMENT === 'development') {
        // For local development
        define('API_URL', 'http://localhost/stories-backend/api/v1');
    } else {
        // For production
        define('API_URL', 'https://api.storiesfromtheweb.org/api/v1');
    }
}

// Database configuration
$config['db'] = [
    'host'     => 'localhost',      // Database host
    'name'     => 'stories_db',     // Database name
    'user'     => 'stories_user',   // Database username
    'password' => '$tw1cac3*sOt',   // Database password - found in direct_login.php
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
    'base_url'     => '/admin/uploads/'  // Use absolute path for base_url
];

// Ensure uploads directory exists with proper permissions
$uploadsDir = BASE_PATH . '/uploads/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Session configuration
$config['session'] = [
    'name'         => 'stories_admin_session',
    'lifetime'     => 86400, // 24 hours
    'path'         => '/',
    'domain'       => '',
    'secure'       => true, // Enabled for production with HTTPS
    'httponly'     => true
];

// Make config available globally
$GLOBALS['config'] = $config;

return $config;