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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: ai-tools.php");
    exit;
}

$id = $_POST['id'];

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

    // Check if AI tool exists
    $stmt = $db->prepare("SELECT id FROM ai_tools WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception("AI tool not found");
    }

    // Delete AI tool
    $stmt = $db->prepare("DELETE FROM ai_tools WHERE id = ?");
    $stmt->execute([$id]);

    // Commit transaction
    $db->commit();

    // Store success message and redirect
    session_start();
    $_SESSION['success'] = "AI tool deleted successfully";
    
    header("Location: ai-tools.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Delete AI tool error: " . $e->getMessage());
    
    // Store error in session and redirect
    session_start();
    $_SESSION['error'] = $e->getMessage();
    
    header("Location: ai-tools.php");
    exit;
}