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

// Get author ID from GET or POST
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if (!$id) {
    $_SESSION['error'] = "No author specified";
    header("Location: authors.php");
    exit;
}

// Set page variables
$pageTitle = 'Delete Author';
$currentPage = 'authors';
$pageDescription = 'Delete author and manage their stories.';

// Include header
require_once '../includes/header.php';

// If form was submitted, process the deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'cancel';
    $newAuthorId = isset($_POST['new_author_id']) ? intval($_POST['new_author_id']) : null;

    if ($action === 'cancel') {
        $_SESSION['info'] = "Author deletion cancelled.";
        header("Location: authors.php");
        exit;
    }

// Get author details and story count
try {
    // Get author details
    $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$id]);
    $author = $stmt->fetch();

    if (!$author) {
        $_SESSION['error'] = "Author not found";
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

    // Get other authors for reassignment
    $otherAuthors = [];
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id != ? ORDER BY name");
    $stmt->execute([$id]);
    $otherAuthors = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: authors.php");
    exit;
}
?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Delete Author: <?php echo htmlspecialchars($author['name']); ?></h2>
        </div>
        <div class="card-body">
            <?php if ($storyCount > 0): ?>
                <div class="alert alert-warning">
                    <h4 class="alert-heading">Warning!</h4>
                    <p>This author has <?php echo $storyCount; ?> stories associated with them.</p>
                    <p>Please choose how to handle these stories:</p>
                </div>

                <form method="POST" action="delete-author.php" class="mb-3">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="action" id="delete_all" value="delete_all">
                        <label class="form-check-label" for="delete_all">
                            Delete author and all their stories
                        </label>
                    </div>

                    <?php if (!empty($otherAuthors)): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="action" id="reassign" value="reassign">
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
                <p>Are you sure you want to delete this author?</p>
                <form method="POST" action="delete-author.php">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" class="btn btn-danger">Delete Author</button>
                    <a href="authors.php" class="btn btn-secondary">Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';