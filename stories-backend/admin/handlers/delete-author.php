<?php
/**
 * Delete Author Handler
 *
 * This script handles the deletion of authors, including associated records
 * like story_authors.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the author ID from the request
$authorId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$authorId) {
    $authorId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Validate the author ID
    if ($authorId <= 0) {
        throw new Exception("Invalid author ID");
    }

    // Start transaction
    $db->beginTransaction();

    // Check if the author exists
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);
    $author = $stmt->fetch();

    if (!$author) {
        throw new Exception("Author not found");
    }

    // Check if the author has stories
    $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
    $stmt->execute([$authorId]);
    $storyCount = $stmt->fetchColumn();

    if ($storyCount > 0) {
        // For now, we'll just delete the author-story associations
        // In a more sophisticated implementation, we might want to reassign stories
        // or provide options to the user
        $stmt = $db->prepare("DELETE FROM story_authors WHERE author_id = ?");
        $stmt->execute([$authorId]);
    }

    // Delete the author
    $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);

    // Commit transaction
    $db->commit();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'Author deleted successfully'
    ];

    // Log the deletion
    error_log("Author deleted: ID=$authorId, Name={$author['name']}, Stories=$storyCount");

    // If this is not an AJAX request, redirect back to the authors list
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set success message in session
        $_SESSION['success'] = 'Author deleted successfully';
        
        // Redirect to authors list
        header('Location: ../content/authors.php');
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
    error_log("Error deleting author: " . $e->getMessage());

    // If this is not an AJAX request, redirect back with error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Failed to delete author: ' . $e->getMessage();
        
        // Redirect to authors list
        header('Location: ../content/authors.php');
        exit;
    }
}

// Output JSON response for AJAX requests
echo json_encode($response);
