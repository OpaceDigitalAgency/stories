<?php

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Blog Post' : 'Add Blog Post';
$currentPage = 'post-form';

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
$post = null;
$authors = [];
$tags = [];
$postTags = [];
$error = null;
$additionalFields = [];
$columns = [];
$columnInfo = [];

// Determine blog table name
$blogTableName = 'blog_posts';
$postTagsTableName = 'post_tags';

try {
    // Get post if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM $blogTableName WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $post = $stmt->fetch();

        if (!$post) {
            header("Location: posts.php");
            exit;
        }

        // Get post tags
        $stmt = $db->prepare("SELECT tag_id FROM $postTagsTableName WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $postTags = array_column($stmt->fetchAll(), 'tag_id');
    }

    // Get tags for dropdown
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get table column information for dynamic form fields
    $stmt = $db->prepare("DESCRIBE $blogTableName");
    $stmt->execute();
    $columns = $stmt->fetchAll();

    // Organize column info for easier access
    foreach ($columns as $column) {
        $columnInfo[$column['Field']] = $column;

        // Skip standard fields that are handled explicitly
        if (!in_array($column['Field'], ['id', 'title', 'content', 'created_at', 'updated_at', 'cover_url'])) {
            $additionalFields[] = $column['Field'];
        }
    }

} catch (PDOException $e) {
    error_log("Post form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="section-body">
        <form method="POST" action="save-post.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $post['id'] ?? ''; ?>">

            <!-- Basic Information -->
            <div class="form-section-title">Basic Information</div>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control" required
                       value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control" required
                       value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>">
                <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
            </div>

            <!-- Content -->
            <div class="form-section-title">Content</div>

            <div class="form-group">
                <label class="form-label" for="content">Post Content <span class="required">*</span></label>
                <textarea id="content" name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
            </div>

            <!-- Image Upload -->
            <div class="form-section-title">Cover URL</div>

            <?php
            // Render image upload component
            renderImageUploadComponent(
                'cover_url',
                $post['cover_url'] ?? '',
                'Cover URL',
                'post',
                $post['id'] ?? null
            );

            // Render AI image generator
            if (function_exists('renderAiImageGenerator')) {
                renderAiImageGenerator(
                    'post',
                    [
                        'title' => $post['title'] ?? '',
                        'excerpt' => $post['excerpt'] ?? '',
                        'content' => $post['content'] ?? ''
                    ],
                    'cover_url',
                    'cover_url_preview'
                );
            }
            ?>

            <!-- Additional Fields -->
            <div class="form-section-title">Additional Information</div>

            <?php foreach ($additionalFields as $field):
                $columnData = $columnInfo[$field];
                $isRequired = strpos($columnData['Type'], 'NOT NULL') !== false;
                $isIntField = strpos($columnData['Type'], 'int') === 0;
                $isDecimalField = strpos($columnData['Type'], 'decimal') === 0;
                $label = ucwords(str_replace('_', ' ', $field));
            ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                    <input type="<?php echo $isIntField || $isDecimalField ? 'number' : 'text'; ?>"
                           id="<?php echo $field; ?>"
                           name="<?php echo $field; ?>"
                           class="form-control"
                           value="<?php echo htmlspecialchars($post[$field] ?? ''); ?>"
                           <?php echo $isDecimalField ? 'step="0.01"' : ''; ?>
                           <?php echo $isRequired ? 'required' : ''; ?>>
                </div>
            <?php endforeach; ?>

            <!-- Tags -->
            <div class="form-group">
                <label class="form-label">Tags</label>
                <div class="checkbox-group">
                    <?php foreach ($tags as $tag): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>"
                                   <?php echo in_array($tag['id'], $postTags) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Post</button>
                <a href="posts.php" class="btn btn-secondary">Cancel</a>
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
