<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// This is a processing script, no UI needed

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

    // Get form data
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate required fields
    if (empty($name) || empty($slug)) {
        throw new Exception("Please fill in all required fields");
    }

    // Validate slug format
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        throw new Exception("Slug can only contain lowercase letters, numbers, and hyphens");
    }

    // Check if slug is already in use by another tag
    $stmt = $db->prepare("SELECT id FROM tags WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $id ?? 0]);
    if ($stmt->fetch()) {
        throw new Exception("Slug is already in use by another tag");
    }

    // Check if description column exists in tags table
    $hasDescriptionColumn = true;
    try {
        $db->query("SELECT description FROM tags LIMIT 1");
    } catch (PDOException $e) {
        $hasDescriptionColumn = false;
    }

    if ($id) {
        // Verify tag exists
        $stmt = $db->prepare("SELECT id FROM tags WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("Tag not found");
        }

        // Update existing tag
        if ($hasDescriptionColumn) {
            $stmt = $db->prepare("UPDATE tags SET name = ?, slug = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $id]);
        } else {
            $stmt = $db->prepare("UPDATE tags SET name = ?, slug = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $slug, $id]);
        }

        $message = "Tag updated successfully";
    } else {
        // Create new tag
        if ($hasDescriptionColumn) {
            $stmt = $db->prepare("INSERT INTO tags (name, slug, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$name, $slug, $description]);
        } else {
            $stmt = $db->prepare("INSERT INTO tags (name, slug, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$name, $slug]);
        }

        $message = "Tag created successfully";
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    $_SESSION['success'] = $message;

    header("Location: tags.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save tag error: " . $e->getMessage());

    // Store error in session and redirect back to form
    $_SESSION['error'] = $e->getMessage();

    $redirect = $id ? "tag-form.php?id=$id" : "tag-form.php";
    header("Location: $redirect");
    exit;
}

// No footer needed for processing script
