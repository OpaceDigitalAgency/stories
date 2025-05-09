<?php
/**
 * View Author Page
 *
 * This page displays the details of an author.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid author ID.";
    header("Location: authors.php");
    exit;
}

$authorId = (int)$_GET['id'];

try {

    // Get author details
    $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);
    $author = $stmt->fetch();

    if (!$author) {
        $_SESSION['error'] = "Author not found.";
        header("Location: authors.php");
        exit;
    }

    // Get stories by this author
    try {
        $stmt = $db->prepare("
            SELECT s.id, s.title, s.created_at
            FROM story_authors sa
            JOIN stories s ON sa.story_id = s.id
            WHERE sa.author_id = ?
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$authorId]);
        $stories = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching stories for author ID " . $authorId . ": " . $e->getMessage());
        $stories = [];
    }

} catch (PDOException $e) {
    error_log("View author error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading author. Please try again.";
    header("Location: authors.php");
    exit;
}
// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Author' : 'Add Author';
$currentPage = 'authors';

// Include header
require_once '../includes/header.php';

?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($author['name']); ?></h1>
                <p class="page-description">
                    <a href="authors.php" class="text-primary">← Back to Authors</a>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="author-form.php?id=<?php echo $author['id']; ?>" class="btn btn-primary">
                    <span class="icon-edit"></span> Edit
                </a>
                <form method="POST" action="delete-author.php" onsubmit="return confirm('Are you sure you want to delete this author?');">
                    <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                    <button type="submit" class="btn btn-danger">
                        <span class="icon-delete"></span> Delete
                    </button>
                </form>
            </div>
        </div>

// Add custom CSS for content preview
$extraHeadContent = '
<style>
    .content-body {
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
    }

    .bg-light {
        background-color: var(--gray-50);
    }

    .border {
        border: 1px solid var(--border-color);
    }

    .rounded {
        border-radius: var(--radius-md);
    }

    .p-4 {
        padding: 1.5rem;
    }
</style>
';

// Include header
require_once '../includes/header.php';
?>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><?php echo htmlspecialchars($author['name']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="d-flex gap-3 mb-3">
                        <?php if (isset($author['created_at'])): ?>
                        <div>
                            <strong>Created:</strong>
                            <?php echo date('M j, Y', strtotime($author['created_at'])); ?>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($author['updated_at'])): ?>
                        <div>
                            <strong>Updated:</strong>
                            <?php echo date('M j, Y', strtotime($author['updated_at'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($author['email']) && !empty($author['email'])): ?>
                    <div class="mb-3">
                        <strong>Email:</strong>
                        <?php echo htmlspecialchars($author['email']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($author['website']) && !empty($author['website'])): ?>
                    <div class="mb-3">
                        <strong>Website:</strong>
                        <a href="<?php echo htmlspecialchars($author['website']); ?>" target="_blank">
                            <?php echo htmlspecialchars($author['website']); ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php
                    // Check if any additional fields exist and display them
                    $skipFields = ['id', 'name', 'email', 'website', 'bio', 'created_at', 'updated_at'];
                    foreach ($author as $key => $value) {
                        if (!in_array($key, $skipFields) && !is_null($value) && $value !== '') {
                            echo '<div class="mb-2"><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' .
                                 htmlspecialchars($value) . '</div>';
                        }
                    }
                    ?>
                </div>

                <?php if (isset($author['bio']) && !empty($author['bio'])): ?>
                <div class="content-preview mb-4">
                    <h3 class="mb-3">Biography</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php
                        // Check if bio might be HTML
                        if (strpos($author['bio'], '<') !== false && strpos($author['bio'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $author['bio'];
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($author['bio']));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="content-preview">
                    <h3 class="mb-3">Stories by this Author</h3>
                    <?php if (empty($stories)): ?>
                        <p>No stories found for this author.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stories as $story): ?>
                                        <tr>
                                            <td><?php echo $story['id']; ?></td>
                                            <td><?php echo htmlspecialchars($story['title']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($story['created_at'])); ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <button type="button" class="btn btn-info btn-sm story-preview-btn" data-story-id="<?php echo $story['id']; ?>">
                                                        <i class="fas fa-eye"></i> Preview
                                                    </button>
                                                    <form method="GET" action="story-form.php" style="display: inline;">
                                                        <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="authors.php" class="btn btn-secondary">
                Back to Authors
            </a>
            <form method="GET" action="author-form.php">
                <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Author
                </button>
            </form>
        </div>

<!-- Include preview JS files -->
<script src="../assets/js/story-preview.js"></script>

<script>
// Initialize preview buttons when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Story preview buttons
    document.querySelectorAll('.story-preview-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const storyId = this.dataset.storyId;
            if (window.storyPreview) {
                window.storyPreview.loadStoryPreview(storyId);
            }
        });
    });
});
</script>

<?php
// Include footer
include_once '../includes/footer.php';
?>