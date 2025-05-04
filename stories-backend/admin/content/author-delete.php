<?php

// Page variables
$pageTitle = 'Author Delete';
$currentPage = 'authors';
$pageDescription = 'Confirm author deletion';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header - must be after auth check and db connection
require_once '../includes/header.php';

// Get author details
$authorId = $_GET['id'] ?? 0;

try {
    // Ensure we have a database connection
    if (!isset($db) || !$db) {
        // Try to connect to the database directly
        try {
            $db = new PDO(
                'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                'stories_user',
                '$tw1cac3*sOt',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            $errorMessage = "Database connection error: " . $e->getMessage();
            error_log("Database connection error in author-delete.php: " . $e->getMessage());
        }
    }

    // Get author details
    $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);
    $author = $stmt->fetch();

    if (!$author) {
        $_SESSION['error'] = "Author not found.";
        header("Location: authors.php");
        exit;
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
        $stmt->execute([$authorId]);
        $storyCount = $stmt->fetchColumn();
    } else {
        // Check if stories table has author_id column
        try {
            $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM stories WHERE author_id = ?");
                $stmt->execute([$authorId]);
                $storyCount = $stmt->fetchColumn();
            }
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
    }

    // Get other authors for reassignment
    $stmt = $db->prepare("SELECT id, name FROM authors WHERE id != ? ORDER BY name");
    $stmt->execute([$authorId]);
    $otherAuthors = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Author delete page error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading author details. Please try again.";
    header("Location: authors.php");
    exit;
}

// Check for success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<div class="content-wrapper">
    <div class="container-fluid">

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section">
            <div class="section-body">
                <p>Are you sure you want to delete the author "<?php echo htmlspecialchars($author['name']); ?>"?</p>

                <?php if ($storyCount > 0): ?>
                    <div class="alert alert-warning">
                        <p>This author has <?php echo $storyCount; ?> associated stories. Please choose how to handle them:</p>

                        <form action="delete-author.php" method="post" class="mt-3">
                            <input type="hidden" name="id" value="<?php echo $authorId; ?>">

                            <div class="form-check mb-3">
                                <input type="radio" id="delete_all" name="action" value="delete_all" class="form-check-input">
                                <label for="delete_all" class="form-check-label">
                                    Delete all associated stories
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input type="radio" id="reassign" name="action" value="reassign" class="form-check-input">
                                <label for="reassign" class="form-check-label">
                                    Reassign stories to another author:
                                </label>
                                <select name="new_author_id" class="form-control mt-2" id="new_author_select" disabled>
                                    <option value="">Select an author</option>
                                    <?php foreach ($otherAuthors as $otherAuthor): ?>
                                        <option value="<?php echo $otherAuthor['id']; ?>">
                                            <?php echo htmlspecialchars($otherAuthor['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-check mb-3">
                                <input type="radio" id="cancel" name="action" value="cancel" class="form-check-input" checked>
                                <label for="cancel" class="form-check-label">
                                    Cancel deletion
                                </label>
                            </div>

                            <button type="submit" class="btn btn-danger">Confirm</button>
                            <a href="authors.php" class="btn btn-secondary">Back</a>
                        </form>
                    </div>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.querySelectorAll('input[name="action"]').forEach(radio => {
                            radio.addEventListener('change', function() {
                                document.getElementById('new_author_select').disabled = this.value !== 'reassign';
                            });
                        });
                    });
                    </script>
                <?php else: ?>
                    <form action="delete-author.php" method="post">
                        <input type="hidden" name="id" value="<?php echo $authorId; ?>">
                        <input type="hidden" name="action" value="delete_all">
                        <button type="submit" class="btn btn-danger">Delete Author</button>
                        <a href="authors.php" class="btn btn-secondary">Cancel</a>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
