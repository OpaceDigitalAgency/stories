<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'Fix Games';
$currentPage = 'fix-games';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

try {
    // Connect to database
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

    // Get existing columns
    $stmt = $db->query("DESCRIBE games");
    $columns = [];
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Add published_at column if it doesn't exist
    if (!in_array('published_at', $columns)) {
        $db->exec("ALTER TABLE games ADD COLUMN published_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER is_published");
        echo "Added published_at column to games table\n";
    }

    // Set published_at to current timestamp for existing records
    $db->exec("UPDATE games SET published_at = CURRENT_TIMESTAMP WHERE published_at IS NULL");
    echo "Updated existing records with published_at timestamp\n";

    echo "\nGames table has been updated successfully\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Include footer
include '../includes/footer.php';
