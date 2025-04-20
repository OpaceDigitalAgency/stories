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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    // Get form data
    $id = $_POST['id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $author_id = $_POST['author_id'] ?? '';
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $tags = $_POST['tags'] ?? [];

    // Validate required fields
    if (empty($title) || empty($author_id) || empty($content)) {
        throw new Exception("Please fill in all required fields");
    }

    // Verify author exists
    $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
    $stmt->execute([$author_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Selected author does not exist");
    }

    // Verify tags exist
    if (!empty($tags)) {
        $placeholders = str_repeat('?,', count($tags) - 1) . '?';
        $stmt = $db->prepare("SELECT COUNT(*) FROM tags WHERE id IN ($placeholders)");
        $stmt->execute($tags);
        if ($stmt->fetchColumn() != count($tags)) {
            throw new Exception("One or more selected tags do not exist");
        }
    }

    // Validate status
    if (!in_array($status, ['draft', 'published'])) {
        throw new Exception("Invalid status value");
    }

    if ($id) {
        // Verify post exists
        $stmt = $db->prepare("SELECT id FROM blog_posts WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("Blog post not found");
        }

        // Update existing post
        $stmt = $db->prepare("UPDATE blog_posts SET title = ?, author_id = ?, content = ?, excerpt = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $author_id, $content, $excerpt, $status, $id]);
        
        // Delete existing tags
        $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
        $stmt->execute([$id]);

        $message = "Blog post updated successfully";
    } else {
        // Create new post
        $stmt = $db->prepare("INSERT INTO blog_posts (title, author_id, content, excerpt, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$title, $author_id, $content, $excerpt, $status]);
        $id = $db->lastInsertId();

        $message = "Blog post created successfully";
    }

    // Add tags
    if (!empty($tags)) {
        $values = array_fill(0, count($tags), "($id, ?)");
        $sql = "INSERT INTO post_tags (post_id, tag_id) VALUES " . implode(', ', $values);
        $stmt = $db->prepare($sql);
        
        $i = 1;
        foreach ($tags as $tag_id) {
            $stmt->bindValue($i++, $tag_id);
        }
        $stmt->execute();
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = $message;
    
    header("Location: blog-posts.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save blog post error: " . $e->getMessage());
    
    // Store error in session and redirect back to form
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = $id ? "post-form.php?id=$id" : "post-form.php";
    header("Location: $redirect");
    exit;
}