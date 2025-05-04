<?php

// Include header
include '../includes/header.php';


// Page variables
$pageTitle = 'Bulk Authors';
$currentPage = 'bulk-authors';

/**
 * Bulk Actions Handler for Authors
 * 
 * Handles bulk operations on authors like delete, etc.
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
    
    // Get the selected author IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No authors selected.';
    } else {
        // Convert IDs to integers and create placeholders for SQL query
        $selectedIds = array_map('intval', $selectedIds);
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        
        try {
            // Perform the selected action
            switch ($action) {
                case 'delete':
                    // First check if any of the authors have associated stories
                    $stmt = $pdo->prepare("
                        SELECT a.id, a.name, COUNT(sa.story_id) as story_count
                        FROM authors a
                        LEFT JOIN story_authors sa ON a.id = sa.author_id
                        WHERE a.id IN ($placeholders)
                        GROUP BY a.id
                        HAVING story_count > 0
                    ");
                    $stmt->execute($selectedIds);
                    $authorsWithStories = $stmt->fetchAll();
                    
                    if (!empty($authorsWithStories)) {
                        // Some authors have associated stories, cannot delete
                        $authorNames = array_map(function($author) {
                            return $author['name'] . ' (' . $author['story_count'] . ' stories)';
                        }, $authorsWithStories);
                        
                        $error = 'Cannot delete the following authors because they have associated stories: ' . implode(', ', $authorNames);
                    } else {
                        // Delete the selected authors
                        $stmt = $pdo->prepare("DELETE FROM authors WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' authors deleted successfully.';
                    }
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

// Redirect back to the authors page with success/error message
$redirectUrl = 'authors.php';

if (!empty($success)) {
    $redirectUrl .= '?success=' . urlencode($success);
} elseif (!empty($error)) {
    $redirectUrl .= '?error=' . urlencode($error);
}

header('Location: ' . $redirectUrl);
exit;


// Include footer
require_once '../includes/footer.php';
