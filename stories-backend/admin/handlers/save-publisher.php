<?php
/**
 * Save Publisher Handler
 * 
 * This script handles AJAX requests to save a new publisher.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$website = isset($_POST['website']) ? trim($_POST['website']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Validate name
if (empty($name)) {
    echo json_encode([
        'success' => false,
        'message' => 'Publisher name is required'
    ]);
    exit;
}

// Validate website if provided
if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid website URL'
    ]);
    exit;
}

try {
    // Check if publishers table exists
    $stmt = $db->query("SHOW TABLES LIKE 'publishers'");
    if ($stmt->rowCount() == 0) {
        // Create publishers table
        $db->exec("CREATE TABLE IF NOT EXISTS publishers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            website VARCHAR(255),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (slug)
        )");
    }
    
    // Generate slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
    $slug = trim($slug, '-');
    
    // Check if publisher already exists
    $stmt = $db->prepare("SELECT id FROM publishers WHERE name = ? OR slug = ?");
    $stmt->execute([$name, $slug]);
    if ($stmt->rowCount() > 0) {
        $publisher = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'message' => 'Publisher already exists',
            'id' => $publisher['id']
        ]);
        exit;
    }
    
    // Insert new publisher
    $stmt = $db->prepare("INSERT INTO publishers (name, slug, website, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $slug, $website, $description]);
    
    // Get the new publisher ID
    $publisherId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Publisher saved successfully',
        'id' => $publisherId
    ]);
} catch (PDOException $e) {
    error_log("Error saving publisher: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
