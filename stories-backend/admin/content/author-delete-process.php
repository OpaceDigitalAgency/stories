<?php
/**
 * Author Delete Confirmation Page
 * 
 * This page displays options for deleting an author:
 * 1. Delete author and all their stories
 * 2. Reassign stories to another author
 * 3. Cancel deletion
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Process deletion if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'cancel';
    $newAuthorId = isset($_POST['new_author_id']) ? intval($_POST['new_author_id']) : null;

    // Handle both single ID and array of IDs
    if (isset($_POST['id'])) {
        $selectedIds = [intval($_POST['id'])];
    } else {
        $selectedIds = isset($_POST['selected_ids']) ? array_map('intval', $_POST['selected_ids']) : [];
    }

    if (empty($selectedIds)) {
        $_SESSION['error'] = "No authors selected";
        header("Location: authors.php");
        exit;
    }

    try {
        // Start transaction
        $db->beginTransaction();

        // Check if story_authors table exists
        $hasStoryAuthorsTable = false;
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
            $hasStoryAuthorsTable = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }

        if ($action === 'delete_all') {
            // Get all stories by these authors
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            $storyIds = [];

            if ($hasStoryAuthorsTable) {
                $stmt = $db->prepare("SELECT story_id FROM story_authors WHERE author_id IN ($placeholders)");
                $stmt->execute($selectedIds);
                $storyIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Delete from story_authors
                $stmt = $db->prepare("DELETE FROM story_authors WHERE author_id IN ($placeholders)");
                $stmt->execute($selectedIds);
            } else {
                // Check if stories table has author_id column
                try {
                    $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                    if ($stmt->rowCount() > 0) {
                        $stmt = $db->prepare("SELECT id FROM stories WHERE author_id IN ($placeholders)");
                        $stmt->execute($selectedIds);
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

                // Delete stories
                $placeholders = str_repeat('?,', count($storyIds) - 1) . '?';
                $stmt = $db->prepare("DELETE FROM stories WHERE id IN ($placeholders)");
                $stmt->execute($storyIds);
            }

            // Delete authors
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM authors WHERE id IN ($placeholders)");
            $stmt->execute($selectedIds);

            $_SESSION['success'] = count($selectedIds) . " author(s) and their stories deleted successfully";

        } elseif ($action === 'reassign' && $newAuthorId) {
            // Verify new author exists
            $stmt = $db->prepare("SELECT id FROM authors WHERE id = ?");
            $stmt->execute([$newAuthorId]);
            if (!$stmt->fetch()) {
                throw new Exception("New author not found");
            }

            if ($hasStoryAuthorsTable) {
                // Update story_authors table
                $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                $stmt = $db->prepare("UPDATE story_authors SET author_id = ? WHERE author_id IN ($placeholders)");
                $params = array_merge([$newAuthorId], $selectedIds);
                $stmt->execute($params);
            } else {
                // Check if stories table has author_id column
                try {
                    $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
                    if ($stmt->rowCount() > 0) {
                        $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
                        $stmt = $db->prepare("UPDATE stories SET author_id = ? WHERE author_id IN ($placeholders)");
                        $params = array_merge([$newAuthorId], $selectedIds);
                        $stmt->execute($params);
                    }
                } catch (PDOException $e) {
                    // Table might not exist, ignore
                }
            }

            // Delete old authors
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            $stmt = $db->prepare("DELETE FROM authors WHERE id IN ($placeholders)");
            $stmt->execute($selectedIds);

            $_SESSION['success'] = "Stories reassigned and " . count($selectedIds) . " author(s) deleted successfully";
        } else {
            throw new Exception("Invalid action");
        }

        // Commit transaction
        $db->commit();
        header("Location: authors.php");
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($db)) {
            $db->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: authors.php");
        exit;
    }
}

// Handle GET request for confirmation page
$isBulk = isset($_GET['bulk']) && $_GET['bulk'] == '1';
$selectedIds = [];

if ($isBulk) {
    // Get IDs from session
    if (!isset($_SESSION['bulk_delete_authors']) || empty($_SESSION['bulk_delete_authors'])) {
        $_SESSION['error'] = "No authors selected for deletion";
        header("Location: authors.php");
        exit;
    }
    $selectedIds = $_SESSION['bulk_delete_authors'];
} else {
    // Get single author ID from GET
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id) {
        $_SESSION['error'] = "No author specified";
        header("Location: authors.php");
        exit;
    }
    $selectedIds = [$id];
}

// Set page variables
$pageTitle = 'Delete Author';
$currentPage = 'authors';
$pageDescription = 'Delete author and manage their stories.';

// Get author details and story counts
try {
    // Get author details
    $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
    $stmt = $db->prepare("SELECT * FROM authors WHERE id IN ($placeholders)");
    $stmt->execute($selectedIds);
    $authors = $stmt->fetchAll();

    if (empty($authors)) {
        $_SESSION['error'] = "No authors found";
        header("Location: authors.php");
        exit;
    }

    // Check if story_authors table exists
    $hasStoryAuthorsTable = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Get story counts and other authors
    $authorStoryCounts = [];
    $totalStoryCount = 0;
    
    if ($hasStoryAuthorsTable) {
        $stmt = $db->prepare("
            SELECT author_id, COUNT(*) as count 
            FROM story_authors 
            WHERE author_id IN ($placeholders)
            GROUP BY author_id
        ");
        $stmt->execute($selectedIds);
        $authorStoryCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } else {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("
                    SELECT author_id, COUNT(*) as count 
                    FROM stories 
                    WHERE author_id IN ($placeholders)
                    GROUP BY author_id
                ");
                $stmt->execute($selectedIds);
                $authorStoryCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
    }

    // Calculate total story count
    foreach ($authorStoryCounts as $count) {
        $totalStoryCount += $count;
    }

    // Get other authors for reassignment
    $otherAuthors = [];
    $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id NOT IN ($placeholders) ORDER BY name");
    $stmt->execute($selectedIds);
    $otherAuthors = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: authors.php");
    exit;
}

// Include header
require_once '../includes/header.php';
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Delete <?php echo $isBulk ? 'Authors' : 'Author'; ?></h2>
        </div>
        <div class="card-body">
            <?php if ($totalStoryCount > 0): ?>
                <div class="alert alert-warning">
                    <h4 class="alert-heading">Warning!</h4>
                    <?php if ($isBulk): ?>
                        <p>The following authors have stories associated with them:</p>
                        <ul>
                            <?php foreach ($authors as $author): ?>
                                <?php if (isset($authorStoryCounts[$author['id']]) && $authorStoryCounts[$author['id']] > 0): ?>
                                    <li><?php echo htmlspecialchars($author['name']); ?> (<?php echo $authorStoryCounts[$author['id']]; ?> stories)</li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>This author has <?php echo $totalStoryCount; ?> stories associated with them.</p>
                    <?php endif; ?>
                    <p>Please choose how to handle these stories:</p>
                </div>

                <form method="POST" action="author-delete-process.php" class="mb-3">
                    <?php if ($isBulk): ?>
                        <?php foreach ($selectedIds as $selectedId): ?>
                            <input type="hidden" name="selected_ids[]" value="<?php echo $selectedId; ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="hidden" name="id" value="<?php echo $selectedIds[0]; ?>">
                    <?php endif; ?>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="action" id="delete_all" value="delete_all" checked>
                        <label class="form-check-label" for="delete_all">
                            Delete <?php echo $isBulk ? 'authors' : 'author'; ?> and all their stories
                        </label>
                    </div>

                    <?php if (!empty($otherAuthors)): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="action" id="reassign" value="reassign" <?php echo empty($otherAuthors) ? 'disabled' : ''; ?>>
                            <label class="form-check-label" for="reassign">
                                Reassign stories to another author:
                            </label>
                            <select name="new_author_id" class="form-control mt-2">
                                <?php foreach ($otherAuthors as $otherAuthor): ?>
                                    <option value="<?php echo $otherAuthor['id']; ?>">
                                        <?php echo htmlspecialchars($otherAuthor['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                        <a href="authors.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <p>Are you sure you want to delete <?php echo $isBulk ? 'these authors' : 'this author'; ?>?</p>
                <form method="POST" action="author-delete-process.php">
                    <?php if ($isBulk): ?>
                        <?php foreach ($selectedIds as $selectedId): ?>
                            <input type="hidden" name="selected_ids[]" value="<?php echo $selectedId; ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="hidden" name="id" value="<?php echo $selectedIds[0]; ?>">
                    <?php endif; ?>
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" class="btn btn-danger">Delete <?php echo $isBulk ? 'Authors' : 'Author'; ?></button>
                    <a href="authors.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>