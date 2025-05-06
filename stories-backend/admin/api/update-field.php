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
    'contact' => 'contacts',
    'directory_item' => 'directory_items',
    'ai_tool' => 'ai_tools',
    'ai_setting' => 'ai_settings',
    'test' => 'test_table' // For debugging purposes
];

// Debug: Log the request data
error_log("Update Field Request: " . json_encode($_POST));
error_log("Session data: " . json_encode($_SESSION));
error_log("Database connection: " . ($db ? 'Connected' : 'Not connected'));

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
    'category_name' => 'category_name',
    'url' => 'url',
    'icon' => 'icon',
    'address' => 'address',
    'phone' => 'phone',
    'model' => 'model',
    'api_key' => 'api_key',
    'prompt_template' => 'prompt_template',
    'is_contacted' => 'is_contacted',
    'admin_notes' => 'admin_notes',
    'feature' => 'feature',
    'message' => 'message'
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
    // For debugging purposes, if the table is 'test_table', just return success
    if ($table === 'test_table') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'debug' => [
                'id' => $id,
                'type' => $type,
                'field' => $field,
                'column' => $column,
                'value' => $value
            ]
        ]);
        exit;
    }

    // Check if the table exists
    $tableExistsQuery = "SHOW TABLES LIKE '$table'";
    $tableExists = $db->query($tableExistsQuery)->rowCount() > 0;

    if (!$tableExists) {
        // Log the error
        error_log("Table '$table' does not exist");

        // Return an error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => "Table '$table' does not exist"]);
        exit;
    }

    // Check if the column exists
    $columnExistsQuery = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $columnExists = $db->query($columnExistsQuery)->rowCount() > 0;

    if (!$columnExists) {
        // Log the error
        error_log("Column '$column' does not exist in table '$table'");

        // Return an error response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => "Column '$column' does not exist in table '$table'"]);
        exit;
    }

    // Prepare the SQL statement
    $sql = "UPDATE `$table` SET `$column` = :value";

    // Add updated_at if it exists
    $updatedAtQuery = "SHOW COLUMNS FROM `$table` LIKE 'updated_at'";
    $hasUpdatedAt = $db->query($updatedAtQuery)->rowCount() > 0;

    if ($hasUpdatedAt) {
        $sql .= ", updated_at = NOW()";
    }

    $sql .= " WHERE id = :id";

    $stmt = $db->prepare($sql);

    // Bind the parameters
    $stmt->bindParam(':value', $value);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    // Execute the statement
    $result = $stmt->execute();

    // Check if the update was successful
    if ($result) {
        // Log the success
        error_log("Field update successful: Table: $table, ID: $id, Column: $column, Value: $value");

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'debug' => [
                'id' => $id,
                'type' => $type,
                'field' => $field,
                'column' => $column,
                'value' => $value,
                'table' => $table
            ]
        ]);
    } else {
        // Log the error
        error_log("Field update failed: Table: $table, ID: $id, Column: $column, Value: $value");

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to update field']);
    }
} catch (PDOException $e) {
    // Log the error
    error_log('Database error: ' . $e->getMessage());

    // Return an error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'id' => $id,
            'type' => $type,
            'field' => $field,
            'column' => $column,
            'value' => $value,
            'table' => $table ?? 'unknown'
        ]
    ]);
}
