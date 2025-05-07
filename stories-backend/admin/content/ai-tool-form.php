<?php

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit AI Tool' : 'Add AI Tool';
$currentPage = 'ai-tools';
$pageDescription = 'Add or edit AI tool information';

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
$tool = null;
$categories = [];
$error = null;

try {
    // Get all categories
    $stmt = $db->query("SHOW TABLES LIKE 'ai_tool_categories'");
    if ($stmt->rowCount() > 0) {
        $categories = $db->query("SELECT * FROM ai_tool_categories ORDER BY name")->fetchAll();
    }

    // Get AI tool if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM ai_tools WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $tool = $stmt->fetch();

        if (!$tool) {
            header("Location: ai-tools.php");
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("AI tool form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="section-body">
        <form method="POST" action="save-ai-tool.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $tool['id'] ?? ''; ?>">

            <!-- Basic Information -->
            <div class="form-section-title">Basic Information</div>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" required
                       value="<?php echo htmlspecialchars($tool['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control" required
                       value="<?php echo htmlspecialchars($tool['slug'] ?? ''); ?>">
                <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="category_id">Category <span class="required">*</span></label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($tool['category_id']) && $tool['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Description -->
            <div class="form-section-title">Description</div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($tool['description'] ?? ''); ?></textarea>
            </div>

            <!-- Tool Details -->
            <div class="form-section-title">Tool Details</div>

            <div class="form-group">
                <label class="form-label" for="tool_url">Tool URL <span class="required">*</span></label>
                <input type="url" id="tool_url" name="tool_url" class="form-control" required
                       value="<?php echo htmlspecialchars($tool['tool_url'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="pricing_type">Pricing Type <span class="required">*</span></label>
                <select id="pricing_type" name="pricing_type" class="form-control" required>
                    <option value="free" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] === 'free') ? 'selected' : ''; ?>>Free</option>
                    <option value="freemium" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] === 'freemium') ? 'selected' : ''; ?>>Freemium</option>
                    <option value="paid" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="subscription" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] === 'subscription') ? 'selected' : ''; ?>>Subscription</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="price_info">Pricing Information</label>
                <input type="text" id="price_info" name="price_info" class="form-control"
                       value="<?php echo htmlspecialchars($tool['price_info'] ?? ''); ?>">
                <small class="form-text text-muted">Example: Free tier available, Plans start at $10/month</small>
            </div>

            <!-- Image Upload -->
            <div class="form-section-title">Image</div>

            <?php
            // Render image upload component
            renderImageUploadComponent(
                'cover_url',
                $tool['cover_url'] ?? '',
                'Tool Image',
                'ai_tool',
                $tool['id'] ?? null
            );

            // Render AI image generator
            if (function_exists('renderAiImageGenerator')) {
                renderAiImageGenerator(
                    'ai_tool',
                    [
                        'title' => $tool['title'] ?? '',
                        'description' => $tool['description'] ?? ''
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
                           <?php echo (!empty($tool['featured'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="featured">Featured Tool</label>
                </div>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="is_published" name="is_published" class="form-check-input" value="1"
                           <?php echo (!empty($tool['is_published'])) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_published">Published</label>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save AI Tool</button>
                <a href="ai-tools.php" class="btn btn-secondary">Cancel</a>
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
