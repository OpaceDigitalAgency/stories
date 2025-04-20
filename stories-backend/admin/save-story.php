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
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Get form data
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $author_id = $_POST['author_id'] ?? '';
    $content = $_POST['content'] ?? '';
    $tags = $_POST['tags'] ?? [];

    // Validate required fields
    if (empty($title) || empty($author_id) || empty($content)) {
        throw new Exception("Please fill in all required fields");
    }

    if ($id) {
        // Update existing story
        $stmt = $db->prepare("UPDATE stories SET title = ?, author_id = ?, content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $author_id, $content, $id]);
        
        // Delete existing tags
        $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id = ?");
        $stmt->execute([$id]);
    } else {
        // Create new story
        $stmt = $db->prepare("INSERT INTO stories (title, author_id, content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$title, $author_id, $content]);
        $id = $db->lastInsertId();
    }

    // Add tags
    if (!empty($tags)) {
        $values = array_fill(0, count($tags), "($id, ?)");
        $sql = "INSERT INTO story_tags (story_id, tag_id) VALUES " . implode(', ', $values);
        $stmt = $db->prepare($sql);
        
        $i = 1;
        foreach ($tags as $tag_id) {
            $stmt->bindValue($i++, $tag_id);
        }
        $stmt->execute();
    }

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

    error_log("Save story error: " . $e->getMessage());
    
    // Store error in session and redirect back to form
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = $id ? "story-form.php?id=$id" : "story-form.php";
    header("Location: $redirect");
    exit;
}