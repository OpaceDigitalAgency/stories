<?php

// Include header
require_once '../includes/header.php';


// Page variables
$pageTitle = 'Bulk Games';
$currentPage = 'bulk-games';

/**
 * Bulk Actions Handler for Games
 * 
 * Handles bulk operations on games like delete, publish, unpublish, feature, unfeature, etc.
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
    
    // Get the selected game IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No games selected.';
    } else {
        // Convert IDs to integers and create placeholders for SQL query
        $selectedIds = array_map('intval', $selectedIds);
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        
        try {
            // Perform the selected action
            switch ($action) {
                case 'delete':
                    // Delete the selected games
                    $stmt = $db->prepare("DELETE FROM games WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' games deleted successfully.';
                    break;
                    
                case 'publish':
                    // Update is_published to 1
                    $stmt = $db->prepare("UPDATE games SET is_published = 1, published_at = NOW() WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' games published successfully.';
                    break;
                    
                case 'unpublish':
                    // Update is_published to 0
                    $stmt = $db->prepare("UPDATE games SET is_published = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' games unpublished successfully.';
                    break;
                    
                case 'feature':
                    // Update featured to 1
                    $stmt = $db->prepare("UPDATE games SET featured = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' games featured successfully.';
                    break;
                    
                case 'unfeature':
                    // Update featured to 0
                    $stmt = $db->prepare("UPDATE games SET featured = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' games unfeatured successfully.';
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

// Redirect back to the games page with success/error message
$redirectUrl = 'games.php';

if (!empty($success)) {
    $redirectUrl .= '?success=' . urlencode($success);
} elseif (!empty($error)) {
    $redirectUrl .= '?error=' . urlencode($error);
}

header('Location: ' . $redirectUrl);
exit;


// Include footer
require_once '../includes/footer.php';
