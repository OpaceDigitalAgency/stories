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
    header("Location: ai-tools.php");
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
    $category_id = $_POST['category_id'] ?? null;
    $tool_url = trim($_POST['tool_url'] ?? '');
    $pricing_type = $_POST['pricing_type'] ?? 'free';
    $price_info = trim($_POST['price_info'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $rating = $_POST['rating'] ?? 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $slug = trim($_POST['slug'] ?? '');
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
        // Update existing AI tool
        $stmt = $db->prepare("UPDATE ai_tools SET 
            title = ?, 
            description = ?, 
            category_id = ?, 
            tool_url = ?, 
            pricing_type = ?, 
            price_info = ?, 
            features = ?, 
            rating = ?, 
            featured = ?, 
            is_published = ?, 
            slug = ?, 
            published_at = ?, 
            updated_at = NOW() 
            WHERE id = ?");
        $stmt->execute([
            $title, 
            $description, 
            $category_id, 
            $tool_url, 
            $pricing_type, 
            $price_info, 
            $features, 
            $rating, 
            $featured, 
            $is_published, 
            $slug, 
            $published_at, 
            $id
        ]);
        $success = "AI tool updated successfully";
    } else {
        // Create new AI tool
        $stmt = $db->prepare("INSERT INTO ai_tools (
            title, 
            description, 
            category_id, 
            tool_url, 
            pricing_type, 
            price_info, 
            features, 
            rating, 
            featured, 
            is_published, 
            slug, 
            published_at, 
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $title, 
            $description, 
            $category_id, 
            $tool_url, 
            $pricing_type, 
            $price_info, 
            $features, 
            $rating, 
            $featured, 
            $is_published, 
            $slug, 
            $published_at
        ]);
        $success = "AI tool created successfully";
    }

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = $success;
    
    header("Location: ai-tools.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Save AI tool error: " . $e->getMessage());
    
    // Store error in session and redirect back to form
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    $redirect = $id ? "ai-tool-form.php?id=$id" : "ai-tool-form.php";
    header("Location: $redirect");
    exit;
}