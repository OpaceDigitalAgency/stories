<?php
/**
 * Bulk Actions Handler for Stories
 * 
 * Handles bulk operations on stories like delete, publish, unpublish, etc.
 */

// Include database connection
require_once '../../includes/db.php';

// Initialize variables
$success = '';
$error = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Get the selected story IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
    
    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No stories selected.';
    } else {
        // Convert IDs to integers and create placeholders for SQL query
        $selectedIds = array_map('intval', $selectedIds);
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        
        try {
            // Perform the selected action
            switch ($action) {
                case 'delete':
                    // Delete the selected stories
                    $stmt = $pdo->prepare("DELETE FROM stories WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' stories deleted successfully.';
                    break;
                    
                case 'publish':
                    // Publish the selected stories
                    $stmt = $pdo->prepare("UPDATE stories SET is_published = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' stories published successfully.';
                    break;
                    
                case 'unpublish':
                    // Unpublish the selected stories
                    $stmt = $pdo->prepare("UPDATE stories SET is_published = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' stories unpublished successfully.';
                    break;
                    
                case 'feature':
                    // Feature the selected stories
                    $stmt = $pdo->prepare("UPDATE stories SET featured = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' stories featured successfully.';
                    break;
                    
                case 'unfeature':
                    // Unfeature the selected stories
                    $stmt = $pdo->prepare("UPDATE stories SET featured = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' stories unfeatured successfully.';
                    break;
                    
                case 'tag':
                    // Add a tag to the selected stories
                    $tagId = isset($_POST['tag_id']) ? (int)$_POST['tag_id'] : 0;
                    
                    if ($tagId <= 0) {
                        $error = 'Invalid tag selected.';
                        break;
                    }
                    
                    // For each story, add the tag if it doesn't already have it
                    foreach ($selectedIds as $storyId) {
                        // Check if the story already has this tag
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM story_tags WHERE story_id = ? AND tag_id = ?");
                        $stmt->execute([$storyId, $tagId]);
                        $hasTag = (int)$stmt->fetchColumn() > 0;
                        
                        if (!$hasTag) {
                            // Add the tag to the story
                            $stmt = $pdo->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                            $stmt->execute([$storyId, $tagId]);
                        }
                    }
                    
                    $success = 'Tag added to ' . count($selectedIds) . ' stories successfully.';
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

// Redirect back to the stories page with success/error message
$redirectUrl = 'stories.php';

if (!empty($success)) {
    $redirectUrl .= '?success=' . urlencode($success);
} elseif (!empty($error)) {
    $redirectUrl .= '?error=' . urlencode($error);
}

header('Location: ' . $redirectUrl);
exit;
