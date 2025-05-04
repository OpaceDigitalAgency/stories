<?php
/**
 * Bulk Actions Handler for Tags
 *
 * Handles bulk operations on tags like delete, etc.
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
    
    // Get the selected tag IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No tags selected.';
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
                    // First check if any of the tags are in use
                    $stmt = $db->prepare("
                        SELECT t.id, t.name, 
                        (SELECT COUNT(*) FROM story_tags WHERE tag_id = t.id) + 
                        (SELECT COUNT(*) FROM post_tags WHERE tag_id = t.id) as usage_count
                        FROM tags t
                        WHERE t.id IN ($placeholders)
                        HAVING usage_count > 0
                    ");
                    $stmt->execute($selectedIds);
                    $tagsInUse = $stmt->fetchAll();
                    
                    if (!empty($tagsInUse)) {
                        // Some tags are in use, cannot delete
                        $tagNames = array_map(function($tag) {
                            return $tag['name'] . ' (' . $tag['usage_count'] . ' uses)';
                        }, $tagsInUse);
                        
                        $error = 'Cannot delete the following tags because they are in use: ' . implode(', ', $tagNames);
                    } else {
                        // Delete the selected tags
                        $stmt = $db->prepare("DELETE FROM tags WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' tags deleted successfully.';
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

// Store message in session and redirect
if (!empty($success)) {
    $_SESSION['success'] = $success;
} elseif (!empty($error)) {
    $_SESSION['error'] = $error;
}

// Redirect back to the tags page
header('Location: tags.php');
exit;
