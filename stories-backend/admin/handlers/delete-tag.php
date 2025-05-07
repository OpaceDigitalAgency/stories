<?php
/**
 * Delete Tag Handler
 *
 * This script handles the deletion of tags, checking for usage in stories and posts.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the tag ID from the request
$tagId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$tagId) {
    $tagId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Validate the tag ID
    if ($tagId <= 0) {
        throw new Exception("Invalid tag ID");
    }

    // Start transaction
    $db->beginTransaction();

    // Check if the tag exists
    $stmt = $db->prepare("SELECT id, name FROM tags WHERE id = ?");
    $stmt->execute([$tagId]);
    $tag = $stmt->fetch();

    if (!$tag) {
        throw new Exception("Tag not found");
    }

    // Check if the tag is used in stories
    $stmt = $db->prepare("SELECT COUNT(*) FROM story_tags WHERE tag_id = ?");
    $stmt->execute([$tagId]);
    $storyCount = $stmt->fetchColumn();

    // Check if the tag is used in blog posts
    $stmt = $db->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
    $stmt->execute([$tagId]);
    $postCount = $stmt->fetchColumn();

    // If the tag is used, we'll delete the associations first
    if ($storyCount > 0) {
        $stmt = $db->prepare("DELETE FROM story_tags WHERE tag_id = ?");
        $stmt->execute([$tagId]);
    }

    if ($postCount > 0) {
        $stmt = $db->prepare("DELETE FROM post_tags WHERE tag_id = ?");
        $stmt->execute([$tagId]);
    }

    // Delete the tag
    $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([$tagId]);

    // Commit transaction
    $db->commit();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'Tag deleted successfully'
    ];

    // Log the deletion
    error_log("Tag deleted: ID=$tagId, Name={$tag['name']}, Stories=$storyCount, Posts=$postCount");

    // If this is not an AJAX request, redirect back to the tags list
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set success message in session
        $_SESSION['success'] = 'Tag deleted successfully';
        
        // Redirect to tags list
        header('Location: ../content/tags.php');
        exit;
    }

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
    error_log("Error deleting tag: " . $e->getMessage());

    // If this is not an AJAX request, redirect back with error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Failed to delete tag: ' . $e->getMessage();
        
        // Redirect to tags list
        header('Location: ../content/tags.php');
        exit;
    }
}

// Output JSON response for AJAX requests
echo json_encode($response);
