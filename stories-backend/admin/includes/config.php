<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'stories_db';     // Changed to match original config
$db_user = 'stories_user';   // Changed to match original config
$db_pass = '$tw1cac3*sOt';   // Changed to match original config

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die('Database connection failed: ' . $e->getMessage());
}

// Site configuration
define('SITE_URL', 'https://api.storiesfromtheweb.org');
define('ADMIN_EMAIL', 'admin@storiesfromtheweb.org');
define('SESSION_LIFETIME', 7200); // 2 hours

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => 'api.storiesfromtheweb.org',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}