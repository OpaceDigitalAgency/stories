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

// Add custom CSS and JS for form styling and preview
$extraHeadContent = '
<!-- Styles for the tabbed interface and compact form -->
<style>
    /* Configure core tab styling */
    .nav-tabs {
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 1rem;
        display: flex;
        flex-wrap: wrap;
    }
    
    .nav-tabs .nav-item {
        margin-bottom: -1px;
    }
    
    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        color: #495057;
        background-color: transparent;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
    }
    
    .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }
    
    .tab-content > .tab-pane {
        display: none;
    }
    
    .tab-content > .active {
        display: block;
    }
    
    /* Compact form elements */
    .wp-card {
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
    }
    
    .wp-card-header {
        background-color: rgba(0, 0, 0, 0.03);
        padding: 0.5rem 1rem;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
    }
    
    .wp-card-body {
        padding: 1rem;
    }
    
    .form-group {
        margin-bottom: 0.75rem;
    }
    
    .form-row {
        display: flex;
        margin-right: -5px;
        margin-left: -5px;
        flex-wrap: wrap;
    }
    
    .form-row > div {
        padding-right: 5px;
        padding-left: 5px;
        flex: 1;
    }
    
    .required {
        color: #dc3545;
    }
    
    /* Sticky save bar at bottom of screen */
    .sticky-save-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 12px 16px;
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
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sticky-save-bar {
            flex-direction: column;
            gap: 8px;
        }
        
        .sticky-save-bar .btn-group {
            width: 100%;
        }
        
        .sticky-save-bar .btn {
            flex: 1;
        }
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
</style>
';

// Include header
require_once '../includes/header.php';

?>

<div class="content-section mb-4">
    <div class="section-body">
        <form method="POST" action="save-directory-item.php" class="content-form">
            <input type="hidden" name="id" value="<?php echo $item['id'] ?? ''; ?>">
            
            <!-- Tabbed Navigation -->
            <ul class="nav nav-tabs" id="directoryTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="basic-tab" data-bs-toggle="tab" href="#basic" role="tab">Basic Info</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="media-tab" data-bs-toggle="tab" href="#media" role="tab">Media</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="content-tab" data-bs-toggle="tab" href="#content" role="tab">Content</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="settings-tab" data-bs-toggle="tab" href="#settings" role="tab">Settings</a>
                </li>
                <li class="nav-item book-tab" style="display: <?php echo (isset($item['type']) && $item['type'] == 'book') ? 'block' : 'none'; ?>">
                    <a class="nav-link" id="book-tab" data-bs-toggle="tab" href="#book" role="tab">Book Details</a>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Basic Info Tab -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                    <div class="wp-card">
                        <div class="wp-card-header">Basic Information</div>
                        <div class="wp-card-body">
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
                            
                            <div class="form-row">
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
                            
                            <!-- Tags -->
                            <?php if (!empty($tags)): ?>
                            <div class="form-group">
                                <label class="form-label" for="tags">Tags</label>
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
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Media Tab -->
                <div class="tab-pane fade" id="media" role="tabpanel">
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
                </div>
                
                <!-- Content Tab -->
                <div class="tab-pane fade" id="content" role="tabpanel">
                    <div class="wp-card">
                        <div class="wp-card-header">Description</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <textarea id="description" name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="wp-card">
                        <div class="wp-card-header">Contact Information</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <label class="form-label" for="website_url">Website URL</label>
                                <input type="url" id="website_url" name="website_url" class="form-control"
                                    value="<?php echo htmlspecialchars($item['website_url'] ?? ''); ?>"
                                    placeholder="https://example.com">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="contact_email">Contact Email</label>
                                    <input type="email" id="contact_email" name="contact_email" class="form-control"
                                        value="<?php echo htmlspecialchars($item['contact_email'] ?? ''); ?>"
                                        placeholder="contact@example.com">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="contact_phone">Contact Phone</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                                        value="<?php echo htmlspecialchars($item['contact_phone'] ?? ''); ?>"
                                        placeholder="+1 (123) 456-7890">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($item['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Tab -->
                <div class="tab-pane fade" id="settings" role="tabpanel">
                    <div class="wp-card">
                        <div class="wp-card-header">Item Settings</div>
                        <div class="wp-card-body">
                            <!-- Published Status -->
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="is_published" name="is_published" class="form-check-input"
                                        value="1" <?php echo (!empty($item['is_published'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_published">Published</label>
                                </div>
                            </div>
                            
                            <!-- Featured Status -->
                            <div class="form-group">
                                <div class="form-check form-switch">
                                    <input type="checkbox" id="featured" name="featured" class="form-check-input"
                                        value="1" <?php echo (!empty($item['featured'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="featured">Featured Item</label>
                                </div>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="form-group">
                                <label class="form-label" for="price_range">Price Range</label>
                                <input type="text" id="price_range" name="price_range" class="form-control"
                                    value="<?php echo htmlspecialchars($item['price_range'] ?? ''); ?>"
                                    placeholder="Free, $10-50, Contact for pricing">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Book Details Tab - Only visible for book type -->
                <div class="tab-pane fade" id="book" role="tabpanel">
                    <div class="wp-card">
                        <div class="wp-card-header">Book Information</div>
                        <div class="wp-card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="author">Author</label>
                                    <input type="text" id="author" name="book_author" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['author'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="publisher">Publisher</label>
                                    <input type="text" id="publisher" name="book_publisher" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['publisher'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="isbn">ISBN</label>
                                    <input type="text" id="isbn" name="book_isbn" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['isbn'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="isbn13">ISBN-13</label>
                                    <input type="text" id="isbn13" name="book_isbn13" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['isbn13'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="publication_date">Publication Date</label>
                                    <input type="date" id="publication_date" name="book_publication_date" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['publication_date'] ?? ''); ?>">
                                    <small class="text-muted">Format: YYYY-MM-DD</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="page_count">Page Count</label>
                                    <input type="number" id="page_count" name="book_page_count" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['page_count'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="genre">Genre</label>
                                    <input type="text" id="genre" name="book_genre" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['genre'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="series">Series</label>
                                    <input type="text" id="series" name="book_series" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['series'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="age_range">Age Range</label>
                                    <input type="text" id="age_range" name="book_age_range" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['age_range'] ?? ''); ?>">
                                    <small class="text-muted">Example: 7-10, 9-12, etc.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="reading_level">Reading Level</label>
                                    <input type="text" id="reading_level" name="book_reading_level" class="form-control"
                                        value="<?php echo htmlspecialchars($bookData['reading_level'] ?? ''); ?>">
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

<!-- Scripts for interactivity -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab navigation
        const tabLinks = document.querySelectorAll('.nav-link');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and panels
                tabLinks.forEach(l => l.classList.remove('active'));
                tabPanes.forEach(p => {
                    p.classList.remove('show', 'active');
                });
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show target panel
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.classList.add('show', 'active');
                }
            });
        });
        
        // Toggle book tab visibility based on item type
        const typeSelect = document.getElementById('type');
        const bookTab = document.querySelector('.book-tab');
        const bookTabLink = document.getElementById('book-tab');
        
        function toggleBookTab() {
            if (typeSelect.value === 'book') {
                bookTab.style.display = 'block';
            } else {
                bookTab.style.display = 'none';
                // If book tab is currently active, switch to basic tab
                if (bookTabLink.classList.contains('active')) {
                    document.getElementById('basic-tab').click();
                }
            }
        }
        
        typeSelect.addEventListener('change', toggleBookTab);
        
        // Auto-generate slug from title if slug is empty
        const titleInput = document.getElementById('title');
        const slugInput = document.getElementById('slug');
        
        if (titleInput && slugInput) {
            // Flag to check if slug has been manually edited
            slugInput._autoGenerated = true;
            
            titleInput.addEventListener('input', function() {
                if (slugInput._autoGenerated || slugInput.value === '') {
                    let slug = this.value.toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove non-word chars
                        .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
                        .replace(/^-+|-+$/g, ''); // Trim hyphens from start and end
                    
                    slugInput.value = slug;
                }
            });
            
            slugInput.addEventListener('input', function() {
                slugInput._autoGenerated = false;
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
    });
</script>

<!-- Include image upload script -->
<script src="../assets/js/image-upload.js"></script>

<!-- Include directory item preview script -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<script src="../assets/js/directory-item-preview.js"></script>
<script src="../assets/js/directory-item-tabs.js"></script>

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
