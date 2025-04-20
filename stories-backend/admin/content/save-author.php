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
    $slug = trim($_POST['slug'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    // Validate required fields
    if (empty($name)) {
        throw new Exception("Please fill in the name field");
    }

    // Get all columns from authors table
    $columns = [];
    $stmt = $db->query("DESCRIBE authors");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Check if email column exists
    $hasEmailColumn = in_array('email', $columns);
    
    // Check if slug column exists
    $hasSlugColumn = in_array('slug', $columns);
    
    // Check if bio column exists
    $hasBioColumn = in_array('bio', $columns);

    // Generate slug from name if not provided and slug column exists
    if ($hasSlugColumn && empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
    }

    // Validate email format if email column exists
    if ($hasEmailColumn) {
        if (empty($email)) {
            throw new Exception("Please fill in the email field");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email is already in use by another author
        $stmt = $db->prepare("SELECT id FROM authors WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id ?? 0]);
        if ($stmt->fetch()) {
            throw new Exception("Email is already in use by another author");
        }
    }

    // Validate slug format if slug column exists
    if ($hasSlugColumn) {
        if (empty($slug)) {
            throw new Exception("Please fill in the slug field");
        }
        
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new Exception("Slug can only contain lowercase letters, numbers, and hyphens");
        }

        // Check if slug is already in use by another author
        $stmt = $db->prepare("SELECT id FROM authors WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id ?? 0]);
        if ($stmt->fetch()) {
            throw new Exception("Slug is already in use by another author");
        }
    }

    if ($id) {
        // Verify author exists
        $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception("Author not found");
        }

        // Update existing author
        $setClause = ["name = ?"];
        $params = [$name];
        
        if ($hasSlugColumn) {
            $setClause[] = "slug = ?";
            $params[] = $slug;
        }
        
        if ($hasEmailColumn) {
            $setClause[] = "email = ?";
            $params[] = $email;
        }
        
        if ($hasBioColumn) {
            $setClause[] = "bio = ?";
            $params[] = $bio;
        }
        
        $setClause[] = "updated_at = NOW()";
        $params[] = $id; // Add ID for WHERE clause
        
        $sql = "UPDATE authors SET " . implode(', ', $setClause) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $message = "Author updated successfully";
    } else {
        // Create new author
        $columns = ["name"];
        $placeholders = ["?"];
        $params = [$name];
        
        if ($hasSlugColumn) {
            $columns[] = "slug";
            $placeholders[] = "?";
            $params[] = $slug;
        }
        
        if ($hasEmailColumn) {
            $columns[] = "email";
            $placeholders[] = "?";
            $params[] = $email;
        }
        
        if ($hasBioColumn) {
            $columns[] = "bio";
            $placeholders[] = "?";
            $params[] = $bio;
        }
        
        $columns[] = "created_at";
        $columns[] = "updated_at";
        $placeholders[] = "NOW()";
        $placeholders[] = "NOW()";
        
        $sql = "INSERT INTO authors (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

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