<?php
/**
 * Bulk Action Handler
 *
 * This script handles bulk actions for various content types.
 * Supported actions: delete, publish, unpublish, feature, unfeature
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the action and item type from the request
$action = isset($_POST['action']) ? $_POST['action'] : '';
$itemType = isset($_POST['item_type']) ? $_POST['item_type'] : '';
$selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];

// Convert selectedIds to array if it's not already
if (!is_array($selectedIds)) {
    $selectedIds = explode(',', $selectedIds);
}

// Filter out any non-numeric IDs
$selectedIds = array_filter($selectedIds, 'is_numeric');

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Validate the request
if (empty($action) || empty($itemType) || empty($selectedIds)) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

// Map item types to database tables
$tableMap = [
    'story' => 'stories',
    'author' => 'authors',
    'post' => 'blog_posts',
    'game' => 'games',
    'tag' => 'tags',
    'ai_tool' => 'ai_tools',
    'directory_item' => 'directory_items'
];

// Get the table name
$tableName = isset($tableMap[$itemType]) ? $tableMap[$itemType] : '';

// If table name is still empty, try to convert the item type to a table name
if (empty($tableName)) {
    $tableName = str_replace('-', '_', $itemType) . 's';
}

try {
    // Start transaction
    $db->beginTransaction();

    // Create placeholders for the IDs
    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));

    // Process the action
    switch ($action) {
        case 'delete':
            // Handle special cases for different content types
            if ($itemType === 'story') {
                // Delete story_tags records first
                $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id IN ($placeholders)");
                $stmt->execute($selectedIds);

                // Delete story_authors records
                $stmt = $db->prepare("DELETE FROM story_authors WHERE story_id IN ($placeholders)");
                $stmt->execute($selectedIds);
            } elseif ($itemType === 'post') {
                // Delete post_tags records first
                $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id IN ($placeholders)");
                $stmt->execute($selectedIds);
            } elseif ($itemType === 'author') {
                // Delete story_authors records
                $stmt = $db->prepare("DELETE FROM story_authors WHERE author_id IN ($placeholders)");
                $stmt->execute($selectedIds);
            } elseif ($itemType === 'tag') {
                // Delete story_tags records
                $stmt = $db->prepare("DELETE FROM story_tags WHERE tag_id IN ($placeholders)");
                $stmt->execute($selectedIds);

                // Delete post_tags records
                $stmt = $db->prepare("DELETE FROM post_tags WHERE tag_id IN ($placeholders)");
                $stmt->execute($selectedIds);
            }

            // Delete the items
            $stmt = $db->prepare("DELETE FROM $tableName WHERE id IN ($placeholders)");
            $stmt->execute($selectedIds);

            $response['message'] = count($selectedIds) . ' items deleted successfully';
            break;

        case 'publish':
            // Check if the table has an is_published column
            $stmt = $db->query("SHOW COLUMNS FROM $tableName LIKE 'is_published'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("UPDATE $tableName SET is_published = 1 WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $response['message'] = count($selectedIds) . ' items published successfully';
            } else {
                throw new Exception("The $tableName table does not have an is_published column");
            }
            break;

        case 'unpublish':
            // Check if the table has an is_published column
            $stmt = $db->query("SHOW COLUMNS FROM $tableName LIKE 'is_published'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("UPDATE $tableName SET is_published = 0 WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $response['message'] = count($selectedIds) . ' items unpublished successfully';
            } else {
                throw new Exception("The $tableName table does not have an is_published column");
            }
            break;

        case 'feature':
            // Check if the table has a featured column
            $stmt = $db->query("SHOW COLUMNS FROM $tableName LIKE 'featured'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("UPDATE $tableName SET featured = 1 WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $response['message'] = count($selectedIds) . ' items featured successfully';
            } else {
                throw new Exception("The $tableName table does not have a featured column");
            }
            break;

        case 'unfeature':
            // Check if the table has a featured column
            $stmt = $db->query("SHOW COLUMNS FROM $tableName LIKE 'featured'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("UPDATE $tableName SET featured = 0 WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $response['message'] = count($selectedIds) . ' items unfeatured successfully';
            } else {
                throw new Exception("The $tableName table does not have a featured column");
            }
            break;

        default:
            throw new Exception("Unsupported action: $action");
    }

    // Commit transaction
    $db->commit();

    // Set success response
    $response['success'] = true;

    // Log the action
    error_log("Bulk action: $action on $itemType, IDs: " . implode(',', $selectedIds));

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    // Set error response
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];

    // Log the error
    error_log("Error performing bulk action: " . $e->getMessage());
}

// Output JSON response
echo json_encode($response);
