<?php
/**
 * Blog Post Form Page
 *
 * This page displays a form for adding or editing a blog post.
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

    // Get post if editing
    if (isset($_GET['id'])) {
        try {
            $stmt = $db->prepare("SELECT * FROM $blogTableName WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $post = $stmt->fetch();

            if (!$post) {
                header("Location: blog-posts.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Error loading post: " . $e->getMessage());
            header("Location: blog-posts.php");
            exit;
        }
    }

    // Get authors for dropdown
    $authors = $db->query("SELECT id, name, author_type FROM authors ORDER BY name")->fetchAll();

    // Get tags for dropdown
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get post tags if editing
    if ($post) {
        $stmt = $db->prepare("SELECT tag_id FROM $postTagsTableName WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $postTags = array_column($stmt->fetchAll(), 'tag_id');
    }

    // Get table column information for dynamic form fields
    $stmt = $db->prepare("DESCRIBE $blogTableName");
    $stmt->execute();
    $columns = $stmt->fetchAll();

    // Organize column info for easier access
    $columnInfo = [];
    $additionalFields = [];

    foreach ($columns as $column) {
        $columnInfo[$column['Field']] = $column;

        // Skip standard fields that are handled explicitly
        if (!in_array($column['Field'], ['id', 'title', 'content', 'created_at', 'updated_at', 'cover_url', 'featured_image', 'slug', 'excerpt', 'author_id'])) {
            $additionalFields[] = $column['Field'];
        }
    }

    // Make sure is_published is treated as a boolean field
    if (isset($columnInfo['is_published'])) {
        $columnInfo['is_published']['Type'] = 'tinyint(1)';
    }

} catch (PDOException $e) {
    error_log("Post form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Blog Post' : 'Add Blog Post';
$currentPage = 'blog-posts';

// Add custom CSS and JS for form styling and rich text editor
$extraHeadContent = '
<!-- Include CKEditor and custom upload adapter -->
<script src="../assets/js/ckeditor.js"></script>
<script src="../assets/js/ckeditor-upload-adapter.js"></script>
<script src="../assets/js/simple-source-editing.js"></script>

<!-- Fallback to load CKEditor from CDN if local file fails -->
<script>
    // Check if CKEditor is loaded after a short delay
    setTimeout(function() {
        if (typeof ClassicEditor === "undefined") {
            console.log("Loading CKEditor from CDN as fallback...");
            var script = document.createElement("script");
            script.src = "https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js";
            script.onload = function() {
                console.log("CKEditor loaded from CDN successfully");
                // Trigger the initialization
                var event = new Event("DOMContentLoaded");
                document.dispatchEvent(event);
            };
            document.head.appendChild(script);
        }
    }, 500);
</script>

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
        <form method="POST" action="save-post.php" class="content-form" id="post-form">
            <input type="hidden" name="id" value="<?php echo $post['id'] ?? ''; ?>">
            <!-- Add a hidden field to track if the image was updated via AJAX -->
            <input type="hidden" name="image_updated" value="0" id="image_updated_field">

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
                                        value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>">
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
                                        value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>">
                                    <small class="form-text text-muted">URL-friendly version of the title (auto-generated if left empty)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Column -->
                <div class="wp-layout-main">
                    <!-- Post Content -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Post Content
                        </div>
                        <div class="wp-card-body">
                            <!-- Summary/Excerpt Field -->
                            <div class="form-group">
                                <label for="excerpt">Summary/Excerpt</label>
                                <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Image Upload -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Cover Image
                        </div>
                        <div class="wp-card-body">
                            <?php
                            // Render image upload component
                            renderImageUploadComponent(
                                'cover_url',
                                $post['cover_url'] ?? '',
                                'Cover Image',
                                'post',
                                $post['id'] ?? null
                            );

                            // Render AI image generator
                            if (function_exists('renderAiImageGenerator')) {
                                renderAiImageGenerator(
                                    'post',
                                    [
                                        'title' => $post['title'] ?? '',
                                        'summary' => $post['excerpt'] ?? '', // Use summary instead of excerpt
                                        'excerpt' => $post['excerpt'] ?? '', // Also include excerpt for compatibility
                                        'content' => $post['content'] ?? ''
                                    ],
                                    'cover_url',
                                    'cover_url_preview'
                                );
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Post Text -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Post Text
                        </div>
                        <div class="wp-card-body">

                            <!-- Post Content Field with WYSIWYG -->
                            <div class="form-group mb-0">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="post_content">Content</label>
                                    <button type="button" id="toggle-html-view" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-code"></i> Toggle HTML
                                    </button>
                                </div>
                                <textarea id="post_content" name="post_content" class="form-control rich-text-editor" rows="15"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                                <textarea id="html_content" name="html_content" class="form-control" rows="15" style="display: none; font-family: monospace;"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                                <input type="hidden" id="content" name="content" value="">
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Tags
                        </div>
                        <div class="wp-card-body">
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
                    </div>
                </div>

                <!-- Sidebar Column -->
                <div class="wp-layout-sidebar">
                    <!-- Author Information -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Author
                        </div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <select id="author_id" name="author_id" class="form-control">
                                    <option value="">Select Author</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?php echo $author['id']; ?>"
                                                data-author-type="<?php echo htmlspecialchars($author['author_type']); ?>"
                                                <?php echo (isset($post['author_id']) && $post['author_id'] == $author['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($author['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Post Settings -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Post Settings
                        </div>
                        <div class="wp-card-body">
                            <!-- Published Status -->
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="is_published" name="is_published" class="form-check-input"
                                        value="1" <?php echo (isset($post['is_published']) && $post['is_published'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_published">Is Published</label>
                                    <input type="hidden" name="is_published_submitted" value="1">
                                </div>
                            </div>

                            <?php
                            // Display other boolean fields
                            foreach ($additionalFields as $field):
                                // Skip is_published as we've already handled it
                                if ($field === 'is_published') continue;

                                $columnData = $columnInfo[$field];
                                $isRequired = strpos($columnData['Type'], 'NOT NULL') !== false;
                                $isIntField = strpos($columnData['Type'], 'int') === 0;
                                $isDecimalField = strpos($columnData['Type'], 'decimal') === 0;
                                $label = ucwords(str_replace('_', ' ', $field));

                                // Check if this is a boolean field (tinyint(1))
                                $isBooleanField = $isIntField && (
                                    strpos($columnData['Type'], 'tinyint(1)') !== false ||
                                    in_array($field, ['is_featured', 'is_sponsored'])
                                );

                                if ($isBooleanField):
                            ?>
                                <div class="form-group">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-check-input"
                                            value="1" <?php echo (isset($post[$field]) && $post[$field] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                                        <input type="hidden" name="<?php echo $field; ?>_submitted" value="1">
                                    </div>
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>

                    <?php
                    // Check if there are any non-boolean additional fields to display
                    $hasAdditionalFields = false;
                    foreach ($additionalFields as $field) {
                        // Skip author_id and is_published as we've already handled them
                        if (in_array($field, ['author_id', 'is_published'])) continue;

                        $columnData = $columnInfo[$field];
                        $isIntField = strpos($columnData['Type'], 'int') === 0;

                        // Check if this is a boolean field (tinyint(1))
                        $isBooleanField = $isIntField && (
                            strpos($columnData['Type'], 'tinyint(1)') !== false ||
                            in_array($field, ['is_featured', 'is_sponsored'])
                        );

                        // Skip boolean fields as they're already displayed
                        if (!$isBooleanField) {
                            $hasAdditionalFields = true;
                            break;
                        }
                    }

                    // Only display the Additional Information section if there are fields to show
                    if ($hasAdditionalFields):
                    ?>
                    <!-- Additional Fields -->
                    <div class="wp-card">
                        <div class="wp-card-header">
                            Additional Information
                        </div>
                        <div class="wp-card-body">
                            <?php
                            foreach ($additionalFields as $field):
                                // Skip author_id and is_published as we've already handled them
                                if (in_array($field, ['author_id', 'is_published'])) continue;

                                $columnData = $columnInfo[$field];
                                $isRequired = strpos($columnData['Type'], 'NOT NULL') !== false;
                                $isIntField = strpos($columnData['Type'], 'int') === 0;
                                $isDecimalField = strpos($columnData['Type'], 'decimal') === 0;
                                $label = ucwords(str_replace('_', ' ', $field));

                                // Check if this is a boolean field (tinyint(1))
                                $isBooleanField = $isIntField && (
                                    strpos($columnData['Type'], 'tinyint(1)') !== false ||
                                    in_array($field, ['is_featured', 'is_sponsored'])
                                );

                                // Skip boolean fields as they're already displayed
                                if ($isBooleanField) {
                                    continue;
                                }
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
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky-save-bar">
                <div class="post-status">
                    <?php if (isset($post['id'])): ?>
                    <span class="text-muted">Last updated: <?php echo date('M j, Y g:i a', strtotime($post['updated_at'] ?? 'now')); ?></span>
                    <?php else: ?>
                    <span class="text-muted">Creating new post</span>
                    <?php endif; ?>
                </div>
                <div class="btn-group">
                    <a href="blog-posts.php" class="btn btn-secondary">Cancel</a>
                    <button type="button" id="preview-post" class="btn btn-info">Preview</button>
                    <button type="submit" class="btn btn-primary">Save Post</button>
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

<script>
    // Initialize CKEditor for the rich text editor
    document.addEventListener('DOMContentLoaded', function() {
        const editorElement = document.getElementById('post_content');
        const htmlEditor = document.getElementById('html_content');
        const contentInput = document.getElementById('content');
        const toggleHtmlButton = document.getElementById('toggle-html-view');

        let editor;

        // Initialize CKEditor
        if (editorElement) {
            ClassicEditor
                .create(editorElement, {
                    toolbar: {
                        items: [
                            'heading',
                            '|',
                            'bold',
                            'italic',
                            'link',
                            'bulletedList',
                            'numberedList',
                            '|',
                            'outdent',
                            'indent',
                            '|',
                            'imageUpload',
                            'blockQuote',
                            'insertTable',
                            'mediaEmbed',
                            'undo',
                            'redo',
                            '|',
                            'sourceEditing'
                        ]
                    },
                    language: 'en',
                    image: {
                        toolbar: [
                            'imageTextAlternative',
                            'imageStyle:inline',
                            'imageStyle:block',
                            'imageStyle:side'
                        ]
                    },
                    table: {
                        contentToolbar: [
                            'tableColumn',
                            'tableRow',
                            'mergeTableCells'
                        ]
                    },
                    // Add custom upload adapter for images
                    extraPlugins: [function(editor) {
                        // This is where we integrate with our custom upload adapter
                        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                            return new MediaLibraryUploadAdapter(loader);
                        };
                    }]
                })
                .then(newEditor => {
                    editor = newEditor;

                    // Update the hidden input with the editor content when the form is submitted
                    const form = editorElement.closest('form');
                    if (form) {
                        form.addEventListener('submit', function() {
                            if (htmlEditor.style.display === 'none') {
                                // If in WYSIWYG mode, get content from CKEditor
                                contentInput.value = editor.getData();
                            } else {
                                // If in HTML mode, get content from HTML editor
                                contentInput.value = htmlEditor.value;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error(error);
                });
        }

        // Toggle between WYSIWYG and HTML view
        if (toggleHtmlButton) {
            toggleHtmlButton.addEventListener('click', function() {
                if (htmlEditor.style.display === 'none') {
                    // Switch to HTML view
                    htmlEditor.value = editor.getData();
                    htmlEditor.style.display = 'block';
                    editorElement.style.display = 'none';
                    document.querySelector('.ck.ck-editor').style.display = 'none';
                } else {
                    // Switch to WYSIWYG view
                    editor.setData(htmlEditor.value);
                    htmlEditor.style.display = 'none';
                    editorElement.style.display = 'block';
                    document.querySelector('.ck.ck-editor').style.display = 'block';
                }
            });
        }
    });
</script>

<!-- Include image upload script -->
<script src="../assets/js/image-upload.js"></script>

<!-- Include debug script -->
<script src="../assets/js/image-upload-debug.js"></script>

<!-- Include custom upload adapter for blog posts -->
<script>
    /**
     * Custom upload adapter for blog posts
     * This extends the MediaLibraryUploadAdapter to set the entity_type to 'post'
     */
    class BlogPostUploadAdapter extends MediaLibraryUploadAdapter {
        _sendRequest(file) {
            // Create FormData
            const data = new FormData();
            data.append('upload', file);
            data.append('entity_type', 'post'); // Use 'post' instead of 'story'
            data.append('for_editor', 'true');

            // Try to get the post ID from the form if available
            const postIdInput = document.querySelector('input[name="id"]');
            if (postIdInput && postIdInput.value) {
                data.append('entity_id', postIdInput.value);
            } else {
                // Use a temporary ID if we don't have a post ID yet
                data.append('entity_id', 'temp-' + Date.now());
            }

            // Add alt text (can be updated later)
            data.append('alt_text', file.name.replace(/\.[^/.]+$/, "")); // Use filename without extension as alt text

            // Show loading indicator
            this._showLoadingIndicator(file.name);

            // Send the request
            this.xhr.send(data);
        }
    }

    // Override the MediaLibraryUploadAdapter for this page
    MediaLibraryUploadAdapter = BlogPostUploadAdapter;
</script>

<!-- Include post preview script -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<script src="../assets/js/post-preview.js"></script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
