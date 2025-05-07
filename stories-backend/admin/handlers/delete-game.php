<?php
/**
 * Delete Game Handler
 *
 * This script handles the deletion of games.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Set content type to JSON for AJAX requests
header('Content-Type: application/json');

// Get the game ID from the request
$gameId = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$gameId) {
    $gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
}

// Initialize response
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

try {
    // Validate the game ID
    if ($gameId <= 0) {
        throw new Exception("Invalid game ID");
    }

    // Start transaction
    $db->beginTransaction();

    // Check if the game exists
    $stmt = $db->prepare("SELECT id, title FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();

    if (!$game) {
        throw new Exception("Game not found");
    }

    // Delete the game
    $stmt = $db->prepare("DELETE FROM games WHERE id = ?");
    $stmt->execute([$gameId]);

    // Commit transaction
    $db->commit();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'Game deleted successfully'
    ];

    // Log the deletion
    error_log("Game deleted: ID=$gameId, Title={$game['title']}");

    // If this is not an AJAX request, redirect back to the games list
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set success message in session
        $_SESSION['success'] = 'Game deleted successfully';
        
        // Redirect to games list
        header('Location: ../content/games.php');
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
    error_log("Error deleting game: " . $e->getMessage());

    // If this is not an AJAX request, redirect back with error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set error message in session
        $_SESSION['error'] = 'Failed to delete game: ' . $e->getMessage();
        
        // Redirect to games list
        header('Location: ../content/games.php');
        exit;
    }
}

// Output JSON response for AJAX requests
echo json_encode($response);
