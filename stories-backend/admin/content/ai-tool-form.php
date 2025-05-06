<?php

// Page variables
$pageTitle = 'AI Tool Form';
$currentPage = 'ai-tools';
$pageDescription = 'Add or edit AI tool information';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

// Initialize variables
$tool = null;
$categories = [];
$error = null;

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
            error_log("Database connection error in ai-tool-form.php: " . $e->getMessage());
        }
    }

    // Get all categories
    $stmt = $db->query("SHOW TABLES LIKE 'ai_tool_categories'");
    if ($stmt->rowCount() > 0) {
        $categories = $db->query("SELECT * FROM ai_tool_categories ORDER BY name")->fetchAll();
    }

    // Get tool if editing
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

// Check for error messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo $tool ? 'Edit' : 'Add'; ?> AI Tool</h1>
                <p class="page-description">
                    <a href="ai-tools.php" class="text-primary">← Back to AI Tools</a>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Tool Information</h2>
                <p class="text-muted">Fields marked with <span class="required">*</span> are required</p>
            </div>
            <div class="section-body">
                <form method="POST" action="save-ai-tool.php" class="content-form">
                    <?php if ($tool): ?>
                        <input type="hidden" name="id" value="<?php echo $tool['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label class="form-label" for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($tool['title'] ?? ''); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($tool['slug'] ?? ''); ?>">
                        <small>URL-friendly version of the title. Will be auto-generated if left empty.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                        <?php echo (isset($tool['category_id']) && $tool['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5"><?php
                            echo htmlspecialchars($tool['description'] ?? '');
                        ?></textarea>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="tool_url">Tool URL</label>
                        <input type="url" id="tool_url" name="tool_url" class="form-control"
                               value="<?php echo htmlspecialchars($tool['tool_url'] ?? ''); ?>">
                    </div>

                    <?php
                    // Render the image upload component for tool image
                    renderImageUploadComponent(
                        'cover_url',
                        $tool['cover_url'] ?? '',
                        'Tool Image',
                        'ai_tool',
                        $tool['id'] ?? null
                    );

                    // Add AI image generator button
                    echo '<div class="mt-2">';
                    renderAiImageGenerator(
                        'ai_tool',
                        [
                            'title' => $tool['title'] ?? '',
                            'description' => $tool['description'] ?? '',
                            'features' => $tool['features'] ?? ''
                        ],
                        'cover_url',
                        'cover_url-preview'
                    );
                    echo '</div>';
                    ?>

                    <div class="form-group mb-3">
                        <label class="form-label" for="pricing_type">Pricing Type</label>
                        <select id="pricing_type" name="pricing_type" class="form-control">
                            <option value="free" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] == 'free') ? 'selected' : ''; ?>>Free</option>
                            <option value="freemium" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] == 'freemium') ? 'selected' : ''; ?>>Freemium</option>
                            <option value="paid" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                            <option value="subscription" <?php echo (isset($tool['pricing_type']) && $tool['pricing_type'] == 'subscription') ? 'selected' : ''; ?>>Subscription</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="price_info">Price Information</label>
                        <input type="text" id="price_info" name="price_info" class="form-control"
                               value="<?php echo htmlspecialchars($tool['price_info'] ?? ''); ?>">
                        <small>E.g., "Free trial, $9.99/month" or "Starting at $19.99"</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="features">Features</label>
                        <textarea id="features" name="features" class="form-control" rows="5"><?php
                            echo htmlspecialchars($tool['features'] ?? '');
                        ?></textarea>
                        <small>List key features, one per line</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="rating">Rating (0-5)</label>
                        <input type="number" id="rating" name="rating" class="form-control" min="0" max="5" step="0.1"
                               value="<?php echo htmlspecialchars($tool['rating'] ?? '0'); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="featured" name="featured" value="1" class="form-check-input"
                                   <?php echo (isset($tool['featured']) && $tool['featured'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="featured">Featured</label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="is_published" name="is_published" value="1" class="form-check-input"
                                   <?php echo (!isset($tool['is_published']) || $tool['is_published'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Published</label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="published_at">Published at</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="form-control"
                               value="<?php echo isset($tool['published_at']) ? date('Y-m-d\TH:i', strtotime($tool['published_at'])) : date('Y-m-d\TH:i'); ?>">
                        <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><?php echo $tool ? 'Update' : 'Add'; ?> AI Tool</button>
                        <a href="ai-tools.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($tool): ?>
            <div class="content-section mb-4">
                <div class="section-header">
                    <h2 class="section-title">Metadata</h2>
                </div>
                <div class="section-body">
                    <div class="metadata-list">
                        <div class="metadata-item">
                            <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($tool['created_at'])); ?>
                        </div>
                        <div class="metadata-item">
                            <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($tool['updated_at'])); ?>
                        </div>
                        <div class="metadata-item">
                            <strong>ID:</strong> <?php echo $tool['id']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .metadata-list {
        background-color: var(--gray-50);
        border-radius: var(--radius-md);
        padding: 1rem;
    }

    .metadata-item {
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--gray-200);
    }

    .metadata-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-check-input {
        margin-top: 0;
    }

    .text-muted {
        color: var(--gray-600);
        font-size: 0.875rem;
    }
</style>

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

<?php require_once '../includes/footer.php'; ?>
