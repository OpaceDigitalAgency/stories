<?php
/**
 * API Endpoint for Updating Fields
 * 
 * This file handles AJAX requests to update fields in the database.
 * It's used by the inline editing functionality.
 */

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Include database connection
require_once '../includes/db-connect.php';

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get the request data
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';
$field = isset($_POST['field']) ? $_POST['field'] : '';
$value = isset($_POST['value']) ? $_POST['value'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate the request data
if ($id <= 0 || empty($type) || empty($field) || $action !== 'update_field') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

// Map content types to database tables
$tableMap = [
    'story' => 'stories',
    'author' => 'authors',
    'post' => 'blog_posts',
    'game' => 'games',
    'media' => 'media',
    'tag' => 'tags',
    'subscriber' => 'subscribers',
    'contact' => 'contacts'
];

// Map field names to database columns
$fieldMap = [
    'title' => 'title',
    'name' => 'name',
    'email' => 'email',
    'bio' => 'bio',
    'content' => 'content',
    'summary' => 'summary',
    'tags' => 'tags',
    'status' => 'status',
    'description' => 'description',
    'alt_text' => 'alt_text',
    'filename' => 'filename',
    'file_type' => 'file_type',
    'website_url' => 'website_url',
    'contact_email' => 'contact_email',
    'pricing_type' => 'pricing_type',
    'category_name' => 'category_name'
];

// Get the table name
$table = isset($tableMap[$type]) ? $tableMap[$type] : '';

// Get the column name
$column = isset($fieldMap[$field]) ? $fieldMap[$field] : $field;

// Validate the table and column
if (empty($table) || empty($column)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid content type or field']);
    exit;
}

try {
    // Prepare the SQL statement
    $sql = "UPDATE $table SET $column = :value, updated_at = NOW() WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    // Bind the parameters
    $stmt->bindParam(':value', $value);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    // Execute the statement
    $result = $stmt->execute();
    
    // Check if the update was successful
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to update field']);
    }
} catch (PDOException $e) {
    // Log the error
    error_log('Database error: ' . $e->getMessage());
    
    // Return an error response
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
