<?php
/**
 * Bulk Actions Handler for Blog Posts
 *
 * Handles bulk operations on blog posts like delete, publish, unpublish, etc.
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
    
    // Get the selected post IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No posts selected.';
    } else {
        // Convert IDs to integers and create placeholders for SQL query
        $selectedIds = array_map('intval', $selectedIds);
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        
        try {
            // Check if blog_posts or blog table exists
            $blogTableName = 'blog_posts';
            $stmt = $db->query("SHOW TABLES LIKE 'blog_posts'");
            if ($stmt->rowCount() === 0) {
                // Check if blog table exists instead
                $stmt = $db->query("SHOW TABLES LIKE 'blog'");
                if ($stmt->rowCount() > 0) {
                    $blogTableName = 'blog';
                } else {
                    throw new Exception('Blog posts table not found.');
                }
            }
            
            // Perform the selected action
            // Strip "Selected" from action name
            $action = str_replace(' Selected', '', $action);
            
            switch (strtolower($action)) {
                case 'delete':
                    // Delete the selected posts
                    $stmt = $db->prepare("DELETE FROM $blogTableName WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    
                    // Also delete from post_tags if it exists
                    $postTagsTableName = 'post_tags';
                    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
                    if ($stmt->rowCount() > 0) {
                        $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                    }
                    
                    // Also delete from blog_tags if it exists
                    $stmt = $db->query("SHOW TABLES LIKE 'blog_tags'");
                    if ($stmt->rowCount() > 0) {
                        $stmt = $db->prepare("DELETE FROM blog_tags WHERE post_id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                    }
                    
                    $success = count($selectedIds) . ' posts deleted successfully.';
                    break;
                    
                case 'publish':
                    // Check if status column exists
                    $stmt = $db->query("SHOW COLUMNS FROM $blogTableName LIKE 'status'");
                    if ($stmt->rowCount() > 0) {
                        // Update status to published
                        $stmt = $db->prepare("UPDATE $blogTableName SET status = 'published' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' posts published successfully.';
                    } else {
                        // Check if is_published column exists
                        $stmt = $db->query("SHOW COLUMNS FROM $blogTableName LIKE 'is_published'");
                        if ($stmt->rowCount() > 0) {
                            // Update is_published to 1
                            $stmt = $db->prepare("UPDATE $blogTableName SET is_published = 1 WHERE id IN ($placeholders)");
                            $stmt->execute($selectedIds);
                            $success = count($selectedIds) . ' posts published successfully.';
                        } else {
                            $error = 'Cannot publish posts: No status or is_published column found.';
                        }
                    }
                    break;
                    
                case 'unpublish':
                    // Check if status column exists
                    $stmt = $db->query("SHOW COLUMNS FROM $blogTableName LIKE 'status'");
                    if ($stmt->rowCount() > 0) {
                        // Update status to draft
                        $stmt = $db->prepare("UPDATE $blogTableName SET status = 'draft' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedIds);
                        $success = count($selectedIds) . ' posts unpublished successfully.';
                    } else {
                        // Check if is_published column exists
                        $stmt = $db->query("SHOW COLUMNS FROM $blogTableName LIKE 'is_published'");
                        if ($stmt->rowCount() > 0) {
                            // Update is_published to 0
                            $stmt = $db->prepare("UPDATE $blogTableName SET is_published = 0 WHERE id IN ($placeholders)");
                            $stmt->execute($selectedIds);
                            $success = count($selectedIds) . ' posts unpublished successfully.';
                        } else {
                            $error = 'Cannot unpublish posts: No status or is_published column found.';
                        }
                    }
                    break;
                    
                default:
                    $error = 'Invalid action selected.';
                    break;
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Store message in session and redirect
if (!empty($success)) {
    $_SESSION['success'] = $success;
} elseif (!empty($error)) {
    $_SESSION['error'] = $error;
}

// Redirect back to the posts page
header('Location: blog-posts.php');
exit;
