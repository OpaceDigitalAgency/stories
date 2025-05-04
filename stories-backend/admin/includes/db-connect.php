<?php

// Include header
include 'header.php';


// Page variables
$pageTitle = 'Db Connect';
$currentPage = 'db-connect';

/**
 * Database Connection Include
 *
 * This file establishes a connection to the database.
 * It should be included in any file that needs database access.
 *
 * Usage:
 * include '../includes/db-connect.php';
 * // Now $db is available for use
 */

// Set error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configurations to try
$dbConfigs = [
    [
        'host' => 'localhost',
        'name' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4',
        'port' => 3306
    ],
    [
        'host' => '127.0.0.1',
        'name' => 'stories_db',
        'user' => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset' => 'utf8mb4',
        'port' => 3306
    ]
];

// Connect to database
$db = null;
$connectionError = null;

foreach ($dbConfigs as $config) {
    try {
        error_log("Trying connection to {$config['host']}");
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        error_log("Connection successful to {$config['host']}");
        break;
    } catch (PDOException $e) {
        $connectionError = $e->getMessage();
        error_log("Connection failed to {$config['host']}: {$connectionError}");
    }
}

if (!$db) {
    // Log the error
    error_log("All database connection attempts failed. Last error: " . $connectionError);

    // Set error message
    $error = "Database connection error. Please try again.";
}


// Include footer
include 'footer.php';
