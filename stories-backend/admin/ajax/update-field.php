<?php
/**
 * Update Field AJAX Handler
 *
 * This file handles AJAX requests for updating fields inline.
 * It returns JSON responses indicating success or failure.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the request is valid
if (!isset($_POST['action']) || $_POST['action'] !== 'update_field') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Check if all required parameters are present
if (!isset($_POST['id']) || !isset($_POST['type']) || !isset($_POST['field']) || !isset($_POST['value'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

// Get the parameters
$id = $_POST['id'];
$type = $_POST['type'];
$field = $_POST['field'];
$value = $_POST['value'];

// Validate the ID
if (!is_numeric($id) || $id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

// Determine the table name based on the type
$tableName = '';
switch ($type) {
    case 'story':
        $tableName = 'stories';
        break;
    case 'author':
        $tableName = 'authors';
        break;
    case 'post':
        $tableName = 'blog_posts';
        break;
    case 'media':
        $tableName = 'media';
        break;
    case 'tag':
        $tableName = 'tags';
        break;
    case 'game':
        $tableName = 'games';
        break;
    case 'ai_tool':
        $tableName = 'ai_tools';
        break;
    default:
        // Try to convert the type to a table name
        $tableName = str_replace('-', '_', $type);
}

// Check if the table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE '$tableName'");
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => "Table '$tableName' does not exist"]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Check if the field exists in the table
try {
    $stmt = $db->query("DESCRIBE $tableName");
    $fields = [];
    while ($row = $stmt->fetch()) {
        $fields[] = $row['Field'];
    }
    
    if (!in_array($field, $fields)) {
        echo json_encode(['success' => false, 'error' => "Field '$field' does not exist in table '$tableName'"]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Update the field
try {
    $stmt = $db->prepare("UPDATE $tableName SET $field = ? WHERE id = ?");
    $stmt->execute([$value, $id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'No rows were updated']);
        exit;
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
