<?php
require_once '../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid author ID']);
    exit;
}

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

    // Start transaction
    $db->beginTransaction();

    $authorId = $_POST['id'];

    // First, get all story IDs by this author
    $stmt = $db->prepare("SELECT story_id FROM story_authors WHERE author_id = ?");
    $stmt->execute([$authorId]);
    $storyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($storyIds)) {
        // Delete story tags first
        $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id IN ($placeholders)");
        $stmt->execute($storyIds);

        // Delete stories
        $stmt = $db->prepare("DELETE FROM stories WHERE id IN ($placeholders)");
        $stmt->execute($storyIds);

        // Delete story_authors entries
        $stmt = $db->prepare("DELETE FROM story_authors WHERE story_id IN ($placeholders)");
        $stmt->execute($storyIds);
    }

    // Finally, delete the author
    $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);

    // Log the deletion
    error_log("Successfully deleted author ID: $authorId with " . count($storyIds) . " associated stories");

    // Commit transaction
    $db->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Delete author error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}