<?php
// Database connection settings
$host = 'localhost';
$dbname = 'stories_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

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
