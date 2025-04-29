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

    // Delete story_tags for all stories by this author
    $stmt = $db->prepare("
        DELETE st FROM story_tags st
        INNER JOIN story_authors sa ON st.story_id = sa.story_id
        WHERE sa.author_id = ?
    ");
    $stmt->execute([$authorId]);

    // Delete story_authors entries
    $stmt = $db->prepare("DELETE FROM story_authors WHERE author_id = ?");
    $stmt->execute([$authorId]);

    // Delete stories by this author
    $stmt = $db->prepare("
        DELETE s FROM stories s
        INNER JOIN story_authors sa ON s.id = sa.story_id
        WHERE sa.author_id = ?
    ");
    $stmt->execute([$authorId]);

    // Finally, delete the author
    $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);

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