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
        // Use a more explicit query to ensure we get the avatar_url field
        $stmt = $db->prepare("SELECT id, name, slug, email, bio, avatar_url, author_type, age, location, created_at, updated_at, is_published FROM authors WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $author = $stmt->fetch(PDO::FETCH_ASSOC);

        // Double-check the avatar_url directly from the database
        $avatarStmt = $db->prepare("SELECT avatar_url FROM authors WHERE id = ?");
        $avatarStmt->execute([$_GET['id']]);
        $avatarResult = $avatarStmt->fetch(PDO::FETCH_ASSOC);

        // Log the direct avatar query result
        error_log("Direct avatar query result: " . print_r($avatarResult, true));

        if (!$author) {
            header("Location: authors.php");
            exit;
        }

        // Log author data for debugging
        error_log("Author data loaded: " . print_r($author, true));
        error_log("Avatar URL: " . ($author['avatar_url'] ?? 'Not set'));
        error_log("Avatar URL type: " . gettype($author['avatar_url'] ?? null));
        error_log("Avatar URL empty check: " . (empty($author['avatar_url']) ? 'EMPTY' : 'NOT EMPTY'));

        // Ensure avatar_url is properly set
        if (isset($author['avatar_url']) && $author['avatar_url'] !== null) {
            error_log("Avatar URL is set to: " . $author['avatar_url']);
        } else {
            error_log("Avatar URL is NULL or not set");
        }
    }

} catch (PDOException $e) {
    error_log("Author form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

?>

<div class="row">
    <!-- Main content column -->
    <div class="col-md-8">
        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Author Details</h2>
                <p class="text-muted">Fields marked with <span class="required">*</span> are required</p>
            </div>
            <div class="section-body">
                <form method="POST" action="save-author.php" class="content-form" id="author-form">
                    <input type="hidden" name="id" value="<?php echo $author['id'] ?? ''; ?>">
                    <!-- Add a hidden field to track if the image was updated via AJAX -->
                    <input type="hidden" name="image_updated" value="0" id="image_updated_field">
                    <!-- Debug info - will be hidden in production -->
                    <div class="alert alert-info">
                        <strong>Debug:</strong>
                        <?php if (isset($author['avatar_url'])): ?>
                            Current avatar URL: <?php echo htmlspecialchars($author['avatar_url']); ?>
                        <?php else: ?>
                            No avatar URL set in author data
                        <?php endif; ?>
                    </div>

                    <!-- Basic Information -->
                    <div class="form-group mb-3">
                        <label class="form-label" for="name">Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($author['name'] ?? ''); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="slug">Slug <span class="required">*</span></label>
                        <input type="text" id="slug" name="slug" class="form-control" required
                               value="<?php echo htmlspecialchars($author['slug'] ?? ''); ?>">
                        <small class="form-text text-muted">URL-friendly version of the name (auto-generated if left empty)</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($author['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="age">Age</label>
                        <input type="number" id="age" name="age" class="form-control" min="0" max="120"
                               value="<?php echo htmlspecialchars($author['age'] ?? ''); ?>">
                    </div>

                    <!-- Author Type -->
                    <div class="form-group mb-3">
                        <label class="form-label" for="author_type">Author Type <span class="required">*</span></label>
                        <select id="author_type" name="author_type" class="form-control" required>
                            <option value="child" <?php echo (isset($author['author_type']) && $author['author_type'] === 'child') ? 'selected' : ''; ?>>Child</option>
                            <option value="parent" <?php echo (isset($author['author_type']) && $author['author_type'] === 'parent') ? 'selected' : ''; ?>>Parent</option>
                            <option value="educator" <?php echo (isset($author['author_type']) && $author['author_type'] === 'educator') ? 'selected' : ''; ?>>Educator</option>
                            <option value="retail" <?php echo (isset($author['author_type']) && $author['author_type'] === 'retail') ? 'selected' : ''; ?>>Retail</option>
                        </select>
                    </div>

                    <!-- Bio -->
                    <div class="form-group mb-3">
                        <label class="form-label" for="bio">Biography</label>
                        <textarea id="bio" name="bio" class="form-control" rows="5"><?php echo htmlspecialchars($author['bio'] ?? ''); ?></textarea>
                    </div>

                    <!-- Social Media -->
                    <div class="form-group mb-3">
                        <label class="form-label" for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-control"
                               value="<?php echo htmlspecialchars($author['website'] ?? ''); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="twitter">Twitter</label>
                        <input type="text" id="twitter" name="twitter" class="form-control"
                               value="<?php echo htmlspecialchars($author['twitter'] ?? ''); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label" for="facebook">Facebook</label>
                        <input type="text" id="facebook" name="facebook" class="form-control"
                               value="<?php echo htmlspecialchars($author['facebook'] ?? ''); ?>">
                    </div>

                    <!-- Additional Fields -->
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="featured" name="featured" class="form-check-input" value="1"
                                   <?php echo (!empty($author['featured'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="featured">Featured Author</label>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" id="is_published" name="is_published" class="form-check-input" value="1"
                                   <?php echo (!empty($author['is_published'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Published</label>
                        </div>
                    </div>

                    <!-- Sticky action bar -->
                    <div class="sticky-action-bar">
                        <div class="author-status">
                            <?php if (isset($author['id'])): ?>
                            <span class="text-muted">Last updated: <?php echo date('M j, Y g:i a', strtotime($author['updated_at'] ?? 'now')); ?></span>
                            <?php else: ?>
                            <span class="text-muted">Creating new author</span>
                            <?php endif; ?>
                        </div>
                        <div class="btn-group">
                            <a href="authors.php" class="btn btn-secondary">Cancel</a>
                            <?php if (isset($author['id'])): ?>
                            <button type="button" id="preview-author" class="btn btn-info" data-author-id="<?php echo $author['id']; ?>" data-action="preview-author">Preview</button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">Save Author</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar column -->
    <div class="col-md-4">
        <!-- Profile Picture -->
        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Profile Picture</h2>
            </div>
            <div class="section-body">
                <?php if (isset($author['avatar_url']) && !empty($author['avatar_url'])): ?>
                <div class="alert alert-info">
                    <strong>Debug:</strong> Avatar URL set in author data: <?php echo htmlspecialchars($author['avatar_url']); ?>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <strong>Debug:</strong> No avatar URL set in author data. This author was likely imported using the direct_import.php script, which doesn't set avatar URLs.
                    <div class="mt-2">
                        <button type="button" id="set-default-avatar" class="btn btn-sm btn-primary">
                            Set Default Avatar
                        </button>
                        <script>
                            document.getElementById('set-default-avatar').addEventListener('click', function() {
                                // Set a default avatar URL
                                const defaultAvatarUrl = 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg';

                                // Make an AJAX request to update the avatar URL
                                const xhr = new XMLHttpRequest();
                                xhr.open('POST', '/admin/handlers/update-thumbnail.php', true);
                                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                                xhr.onload = function() {
                                    if (xhr.status === 200) {
                                        try {
                                            const response = JSON.parse(xhr.responseText);
                                            console.log('Avatar update response:', response);

                                            if (response.success) {
                                                alert('Default avatar set successfully!');
                                                // Reload the page to see the changes
                                                window.location.reload();
                                            } else {
                                                alert('Failed to set default avatar: ' + response.message);
                                            }
                                        } catch (e) {
                                            console.error('Error parsing response:', e);
                                            alert('Error setting default avatar');
                                        }
                                    } else {
                                        alert('Error setting default avatar: ' + xhr.status);
                                    }
                                };

                                xhr.onerror = function() {
                                    alert('Network error while setting default avatar');
                                };

                                // Send the request
                                const data = 'item_type=author&item_id=<?php echo $author['id']; ?>&image_url=' + encodeURIComponent(defaultAvatarUrl);
                                xhr.send(data);
                            });
                        </script>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                // Render image upload component
                // Check if avatar_url is empty or points to default-avatar.svg
                $avatarUrl = $author['avatar_url'] ?? '';

                // If we have a direct avatar query result, use that instead
                if (isset($avatarResult) && isset($avatarResult['avatar_url'])) {
                    $avatarUrl = $avatarResult['avatar_url'];
                    error_log("Using avatar URL from direct query: " . ($avatarUrl ?? 'NULL'));
                }

                if (empty($avatarUrl) || strpos($avatarUrl, 'default-avatar.svg') !== false) {
                    $avatarUrl = ''; // Clear it so the component shows "No image selected"
                }

                // Add a hidden field to ensure the avatar URL is included in the form
                echo '<input type="hidden" id="avatar_url_backup" value="' . htmlspecialchars($avatarUrl) . '">';

                renderImageUploadComponent(
                    'avatar_url',
                    $avatarUrl,
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
            </div>
        </div>

        <?php if (isset($author['id'])): ?>
        <!-- Metadata -->
        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Metadata</h2>
            </div>
            <div class="section-body">
                <div class="metadata-list">
                    <?php if (isset($author['created_at'])): ?>
                    <div class="metadata-item">
                        <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($author['created_at'])); ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($author['updated_at'])): ?>
                    <div class="metadata-item">
                        <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($author['updated_at'])); ?>
                    </div>
                    <?php endif; ?>

                    <div class="metadata-item">
                        <strong>ID:</strong> <?php echo $author['id']; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .sticky-action-bar {
        position: fixed;
        bottom: 60px; /* Position above the footer */
        left: 0;
        right: 0;
        background: white;
        padding: 15px 20px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1001; /* Higher than footer */
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

    .sticky-action-bar .btn-group {
        display: flex;
        gap: 10px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .sticky-action-bar {
            flex-direction: column;
            gap: 10px;
        }

        .sticky-action-bar .btn-group {
            width: 100%;
        }

        .sticky-action-bar .btn {
            flex: 1;
        }
    }

    .metadata-list {
        background-color: var(--gray-50, #f8f9fa);
        border-radius: var(--radius-md, 0.375rem);
        padding: 1rem;
    }

    .metadata-item {
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--gray-200, #e9ecef);
    }

    .metadata-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
</style>

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

<!-- Include debug script -->
<script src="../assets/js/image-upload-debug.js"></script>

<!-- Include author preview script -->
<link rel="stylesheet" href="../assets/css/preview-modal.css">
<script src="../assets/js/author-preview.js"></script>

<!-- Custom fix for author form -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Author form fix script loaded');

    // Find the form
    const form = document.getElementById('author-form');
    console.log('Form element by ID:', form);

    // Find the image_updated field
    const imageUpdatedField = document.getElementById('image_updated_field');
    console.log('Image updated field by ID:', imageUpdatedField);

    // Find the image upload component
    const imageUploadComponent = document.querySelector('.image-upload-component');
    if (imageUploadComponent) {
        console.log('Image upload component found');

        // Create a MutationObserver to watch for changes to the image preview
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                    console.log('Image component mutation detected');

                    // Check if the image was removed (placeholder is visible)
                    const placeholder = imageUploadComponent.querySelector('.placeholder');
                    const isVisible = placeholder && (window.getComputedStyle(placeholder).display !== 'none');

                    if (isVisible) {
                        console.log('Image was removed (placeholder is visible)');

                        // Set the image_updated field to 1
                        if (imageUpdatedField) {
                            imageUpdatedField.value = '1';
                            console.log('Set image_updated to 1 because image was removed');
                        }
                    }
                }
            });
        });

        // Start observing the image upload component
        observer.observe(imageUploadComponent, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }

    // Direct event listener for the remove button
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-image') ||
            (event.target.parentElement && event.target.parentElement.classList.contains('remove-image'))) {
            console.log('Remove button clicked (captured by event delegation)');

            // Set the image_updated field to 1
            if (imageUpdatedField) {
                imageUpdatedField.value = '1';
                console.log('Set image_updated to 1 because remove button was clicked');

                // Also clear the avatar_url field
                const avatarUrlField = document.querySelector('input[name="avatar_url"]');
                if (avatarUrlField) {
                    avatarUrlField.value = '';
                    console.log('Cleared avatar_url field');
                }
            }
        }
    });

    // Add a submit handler to the form
    if (form) {
        form.addEventListener('submit', function() {
            console.log('Form is being submitted');
            console.log('image_updated value:', imageUpdatedField ? imageUpdatedField.value : 'not found');

            // Check if the avatar_url field is empty
            const avatarUrlField = document.querySelector('input[name="avatar_url"]');
            if (avatarUrlField && !avatarUrlField.value) {
                console.log('avatar_url is empty, setting image_updated to 1');
                if (imageUpdatedField) {
                    imageUpdatedField.value = '1';
                }
            }
        });
    }
});</script>

<!-- Custom script to ensure image URL is properly transferred to the form -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Listen for form submission
        const form = document.getElementById('author-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Get the avatar URL from the image upload component
                const avatarUrlInput = document.querySelector('input[name="avatar_url"]');
                const avatarUrlBackup = document.getElementById('avatar_url_backup');

                if (avatarUrlInput) {
                    console.log('Form submission - Avatar URL:', avatarUrlInput.value);
                    console.log('Avatar URL backup:', avatarUrlBackup ? avatarUrlBackup.value : 'not found');

                    // If the avatar URL is empty but there's an image in the preview, try to get it from there
                    if (!avatarUrlInput.value) {
                        const previewImg = document.querySelector('.image-preview img');
                        if (previewImg && previewImg.src && previewImg.style.display !== 'none') {
                            avatarUrlInput.value = previewImg.src;
                            console.log('Using image from preview:', previewImg.src);
                        } else if (avatarUrlBackup && avatarUrlBackup.value) {
                            // If we still don't have a value, use the backup
                            avatarUrlInput.value = avatarUrlBackup.value;
                            console.log('Using backup avatar URL:', avatarUrlBackup.value);
                        }
                    }

                    // Set the image_updated field to 1 if we have an avatar URL
                    if (avatarUrlInput.value) {
                        const imageUpdatedField = document.getElementById('image_updated_field');
                        if (imageUpdatedField) {
                            imageUpdatedField.value = '1';
                            console.log('Set image_updated to 1 because we have an avatar URL');
                        }
                    }

                    // Log the final form data
                    console.log('Final avatar URL before submission:', avatarUrlInput.value);
                    console.log('Image updated field value:', document.getElementById('image_updated_field').value);
                }
            });

            // Also add a direct event listener for the Save button
            const saveButton = form.querySelector('button[type="submit"]');
            if (saveButton) {
                saveButton.addEventListener('click', function() {
                    console.log('Save button clicked');

                    // Force the image_updated field to 1
                    const imageUpdatedField = document.getElementById('image_updated_field');
                    if (imageUpdatedField) {
                        imageUpdatedField.value = '1';
                        console.log('Set image_updated to 1 because save button was clicked');
                    }
                });
            }
        }

        // Also listen for changes to the image preview
        const imageUploadComponent = document.querySelector('.image-upload-component');
        if (imageUploadComponent) {
            // Create a MutationObserver to watch for changes to the image preview
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'attributes') {
                        // Check if there's an image in the preview
                        const previewImg = imageUploadComponent.querySelector('.image-preview img');
                        if (previewImg && previewImg.src && previewImg.style.display !== 'none') {
                            // Update the avatar URL input
                            const avatarUrlInput = document.querySelector('input[name="avatar_url"]');
                            if (avatarUrlInput) {
                                avatarUrlInput.value = previewImg.src;
                                document.getElementById('image_updated_field').value = '1';
                                console.log('Image preview changed - Updated avatar URL:', previewImg.src);
                            }
                        }
                    }
                });
            });

            // Start observing the image preview
            observer.observe(imageUploadComponent, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['src', 'style']
            });
        }
    });
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
