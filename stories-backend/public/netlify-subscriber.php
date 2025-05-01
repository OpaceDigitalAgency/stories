<?php
/**
 * Netlify Subscriber Endpoint
 * This script is specifically designed to work with the Netlify frontend
 */

// Allow cross-origin requests from Netlify domains
header('Access-Control-Allow-Origin: https://storiesfromtheweb.netlify.app');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Log all request details for debugging
error_log("Netlify subscriber request received");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request headers: " . print_r(getallheaders(), true));

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Function to handle errors
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    error_log("Error: $message");
    exit;
}

// Get data from various sources
$data = [];

// Check GET parameters first (for direct links)
if (!empty($_GET['email'])) {
    $data['email'] = $_GET['email'];
    $data['feature'] = $_GET['feature'] ?? 'premium stories';
    error_log("Using GET parameters: " . print_r($data, true));
}

// Check POST data
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
    error_log("Using POST data: " . print_r($data, true));
}

// Check JSON input
if (empty($data)) {
    $requestBody = file_get_contents('php://input');
    error_log("Raw request body: " . $requestBody);
    
    if (!empty($requestBody)) {
        $jsonData = json_decode($requestBody, true);
        if ($jsonData) {
            $data = $jsonData;
            error_log("Using JSON data: " . print_r($data, true));
        } else {
            // Try to parse manually if JSON decode fails
            if (preg_match('/"email"\s*:\s*"([^"]+)"/', $requestBody, $matches)) {
                $data['email'] = $matches[1];
                error_log("Manually extracted email: " . $data['email']);
            }
            
            if (preg_match('/"feature"\s*:\s*"([^"]+)"/', $requestBody, $matches)) {
                $data['feature'] = $matches[1];
                error_log("Manually extracted feature: " . $data['feature']);
            }
        }
    }
}

// Validate email
if (empty($data['email'])) {
    handleError('Email is required', 400);
}

// Set default feature if not provided
if (empty($data['feature'])) {
    $data['feature'] = 'premium stories';
}

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
    
    error_log("Database connection established");
    
    // Check if subscribers table exists, create if not
    $stmt = $db->query("SHOW TABLES LIKE 'subscribers'");
    if ($stmt->rowCount() === 0) {
        error_log("Creating subscribers table");
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
        error_log("Updating existing subscriber with ID: " . $existingSubscriber['id']);
        // Update existing subscriber
        $stmt = $db->prepare("UPDATE subscribers SET 
            feature = ?, 
            updated_at = NOW() 
            WHERE email = ?");
        $stmt->execute([$data['feature'], $data['email']]);
        
        $response = [
            'success' => true,
            'message' => 'Your subscription has been updated. We\'ll notify you when this feature is available.',
            'updated' => true,
            'id' => $existingSubscriber['id']
        ];
    } else {
        error_log("Adding new subscriber with email: " . $data['email']);
        // Insert new subscriber
        $stmt = $db->prepare("INSERT INTO subscribers (email, feature, created_at, updated_at) 
            VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$data['email'], $data['feature']]);
        
        $newId = $db->lastInsertId();
        error_log("New subscriber added with ID: " . $newId);
        
        $response = [
            'success' => true,
            'message' => 'Thank you for subscribing! We\'ll notify you when this feature is available.',
            'id' => $newId
        ];
    }
    
    // Return success response
    echo json_encode($response);
    error_log("Success response sent: " . json_encode($response));
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    handleError('Database error: ' . $e->getMessage());
}
