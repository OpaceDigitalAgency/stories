<?php
/**
 * Bulk Actions Handler for Authors
 *
 * Handles bulk operations on authors like delete, etc.
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
            // Strip "Selected" from action name
            $action = str_replace(' Selected', '', $action);
            
            switch (strtolower($action)) {
                case 'delete':
                    // First check if any of the authors have associated stories
                    // Check if story_authors table exists
                    $hasStoryAuthorsTable = false;
                    try {
                        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
                        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
                    } catch (PDOException $e) {
                        // Table might not exist, ignore
                    }

                    // Build query based on table structure
                    if ($hasStoryAuthorsTable) {
                        $stmt = $db->prepare("
                            SELECT a.id, a.name, COUNT(sa.story_id) as story_count
                            FROM authors a
                            LEFT JOIN story_authors sa ON a.id = sa.author_id
                            WHERE a.id IN ($placeholders)
                            GROUP BY a.id
                            HAVING story_count > 0
                        ");
                    } else {
                        // Check if stories table has author_id column
                        try {
                            $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                            if ($stmt->rowCount() > 0) {
                                $stmt = $db->prepare("
                                    SELECT a.id, a.name, COUNT(s.id) as story_count
                                    FROM authors a
                                    LEFT JOIN stories s ON a.id = s.author_id
                                    WHERE a.id IN ($placeholders)
                                    GROUP BY a.id
                                    HAVING story_count > 0
                                ");
                            } else {
                                // No story associations possible
                                $stmt = $db->prepare("
                                    SELECT a.id, a.name, 0 as story_count
                                    FROM authors a
                                    WHERE a.id IN ($placeholders)
                                ");
                            }
                        } catch (PDOException $e) {
                            // Table might not exist, ignore
                            $stmt = $db->prepare("
                                SELECT a.id, a.name, 0 as story_count
                                FROM authors a
                                WHERE a.id IN ($placeholders)
                            ");
                        }
                    }
                    $stmt->execute($selectedIds);
                    $authorsWithStories = $stmt->fetchAll();
                    
                    // If any authors have stories, redirect to confirmation page
                    if (!empty($authorsWithStories)) {
                        $_SESSION['bulk_delete_authors'] = $selectedIds;
                        header('Location: author-delete-process.php?bulk=1');
                        exit;
                    } else {
                        // Delete the selected authors
                        $stmt = $db->prepare("DELETE FROM authors WHERE id IN ($placeholders)");
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

// Store message in session and redirect
if (!empty($success)) {
    $_SESSION['success'] = $success;
} elseif (!empty($error)) {
    $_SESSION['error'] = $error;
}

// Redirect back to the authors page
header('Location: authors.php');
exit;
