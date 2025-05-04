<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'Bulk Directory Items';
$currentPage = 'bulk-directory-items';

/**
 * Bulk Actions Handler for Directory Items
 * 
 * Handles bulk operations on directory items like delete, publish, unpublish, feature, unfeature, etc.
 */

// Include auth check
include_once '../includes/auth-check.php';

// Include database connection
include_once '../includes/db-connect.php';

// Initialize variables
$success = '';
$error = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Get the selected directory item IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No directory items selected.';
    } else {
        // Convert IDs to integers and create placeholders for SQL query
        $selectedIds = array_map('intval', $selectedIds);
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        
        try {
            // Perform the selected action
            switch ($action) {
                case 'delete':
                    // Delete the selected directory items
                    $stmt = $db->prepare("DELETE FROM directory_items WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' directory items deleted successfully.';
                    break;
                    
                case 'publish':
                    // Update is_published to 1
                    $stmt = $db->prepare("UPDATE directory_items SET is_published = 1, published_at = NOW() WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' directory items published successfully.';
                    break;
                    
                case 'unpublish':
                    // Update is_published to 0
                    $stmt = $db->prepare("UPDATE directory_items SET is_published = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' directory items unpublished successfully.';
                    break;
                    
                case 'feature':
                    // Update featured to 1
                    $stmt = $db->prepare("UPDATE directory_items SET featured = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' directory items featured successfully.';
                    break;
                    
                case 'unfeature':
                    // Update featured to 0
                    $stmt = $db->prepare("UPDATE directory_items SET featured = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' directory items unfeatured successfully.';
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

// Redirect back to the directory items page with success/error message
$redirectUrl = 'directory-items.php';

if (!empty($success)) {
    $redirectUrl .= '?success=' . urlencode($success);
} elseif (!empty($error)) {
    $redirectUrl .= '?error=' . urlencode($error);
}

header('Location: ' . $redirectUrl);
exit;


// Include footer
require_once '../includes/footer.php';
