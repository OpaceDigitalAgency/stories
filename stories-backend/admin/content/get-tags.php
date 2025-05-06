<?php

// Include auth check
require_once '../includes/auth-check.php';

// Page variables
$pageTitle = 'Get Tags';
$currentPage = 'get-tags';

/**
 * Get Tags API
 *
 * Returns a JSON array of all tags for use in the bulk actions dropdown.
 */

// Initialize response
$tags = [];

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

    // Check if tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    if ($stmt->rowCount() > 0) {
        // Get all tags
        $stmt = $db->query("SELECT id, name FROM tags ORDER BY name ASC");
        $tags = $stmt->fetchAll();
    } else {
        // Return some default tags if the table doesn't exist
        $tags = [
            ['id' => 1, 'name' => 'Fiction'],
            ['id' => 2, 'name' => 'Non-Fiction'],
            ['id' => 3, 'name' => 'Adventure'],
            ['id' => 4, 'name' => 'Fantasy'],
            ['id' => 5, 'name' => 'Science Fiction']
        ];
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Return tags as JSON
header('Content-Type: application/json');
echo json_encode($tags);


// Include footer
require_once '../includes/footer.php';
