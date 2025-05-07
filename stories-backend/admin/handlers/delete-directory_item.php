<?php
/**
 * Delete Directory Item Handler
 *
 * This script handles the deletion of directory items.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the directory item ID from the request
$directoryItemId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$directoryItemId) {
    $directoryItemId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Validate the directory item ID
    if ($directoryItemId <= 0) {
        throw new Exception("Invalid directory item ID");
    }

    // Start transaction
    $db->beginTransaction();

    // Check if the directory item exists
    $stmt = $db->prepare("SELECT id, title FROM directory_items WHERE id = ?");
    $stmt->execute([$directoryItemId]);
    $directoryItem = $stmt->fetch();

    if (!$directoryItem) {
        throw new Exception("Directory item not found");
    }

    // Delete the directory item
    $stmt = $db->prepare("DELETE FROM directory_items WHERE id = ?");
    $stmt->execute([$directoryItemId]);

    // Commit transaction
    $db->commit();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'Directory item deleted successfully'
    ];

    // Log the deletion
    error_log("Directory item deleted: ID=$directoryItemId, Title={$directoryItem['title']}");

    // If this is not an AJAX request, redirect back to the directory items list
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set success message in session
        $_SESSION['success'] = 'Directory item deleted successfully';
        
        // Redirect to directory items list
        header('Location: ../content/directory-items.php');
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
    error_log("Error deleting directory item: " . $e->getMessage());

    // If this is not an AJAX request, redirect back with error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Failed to delete directory item: ' . $e->getMessage();
        
        // Redirect to directory items list
        header('Location: ../content/directory-items.php');
        exit;
    }
}

// Output JSON response for AJAX requests
echo json_encode($response);
