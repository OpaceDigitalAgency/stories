<?php
/**
 * Bulk Actions Handler for AI Tools
 *
 * Handles bulk operations on AI tools like delete, publish, unpublish, feature, unfeature, etc.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Initialize variables
$success = '';
$error = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Get the selected AI tool IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No AI tools selected.';
    } else {
        // Convert IDs to integers and create placeholders for SQL query
        $selectedIds = array_map('intval', $selectedIds);
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        
        try {
            // Perform the selected action
            // Strip "Selected" from action name
            $action = str_replace(' Selected', '', $action);
            
            switch (strtolower($action)) {
                case 'delete':
                    // Delete the selected AI tools
                    $stmt = $db->prepare("DELETE FROM ai_tools WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' AI tools deleted successfully.';
                    break;
                    
                case 'publish':
                    // Update is_published to 1
                    $stmt = $db->prepare("UPDATE ai_tools SET is_published = 1, published_at = NOW() WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' AI tools published successfully.';
                    break;
                    
                case 'unpublish':
                    // Update is_published to 0
                    $stmt = $db->prepare("UPDATE ai_tools SET is_published = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' AI tools unpublished successfully.';
                    break;
                    
                case 'feature':
                    // Update featured to 1
                    $stmt = $db->prepare("UPDATE ai_tools SET featured = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' AI tools featured successfully.';
                    break;
                    
                case 'unfeature':
                    // Update featured to 0
                    $stmt = $db->prepare("UPDATE ai_tools SET featured = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' AI tools unfeatured successfully.';
                    break;
                    
                default:
                    $error = 'Invalid action selected.';
                    break;
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Store message in session and redirect
if (!empty($success)) {
    $_SESSION['success'] = $success;
} elseif (!empty($error)) {
    $_SESSION['error'] = $error;
}

// Redirect back to the AI tools page
header('Location: ai-tools.php');
exit;
