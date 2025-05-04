<?php
/**
 * Bulk Actions Handler for Media
 *
 * Handles bulk operations on media files like delete, optimize, etc.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image optimizer
require_once '../../includes/image_optimizer.php';

// Initialize variables
$success = '';
$error = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the selected action
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // Get the selected media IDs
    $selectedIds = isset($_POST['selected_ids']) ? $_POST['selected_ids'] : [];

    // Validate inputs
    if (empty($action)) {
        $error = 'No action selected.';
    } elseif (empty($selectedIds)) {
        $error = 'No media files selected.';
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
                    // First get file paths
                    $stmt = $db->prepare("SELECT file_path FROM media WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    // Delete files from filesystem
                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            unlink($file);
                        }
                    }

                    // Delete records from database
                    $stmt = $db->prepare("DELETE FROM media WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $success = count($selectedIds) . ' media files deleted successfully.';
                    break;

                case 'optimize':
                    // Get file paths
                    $stmt = $db->prepare("SELECT id, file_path FROM media WHERE id IN ($placeholders) AND file_type LIKE 'image/%'");
                    $stmt->execute($selectedIds);
                    $images = $stmt->fetchAll();

                    $optimizedCount = 0;
                    foreach ($images as $image) {
                        // Create optimized directory if it doesn't exist
                        $optimizedDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/optimized/';
                        if (!is_dir($optimizedDir)) {
                            mkdir($optimizedDir, 0755, true);
                        }

                        // Optimize the image
                        $variants = createImageVariants($image['file_path'], $optimizedDir);
                        if ($variants) {
                            // Update the media record with optimized URLs
                            updateMediaRecord($db, $image['id'], $variants);
                            $optimizedCount++;
                        }
                    }

                    $success = $optimizedCount . ' images optimized successfully.';
                    break;

                default:
                    $error = 'Invalid action selected.';
                    break;
            }
        } catch (PDOException $e) {
            error_log("Bulk media action error: " . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
        } catch (Exception $e) {
            error_log("Bulk media action error: " . $e->getMessage());
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

// Redirect back to the media page
header('Location: media.php');
exit;