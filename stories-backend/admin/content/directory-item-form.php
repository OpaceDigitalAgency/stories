<?php
/**
 * Directory Item Form Page
 *
 * This page displays a form for adding or editing a directory item.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

// Include tag component if it exists
if (file_exists('../includes/tag-component.php')) {
    require_once '../includes/tag-component.php';
}

try {
    // Initialize variables
    $item = null;
    $categories = [];
    $error = null;
    $bookData = []; // Initialize book data
    $tags = [];

    // Get all categories
    $stmt = $db->query("SHOW TABLES LIKE 'directory_categories'");
    if ($stmt->rowCount() > 0) {
        $categories = $db->query("SELECT * FROM directory_categories ORDER BY name")->fetchAll();
    }

    // Get tags if they exist
    if ($db->query("SHOW TABLES LIKE 'tags'")->rowCount() > 0) {
        $tags = $db->query("SELECT * FROM tags ORDER BY name")->fetchAll();
    }

    // Get directory item if editing
    if (isset($_GET['id'])) {
        try {
            $stmt = $db->prepare("SELECT * FROM directory_items WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $item = $stmt->fetch();

            // If this is a book type directory item, get the book data
            if ($item && isset($item['type']) && $item['type'] == 'book') {
                $bookStmt = $db->prepare("SELECT * FROM books WHERE directory_item_id = ?");
                $bookStmt->execute([$_GET['id']]);
                $bookData = $bookStmt->fetch();

                // Format purchase_links JSON for display
                if (isset($bookData['purchase_links']) && !empty($bookData['purchase_links'])) {
                    try {
                        $links = json_decode($bookData['purchase_links'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $bookData['purchase_links'] = json_encode($links, JSON_PRETTY_PRINT);
                        }
                    } catch (Exception $e) {
                        // Keep original if can't parse JSON
                    }
                }
            }

            // Get item tags if they exist
            if ($db->query("SHOW TABLES LIKE 'item_tags'")->rowCount() > 0) {
                $tagStmt = $db->prepare("
                    SELECT t.id, t.name
                    FROM tags t
                    JOIN item_tags it ON t.id = it.tag_id
                    WHERE it.item_id = ? AND it.item_type = 'directory_item'
                ");
                $tagStmt->execute([$_GET['id']]);
                $itemTags = $tagStmt->fetchAll();
            }

            if (!$item) {
                header("Location: directory-items.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Error loading directory item: " . $e->getMessage());
            header("Location: directory-items.php");
            exit;
        }
    }
} catch (PDOException $e) {
    error_log("Directory item form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Directory Item' : 'Add Directory Item';
$currentPage = 'directory';

// Add custom CSS for form styling
$extraHeadContent = '
<!-- Include purchase links formatter script -->
<script src="js/purchase-links-formatter.js"></script>
<style>
    /* Grid layout for space efficiency */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -10px;
        margin-left: -10px;
    }

    .form-row > .col,
    .form-row > [class*="col-"] {
        padding-right: 10px;
        padding-left: 10px;
    }

    /* Compact form elements */
    .form-group {
        margin-bottom: 0.75rem;
    }

    .form-label {
        margin-bottom: 0.25rem;
        font-weight: 500;
    }

    .form-control {
        padding: 0.375rem 0.5rem;
    }

    .card, .wp-card {
        margin-bottom: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .card-header, .wp-card-header {
        padding: 0.5rem 0.75rem;
        background-color: rgba(0,0,0,0.03);
        font-weight: 600;
        border-bottom: 1px solid rgba(0,0,0,0.125);
    }

    .card-body, .wp-card-body {
        padding: 0.75rem;
    }

    /* Compact sections */
    .content-section {
        padding: 0.5rem !important;
    }

    .section-body {
        padding: 0.5rem !important;
    }

    /* Sticky save bar at bottom of screen */
    .sticky-save-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 10px 15px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #dee2e6;
    }

    /* Add padding to the bottom of the form to prevent content from being hidden behind the sticky bar */
    .content-form {
        padding-bottom: 60px;
    }

    .sticky-save-bar .btn-group {
        display: flex;
        gap: 8px;
    }

    /* Book fields toggle */
    .book-fields {
        display: none;
    }

    /* Image preview styling */
    .image-preview-container {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 0.5rem;
        background-color: #f8f9fa;
        margin-top: 0.5rem;
        min-height: 150px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .image-preview {
        max-width: 100%;
        max-height: 300px;
        object-fit: contain;
    }

    /* Tag styling */
    .tag-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .tag-badge {
        background-color: #e9ecef;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .tag-badge .remove-tag {
        cursor: pointer;
        color: #dc3545;
    }

    /* Stack in mobile */
    @media (max-width: 767px) {
        .form-row {
            flex-direction: column;
        }

        .form-row > .col,
        .form-row > [class*="col-"] {
            width: 100%;
        }
    }
</style>
';

// Include header
require_once '../includes/header.php';
?>

<div class="content-section mb-3">
    <div class="section-body">
        <form method="POST" action="save-directory-item.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $item['id'] ?? ''; ?>">

            <div class="row">
                <!-- Left Column - Basic Info and Settings -->
                <div class="col-md-8">
                    <!-- Basic Information Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Basic Information</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" class="form-control" required
                                    value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="slug">Slug <span class="text-danger">*</span></label>
                                        <input type="text" id="slug" name="slug" class="form-control" required
                                            value="<?php echo htmlspecialchars($item['slug'] ?? ''); ?>">
                                        <small class="form-text text-muted">URL-friendly version (auto-generated if empty)</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="type">Item Type</label>
                                        <select id="type" name="type" class="form-control">
                                            <option value="general" <?php echo (isset($item['type']) && $item['type'] == 'general') ? 'selected' : ''; ?>>General</option>
                                            <option value="book" <?php echo (isset($item['type']) && $item['type'] == 'book') ? 'selected' : ''; ?>>Book</option>
                                            <option value="resource" <?php echo (isset($item['type']) && $item['type'] == 'resource') ? 'selected' : ''; ?>>Resource</option>
                                            <option value="organization" <?php echo (isset($item['type']) && $item['type'] == 'organization') ? 'selected' : ''; ?>>Organization</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
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
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="price_range">Price Range</label>
                                        <input type="text" id="price_range" name="price_range" class="form-control"
                                            value="<?php echo htmlspecialchars($item['price_range'] ?? ''); ?>"
                                            placeholder="Free, $10-50, Contact for pricing">
                                    </div>
                                </div>
                            </div>

                            <!-- Settings Options -->
                            <div class="form-row mt-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_published" name="is_published" class="form-check-input"
                                            value="1" <?php echo (!empty($item['is_published'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_published">Published</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" id="featured" name="featured" class="form-check-input"
                                            value="1" <?php echo (!empty($item['featured'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="featured">Featured Item</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Description</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Contact Information</div>
                        <div class="wp-card-body">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="website_url">Website URL</label>
                                        <input type="url" id="website_url" name="website_url" class="form-control"
                                            value="<?php echo htmlspecialchars($item['website_url'] ?? ''); ?>"
                                            placeholder="https://example.com">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="contact_email">Contact Email</label>
                                        <input type="email" id="contact_email" name="contact_email" class="form-control"
                                            value="<?php echo htmlspecialchars($item['contact_email'] ?? ''); ?>"
                                            placeholder="contact@example.com">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="contact_phone">Contact Phone</label>
                                        <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                                            value="<?php echo htmlspecialchars($item['contact_phone'] ?? ''); ?>"
                                            placeholder="+1 (123) 456-7890">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="address">Address</label>
                                        <textarea id="address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($item['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Book Information Card -->
                    <div class="wp-card book-fields" <?php echo (isset($item['type']) && $item['type'] == 'book') ? 'style="display:block"' : ''; ?>>
                        <div class="wp-card-header">Book Information</div>
                        <div class="wp-card-body">
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="author">Author</label>
                                        <input type="text" id="author" name="book_author" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['author'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="publisher">Publisher</label>
                                        <input type="text" id="publisher" name="book_publisher" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['publisher'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="isbn">ISBN</label>
                                        <input type="text" id="isbn" name="book_isbn" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['isbn'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="isbn13">ISBN-13</label>
                                        <input type="text" id="isbn13" name="book_isbn13" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['isbn13'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="page_count">Page Count</label>
                                        <input type="number" id="page_count" name="book_page_count" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['page_count'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="publication_date">Publication Date</label>
                                        <input type="date" id="publication_date" name="book_publication_date" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['publication_date'] ?? ''); ?>">
                                        <small class="text-muted">Format: YYYY-MM-DD</small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="genre">Genre</label>
                                        <input type="text" id="genre" name="book_genre" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['genre'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="series">Series</label>
                                        <input type="text" id="series" name="book_series" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['series'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="age_range">Age Range</label>
                                        <input type="text" id="age_range" name="book_age_range" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['age_range'] ?? ''); ?>"
                                            placeholder="7-10, 9-12, etc.">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="reading_level">Reading Level</label>
                                        <input type="text" id="reading_level" name="book_reading_level" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['reading_level'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="purchase_links">Purchase Links (JSON)</label>
                                <textarea id="purchase_links" name="book_purchase_links" class="form-control" rows="3"><?php echo htmlspecialchars($bookData['purchase_links'] ?? ''); ?></textarea>
                                <small class="text-muted">Format: {"amazon":"https://amazon.com/...", "goodreads":"..."}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Image and Tags -->
                <div class="col-md-4">
                    <!-- Cover Image Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Cover Image</div>
                        <div class="wp-card-body">
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
                                        'description' => $item['description'] ?? '',
                                        'summary' => $item['description'] ?? '' // Also include summary for compatibility
                                    ],
                                    'cover_url',
                                    'cover_url_preview'
                                );
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Tags Card -->
                    <?php if (!empty($tags)): ?>
                    <div class="wp-card">
                        <div class="wp-card-header">Tags</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <label class="form-label" for="tag-select">Add Tags</label>
                                <select id="tag-select" class="form-control">
                                    <option value="">Select a tag to add</option>
                                    <?php foreach ($tags as $tag): ?>
                                        <option value="<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="tag-container" id="tag-container">
                                    <?php if (isset($itemTags)): ?>
                                        <?php foreach($itemTags as $tag): ?>
                                            <span class="tag-badge" data-tag-id="<?php echo $tag['id']; ?>">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                                <i class="fas fa-times remove-tag"></i>
                                                <input type="hidden" name="tags[]" value="<?php echo $tag['id']; ?>">
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky-save-bar">
                <div>
                    <?php if (isset($item['id'])): ?>
                        <span class="text-muted">Editing item #<?php echo $item['id']; ?></span>
                    <?php else: ?>
                        <span class="text-muted">Creating new directory item</span>
                    <?php endif; ?>
                </div>

                <div class="btn-group">
                    <a href="directory-items.php" class="btn btn-secondary">Cancel</a>
                    <button type="button" id="preview-directory-item" class="btn btn-info">Preview</button>
                    <button type="submit" class="btn btn-primary">Save Directory Item</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript to handle dynamic behavior -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle book fields visibility based on item type
    const typeSelect = document.getElementById('type');
    const bookFields = document.querySelector('.book-fields');

    if (typeSelect && bookFields) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'book') {
                bookFields.style.display = 'block';
            } else {
                bookFields.style.display = 'none';
            }
        });
    }

    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    if (titleInput && slugInput && slugInput.value === '') {
        titleInput.addEventListener('input', function() {
            let slug = this.value.toLowerCase()
                .replace(/[^\w\s-]/g, '')  // Remove special characters
                .replace(/\s+/g, '-')      // Replace spaces with hyphens
                .replace(/-+/g, '-')       // Replace multiple hyphens with single hyphen
                .replace(/^-+|-+$/g, '');  // Remove hyphens from start and end

            slugInput.value = slug;
        });
    }

    // Tag management
    const tagSelect = document.getElementById('tag-select');
    const tagContainer = document.getElementById('tag-container');

    if (tagSelect && tagContainer) {
        // Add tag when selected from dropdown
        tagSelect.addEventListener('change', function() {
            if (this.value) {
                const tagId = this.value;
                const tagName = this.options[this.selectedIndex].text;

                // Check if tag already exists
                const existingTag = document.querySelector(`.tag-badge[data-tag-id="${tagId}"]`);
                if (!existingTag) {
                    const tagBadge = document.createElement('span');
                    tagBadge.className = 'tag-badge';
                    tagBadge.setAttribute('data-tag-id', tagId);
                    tagBadge.innerHTML = `
                        ${tagName}
                        <i class="fas fa-times remove-tag"></i>
                        <input type="hidden" name="tags[]" value="${tagId}">
                    `;

                    tagContainer.appendChild(tagBadge);
                }

                // Reset select
                this.value = '';
            }
        });

        // Remove tag when clicked
        tagContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tag')) {
                const badge = e.target.closest('.tag-badge');
                if (badge) {
                    badge.remove();
                }
            }
        });
    }

    // Preview functionality
    const previewButton = document.getElementById('preview-directory-item');

    if (previewButton) {
        previewButton.addEventListener('click', function() {
            const formData = new FormData(document.querySelector('form.content-form'));
            const id = formData.get('id');

            if (id) {
                window.open('../handlers/direct-directory-item-preview.php?id=' + id, '_blank');
            } else {
                alert('Please save the item first before previewing.');
            }
        });
    }

    // JSON validation for purchase links
    const purchaseLinksField = document.getElementById('purchase_links');

    if (purchaseLinksField) {
        purchaseLinksField.addEventListener('blur', function() {
            try {
                if (this.value.trim()) {
                    const parsed = JSON.parse(this.value);
                    this.value = JSON.stringify(parsed, null, 2);
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            } catch (e) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                console.error('Invalid JSON format in purchase links:', e);
            }
        });
    }
});
</script>

<!-- Include image upload script -->
<script src="../assets/js/image-upload.js"></script>

<!-- Include directory item preview script -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<script src="../assets/js/directory-item-preview.js"></script>

<style>
/* Image preview container styling */
.image-preview-container {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.5rem;
    background-color: #f8f9fa;
    margin-top: 0.5rem;
    min-height: 150px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.image-preview {
    max-width: 100%;
    max-height: 300px;
    object-fit: contain;
}
</style>

<?php
// Include footer
require_once '../includes/footer.php';
?>
