<?php
/**
 * View Story Page
 *
 * This page displays the details of a story.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include simple_auth.php directly
require_once '../../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
$user = SimpleAuth::check();
if (!$user) {
    // Redirect to login
    header("Location: ../login.php");
    exit;
}

// Include database connection
require_once '../includes/db-connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid story ID.";
    header("Location: stories.php");
    exit;
}

$storyId = (int)$_GET['id'];

try {

    // Get story details
    $stmt = $db->prepare("SELECT * FROM stories WHERE id = ?");
    $stmt->execute([$storyId]);
    $story = $stmt->fetch();

    if (!$story) {
        $_SESSION['error'] = "Story not found.";
        header("Location: stories.php");
        exit;
    }

    // Get author information
    try {
        $stmt = $db->prepare("
            SELECT a.id, a.name
            FROM story_authors sa
            JOIN authors a ON sa.author_id = a.id
            WHERE sa.story_id = ?
        ");
        $stmt->execute([$storyId]);
        $author = $stmt->fetch();

        if ($author) {
            $story['author_id'] = $author['id'];
            $story['author_name'] = $author['name'];
        } else {
            $story['author_name'] = $story['author'] ?? 'Unknown';
        }
    } catch (Exception $e) {
        error_log("Error fetching author for story ID " . $storyId . ": " . $e->getMessage());
        $story['author_name'] = $story['author'] ?? 'Unknown';
    }

    // Get tags for the story
    try {
        $stmt = $db->prepare("
            SELECT GROUP_CONCAT(t.name ORDER BY t.name ASC SEPARATOR ', ') as tags
            FROM story_tags st
            JOIN tags t ON st.tag_id = t.id
            WHERE st.story_id = ?
        ");
        $stmt->execute([$storyId]);
        $tags = $stmt->fetch();

        if ($tags && isset($tags['tags'])) {
            $story['tags'] = $tags['tags'];
        } else {
            $story['tags'] = '';
        }
    } catch (Exception $e) {
        error_log("Error fetching tags for story ID " . $storyId . ": " . $e->getMessage());
        $story['tags'] = '';
    }

} catch (PDOException $e) {
    error_log("View story error: " . $e->getMessage());
    $_SESSION['error'] = "Error loading story. Please try again.";
    header("Location: stories.php");
    exit;
}
// Set page variables for header
$pageTitle = 'View Story';
$currentPage = 'stories';
$pageDescription = '<a href="stories.php" class="text-primary">← Back to Stories</a>';
$pageActions = '
<div class="d-flex gap-2">
    <form method="GET" action="story-form.php">
        <input type="hidden" name="id" value="' . $story['id'] . '">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </button>
    </form>
    <form method="POST" action="delete-story.php" onsubmit="return confirm(\'Are you sure you want to delete this story?\');">
        <input type="hidden" name="id" value="' . $story['id'] . '">
        <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Delete
        </button>
    </form>
</div>
';

// Add custom CSS for content preview
$extraHeadContent = '
<style>
    .content-body {
        max-height: 600px;
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
                <h2 class="section-title"><?php echo htmlspecialchars($story['title']); ?></h2>
            </div>
            <div class="section-body">
                <div class="mb-4">
                    <div class="d-flex gap-3 mb-3">
                        <div>
                            <strong>Author:</strong>
                            <?php echo htmlspecialchars($story['author_name']); ?>
                        </div>
                        <div>
                            <strong>Created:</strong>
                            <?php echo date('M j, Y', strtotime($story['created_at'])); ?>
                        </div>
                        <div>
                            <strong>Updated:</strong>
                            <?php echo date('M j, Y', strtotime($story['updated_at'])); ?>
                        </div>
                    </div>

                    <?php if (!empty($story['tags'])): ?>
                    <div class="mb-3">
                        <strong>Tags:</strong>
                        <?php echo htmlspecialchars($story['tags']); ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    // Check if any additional fields exist and display them
                    $skipFields = ['id', 'title', 'content', 'created_at', 'updated_at', 'author_id', 'author_name', 'tags'];
                    foreach ($story as $key => $value) {
                        if (!in_array($key, $skipFields) && !is_null($value) && $value !== '') {
                            echo '<div class="mb-2"><strong>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ':</strong> ' .
                                 htmlspecialchars($value) . '</div>';
                        }
                    }
                    ?>
                </div>

                <div class="content-preview">
                    <h3 class="mb-3">Content</h3>
                    <div class="content-body p-4 bg-light border rounded">
                        <?php
                        // Check if content might be HTML
                        if (strpos($story['content'], '<') !== false && strpos($story['content'], '>') !== false) {
                            // It might be HTML, so display it as is
                            echo $story['content'];
                        } else {
                            // It's plain text, so preserve line breaks
                            echo nl2br(htmlspecialchars($story['content']));
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="stories.php" class="btn btn-secondary">
                Back to Stories
            </a>
            <form method="GET" action="story-form.php">
                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Story
                </button>
            </form>
        </div>

<?php
// Include footer
include_once '../includes/footer.php';
?>