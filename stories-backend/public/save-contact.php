<?php
/**
 * Contact form submission endpoint
 * Saves contact form submissions to the database
 */

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
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

// Helper function to handle errors
function handleError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// If form data was submitted instead of JSON
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// Debug log
error_log("Contact form data received: " . print_r($data, true));

// Validate required fields
if (empty($data['email'])) {
    handleError('Email is required');
}

if (empty($data['name'])) {
    handleError('Name is required');
}

if (empty($data['subject'])) {
    handleError('Subject is required');
}

if (empty($data['message'])) {
    handleError('Message is required');
}

// Set default values
$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$subject = $data['subject'] ?? '';
$message = $data['message'] ?? '';
$is_responded = 0;
$admin_notes = '';

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

    // Check if contacts table exists, create if not
    $stmt = $db->query("SHOW TABLES LIKE 'contacts'");
    if ($stmt->rowCount() === 0) {
        error_log("Creating contacts table as it doesn't exist");
        $db->exec("CREATE TABLE contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_responded TINYINT(1) DEFAULT 0,
            admin_notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Insert new contact
    error_log("Inserting new contact");
    $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message, is_responded, admin_notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$name, $email, $subject, $message, $is_responded, $admin_notes]);

    $newId = $db->lastInsertId();
    error_log("New contact inserted with ID: {$newId}");

    // Send notification email to admin
    $adminEmail = 'admin@storiesfromtheweb.org';
    $adminSubject = 'New Contact Form Submission';
    $adminMessage = "A new contact form submission has been received:\n\n";
    $adminMessage .= "Name: {$name}\n";
    $adminMessage .= "Email: {$email}\n";
    $adminMessage .= "Subject: {$subject}\n";
    $adminMessage .= "Message: {$message}\n\n";
    $adminMessage .= "You can view and respond to this message in the admin panel.";

    $headers = "From: noreply@storiesfromtheweb.org\r\n";
    $headers .= "Reply-To: {$email}\r\n";

    mail($adminEmail, $adminSubject, $adminMessage, $headers);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We\'ll get back to you as soon as possible.',
        'id' => $newId
    ]);

} catch (PDOException $e) {
    error_log("Database error in save-contact.php: " . $e->getMessage());
    handleError('Database error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log("Error in save-contact.php: " . $e->getMessage());
    handleError('Server error: ' . $e->getMessage(), 500);
}
