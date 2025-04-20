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
    header("Location: stories.php");
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

    // Delete story tags first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id = ?");
    $stmt->execute([$id]);

    // Delete story
    $stmt = $db->prepare("DELETE FROM stories WHERE id = ?");
    $stmt->execute([$id]);

    // Commit transaction
    $db->commit();

    // Redirect back to stories list
    header("Location: stories.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Delete story error: " . $e->getMessage());
    
    // Store error in session and redirect back
    session_start();
    $_SESSION['error'] = "Failed to delete story. Please try again.";
    
    header("Location: stories.php");
    exit;
}