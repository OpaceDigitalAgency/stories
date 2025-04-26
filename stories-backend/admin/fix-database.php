<?php
require_once '../simple_auth.php';

// Initialize SimpleAuth
SimpleAuth::initDB([
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
]);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header("Location: login.php");
    exit;
}

header('Content-Type: text/plain');

try {
    // Connect to database
    $db = new PDO(
        "mysql:host=localhost;dbname=stories_db;charset=utf8mb4",
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Tables to check
    $tables = ['stories', 'games', 'directory_items', 'ai_tools'];

    foreach ($tables as $table) {
        // Get existing columns
        $columns = [];
        $stmt = $db->query("DESCRIBE $table");
        while ($row = $stmt->fetch()) {
            $columns[] = $row['Field'];
        }

        // Add featured column if it doesn't exist
        if (!in_array('featured', $columns)) {
            $db->exec("ALTER TABLE $table ADD COLUMN featured TINYINT(1) DEFAULT 0 AFTER description");
            echo "Added featured column to $table table\n";
        }

        // Add is_published column if it doesn't exist
        if (!in_array('is_published', $columns)) {
            $db->exec("ALTER TABLE $table ADD COLUMN is_published TINYINT(1) DEFAULT 1 AFTER featured");
            echo "Added is_published column to $table table\n";
        }

        // Set is_published to 1 for existing records
        $db->exec("UPDATE $table SET is_published = 1 WHERE is_published IS NULL");
        echo "Updated existing records in $table table\n";
    }

    echo "\nAll content tables have been updated successfully\n";
    echo "\nYou can now return to the admin interface and try saving content again.";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}