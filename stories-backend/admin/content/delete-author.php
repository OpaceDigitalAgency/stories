<?php
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
    header("Location: authors.php");
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

    // Verify author exists
    $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception("Author not found");
    }

    // Check if author has any stories
    $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Cannot delete author with existing stories");
    }

    // Check if author has any blog posts
    $stmt = $db->prepare("SELECT COUNT(*) FROM blog_posts WHERE author_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Cannot delete author with existing blog posts");
    }

    // Delete author
    $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
    $stmt->execute([$id]);

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = "Author deleted successfully";

    header("Location: authors.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Delete author error: " . $e->getMessage());
    
    // Store error in session and redirect back
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    header("Location: authors.php");
    exit;
}