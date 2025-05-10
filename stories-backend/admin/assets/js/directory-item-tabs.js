/**
 * Directory Item Form Tabs Handler
 *
 * This script manages the tabbed interface for the directory item form,
 * making it more organized and compact.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Select all tab navigation links
    const tabLinks = document.querySelectorAll('#directoryTabs .nav-link');

    // Select all tab content panes
    const tabPanes = document.querySelectorAll('.tab-pane');

    // Tab switching functionality
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all tab links and panes
            tabLinks.forEach(tab => tab.classList.remove('active'));
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            // Add active class to the clicked tab
            this.classList.add('active');

            // Get the target tab content and activate it
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });

    // Toggle book-specific fields based on item type selection
    const typeSelect = document.getElementById('type');
    const bookTab = document.querySelector('.book-tab');

    function toggleBookFields() {
        if (typeSelect && bookTab) {
            if (typeSelect.value === 'book') {
                bookTab.style.display = 'block';
            } else {
                bookTab.style.display = 'none';

                // If book tab is currently active, switch to basic tab
                if (document.querySelector('#book-tab.active')) {
                    document.querySelector('#basic-tab').click();
                }
            }
        }
    }

    // Run when type selection changes
    if (typeSelect) {
        typeSelect.addEventListener('change', toggleBookFields);

        // Also run on page load
        toggleBookFields();
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

    // Initialize image preview in the Media tab
    function updateImagePreview() {
        const coverUrlInput = document.getElementById('cover_url');
        const coverPreview = document.getElementById('cover_url_preview');

        if (coverUrlInput && coverPreview) {
            const imageUrl = coverUrlInput.value;
            if (imageUrl) {
                coverPreview.src = imageUrl;
                coverPreview.style.display = 'block';
                document.getElementById('no-image-message').style.display = 'none';
            } else {
                coverPreview.style.display = 'none';
                document.getElementById('no-image-message').style.display = 'block';
            }
        }
    }

    // Update preview when URL changes
    const coverUrlInput = document.getElementById('cover_url');
    if (coverUrlInput) {
        coverUrlInput.addEventListener('change', updateImagePreview);
        coverUrlInput.addEventListener('input', updateImagePreview);

        // Initial preview
        updateImagePreview();
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

    // Initialize preview functionality
    const previewButton = document.getElementById('preview-directory-item');

    if (previewButton) {
        previewButton.addEventListener('click', function() {
            const formData = new FormData(document.querySelector('form.content-form'));
            const id = formData.get('id');

            if (id) {
                // Use the DirectoryItemPreview class to show in lightbox
                if (window.directoryItemPreview) {
                    window.directoryItemPreview.loadDirectoryItemPreview(id);
                } else {
                    // Fallback if the preview class isn't available
                    let url = '../handlers/direct-directory-item-preview.php?id=' + id;
                    window.open(url, '_blank');
                }
            } else {
                // For new items that haven't been saved yet
                alert('Please save the item first before previewing.');
                return;
            }
        });
    }
});