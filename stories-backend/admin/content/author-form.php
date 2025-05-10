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
        $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
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
                                console.log('Setting default avatar URL:', defaultAvatarUrl);

                                // Set the value in the avatar_url field
                                const avatarUrlField = document.querySelector('input[name="avatar_url"]');
                                if (avatarUrlField) {
                                    avatarUrlField.value = defaultAvatarUrl;
                                    console.log('Set avatar_url field to:', defaultAvatarUrl);
                                }

                                // Set the value in the backup field
                                const backupField = document.querySelector('input[name="avatar_url_backup"]');
                                if (backupField) {
                                    backupField.value = defaultAvatarUrl;
                                    console.log('Set avatar_url_backup field to:', defaultAvatarUrl);
                                }

                                // Update the preview image
                                const previewContainer = document.querySelector('.image-preview-container');
                                const preview = document.querySelector('.image-preview');

                                if (preview) {
                                    // Clear existing content
                                    preview.innerHTML = '';
                                    preview.classList.remove('empty');

                                    // Create image element
                                    const img = document.createElement('img');
                                    img.src = defaultAvatarUrl;
                                    img.alt = 'Preview';
                                    preview.appendChild(img);

                                    // Create info div with remove button
                                    const infoDiv = document.createElement('div');
                                    infoDiv.className = 'image-info';

                                    const removeButton = document.createElement('button');
                                    removeButton.type = 'button';
                                    removeButton.className = 'btn btn-sm btn-danger remove-image';
                                    removeButton.innerHTML = '<i class="fas fa-times"></i> Remove';
                                    infoDiv.appendChild(removeButton);

                                    preview.appendChild(infoDiv);

                                    // Add has-image class to container
                                    if (previewContainer) {
                                        previewContainer.classList.add('has-image');
                                    }
                                }

                                // Update the alert to show success
                                this.closest('.alert').className = 'alert alert-success';
                                this.closest('.alert').innerHTML = '<strong>Success:</strong> Default avatar set. Click Save to apply changes.';
                            });
                        </script>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                // Render image upload component
                // Get avatar URL from author data
                $avatarUrl = $author['avatar_url'] ?? '';

                // If we have a direct avatar query result, use that instead
                if (isset($avatarResult) && isset($avatarResult['avatar_url'])) {
                    $avatarUrl = $avatarResult['avatar_url'];
                    error_log("Using avatar URL from direct query: " . ($avatarUrl ?? 'NULL'));
                }

                // IMPORTANT: Don't clear the default avatar URL - we want to show it
                // This was causing the form to not display the default avatar

                // Check if avatar URL is empty or NULL
                $hasAvatar = !empty($avatarUrl);

                // Show appropriate debug message
                if (!$hasAvatar) {
                    echo '<div class="alert alert-warning">';
                    echo '<strong>Debug:</strong> No avatar URL set in author data. This author was likely imported using the direct_import.php script, which doesn\'t set avatar URLs.';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-info">';
                    echo '<strong>Debug:</strong> Avatar URL set in author data: ' . htmlspecialchars($avatarUrl);
                    echo '</div>';
                }

                // Add a hidden field to ensure the avatar URL is included in the form
                echo '<input type="hidden" id="avatar_url_backup" name="avatar_url_backup" value="' . htmlspecialchars($avatarUrl) . '">';

                // Debug the avatar URL before rendering
                error_log("Avatar URL being passed to renderImageUploadComponent: " . ($avatarUrl ?? 'NULL'));

                renderImageUploadComponent(
                    'avatar_url',
                    $avatarUrl,
                    'Profile Picture',
                    'author',
                    $author['id'] ?? null
                );

                // Add JavaScript for the Set Default Avatar button and to fix avatar display
                echo '<script>
                $(document).ready(function() {
                    // Fix for avatar display - check if we have a default avatar in the URL
                    const avatarUrl = $("#avatar_url").val();
                    console.log("Avatar URL on page load:", avatarUrl);

                    if (avatarUrl && avatarUrl.includes("default-avatar.svg")) {
                        console.log("Default avatar detected in field, fixing display");

                        // Force the preview to show
                        const previewImg = $(".image-preview img");
                        if (previewImg.length) {
                            previewImg.attr("src", avatarUrl);
                            previewImg.show();

                            // Hide the placeholder
                            $(".image-preview .placeholder").hide();

                            // Add has-image class to container
                            $(".image-preview-container").addClass("has-image");

                            // Update the image preview to show the image
                            $(".image-preview").removeClass("empty");
                        } else {
                            console.log("Preview image element not found, creating one");

                            // Create the preview image if it doesn\'t exist
                            const preview = $(".image-preview");
                            if (preview.length) {
                                // Clear existing content
                                preview.empty();

                                // Create image element
                                const img = $("<img>").attr({
                                    "src": avatarUrl,
                                    "alt": "Preview"
                                });
                                preview.append(img);

                                // Create info div with remove button
                                const infoDiv = $("<div>").addClass("image-info");
                                const removeBtn = $("<button>").attr({
                                    "type": "button",
                                    "class": "btn btn-sm btn-danger remove-image"
                                }).html(\'<i class="fas fa-times"></i> Remove\');

                                infoDiv.append(removeBtn);
                                preview.append(infoDiv);

                                // Add has-image class to container
                                $(".image-preview-container").addClass("has-image");
                                preview.removeClass("empty");

                                // Add event listener to remove button
                                removeBtn.on("click", function() {
                                    // Clear the URL input
                                    $("#avatar_url").val("");
                                    $("#avatar_url_backup").val("");

                                    // Reset the preview
                                    preview.empty().addClass("empty").html(`
                                        <div class="placeholder">
                                            <i class="fas fa-image"></i>
                                            <span>No image selected</span>
                                        </div>
                                    `);

                                    // Remove has-image class from container
                                    $(".image-preview-container").removeClass("has-image");

                                    // Set the image_updated flag
                                    $("#image_updated_field").val("1");
                                });
                            }
                        }
                    }

                    // Set Default Avatar button handler
                    $(".set-default-avatar").click(function() {
                        const defaultAvatarUrl = "https://api.storiesfromtheweb.org/uploads/default-avatar.svg";
                        console.log("Setting default avatar URL:", defaultAvatarUrl);

                        // Set the value in both the visible and hidden fields
                        $("#avatar_url").val(defaultAvatarUrl);
                        $("#avatar_url_backup").val(defaultAvatarUrl);

                        // Update the preview image
                        const previewImg = $(".image-preview img");
                        if (previewImg.length) {
                            previewImg.attr("src", defaultAvatarUrl);
                            previewImg.show();

                            // Hide the placeholder
                            $(".image-preview .placeholder").hide();
                        } else {
                            // Create the preview image if it doesn\'t exist
                            const preview = $(".image-preview");
                            if (preview.length) {
                                // Clear existing content
                                preview.empty();

                                // Create image element
                                const img = $("<img>").attr({
                                    "src": defaultAvatarUrl,
                                    "alt": "Preview"
                                });
                                preview.append(img);

                                // Create info div with remove button
                                const infoDiv = $("<div>").addClass("image-info");
                                const removeBtn = $("<button>").attr({
                                    "type": "button",
                                    "class": "btn btn-sm btn-danger remove-image"
                                }).html(\'<i class="fas fa-times"></i> Remove\');

                                infoDiv.append(removeBtn);
                                preview.append(infoDiv);

                                // Add event listener to remove button
                                removeBtn.on("click", function() {
                                    // Clear the URL input
                                    $("#avatar_url").val("");
                                    $("#avatar_url_backup").val("");

                                    // Reset the preview
                                    preview.empty().addClass("empty").html(`
                                        <div class="placeholder">
                                            <i class="fas fa-image"></i>
                                            <span>No image selected</span>
                                        </div>
                                    `);

                                    // Remove has-image class from container
                                    $(".image-preview-container").removeClass("has-image");

                                    // Set the image_updated flag
                                    $("#image_updated_field").val("1");
                                });
                            }
                        }

                        // Add has-image class to container
                        $(".image-preview-container").addClass("has-image");

                        // Update the image preview to show the image
                        $(".image-preview").removeClass("empty");

                        // Set the image_updated flag
                        $("#image_updated_field").val("1");

                        // Update debug info
                        $(this).closest(".alert").removeClass("alert-warning").addClass("alert-success")
                            .html("<strong>Success:</strong> Default avatar set. Click Save to apply changes.");
                    });

                    // Add form submission handler to ensure avatar URL is properly submitted
                    $("#author-form").on("submit", function(e) {
                        console.log("Form submission handler triggered");

                        // Check if we have an image in the preview
                        const previewImg = $(".image-preview img");
                        if (previewImg.length && previewImg.is(":visible")) {
                            const imgSrc = previewImg.attr("src");
                            console.log("Found visible preview image with src:", imgSrc);

                            // If the avatar_url field is empty but we have an image, use the image src
                            const avatarUrlField = $("#avatar_url");
                            if (!avatarUrlField.val() && imgSrc) {
                                avatarUrlField.val(imgSrc);
                                console.log("Updated empty avatar_url field with preview image src");

                                // Also update the backup field
                                $("#avatar_url_backup").val(imgSrc);

                                // No need to set image_updated flag anymore
                            }
                        }
                    });
                });
                </script>';

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

        // Add debug info to the page
        console.log("Author form fix script loaded");

        // Find the form element
        const form = document.getElementById('author-form');
        console.log("Form element by ID:", form);

        // Find the image_updated field
        const imageUpdatedField = document.getElementById('image_updated_field');
        console.log("Image updated field by ID:", imageUpdatedField);

        // Find the image upload component
        const imageUploadComponent = document.querySelector('.image-upload-component');
        if (imageUploadComponent) {
            console.log("Image upload component found");
        }

        // Log the avatar URL
        const avatarUrlInput = document.querySelector('input[name="avatar_url"]');
        if (avatarUrlInput) {
            console.log("Avatar URL on page load:", avatarUrlInput.value);
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

<!-- Custom script removed - simplified approach -->

<!-- Custom script to ensure image URL is properly transferred from preview to form -->
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

                    // Log the final form data
                    console.log('Final avatar URL before submission:', avatarUrlInput.value);
                }
            });
        }

        // Listen for changes to the image preview
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
