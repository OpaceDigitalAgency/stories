<?php
/**
 * Configuration file for the Stories API
 * 
 * This file contains all the configuration settings for the API,
 * including database connection details, API settings, and security settings.
 * 
 * @package Stories API
 * @version 1.0.0
 */

// Define the environment (development, testing, production)
define('ENVIRONMENT', 'development');

// Set error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
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

// API configuration
$config['api'] = [
    'version'      => 'v1',
    'page_size'    => 25,           // Default page size for pagination
    'max_page_size'=> 100,          // Maximum allowed page size
    'cache_time'   => 3600,         // Cache time in seconds (1 hour)
    'rate_limit'   => 100           // Rate limit (requests per minute)
];

// Security configuration
$config['security'] = [
    'jwt_secret'   => 'your_jwt_secret_key', // JWT secret key (use environment variables in production)
    'token_expiry' => 86400,                 // Token expiry time in seconds (24 hours)
    'cors' => [
        'allowed_origins' => [
            'https://storiesfromtheweb.netlify.app', // Production Netlify site
            'http://localhost:3000',                 // Local development
            'http://localhost:4321'                  // Astro dev server
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'expose_headers'  => ['X-Total-Count', 'X-Pagination-Total-Pages'],
        'max_age'         => 86400 // 24 hours
    ]
];

// Media configuration
$config['media'] = [
    'upload_dir'   => __DIR__ . '/../../../uploads/',
    'max_file_size'=> 5242880, // 5MB
    'allowed_types'=> ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'base_url'     => '/uploads/'
];

return $config;