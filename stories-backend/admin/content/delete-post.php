<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'Delete Post';
$currentPage = 'delete-post';

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
    header("Location: blog-posts.php");
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

    // Verify post exists
    $stmt = $db->prepare("SELECT id FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception("Blog post not found");
    }

    // Delete post tags first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt->execute([$id]);

    // Delete post
    $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = "Blog post deleted successfully";

    header("Location: blog-posts.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Delete blog post error: " . $e->getMessage());
    
    // Store error in session and redirect back
    session_start();
    $_SESSION['error'] = "Failed to delete blog post. Please try again.";
    
    header("Location: blog-posts.php");
    exit;
}

// Include footer
require_once '../includes/footer.php';
