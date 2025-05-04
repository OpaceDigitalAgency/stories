<?php

// Include header
require_once '../includes/header.php';


// Page variables
$pageTitle = 'Delete Tag';
$currentPage = 'delete-tag';

require_once '../../simple_auth.php';

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
    header("Location: ../login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header("Location: tags.php");
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

    $id = $_POST['id'];

    // Verify tag exists
    $stmt = $db->prepare("SELECT id FROM tags WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception("Tag not found");
    }

    // Check if tag is used in stories
    $stmt = $db->prepare("SELECT COUNT(*) FROM story_tags WHERE tag_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Cannot delete tag that is used in stories");
    }

    // Check if tag is used in blog posts
    $stmt = $db->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Cannot delete tag that is used in blog posts");
    }

    // Delete tag
    $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([$id]);

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = "Tag deleted successfully";

    header("Location: tags.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Delete tag error: " . $e->getMessage());
    
    // Store error in session and redirect back
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    header("Location: tags.php");
    exit;
}

// Include footer
require_once '../includes/footer.php';
