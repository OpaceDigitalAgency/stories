<?php
/**
 * Subscriber Proxy Script
 * This script acts as a proxy for the save-subscriber.php endpoint
 * It can be used to bypass CORS issues
 */

// Allow cross-origin requests from anywhere
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// If form data was submitted instead of JSON
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// Validate required fields
if (empty($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit;
}

// Set default values
$feature = $data['feature'] ?? 'premium stories';

// Connect to database
try {
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    // Check if subscribers table exists, create if not
    $stmt = $db->query("SHOW TABLES LIKE 'subscribers'");
    if ($stmt->rowCount() === 0) {
        $db->exec("CREATE TABLE subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255),
            feature VARCHAR(100) NOT NULL,
            message TEXT,
            is_contacted TINYINT(1) DEFAULT 0,
            admin_notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM subscribers WHERE email = ?");
    $stmt->execute([$data['email']]);
    $existingSubscriber = $stmt->fetch();
    
    if ($existingSubscriber) {
        // Update existing subscriber
        $stmt = $db->prepare("UPDATE subscribers SET 
            feature = ?, 
            updated_at = NOW() 
            WHERE email = ?");
        $stmt->execute([$feature, $data['email']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Your subscription has been updated. We\'ll notify you when this feature is available.',
            'updated' => true
        ]);
    } else {
        // Insert new subscriber
        $stmt = $db->prepare("INSERT INTO subscribers (email, feature, created_at, updated_at) 
            VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$data['email'], $feature]);
        
        $newId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for subscribing! We\'ll notify you when this feature is available.',
            'id' => $newId
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error in subscriber-proxy.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
