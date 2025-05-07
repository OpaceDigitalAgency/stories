<?php
/**
 * Delete Story Handler
 *
 * This script handles the deletion of stories, including associated records
 * like story_tags and story_authors.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the story ID from the request
$storyId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$storyId) {
    $storyId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Validate the story ID
    if ($storyId <= 0) {
        throw new Exception("Invalid story ID");
    }

    // Start transaction
    $db->beginTransaction();

    // Check if the story exists
    $stmt = $db->prepare("SELECT id, title FROM stories WHERE id = ?");
    $stmt->execute([$storyId]);
    $story = $stmt->fetch();

    if (!$story) {
        throw new Exception("Story not found");
    }

    // Delete story_tags records first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id = ?");
    $stmt->execute([$storyId]);

    // Delete story_authors records (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM story_authors WHERE story_id = ?");
    $stmt->execute([$storyId]);

    // Delete the story
    $stmt = $db->prepare("DELETE FROM stories WHERE id = ?");
    $stmt->execute([$storyId]);

    // Commit transaction
    $db->commit();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'Story deleted successfully'
    ];

    // Log the deletion
    error_log("Story deleted: ID=$storyId, Title={$story['title']}");

    // If this is not an AJAX request, redirect back to the stories list
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set success message in session
        $_SESSION['success'] = 'Story deleted successfully';
        
        // Redirect to stories list
        header('Location: ../content/stories.php');
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
    error_log("Error deleting story: " . $e->getMessage());

    // If this is not an AJAX request, redirect back with error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Failed to delete story: ' . $e->getMessage();
        
        // Redirect to stories list
        header('Location: ../content/stories.php');
        exit;
    }
}

// Output JSON response for AJAX requests
echo json_encode($response);
