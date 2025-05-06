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

// Get the table name
$tableName = isset($tableMap[$type]) ? $tableMap[$type] : '';

// If table name is still empty, try to convert the type to a table name
if (empty($tableName)) {
    $tableName = str_replace('-', '_', $type);
}

// Debug: Log the request data
error_log("Update Field Request: " . json_encode($_POST));
error_log("Table name: " . $tableName);
error_log("Field: " . $field);

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

// Map field names to database columns
$fieldMap = [
    'title' => 'title',
    'name' => 'name',
    'email' => 'email',
    'bio' => 'bio',
    'content' => 'content',
    'summary' => 'summary',
    'tags' => 'tags',
    'status' => 'is_published',
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
    'message' => 'message',
    'slug' => 'slug',
    'category' => 'category_id',
    'website' => 'website_url',
    'featured' => 'featured',
    'pricing' => 'pricing_type',
    'rating' => 'rating'
];

// Get the column name
$column = isset($fieldMap[$field]) ? $fieldMap[$field] : $field;

// Check if the field exists in the table
try {
    $stmt = $db->query("DESCRIBE $tableName");
    $fields = [];
    while ($row = $stmt->fetch()) {
        $fields[] = $row['Field'];
    }

    if (!in_array($column, $fields)) {
        error_log("Field '$column' does not exist in table '$tableName'");
        error_log("Available fields: " . implode(', ', $fields));
        echo json_encode(['success' => false, 'error' => "Field '$column' does not exist in table '$tableName'"]);
        exit;
    }
} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Update the field
try {
    // Prepare the SQL statement
    $sql = "UPDATE `$tableName` SET `$column` = :value";

    // Add updated_at if it exists
    $updatedAtQuery = "SHOW COLUMNS FROM `$tableName` LIKE 'updated_at'";
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

    if ($stmt->rowCount() === 0) {
        error_log("No rows were updated: Table: $tableName, ID: $id, Column: $column, Value: $value");
        echo json_encode(['success' => false, 'error' => 'No rows were updated']);
        exit;
    }

    // Log the success
    error_log("Field update successful: Table: $tableName, ID: $id, Column: $column, Value: $value");

    echo json_encode([
        'success' => true,
        'debug' => [
            'id' => $id,
            'type' => $type,
            'field' => $field,
            'column' => $column,
            'value' => $value,
            'table' => $tableName
        ]
    ]);
} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
