<?php
/**
 * Subscribers API Endpoint
 * Handles subscription requests for premium features
 */

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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

    // Handle POST request (new subscriber)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the request body
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);

        // Debug log
        error_log("Received subscriber request. Raw input: " . $requestBody);

        // If form data was submitted instead of JSON
        if (empty($data) && !empty($_POST)) {
            $data = $_POST;
            error_log("Using POST data instead of JSON: " . print_r($data, true));
        }

        error_log("Processing subscriber data: " . print_r($data, true));

        // Validate required fields
        if (empty($data['email'])) {
            error_log("Validation error: Email is required");
            http_response_code(400);
            echo json_encode(['error' => 'Email is required']);
            exit;
        }

        // Set default values
        $feature = $data['feature'] ?? 'premium';
        $name = $data['name'] ?? null;
        $message = $data['message'] ?? null;

        error_log("Subscriber values - Email: {$data['email']}, Feature: {$feature}, Name: {$name}");

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

                $response = [
                    'success' => true,
                    'message' => 'Your subscription has been updated. We\'ll notify you when this feature is available.',
                    'updated' => true
                ];
                error_log("Response for update: " . json_encode($response));
                echo json_encode($response);
            } else {
                error_log("Inserting new subscriber");
                // Insert new subscriber
                $stmt = $db->prepare("INSERT INTO subscribers (email, name, feature, message, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$data['email'], $name, $feature, $message]);

                $newId = $db->lastInsertId();
                error_log("New subscriber inserted with ID: {$newId}");

                $response = [
                    'success' => true,
                    'message' => 'Thank you for subscribing! We\'ll notify you when this feature is available.',
                    'id' => $newId
                ];
                error_log("Response for insert: " . json_encode($response));
                echo json_encode($response);
            }
        } catch (PDOException $e) {
            error_log("Subscriber database error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
    // Handle GET request (admin list subscribers)
    else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Simple authentication check - in a real app, use proper authentication
        $isAdmin = false;

        // Check for admin session or token
        if (isset($_GET['admin_token']) && $_GET['admin_token'] === 'stories_admin_token') {
            $isAdmin = true;
        }

        if (!$isAdmin) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Get subscribers list
        $feature = isset($_GET['feature']) ? $_GET['feature'] : null;

        if ($feature) {
            $stmt = $db->prepare("SELECT * FROM subscribers WHERE feature = ? ORDER BY created_at DESC");
            $stmt->execute([$feature]);
        } else {
            $stmt = $db->query("SELECT * FROM subscribers ORDER BY created_at DESC");
        }

        $subscribers = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'subscribers' => $subscribers
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error']);
    exit;
} catch (Exception $e) {
    error_log("Server error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
