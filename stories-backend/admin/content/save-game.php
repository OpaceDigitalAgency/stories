<?php

// Include header
require_once '../includes/header.php';


// Page variables
$pageTitle = 'Save Game';
$currentPage = 'save-game';

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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: games.php");
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

    // Get form data
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $published_at = $_POST['published_at'] ?? null;

    // Validate required fields
    if (empty($title)) {
        throw new Exception("Title is required");
    }

    // Generate slug if not provided
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^\w\s-]+/', '', $title));
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
    }

    // Format published_at
    if (!empty($published_at)) {
        $date = new DateTime($published_at);
        $published_at = $date->format('Y-m-d H:i:s');
    } else {
        $published_at = null;
    }

    if ($id) {
        // Update existing game
        $stmt = $db->prepare("UPDATE games SET 
            title = ?, 
            description = ?, 
            slug = ?, 
            featured = ?, 
            is_published = ?, 
            published_at = ?, 
            updated_at = NOW() 
            WHERE id = ?");
        $stmt->execute([
            $title, 
            $description, 
            $slug, 
            $featured, 
            $is_published, 
            $published_at, 
            $id
        ]);
        $success = "Game updated successfully";
    } else {
        // Create new game
        $stmt = $db->prepare("INSERT INTO games (
            title, 
            description, 
            slug, 
            featured, 
            is_published, 
            published_at, 
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $title, 
            $description, 
            $slug, 
            $featured, 
            $is_published, 
            $published_at
        ]);
        $success = "Game created successfully";
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = $success;
    
    header("Location: games.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save game error: " . $e->getMessage());
    
    // Store error in session and redirect back to form
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = $id ? "game-form.php?id=$id" : "game-form.php";
    header("Location: $redirect");
    exit;
}

// Include footer
require_once '../includes/footer.php';
