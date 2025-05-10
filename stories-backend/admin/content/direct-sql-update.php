<?php
// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'debug' => []
];

try {
    // Get the ID from the query string
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Get the image URL from the query string
    $imageUrl = isset($_GET['url']) ? $_GET['url'] : '';

    // Get the table and field from the query string (default to authors and avatar_url)
    $table = isset($_GET['table']) ? $_GET['table'] : 'authors';
    $field = isset($_GET['field']) ? $_GET['field'] : 'avatar_url';

    // Validate table and field
    $validTables = ['authors', 'stories', 'directory_items', 'books'];
    if (!in_array($table, $validTables)) {
        throw new Exception('Invalid table name');
    }

    $validFields = ['avatar_url', 'cover_url', 'cover_image_url', 'thumbnail_url'];
    if (!in_array($field, $validFields)) {
        throw new Exception('Invalid field name');
    }

    // Log the parameters
    error_log("Direct SQL update - Table: $table, ID: $id, Field: $field, Value: $imageUrl");
    $response['debug']['params'] = [
        'table' => $table,
        'id' => $id,
        'field' => $field,
        'value' => $imageUrl
    ];

    // Check if the record exists
    $checkStmt = $db->prepare("SELECT id FROM `$table` WHERE id = ?");
    $checkStmt->execute([$id]);
    $record = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new Exception("Record with ID $id not found in table $table");
    }

    // Get the current value for debugging
    $currentValueStmt = $db->prepare("SELECT `$field` FROM `$table` WHERE id = ?");
    $currentValueStmt->execute([$id]);
    $currentRecord = $currentValueStmt->fetch(PDO::FETCH_ASSOC);

    // Log the current data
    error_log("Current record data: " . print_r($currentRecord, true));
    $response['debug']['before'] = $currentRecord;

    // Execute a direct SQL query without prepared statements
    $escapedUrl = $db->quote($imageUrl);
    $sql = "UPDATE `$table` SET `$field` = {$escapedUrl} WHERE id = {$id}";
    $rowCount = $db->exec($sql);

    // Log the SQL and number of affected rows
    error_log("Direct SQL: " . $sql);
    error_log("Update affected $rowCount rows");
    $response['debug']['sql'] = $sql;
    $response['debug']['rows_affected'] = $rowCount;

    // Verify the update
    $verifyStmt = $db->prepare("SELECT `$field` FROM `$table` WHERE id = ?");
    $verifyStmt->execute([$id]);
    $updatedRecord = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    // Log the updated data
    error_log("Updated record data: " . print_r($updatedRecord, true));
    $response['debug']['after'] = $updatedRecord;

    // Set success response
    $response['success'] = true;
    $response['message'] = "Field '$field' updated successfully for ID $id in table '$table'";

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in direct-sql-update.php: " . $e->getMessage());
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
