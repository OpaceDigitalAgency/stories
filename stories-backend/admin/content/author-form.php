<?php

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Author' : 'Add Author';
$currentPage = 'author-form';

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
$author = null;
$error = null;

try {
    // Get author if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $author = $stmt->fetch();

        if (!$author) {
            header("Location: authors.php");
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("Author form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="section-body">
        <form method="POST" action="save-author.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $author['id'] ?? ''; ?>">

            <!-- Basic Information -->
            <div class="form-section-title">Basic Information</div>

            <div class="form-group">
                <label class="form-label" for="name">Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-control" required
                       value="<?php echo htmlspecialchars($author['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control" required
                       value="<?php echo htmlspecialchars($author['slug'] ?? ''); ?>">
                <small class="form-text text-muted">URL-friendly version of the name (auto-generated if left empty)</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?php echo htmlspecialchars($author['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="age">Age</label>
                <input type="number" id="age" name="age" class="form-control" min="0" max="120"
                       value="<?php echo htmlspecialchars($author['age'] ?? ''); ?>">
            </div>

            <!-- Author Type -->
            <div class="form-section-title">Author Type</div>

            <div class="form-group">
                <label class="form-label" for="author_type">Author Type <span class="required">*</span></label>
                <select id="author_type" name="author_type" class="form-control" required>
                    <option value="child" <?php echo (isset($author['author_type']) && $author['author_type'] === 'child') ? 'selected' : ''; ?>>Child</option>
                    <option value="parent" <?php echo (isset($author['author_type']) && $author['author_type'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                    <option value="educator" <?php echo (isset($author['author_type']) && $author['author_type'] === 'educator') ? 'selected' : ''; ?>>Educator</option>
                    <option value="retail" <?php echo (isset($author['author_type']) && $author['author_type'] === 'retail') ? 'selected' : ''; ?>>Retail</option>
                </select>
            </div>

            <!-- Bio -->
            <div class="form-section-title">Biography</div>

            <div class="form-group">
                <label class="form-label" for="bio">Biography</label>
                <textarea id="bio" name="bio" class="form-control" rows="5"><?php echo htmlspecialchars($author['bio'] ?? ''); ?></textarea>
            </div>

            <!-- Profile Picture -->
            <div class="form-section-title">Image</div>

            <?php
            // Render image upload component
            renderImageUploadComponent(
                'avatar_url',
                $author['avatar_url'] ?? '',
                'Profile Picture',
                'author',
                $author['id'] ?? null
            );

            // Render AI image generator
            if (function_exists('renderAiImageGenerator')) {
                renderAiImageGenerator(
                    'author',
                    [
                        'name' => $author['name'] ?? '',
                        'bio' => $author['bio'] ?? '',
                        'author_type' => $author['author_type'] ?? '',
                        'age' => $author['age'] ?? ''
                    ],
                    'avatar_url',
                    'avatar_url_preview'
                );
            }
            ?>

            <!-- Social Media -->
            <div class="form-section-title">Social Media</div>

            <div class="form-group">
                <label class="form-label" for="website">Website</label>
                <input type="url" id="website" name="website" class="form-control"
                       value="<?php echo htmlspecialchars($author['website'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="twitter">Twitter</label>
                <input type="text" id="twitter" name="twitter" class="form-control"
                       value="<?php echo htmlspecialchars($author['twitter'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="facebook">Facebook</label>
                <input type="text" id="facebook" name="facebook" class="form-control"
                       value="<?php echo htmlspecialchars($author['facebook'] ?? ''); ?>">
            </div>

            <!-- Additional Fields -->
            <div class="form-section-title">Additional Information</div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="featured" name="featured" class="form-check-input" value="1"
                           <?php echo (!empty($author['featured'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="featured">Featured Author</label>
                </div>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="is_published" name="is_published" class="form-check-input" value="1"
                           <?php echo (!empty($author['is_published'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_published">Published</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Author</button>
                <a href="authors.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-generate slug from name
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');

        if (nameInput && slugInput) {
            nameInput.addEventListener('input', function() {
                // Only auto-generate if slug is empty or hasn't been manually edited
                if (!slugInput.value || slugInput._autoGenerated) {
                    const slug = nameInput.value
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

<?php
// Include footer
require_once '../includes/footer.php';
?>
