<?php
/**
 * Update Thumbnail Handler
 *
 * This script updates the thumbnail URL for a content item.
 * It's used when an image is selected from the media library or generated with AI.
 */

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
    'thumbnail_url' => ''
];

try {
    // Check if required parameters are provided
    if (!isset($_POST['item_type']) || !isset($_POST['item_id'])) {
        throw new Exception('Missing required parameters');
    }

    $itemType = $_POST['item_type'];
    $itemId = intval($_POST['item_id']);
    $imageUrl = isset($_POST['image_url']) ? $_POST['image_url'] : '';

    // Log the request for debugging
    error_log("Update thumbnail request: Type: $itemType, ID: $itemId, URL: $imageUrl");

    // Validate item type
    $validItemTypes = ['story', 'post', 'author', 'game', 'ai_tool', 'directory_item'];
    if (!in_array($itemType, $validItemTypes)) {
        throw new Exception('Invalid item type');
    }

    // Get the table name and field name based on item type
    $tableName = '';
    $imageField = '';
    $thumbnailField = '';
    $idField = 'id'; // Default ID field name

    switch ($itemType) {
        case 'story':
            $tableName = 'stories';
            $imageField = 'cover_url';
            $thumbnailField = 'thumbnail_url';
            $idField = 'id';
            break;
        case 'post':
            $tableName = 'posts';
            $imageField = 'featured_image';
            $thumbnailField = 'thumbnail_url';
            $idField = 'id';
            break;
        case 'author':
            $tableName = 'authors';
            $imageField = 'avatar_url';
            $thumbnailField = ''; // Authors table doesn't have a thumbnail_url field
            $idField = 'id';
            break;
        case 'game':
            $tableName = 'games';
            $imageField = 'cover_url';
            $thumbnailField = 'thumbnail_url';
            $idField = 'id';
            break;
        case 'ai_tool':
            $tableName = 'ai_tools';
            $imageField = 'cover_url';
            $thumbnailField = 'thumbnail_url';
            $idField = 'id';
            break;
        case 'directory_item':
            $tableName = 'directory_items';
            $imageField = 'cover_url'; // Correct field name for directory_items
            $thumbnailField = 'thumbnail_url';
            $idField = 'id';
            break;
    }

    // Check if the thumbnail field exists in the table (skip for authors as they don't need it)
    if (!empty($thumbnailField)) {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tableName} LIKE ?");
        $stmt->execute([$thumbnailField]);
        if ($stmt->rowCount() === 0) {
            // Add the thumbnail field if it doesn't exist
            $db->exec("ALTER TABLE {$tableName} ADD COLUMN {$thumbnailField} VARCHAR(255) AFTER {$imageField}");
        }
    }

    // Generate thumbnail URL from the image URL
    $thumbnailUrl = '';
    if (strpos($imageUrl, '/uploads/') !== false && strpos($imageUrl, '-thumbnail') === false) {
        // Try to use the thumbnail version if it exists
        $pathInfo = pathinfo($imageUrl);

        // Use the correct path format without any unique ID prefix
        // First, remove any unique ID prefix if it exists (like '6819c7559130f-')
        $filename = $pathInfo['filename'];
        if (preg_match('/^[a-f0-9]+-(.+)$/', $filename, $matches)) {
            $filename = $matches[1];
        }

        // Use .webp extension for thumbnails as that's what the system is using
        $thumbnailPath = $pathInfo['dirname'] . '/optimized/' . $filename . '-thumbnail.webp';

        // Check if the thumbnail exists on the server
        $thumbnailPathAbs = $_SERVER['DOCUMENT_ROOT'] . $thumbnailPath;
        if (file_exists($thumbnailPathAbs)) {
            $thumbnailUrl = $thumbnailPath;
        } else {
            // Try with jpg extension as fallback
            $thumbnailPathJpg = $pathInfo['dirname'] . '/optimized/' . $filename . '-thumbnail.jpg';
            $thumbnailPathJpgAbs = $_SERVER['DOCUMENT_ROOT'] . $thumbnailPathJpg;
            if (file_exists($thumbnailPathJpgAbs)) {
                $thumbnailUrl = $thumbnailPathJpg;
            }
        }
    }

    // If no thumbnail was found, use the original image
    if (empty($thumbnailUrl)) {
        $thumbnailUrl = $imageUrl;
    }

    // Log the update query for debugging
    error_log("Updating {$tableName} SET {$imageField} = '{$imageUrl}', {$thumbnailField} = '{$thumbnailUrl}' WHERE {$idField} = {$itemId}");

    try {
        // Use direct query with quoted values to avoid prepared statement issues
        $imageUrlEscaped = $db->quote($imageUrl);
        $thumbnailUrlEscaped = $db->quote($thumbnailUrl);
        $itemIdEscaped = intval($itemId); // Integer values don't need quotes

        // Construct the SQL query with proper quoting
        if (!empty($thumbnailField)) {
            $sql = "UPDATE `{$tableName}` SET `{$imageField}` = {$imageUrlEscaped}, `{$thumbnailField}` = {$thumbnailUrlEscaped} WHERE `{$idField}` = {$itemIdEscaped}";
        } else {
            // For tables without a thumbnail field (like authors)
            $sql = "UPDATE `{$tableName}` SET `{$imageField}` = {$imageUrlEscaped} WHERE `{$idField}` = {$itemIdEscaped}";
        }
        error_log("Direct SQL: " . $sql);

        // Execute the direct SQL query
        $rowCount = $db->exec($sql);
        error_log("Update affected {$rowCount} rows");

        // Verify the update by querying the database
        if (!empty($thumbnailField)) {
            $verifySQL = "SELECT `{$imageField}`, `{$thumbnailField}` FROM `{$tableName}` WHERE `{$idField}` = {$itemIdEscaped}";
        } else {
            // For tables without a thumbnail field (like authors)
            $verifySQL = "SELECT `{$imageField}` FROM `{$tableName}` WHERE `{$idField}` = {$itemIdEscaped}";
        }
        error_log("Verify SQL: " . $verifySQL);

        $verifyResult = $db->query($verifySQL);
        $result = $verifyResult->fetch(PDO::FETCH_ASSOC);
        error_log("After update: " . print_r($result, true));

        // Set success response
        $response['success'] = true;
        if (!empty($thumbnailField)) {
            $response['message'] = "Image and thumbnail updated successfully ({$rowCount} rows affected)";
            $response['thumbnail_url'] = $thumbnailUrl;
        } else {
            $response['message'] = "Image updated successfully ({$rowCount} rows affected)";
            // For authors, we don't have a thumbnail_url field, so we use the main image URL
            $response['thumbnail_url'] = $imageUrl;
        }
        $response['debug'] = [
            'table' => $tableName,
            'field' => $imageField,
            'thumbnail_field' => $thumbnailField,
            'id' => $itemId,
            'rows_affected' => $rowCount,
            'verification' => $result
        ];
    } catch (PDOException $pdoEx) {
        error_log("Database error updating thumbnail: " . $pdoEx->getMessage());
        throw new Exception("Database error: " . $pdoEx->getMessage());
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
