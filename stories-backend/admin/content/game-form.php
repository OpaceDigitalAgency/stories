<?php

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Game' : 'Add Game';
$currentPage = 'game-form';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

// Include header
require_once '../includes/header.php';

// Initialize variables
$game = null;
$error = null;

try {
    // Get game if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $game = $stmt->fetch();

        if (!$game) {
            header("Location: games.php");
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("Game form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="section-body">
        <form method="POST" action="save-game.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $game['id'] ?? ''; ?>">

            <!-- Basic Information -->
            <div class="form-section-title">Basic Information</div>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" required
                       value="<?php echo htmlspecialchars($game['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control" required
                       value="<?php echo htmlspecialchars($game['slug'] ?? ''); ?>">
                <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($game['description'] ?? ''); ?></textarea>
            </div>

            <!-- Game Details -->
            <div class="form-section-title">Game Details</div>

            <div class="form-group">
                <label class="form-label" for="website_url">Game URL <span class="required">*</span></label>
                <input type="url" id="website_url" name="website_url" class="form-control" required
                       value="<?php echo htmlspecialchars($game['website_url'] ?? ''); ?>">
            </div>

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

            <div class="form-group">
                <label class="form-label" for="price">Price</label>
                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0"
                       value="<?php echo htmlspecialchars($game['price'] ?? '0.00'); ?>">
                <small class="form-text text-muted">Enter 0 for free games</small>
            </div>

            <!-- Image Upload -->
            <div class="form-section-title">Game Image</div>

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

            <!-- Additional Fields -->
            <div class="form-section-title">Additional Information</div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="featured" name="featured" class="form-check-input" value="1"
                           <?php echo (!empty($game['featured'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="featured">Featured Game</label>
                </div>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="is_published" name="is_published" class="form-check-input" value="1"
                           <?php echo (!empty($game['is_published'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_published">Published</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Game</button>
                <a href="games.php" class="btn btn-secondary">Cancel</a>
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

<?php
// Include footer
require_once '../includes/footer.php';
?>
