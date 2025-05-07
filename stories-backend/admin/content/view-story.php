<?php
/**
 * View Story Page
 *
 * This page displays the details of a story.
 */

// Include auth check
require_once '../includes/auth-check.php';

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

// Get frontend URL from config or use default
$frontendBaseUrl = '';

// Try to get from config.php if it exists
if (file_exists('../includes/config.php')) {
    include_once '../includes/config.php';
    if (function_exists('get_config')) {
        $frontendBaseUrl = get_config('site.url', '');
    }
}

// If not found in config, use default based on environment
if (empty($frontendBaseUrl)) {
    $host = $_SERVER['HTTP_HOST'];
    if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
        $frontendBaseUrl = 'http://localhost:3000';
    } else if (strpos($host, 'staging') !== false || strpos($host, 'test') !== false) {
        $frontendBaseUrl = 'https://staging.storiesfromtheweb.org';
    } else {
        $frontendBaseUrl = 'https://storiesfromtheweb.netlify.app';
    }
}

// Construct the frontend URL
$frontendUrl = $frontendBaseUrl . '/stories/' . $story['slug'];

// Add custom CSS and JS for preview
$extraHeadContent = '
<!-- Add Story Preview CSS and JS -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<script src="../assets/js/story-preview.js"></script>
<script>
    // Set the frontend base URL
    window.FRONTEND_BASE_URL = "' . $frontendBaseUrl . '";

    // Auto-open the preview when the page loads
    document.addEventListener("DOMContentLoaded", function() {
        // Wait a moment for the StoryPreview class to initialize
        setTimeout(function() {
            if (window.storyPreview) {
                window.storyPreview.loadStoryPreview("' . $storyId . '");
            }
        }, 500);
    });
</script>
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
            <h3 class="mb-3">Story Preview</h3>
            <div class="alert alert-info">
                <p><i class="fas fa-info-circle"></i> Loading story preview... If the preview doesn't open automatically, click the button below.</p>
                <button class="btn btn-primary story-preview-btn" data-story-id="<?php echo $storyId; ?>">
                    <i class="fas fa-external-link-alt"></i> Open Story Preview
                </button>
            </div>

            <div class="mt-4">
                <p>You can view this story on the frontend at:</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($frontendUrl); ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="window.open('<?php echo htmlspecialchars($frontendUrl); ?>', '_blank')">
                        <i class="fas fa-external-link-alt"></i> Open
                    </button>
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($frontendUrl); ?>').then(() => alert('URL copied to clipboard!'))">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
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