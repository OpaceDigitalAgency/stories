<?php
/**
 * Author Delete Process
 * 
 * This file processes the author deletion from the confirmation page.
 * It handles different actions like deleting all associated stories or canceling.
 */

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Page variables
$pageTitle = 'Process Author Deletion';
$currentPage = 'authors';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Define flag to track if header has been included
$GLOBALS['header_included'] = true;

// Check if we already have a database connection
if (!isset($db) || !$db) {
    try {
        // Database configuration
        $config = [
            'host' => 'localhost',
            'name' => 'stories_db',
            'user' => 'stories_user',
            'password' => '$tw1cac3*sOt',
            'charset' => 'utf8mb4',
            'port' => 3306
        ];

        // Connect to database
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        error_log("Database connection error in author-delete-process.php: " . $e->getMessage());
        $_SESSION['error'] = "Database connection error. Please try again later.";
        header("Location: authors.php");
        exit;
    }
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    $_SESSION['error'] = "Invalid request. Please try again.";
    header("Location: authors.php");
    exit;
}

$action = $_POST['action'] ?? 'cancel';
$id = intval($_POST['id']);
$newAuthorId = isset($_POST['new_author_id']) ? intval($_POST['new_author_id']) : null;

// Log debug information
error_log("Author Delete Process - Action: $action, ID: $id, New Author ID: " . ($newAuthorId ?: 'none'));

if ($action === 'cancel') {
    $_SESSION['info'] = "Author deletion cancelled.";
    header("Location: authors.php");
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();

    // Verify author exists
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    $author = $stmt->fetch();
    
    if (!$author) {
        throw new Exception("Author not found with ID: $id");
    }
    
    error_log("Processing deletion for author: " . $author['name'] . " (ID: $id)");

    // Check if story_authors junction table exists
    $hasStoryAuthorsTable = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
        error_log("Error checking story_authors table: " . $e->getMessage());
    }

    // Get story count
    $storyCount = 0;
    $storyIds = [];
    
    if ($hasStoryAuthorsTable) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
        $stmt->execute([$id]);
        $storyCount = $stmt->fetchColumn();
        
        if ($storyCount > 0) {
            $stmt = $db->prepare("SELECT story_id FROM story_authors WHERE author_id = ?");
            $stmt->execute([$id]);
            $storyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } else {
        // Check if stories table has author_id column
        try {
            $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
                $stmt->execute([$id]);
                $storyCount = $stmt->fetchColumn();
                
                if ($storyCount > 0) {
                    $stmt = $db->prepare("SELECT id FROM stories WHERE author_id = ?");
                    $stmt->execute([$id]);
                    $storyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            }
        } catch (PDOException $e) {
            // Table might not exist, ignore
            error_log("Error checking stories table: " . $e->getMessage());
        }
    }
    
    error_log("Found $storyCount stories for author ID $id");

    if ($action === 'delete_all') {
        // Delete story tags if they exist
        if (!empty($storyIds)) {
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
                if ($stmt->rowCount() > 0) {
                    $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
                    $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id IN ($placeholders)");
                    $stmt->execute($storyIds);
                    error_log("Deleted associated tags for " . count($storyIds) . " stories");
                }
            } catch (PDOException $e) {
                // Table might not exist, ignore
                error_log("Error deleting from story_tags: " . $e->getMessage());
            }
        }

        // Delete from story_authors junction table
        if ($hasStoryAuthorsTable) {
            $stmt = $db->prepare("DELETE FROM story_authors WHERE author_id = ?");
            $stmt->execute([$id]);
            error_log("Deleted entries from story_authors junction table");
        }

        // Delete stories directly if they have author_id column
        if (!empty($storyIds)) {
            $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM stories WHERE id IN ($placeholders)");
            $stmt->execute($storyIds);
            error_log("Deleted " . count($storyIds) . " stories with IDs: " . implode(', ', $storyIds));
        }

        // Delete author
        $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        error_log("Deleted author with ID $id");

        $_SESSION['success'] = "Author \"" . htmlspecialchars($author['name']) . "\" and all associated stories deleted successfully.";
    }
    elseif ($action === 'reassign' && $newAuthorId) {
        // Verify new author exists
        $stmt = $db->prepare("SELECT id, name FROM authors WHERE id = ?");
        $stmt->execute([$newAuthorId]);
        $newAuthor = $stmt->fetch();
        
        if (!$newAuthor) {
            throw new Exception("New author not found with ID: $newAuthorId");
        }
        
        error_log("Reassigning stories from author ID $id to author ID $newAuthorId");

        if ($hasStoryAuthorsTable) {
            // Update story_authors table
            $stmt = $db->prepare("UPDATE story_authors SET author_id = ? WHERE author_id = ?");
            $stmt->execute([$newAuthorId, $id]);
            error_log("Updated story_authors junction table");
        } else {
            // Check if stories table has author_id column
            try {
                $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                if ($stmt->rowCount() > 0) {
                    // Update stories table directly
                    $stmt = $db->prepare("UPDATE stories SET author_id = ? WHERE author_id = ?");
                    $stmt->execute([$newAuthorId, $id]);
                    error_log("Updated stories table author_id column");
                }
            } catch (PDOException $e) {
                // Table might not exist, ignore
                error_log("Error updating stories table: " . $e->getMessage());
            }
        }

        // Delete old author
        $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
        $stmt->execute([$id]);
        error_log("Deleted author with ID $id after reassigning stories");

        $_SESSION['success'] = "Stories reassigned to \"" . htmlspecialchars($newAuthor['name']) . "\" and author \"" . htmlspecialchars($author['name']) . "\" deleted successfully.";
    }
    else {
        throw new Exception("Invalid action: $action");
    }

    // Commit transaction
    $db->commit();
    error_log("Transaction committed successfully");

    // Check if this is an AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        // Return JSON success response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Author deleted successfully']);
    } else {
        // Redirect for regular form submissions
        header("Location: authors.php");
    }
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
        error_log("Transaction rolled back: " . $e->getMessage());
    }

    error_log("Author delete error: " . $e->getMessage());

    // Check if this is an AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        // Return JSON error response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        // Store error in session and redirect for regular form submissions
        $_SESSION['error'] = $e->getMessage();
        header("Location: authors.php");
    }
    exit;
}

// End output buffering and flush content
if (ob_get_length()) ob_end_flush();