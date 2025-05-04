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

// Check if this is a bulk deletion
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
    // Get single author ID from GET or POST
    $id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
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

                <form method="POST" action="delete-author.php" class="mb-3">
                    <?php foreach ($selectedIds as $selectedId): ?>
                        <input type="hidden" name="selected_ids[]" value="<?php echo $selectedId; ?>">
                    <?php endforeach; ?>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="action" id="delete_all" value="delete_all">
                        <label class="form-check-label" for="delete_all">
                            Delete <?php echo $isBulk ? 'authors' : 'author'; ?> and all their stories
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
                <p>Are you sure you want to delete <?php echo $isBulk ? 'these authors' : 'this author'; ?>?</p>
                <form method="POST" action="delete-author.php">
                    <?php foreach ($selectedIds as $selectedId): ?>
                        <input type="hidden" name="selected_ids[]" value="<?php echo $selectedId; ?>">
                    <?php endforeach; ?>
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