<?php

// Include auth check
require_once '../includes/auth-check.php';

// Page variables
$pageTitle = 'Get Author Story Count';
$currentPage = 'get-author-story-count';

// Check if author ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid author ID']);
    exit;
}

$authorId = (int)$_GET['id'];

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

    // Get story count
    $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
    $stmt->execute([$authorId]);
    $storyCount = $stmt->fetchColumn();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['count' => $storyCount]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}


// Include footer
require_once '../includes/footer.php';
