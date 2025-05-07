<?php
/**
 * Delete Blog Post Handler
 *
 * This script handles the deletion of blog posts, including associated records
 * like post_tags.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the post ID from the request
$postId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$postId) {
    $postId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Validate the post ID
    if ($postId <= 0) {
        throw new Exception("Invalid post ID");
    }

    // Start transaction
    $db->beginTransaction();

    // Check if the post exists
    $stmt = $db->prepare("SELECT id, title FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();

    if (!$post) {
        throw new Exception("Blog post not found");
    }

    // Delete post_tags records first (foreign key constraint)
    $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt->execute([$postId]);

    // Delete the post
    $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);

    // Commit transaction
    $db->commit();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'Blog post deleted successfully'
    ];

    // Log the deletion
    error_log("Blog post deleted: ID=$postId, Title={$post['title']}");

    // If this is not an AJAX request, redirect back to the blog posts list
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set success message in session
        $_SESSION['success'] = 'Blog post deleted successfully';
        
        // Redirect to blog posts list
        header('Location: ../content/blog-posts.php');
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
    error_log("Error deleting blog post: " . $e->getMessage());

    // If this is not an AJAX request, redirect back with error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Failed to delete blog post: ' . $e->getMessage();
        
        // Redirect to blog posts list
        header('Location: ../content/blog-posts.php');
        exit;
    }
}

// Output JSON response for AJAX requests
echo json_encode($response);
