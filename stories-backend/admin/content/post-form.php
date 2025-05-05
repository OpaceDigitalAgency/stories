<?php

// Page variables
$pageTitle = 'Post Form';
$currentPage = 'post-form';

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include header
require_once '../includes/header.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

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
            error_log("Database connection error in post-form.php: " . $e->getMessage());
        }
    }

    // Get table columns
    $stmt = $db->query("SHOW TABLES LIKE '$blogTableName'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM $blogTableName");
        $columns = array_column($stmt->fetchAll(), 'Field');

        // Get column information for validation
        $stmt = $db->query("SHOW COLUMNS FROM $blogTableName");
        $columnInfo = [];
        while ($row = $stmt->fetch()) {
            $columnInfo[$row['Field']] = $row;
        }
    } else {
        $error = "Blog posts table not found. Please check your database setup.";
    }

    // Check for specific columns
    $hasSlugColumn = in_array('slug', $columns);
    $hasAuthorIdColumn = in_array('author_id', $columns);
    $hasExcerptColumn = in_array('excerpt', $columns);

    // Get post if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM $blogTableName WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $post = $stmt->fetch();

        if (!$post) {
            header("Location: blog-posts.php");
            exit;
        }
    }

    // Get authors for dropdown
    $authors = $db->query("SELECT id, name FROM authors ORDER BY name")->fetchAll();

    // Get tags for dropdown
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get post tags if editing
    $postTags = [];
    if ($post) {
        $stmt = $db->prepare("SELECT tag_id FROM $postTagsTableName WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $postTags = array_column($stmt->fetchAll(), 'tag_id');
    }

    // Get additional fields from the database
    $additionalFields = [];
    foreach ($columns as $column) {
        if (!in_array($column, ['id', 'title', 'author_id', 'content', 'excerpt', 'status', 'is_published', 'created_at', 'updated_at'])) {
            $additionalFields[] = $column;
        }
    }

} catch (PDOException $e) {
    error_log("Post form error: " . $e->getMessage());
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
                <h1 class="page-title"><?php echo $post ? 'Edit' : 'Add'; ?> Blog Post</h1>
                <p class="page-description">
                    <a href="blog-posts.php" class="text-primary">← Back to Blog Posts</a>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Post Information</h2>
                <p class="text-muted">Fields marked with <span class="required">*</span> are required</p>
            </div>
            <div class="section-body">
                <form method="POST" action="save-post.php" class="content-form">
                    <?php if ($post): ?>
                        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label class="form-label" for="title">Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>"
                               onkeyup="generateSlug(this.value)">
                    </div>

                    <?php if ($hasSlugColumn): ?>
                    <div class="form-group mb-3">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>"
                               placeholder="post-title-in-lowercase">
                        <small>Use lowercase letters, numbers, and hyphens only. No spaces. Will be auto-generated from title if left empty.</small>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasAuthorIdColumn): ?>
                    <div class="form-group mb-3">
                        <label class="form-label" for="author_id">Author <span class="required">*</span></label>
                        <select id="author_id" name="author_id" class="form-control" required>
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo $author['id']; ?>"
                                        <?php echo isset($post['author_id']) && $post['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($author['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <label class="form-label" for="content">Content <span class="required">*</span></label>
                        <textarea id="content" name="content" class="form-control" rows="10" required><?php
                            echo htmlspecialchars($post['content'] ?? '');
                        ?></textarea>
                    </div>

                    <?php if ($hasExcerptColumn): ?>
                    <div class="form-group mb-3">
                        <label class="form-label" for="excerpt">Excerpt</label>
                        <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php
                            echo htmlspecialchars($post['excerpt'] ?? '');
                        ?></textarea>
                        <small>A short summary of the post. If left empty, it will be auto-generated from the content.</small>
                    </div>
                    <?php endif; ?>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="is_published" name="is_published" value="1" class="form-check-input"
                                   <?php echo (!isset($post['is_published']) || $post['is_published'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Published</label>
                        </div>
                    </div>

                    <?php foreach ($additionalFields as $field): ?>
                        <?php
                        $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                        $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                        $isBooleanField = isset($columnInfo[$field]) && (
                            (strpos($columnInfo[$field]['Type'], 'tinyint(1)') !== false) ||
                            (strpos($field, 'is_') === 0) ||
                            (strpos($field, 'has_') === 0) ||
                            (strpos($field, 'needs_') === 0)
                        );
                        ?>

                        <?php if ($isBooleanField): ?>
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="1" class="form-check-input"
                                       <?php echo (isset($post[$field]) && $post[$field] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="<?php echo $field; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                                    <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="form-group mb-3">
                            <label class="form-label" for="<?php echo $field; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                                <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                            </label>

                            <?php if ($isDateTime): ?>
                                <input type="datetime-local" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                       value="<?php echo isset($post[$field]) ? date('Y-m-d\TH:i', strtotime($post[$field])) : date('Y-m-d\TH:i'); ?>"
                                       <?php echo $isRequired ? 'required' : ''; ?>>
                                <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
                            <?php elseif ($field === 'featured_image' || $field === 'cover_image' || $field === 'image_url' || strpos($field, 'image') !== false): ?>
                                <?php
                                // Render the image upload component for image fields
                                renderImageUploadComponent(
                                    $field,
                                    $post[$field] ?? '',
                                    ucfirst(str_replace('_', ' ', $field)),
                                    'post',
                                    $post['id'] ?? null
                                );
                                ?>
                            <?php else: ?>
                                <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                       value="<?php echo htmlspecialchars($post[$field] ?? ''); ?>"
                                       <?php echo $isRequired ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="form-group mb-3">
                        <label class="form-label">Tags</label>
                        <div class="tag-checkboxes">
                            <?php foreach ($tags as $tag): ?>
                                <div class="form-check">
                                    <input type="checkbox" id="tag_<?php echo $tag['id']; ?>" name="tags[]" value="<?php echo $tag['id']; ?>" class="form-check-input"
                                           <?php echo in_array($tag['id'], $postTags) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tag_<?php echo $tag['id']; ?>">
                                        <?php echo htmlspecialchars($tag['name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Save Post</button>
                        <a href="blog-posts.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($post): ?>
            <div class="content-section mb-4">
                <div class="section-header">
                    <h2 class="section-title">Metadata</h2>
                </div>
                <div class="section-body">
                    <div class="metadata-list">
                        <div class="metadata-item">
                            <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                        </div>
                        <div class="metadata-item">
                            <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($post['updated_at'])); ?>
                        </div>
                        <div class="metadata-item">
                            <strong>ID:</strong> <?php echo $post['id']; ?>
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

        .tag-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }
    </style>

    <script>
        function generateSlug(title) {
            const slugInput = document.getElementById('slug');
            if (slugInput && !slugInput.value) {
                const slug = title.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slugInput.value = slug;
            }
        }
    </script>

    <!-- Include image upload script -->
    <script src="../assets/js/image-upload.js"></script>

<?php require_once '../includes/footer.php'; ?>
