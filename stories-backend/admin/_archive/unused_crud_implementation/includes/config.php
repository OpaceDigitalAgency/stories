<?php
/**
 * Admin Configuration File
 * 
 * This file contains configuration settings for the admin interface.
 */

// Database configuration
$config = [
    'db' => [
        'host'     => 'localhost',      // Database host
        'name'     => 'stories_db',     // Database name
        'user'     => 'stories_user',   // Database username
        'password' => '$tw1cac3*sOt',   // Database password
        'charset'  => 'utf8mb4',        // Character set
        'port'     => 3306             // Database port
    ],
    'api' => [
        'base_url' => 'https://api.storiesfromtheweb.org/api/v1',
        'timeout'  => 30,
        'debug'    => true
    ],
    'auth' => [
        'session_lifetime' => 86400, // 24 hours
        'cookie_secure'    => true,
        'cookie_httponly'  => true,
        'cookie_samesite'  => 'Strict'
    ],
    'upload' => [
        'max_size'      => 5242880, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'upload_path'   => __DIR__ . '/../uploads/',
        'public_path'   => '/uploads/'
    ]
];

// Error reporting
if ($config['api']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration - only set if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', $config['auth']['session_lifetime']);
    ini_set('session.gc_maxlifetime', $config['auth']['session_lifetime']);
    ini_set('session.cookie_secure', $config['auth']['cookie_secure']);
    ini_set('session.cookie_httponly', $config['auth']['cookie_httponly']);
    ini_set('session.cookie_samesite', $config['auth']['cookie_samesite']);
}

return $config;