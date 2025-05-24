<?php
// Get database settings from environment variables or use defaults
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'stories_db';
$username = getenv('DB_USER') ?: 'stories_user';
$password = getenv('DB_PASS') ?: 'stories_password';
$charset = 'utf8mb4';

// Log connection attempt
error_log("Attempting database connection to $host as $username");

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Create PDO instance
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // If connection fails, throw exception
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
