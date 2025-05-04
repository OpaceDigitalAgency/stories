<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'Fix Blog Posts';
$currentPage = 'fix-blog-posts';

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

    // Add is_published column if it doesn't exist
    $stmt = $db->query("DESCRIBE blog_posts");
    $columns = [];
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    if (!in_array('is_published', $columns)) {
        $db->exec("ALTER TABLE blog_posts ADD COLUMN is_published TINYINT(1) DEFAULT 1 AFTER content");
        echo "Added is_published column to blog_posts table\n";
    }

    // Convert status to is_published if status column exists
    if (in_array('status', $columns)) {
        $db->exec("UPDATE blog_posts SET is_published = CASE WHEN status = 'published' THEN 1 ELSE 0 END");
        $db->exec("ALTER TABLE blog_posts DROP COLUMN status");
        echo "Converted status to is_published\n";
    }

    echo "\nBlog posts table has been updated successfully\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Include footer
require_once '../includes/footer.php';
