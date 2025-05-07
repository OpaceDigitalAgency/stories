<?php
/**
 * Delete AI Tool Handler
 *
 * This script handles the deletion of AI tools.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the AI tool ID from the request
$aiToolId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$aiToolId) {
    $aiToolId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Validate the AI tool ID
    if ($aiToolId <= 0) {
        throw new Exception("Invalid AI tool ID");
    }

    // Start transaction
    $db->beginTransaction();

    // Check if the AI tool exists
    $stmt = $db->prepare("SELECT id, title FROM ai_tools WHERE id = ?");
    $stmt->execute([$aiToolId]);
    $aiTool = $stmt->fetch();

    if (!$aiTool) {
        throw new Exception("AI tool not found");
    }

    // Delete the AI tool
    $stmt = $db->prepare("DELETE FROM ai_tools WHERE id = ?");
    $stmt->execute([$aiToolId]);

    // Commit transaction
    $db->commit();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'AI tool deleted successfully'
    ];

    // Log the deletion
    error_log("AI tool deleted: ID=$aiToolId, Title={$aiTool['title']}");

    // If this is not an AJAX request, redirect back to the AI tools list
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set success message in session
        $_SESSION['success'] = 'AI tool deleted successfully';
        
        // Redirect to AI tools list
        header('Location: ../content/ai-tools.php');
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
    error_log("Error deleting AI tool: " . $e->getMessage());

    // If this is not an AJAX request, redirect back with error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Failed to delete AI tool: ' . $e->getMessage();
        
        // Redirect to AI tools list
        header('Location: ../content/ai-tools.php');
        exit;
    }
}

// Output JSON response for AJAX requests
echo json_encode($response);
