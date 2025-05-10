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
    if (!isset($_POST['item_type']) || !isset($_POST['item_id']) || !isset($_POST['image_url'])) {
        throw new Exception('Missing required parameters');
    }

    $itemType = $_POST['item_type'];
    $itemId = intval($_POST['item_id']);
    $imageUrl = $_POST['image_url'];

    // Validate item type
    $validItemTypes = ['story', 'post', 'author', 'game', 'ai_tool', 'directory_item'];
    if (!in_array($itemType, $validItemTypes)) {
        throw new Exception('Invalid item type');
    }

    // Get the table name and field name based on item type
    $tableName = '';
    $imageField = '';
    $thumbnailField = '';

    switch ($itemType) {
        case 'story':
            $tableName = 'stories';
            $imageField = 'cover_url';
            $thumbnailField = 'thumbnail_url';
            break;
        case 'post':
            $tableName = 'posts';
            $imageField = 'featured_image';
            $thumbnailField = 'thumbnail_url';
            break;
        case 'author':
            $tableName = 'authors';
            $imageField = 'avatar_url';
            $thumbnailField = 'thumbnail_url';
            break;
        case 'game':
            $tableName = 'games';
            $imageField = 'cover_url';
            $thumbnailField = 'thumbnail_url';
            break;
        case 'ai_tool':
            $tableName = 'ai_tools';
            $imageField = 'cover_url';
            $thumbnailField = 'thumbnail_url';
            break;
        case 'directory_item':
            $tableName = 'directory_items';
            $imageField = 'cover_image_url';
            $thumbnailField = 'thumbnail_url';
            break;
    }

    // Check if the thumbnail field exists in the table
    $stmt = $db->prepare("SHOW COLUMNS FROM {$tableName} LIKE ?");
    $stmt->execute([$thumbnailField]);
    if ($stmt->rowCount() === 0) {
        // Add the thumbnail field if it doesn't exist
        $db->exec("ALTER TABLE {$tableName} ADD COLUMN {$thumbnailField} VARCHAR(255) AFTER {$imageField}");
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
    error_log("Updating {$tableName} SET {$imageField} = '{$imageUrl}', {$thumbnailField} = '{$thumbnailUrl}' WHERE id = {$itemId}");

    try {
        // Update the item in the database
        $stmt = $db->prepare("UPDATE {$tableName} SET {$imageField} = ?, {$thumbnailField} = ? WHERE id = ?");
        $stmt->execute([$imageUrl, $thumbnailUrl, $itemId]);

        // Check if the update was successful
        $rowCount = $stmt->rowCount();
        error_log("Update affected {$rowCount} rows");

        // Verify the update by querying the database
        $verifyStmt = $db->prepare("SELECT {$imageField}, {$thumbnailField} FROM {$tableName} WHERE id = ?");
        $verifyStmt->execute([$itemId]);
        $result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        error_log("After update: " . print_r($result, true));

        // Set success response
        $response['success'] = true;
        $response['message'] = "Thumbnail updated successfully ({$rowCount} rows affected)";
        $response['thumbnail_url'] = $thumbnailUrl;
        $response['debug'] = [
            'table' => $tableName,
            'field' => $imageField,
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
