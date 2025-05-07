<?php
/**
 * Game Form Page
 *
 * This page displays a form for adding or editing a game.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

try {
    // Initialize variables
    $game = null;
    $error = null;

    // Get game if editing
    if (isset($_GET['id'])) {
        try {
            $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $game = $stmt->fetch();

            if (!$game) {
                header("Location: games.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Error loading game: " . $e->getMessage());
            header("Location: games.php");
            exit;
        }
    }
} catch (PDOException $e) {
    error_log("Game form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Game' : 'Add Game';
$currentPage = 'games';

// Add custom CSS and JS for form styling and preview
$extraHeadContent = '
<!-- Loading overlay styles -->
<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        color: white;
    }

    .loading-overlay.active {
        display: flex;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
        margin-bottom: 15px;
    }

    .loading-message {
        font-size: 18px;
        text-align: center;
        max-width: 80%;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<style>
    /* Base form styles */
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .form-section-title {
        margin-top: 20px;
        margin-bottom: 10px;
        font-size: 1.25rem;
        color: var(--gray-800);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 5px;
    }

    .checkbox-section {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
        background-color: var(--gray-50);
        padding: 15px;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
    }

    .checkbox-group-item {
        margin-bottom: 0;
    }

    .content-form {
        background: white;
        padding: 20px;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
    }

    /* WordPress-like layout */
    .wp-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    @media (min-width: 992px) {
        .wp-layout {
            grid-template-columns: 2fr 1fr;
        }
    }

    .wp-layout-top {
        grid-column: 1 / -1;
        margin-bottom: 20px;
    }

    .wp-layout-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .wp-layout-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .wp-card {
        background: white;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .wp-card-header {
        padding: 12px 15px;
        border-bottom: 1px solid var(--border-color);
        background-color: var(--gray-50);
        font-weight: 600;
        color: var(--gray-800);
    }

    .wp-card-body {
        padding: 15px;
    }

    /* Sticky save bar */
    .sticky-save-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 15px 20px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        border-top: 1px solid var(--border-color);
    }

    /* Add padding to the bottom of the form to prevent content from being hidden behind the sticky bar */
    .content-form {
        padding-bottom: 70px;
    }

    .sticky-save-bar .btn-group {
        display: flex;
        gap: 10px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sticky-save-bar {
            flex-direction: column;
            gap: 10px;
        }

        .sticky-save-bar .btn-group {
            width: 100%;
        }

        .sticky-save-bar .btn {
            flex: 1;
        }
    }
</style>
';

// Include header
require_once '../includes/header.php';

?>

<div class="content-section mb-4">
    <div class="section-body">
        <form method="POST" action="save-game.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $game['id'] ?? ''; ?>">

            <!-- WordPress-like Layout -->
            <div class="wp-layout">
                <!-- Top Section (Title & Slug) -->
                <div class="wp-layout-top wp-card">
                    <div class="wp-card-header">
                        Basic Information
                    </div>
                    <div class="wp-card-body">
                        <div class="form-group">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <label class="form-label mb-md-0" for="title">Title <span class="required">*</span></label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" id="title" name="title" class="form-control" required
                                        value="<?php echo htmlspecialchars($game['title'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <label class="form-label mb-md-0" for="slug">Slug <span class="required">*</span></label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" id="slug" name="slug" class="form-control" required
                                        value="<?php echo htmlspecialchars($game['slug'] ?? ''); ?>">
                                    <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Column -->
                <div class="wp-layout-main">
                    <!-- Image Upload -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Game Image
                        </div>
                        <div class="wp-card-body">
                            <?php
                            // Render image upload component
                            renderImageUploadComponent(
                                'cover_url',
                                $game['cover_url'] ?? '',
                                'Game Image',
                                'game',
                                $game['id'] ?? null
                            );

                            // Render AI image generator
                            if (function_exists('renderAiImageGenerator')) {
                                renderAiImageGenerator(
                                    'game',
                                    [
                                        'title' => $game['title'] ?? '',
                                        'description' => $game['description'] ?? '',
                                        'genre' => $game['genre'] ?? '',
                                        'platform' => $game['platform'] ?? ''
                                    ],
                                    'cover_url',
                                    'cover_url_preview'
                                );
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Game Description -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Game Description
                        </div>
                        <div class="wp-card-body">
                            <div class="form-group mb-0">
                                <textarea id="description" name="description" class="form-control" rows="8" required><?php echo htmlspecialchars($game['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Game URL -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Game URL
                        </div>
                        <div class="wp-card-body">
                            <div class="form-group mb-0">
                                <input type="url" id="website_url" name="website_url" class="form-control" required
                                    value="<?php echo htmlspecialchars($game['website_url'] ?? ''); ?>"
                                    placeholder="https://example.com/game">
                                <small class="form-text text-muted">The URL where users can play or download the game</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div class="wp-layout-sidebar">
                    <!-- Game Settings -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Game Settings
                        </div>
                        <div class="wp-card-body">
                            <!-- Published Status -->
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="is_published" name="is_published" class="form-check-input"
                                        value="1" <?php echo (!empty($game['is_published'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_published">Published</label>
                                </div>
                            </div>

                            <!-- Featured Status -->
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="featured" name="featured" class="form-check-input"
                                        value="1" <?php echo (!empty($game['featured'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="featured">Featured Game</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Details -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Game Details
                        </div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <label class="form-label" for="genre">Genre</label>
                                <input type="text" id="genre" name="genre" class="form-control"
                                    value="<?php echo htmlspecialchars($game['genre'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="platform">Platform</label>
                                <input type="text" id="platform" name="platform" class="form-control"
                                    value="<?php echo htmlspecialchars($game['platform'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="developer">Developer</label>
                                <input type="text" id="developer" name="developer" class="form-control"
                                    value="<?php echo htmlspecialchars($game['developer'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="publisher">Publisher</label>
                                <input type="text" id="publisher" name="publisher" class="form-control"
                                    value="<?php echo htmlspecialchars($game['publisher'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="release_date">Release Date</label>
                                <input type="date" id="release_date" name="release_date" class="form-control"
                                    value="<?php echo htmlspecialchars($game['release_date'] ?? ''); ?>">
                            </div>

                            <div class="form-group mb-0">
                                <label class="form-label" for="price">Price</label>
                                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0"
                                    value="<?php echo htmlspecialchars($game['price'] ?? '0.00'); ?>">
                                <small class="form-text text-muted">Enter 0 for free games</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky-save-bar">
                <div class="game-status">
                    <?php if (isset($game['id'])): ?>
                    <span class="text-muted">Last updated: <?php echo date('M j, Y g:i a', strtotime($game['updated_at'] ?? 'now')); ?></span>
                    <?php else: ?>
                    <span class="text-muted">Creating new game</span>
                    <?php endif; ?>
                </div>
                <div class="btn-group">
                    <a href="games.php" class="btn btn-secondary">Cancel</a>
                    <button type="button" id="preview-game" class="btn btn-info">Preview</button>
                    <button type="submit" class="btn btn-primary">Save Game</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-generate slug from title
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');

        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function() {
                // Only auto-generate if slug is empty or hasn't been manually edited
                if (!slugInput.value || slugInput._autoGenerated) {
                    const slug = titleInput.value
                        .toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove special characters
                        .replace(/\s+/g, '-')     // Replace spaces with hyphens
                        .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen

                    slugInput.value = slug;
                    slugInput._autoGenerated = true;
                }
            });

            // Mark when user manually edits the slug
            slugInput.addEventListener('input', function() {
                slugInput._autoGenerated = false;
            });
        }
    });
</script>

<!-- Include image upload script -->
<script src="../assets/js/image-upload.js"></script>

<!-- Include game preview script -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<script src="../assets/js/game-preview.js"></script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
