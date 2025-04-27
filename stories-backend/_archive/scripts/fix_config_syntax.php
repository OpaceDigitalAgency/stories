<?php
/**
 * Fix Config Syntax
 * 
 * This script fixes the syntax error in config.php:
 * "Parse error: syntax error, unexpected token "<", expecting end of file"
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Config Syntax</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$configPath = $apiPath . '/config';

// Check if config directory exists
if (!is_dir($configPath)) {
    echo "<p style='color:red'>❌ Config directory not found!</p>";
    exit;
}

echo "<p>Using config directory: $configPath</p>";

// Find the config file
$configFile = $configPath . '/config.php';
if (!file_exists($configFile)) {
    echo "<p style='color:red'>❌ Config file not found at: $configFile</p>";
    exit;
}

echo "<p>Found config file at: $configFile</p>";

// Create a backup
$backupFile = $configFile . '.bak.' . date('YmdHis');
if (copy($configFile, $backupFile)) {
    echo "<p>Created backup at: $backupFile</p>";
}

// Create the fixed config file
$configContent = <<<'EOD'
<?php
/**
 * Configuration file for the Stories API
 * 
 * This file contains all the configuration settings for the API,
 * including database connection details, API settings, and security settings.
 */

// Only define ENVIRONMENT if not already defined
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

// Database configuration
$config['db'] = [
    'host'     => 'localhost',      // Database host
    'name'     => 'stories_db',     // Database name
    'user'     => 'stories_user',   // Database username
    'password' => '$tw1cac3*sOt',   // Database password
    'charset'  => 'utf8mb4',        // Character set
    'port'     => 3306             // Database port
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
    'jwt_secret'   => 'a8f5e167d9f8b3c2e7b6d4a1c9e8d7f6', // JWT secret key
    'token_expiry' => 86400,        // Token expiry time in seconds (24 hours)
    'cors' => [
        'allowed_origins' => [
            'https://storiesfromtheweb.netlify.app',
            'https://api.storiesfromtheweb.org',
            'http://localhost:3000',
            'http://localhost:4321',
            'http://localhost:8000'
        ],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'expose_headers'  => ['X-Total-Count', 'X-Pagination-Total-Pages'],
        'max_age'        => 86400
    ]
];

// Media configuration
$config['media'] = [
    'upload_dir'   => __DIR__ . '/../../../uploads/',
    'max_file_size'=> 5242880,      // 5MB
    'allowed_types'=> ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'base_url'     => '/uploads/'
];

return $config;
EOD;

if (file_put_contents($configFile, $configContent)) {
    echo "<p style='color:green'>✅ Updated config file successfully!</p>";
    
    echo "<h2>Config File Updates</h2>";
    echo "<ul>";
    echo "<li>Fixed syntax error with heredoc string</li>";
    echo "<li>Added proper PHP opening tag</li>";
    echo "<li>Added check for ENVIRONMENT constant</li>";
    echo "<li>Properly formatted array syntax</li>";
    echo "<li>Added proper return statement</li>";
    echo "</ul>";
    
    echo "<h2>Next Steps</h2>";
    echo "<p>The config file has been fixed. Test the endpoints:</p>";
    echo "<ul>";
    echo "<li><a href='/api/v1/stories'>/api/v1/stories</a></li>";
    echo "<li><a href='/api/v1/authors'>/api/v1/authors</a></li>";
    echo "<li><a href='/api/v1/games'>/api/v1/games</a></li>";
    echo "<li><a href='/api/v1/directory-items'>/api/v1/directory-items</a></li>";
    echo "<li><a href='/api/v1/ai-tools'>/api/v1/ai-tools</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ Failed to update config file.</p>";
}