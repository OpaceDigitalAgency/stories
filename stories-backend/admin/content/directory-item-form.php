<?php

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Directory Item' : 'Add Directory Item';
$currentPage = 'directory-item-form';

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
$item = null;
$categories = [];
$error = null;

try {
    // Get all categories
    $stmt = $db->query("SHOW TABLES LIKE 'directory_categories'");
    if ($stmt->rowCount() > 0) {
        $categories = $db->query("SELECT * FROM directory_categories ORDER BY name")->fetchAll();
    }

    // Get directory item if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM directory_items WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $item = $stmt->fetch();

        if (!$item) {
            header("Location: directory.php");
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("Directory item form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="section-body">
        <form method="POST" action="save-directory-item.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $item['id'] ?? ''; ?>">

            <!-- Basic Information -->
            <div class="form-section-title">Basic Information</div>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" required
                       value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control" required
                       value="<?php echo htmlspecialchars($item['slug'] ?? ''); ?>">
                <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="category_id">Category <span class="required">*</span></label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($item['category_id']) && $item['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="form-section-title">Description</div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
            </div>

            <!-- Contact Information -->
            <div class="form-section-title">Contact Information</div>

            <div class="form-group">
                <label class="form-label" for="website_url">Website URL</label>
                <input type="url" id="website_url" name="website_url" class="form-control"
                       value="<?php echo htmlspecialchars($item['website_url'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control"
                       value="<?php echo htmlspecialchars($item['contact_email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_phone">Contact Phone</label>
                <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                       value="<?php echo htmlspecialchars($item['contact_phone'] ?? ''); ?>">
            </div>

            <!-- Location -->
            <div class="form-section-title">Location</div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($item['address'] ?? ''); ?></textarea>
            </div>

            <!-- Image Upload -->
            <div class="form-section-title">Image</div>

            <?php
            // Render image upload component
            renderImageUploadComponent(
                'cover_url',
                $item['cover_url'] ?? '',
                'Cover Image',
                'directory_item',
                $item['id'] ?? null
            );

            // Render AI image generator
            if (function_exists('renderAiImageGenerator')) {
                renderAiImageGenerator(
                    'directory_item',
                    [
                        'title' => $item['title'] ?? '',
                        'description' => $item['description'] ?? ''
                    ],
                    'cover_url',
                    'cover_url_preview'
                );
            }
            ?>

            <!-- Additional Fields -->
            <div class="form-section-title">Additional Information</div>

            <div class="form-group">
                <label class="form-label" for="price_range">Price Range</label>
                <input type="text" id="price_range" name="price_range" class="form-control"
                       value="<?php echo htmlspecialchars($item['price_range'] ?? ''); ?>">
                <small class="form-text text-muted">Example: $10-50, Free, Contact for pricing</small>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="featured" name="featured" class="form-check-input" value="1"
                           <?php echo (!empty($item['featured'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="featured">Featured Item</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Directory Item</button>
                <a href="directory.php" class="btn btn-secondary">Cancel</a>
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

<?php
// Include footer
require_once '../includes/footer.php';
?>
