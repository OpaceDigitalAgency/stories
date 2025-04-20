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

    // Get form data
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email)) {
        throw new Exception("Please fill in all required fields");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check if email is already in use by another author
    $stmt = $db->prepare("SELECT id FROM authors WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id ?? 0]);
    if ($stmt->fetch()) {
        throw new Exception("Email is already in use by another author");
    }

    if ($id) {
        // Verify author exists
        $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("Author not found");
        }

        // Update existing author
        $stmt = $db->prepare("UPDATE authors SET name = ?, email = ?, bio = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $bio, $id]);

        $message = "Author updated successfully";
    } else {
        // Create new author
        $stmt = $db->prepare("INSERT INTO authors (name, email, bio, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$name, $email, $bio]);

        $message = "Author created successfully";
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = $message;
    
    header("Location: authors.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save author error: " . $e->getMessage());
    
    // Store error in session and redirect back to form
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = $id ? "author-form.php?id=$id" : "author-form.php";
    header("Location: $redirect");
    exit;
}