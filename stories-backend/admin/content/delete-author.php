<?php

// Page variables
$pageTitle = 'Delete Author';
$currentPage = 'delete-author';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

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
        error_log("Database connection error in delete-author.php: " . $e->getMessage());
        die("Database connection error. Please try again later.");
    }
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header("Location: authors.php");
    exit;
}

$action = $_POST['action'] ?? 'cancel';
$id = $_POST['id'];
$newAuthorId = $_POST['new_author_id'] ?? null;

if ($action === 'cancel') {
    header("Location: authors.php");
    exit;
}

try {
    // Start transaction
    $db->beginTransaction();

    $id = $_POST['id'];

    // Verify author exists
    $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception("Author not found");
    }

    // Check if story_authors junction table exists
    $hasStoryAuthorsTable = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Get story count
    $storyCount = 0;
    if ($hasStoryAuthorsTable) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
        $stmt->execute([$id]);
        $storyCount = $stmt->fetchColumn();
    } else {
        // Check if stories table has author_id column
        try {
            $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
                $stmt->execute([$id]);
                $storyCount = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
    }

    if ($action === 'delete_all') {
        $storyIds = [];

        // Get all stories by this author
        if ($hasStoryAuthorsTable) {
            $stmt = $db->prepare("SELECT story_id FROM story_authors WHERE author_id = ?");
            $stmt->execute([$id]);
            $storyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Delete story authors
            $stmt = $db->prepare("DELETE FROM story_authors WHERE author_id = ?");
            $stmt->execute([$id]);
        } else {
            // Check if stories table has author_id column
            try {
                $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                if ($stmt->rowCount() > 0) {
                    $stmt = $db->prepare("SELECT id FROM stories WHERE author_id = ?");
                    $stmt->execute([$id]);
                    $storyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            } catch (PDOException $e) {
                // Table might not exist, ignore
            }
        }

        // Delete story tags if they exist
        if (!empty($storyIds)) {
            try {
                $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
                if ($stmt->rowCount() > 0) {
                    $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
                    $stmt = $db->prepare("DELETE FROM story_tags WHERE story_id IN ($placeholders)");
                    $stmt->execute($storyIds);
                }
            } catch (PDOException $e) {
                // Table might not exist, ignore
            }
        }

        // Delete stories
        if (!empty($storyIds)) {
            $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM stories WHERE id IN ($placeholders)");
            $stmt->execute($storyIds);
        }

        // Delete author
        $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Author and all associated stories deleted successfully";
    }
    elseif ($action === 'reassign' && $newAuthorId) {
        // Verify new author exists
        $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
        $stmt->execute([$newAuthorId]);
        if (!$stmt->fetch()) {
            throw new Exception("New author not found");
        }

        if ($hasStoryAuthorsTable) {
            // Update story_authors table
            $stmt = $db->prepare("UPDATE story_authors SET author_id = ? WHERE author_id = ?");
            $stmt->execute([$newAuthorId, $id]);
        } else {
            // Check if stories table has author_id column
            try {
                $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                if ($stmt->rowCount() > 0) {
                    // Update stories table directly
                    $stmt = $db->prepare("UPDATE stories SET author_id = ? WHERE author_id = ?");
                    $stmt->execute([$newAuthorId, $id]);
                }
            } catch (PDOException $e) {
                // Table might not exist, ignore
            }
        }

        // Delete old author
        $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Stories reassigned and author deleted successfully";
    }
    else {
        throw new Exception("Invalid action");
    }

    // Commit transaction
    $db->commit();

    // Check if this is an AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        // Return JSON success response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Author deleted successfully']);
    } else {
        // Store success message and redirect for regular form submissions
        session_start();
        $_SESSION['success'] = "Author deleted successfully";
        header("Location: authors.php");
    }
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db)) {
        $db->rollBack();
    }

    error_log("Delete author error: " . $e->getMessage());

    // Check if this is an AJAX request
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        // Return JSON error response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    } else {
        // Store error in session and redirect for regular form submissions
        session_start();
        $_SESSION['error'] = $e->getMessage();
        header("Location: authors.php");
    }
    exit;
}

// We don't include the footer here as this is a processing script
