<?php
/**
 * Direct Subscriber API Endpoint
 * A simplified endpoint for saving subscriber information
 */

// Start session for anti-bot protection
session_start();

// Include anti-bot protection
include_once '../includes/anti-bot.php';

// Allow cross-origin requests from specific domains
$allowedOrigins = [
    'https://storiesfromtheweb.netlify.app',
    'https://storiesfromtheweb.org',
    'http://localhost:3000',
    'http://localhost:4321'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // For any other domain, allow it during development
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Error handling
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
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

    // Log successful connection
    error_log("Database connection established in save-subscriber.php");
} catch (PDOException $e) {
    error_log("Database connection error in save-subscriber.php: " . $e->getMessage());
    handleError('Database connection error');
}

// Handle POST request (new subscriber)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the request body
    $requestBody = file_get_contents('php://input');
    error_log("Raw request body: " . $requestBody);

    $data = json_decode($requestBody, true);

    // If form data was submitted instead of JSON
    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
        error_log("Using POST data instead of JSON");
    }

    // If JSON parsing failed, try to handle it
    if (empty($data) && !empty($requestBody)) {
        error_log("JSON parsing failed, trying to parse manually");
        // Try to extract email and feature from the raw body
        if (preg_match('/"email"\s*:\s*"([^"]+)"/', $requestBody, $matches)) {
            $email = $matches[1];
            $data['email'] = $email;
            error_log("Extracted email: " . $email);
        }

        if (preg_match('/"feature"\s*:\s*"([^"]+)"/', $requestBody, $matches)) {
            $feature = $matches[1];
            $data['feature'] = $feature;
            error_log("Extracted feature: " . $feature);
        }
    }

    // Debug log
    error_log("Subscriber data received in save-subscriber.php: " . print_r($data, true));
    error_log("Request headers: " . print_r(getallheaders(), true));

    // Check for bot submissions
    if (isLikelyBot($data)) {
        // Pretend success but don't actually save the data
        error_log("Bot submission detected and blocked in subscriber form");
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for subscribing! We\'ll notify you when this feature is available.'
        ]);
        exit;
    }

    // Validate required fields
    if (empty($data['email'])) {
        handleError('Email is required', 400);
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        handleError('Please enter a valid email address', 400);
    }

    // Set default values
    $feature = $data['feature'] ?? 'premium';
    $name = $data['name'] ?? null;
    $message = $data['message'] ?? null;

    try {
        // Check if subscribers table exists, create if not
        $stmt = $db->query("SHOW TABLES LIKE 'subscribers'");
        if ($stmt->rowCount() === 0) {
            error_log("Creating subscribers table as it doesn't exist");
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
            error_log("Updating existing subscriber with ID: {$existingSubscriber['id']}");
            // Update existing subscriber
            $stmt = $db->prepare("UPDATE subscribers SET
                feature = ?,
                name = ?,
                message = ?,
                updated_at = NOW()
                WHERE email = ?");
            $stmt->execute([$feature, $name, $message, $data['email']]);

            echo json_encode([
                'success' => true,
                'message' => 'Your subscription has been updated. We\'ll notify you when this feature is available.',
                'updated' => true
            ]);
        } else {
            error_log("Inserting new subscriber");
            // Insert new subscriber
            $stmt = $db->prepare("INSERT INTO subscribers (email, name, feature, message, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$data['email'], $name, $feature, $message]);

            $newId = $db->lastInsertId();
            error_log("New subscriber inserted with ID: {$newId}");

            echo json_encode([
                'success' => true,
                'message' => 'Thank you for subscribing! We\'ll notify you when this feature is available.',
                'id' => $newId
            ]);
        }
    } catch (PDOException $e) {
        error_log("Database error in save-subscriber.php: " . $e->getMessage());
        handleError('Database error: ' . $e->getMessage());
    }
} else {
    handleError('Method not allowed', 405);
}
