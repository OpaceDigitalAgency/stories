<?php

// Include auth check
require_once '../includes/auth-check.php';

// Page variables
$pageTitle = 'Get Authors';
$currentPage = 'get-authors';

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

    // Get excluded author ID
    $excludeId = isset($_GET['exclude']) ? (int)$_GET['exclude'] : 0;

    // Get all authors except the one being deleted
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id != ? ORDER BY name");
    $stmt->execute([$excludeId]);
    $authors = $stmt->fetchAll();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($authors);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

// Include footer
require_once '../includes/footer.php';
