/**
 * Image Upload Module
 *
 * Handles image upload functionality for the admin interface.
 * Features:
 * - Drag and drop
 * - File validation
 * - AJAX upload
 * - Progress indication
 * - Image preview
 * - Media library integration
 * - AI image generation (placeholder)
 */

class ImageUploader {
    constructor() {
        this.initComponents();
    }

    /**
     * Initialize all image upload components on the page
     */
    initComponents() {
        const components = document.querySelectorAll('.image-upload-component');

        components.forEach(component => {
            this.initComponent(component);
        });
    }

    /**
     * Initialize a single image upload component
     * @param {HTMLElement} component - The component element
     */
    initComponent(component) {
        // Get elements
        const dropzone = component.querySelector('.dropzone');
        const fileInput = component.querySelector('.file-input');
        const urlInput = component.querySelector('.image-url-input');
        const previewContainer = component.querySelector('.image-preview-container');
        const preview = component.querySelector('.image-preview');
        const removeButton = component.querySelector('.remove-image');
        const selectFromMediaButton = component.querySelector('.select-from-media');
        const generateAiButton = component.querySelector('.generate-ai');
        const progressBar = component.querySelector('.progress-bar');
        const progressContainer = component.querySelector('.upload-progress');

        // Get entity info
        const entityType = component.querySelector('.entity-type').value;
        const entityId = component.querySelector('.entity-id').value;

        // Handle drag and drop
        if (dropzone) {
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');

                if (e.dataTransfer.files.length) {
                    const file = e.dataTransfer.files[0];
                    if (this.validateFile(file)) {
                        this.uploadFile(file, component, entityType, entityId);
                    }
                }
            });
        }

        // Handle file input change
        if (fileInput) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length) {
                    const file = fileInput.files[0];
                    if (this.validateFile(file)) {
                        this.uploadFile(file, component, entityType, entityId);
                    }
                }
            });
        }

        // Handle remove button
        if (removeButton) {
            removeButton.addEventListener('click', () => {
                this.removeImage(component);
            });
        }

        // Handle select from media button
        if (selectFromMediaButton) {
            selectFromMediaButton.addEventListener('click', () => {
                this.openMediaLibrary(component);
            });
        }

        // Handle generate AI button
        if (generateAiButton) {
            generateAiButton.addEventListener('click', () => {
                this.openAiGenerator(component);
            });
        }
    }

    /**
     * Validate the selected file
     * @param {File} file - The file to validate
     * @returns {boolean} - Whether the file is valid
     */
    validateFile(file) {
        // Check if it's an image
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file.');
            return false;
        }

        // Check file size (max 10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            alert('File size exceeds 10MB. Please select a smaller file.');
            return false;
        }

        return true;
    }

    /**
     * Upload a file using AJAX
     * @param {File} file - The file to upload
     * @param {HTMLElement} component - The component element
     * @param {string} entityType - The type of entity (author, story, etc.)
     * @param {string|number} entityId - The ID of the entity
     */
    uploadFile(file, component, entityType, entityId) {
        const urlInput = component.querySelector('.image-url-input');
        const previewContainer = component.querySelector('.image-preview-container');
        const preview = component.querySelector('.image-preview');
        const progressBar = component.querySelector('.progress-bar');
        const progressContainer = component.querySelector('.upload-progress');

        // Create FormData
        const formData = new FormData();
        formData.append('file', file);
        formData.append('entity_type', entityType);
        formData.append('entity_id', entityId);
        formData.append('field_name', urlInput.name);

        // Show progress bar
        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';

        // Show the global progress overlay
        const overlay = document.getElementById("progressOverlay");
        if (overlay) {
            const title = document.getElementById("progressTitle");
            const message = document.getElementById("progressMessage");

            if (title) title.textContent = "Uploading and Optimizing";
            if (message) message.textContent = "Please wait while we upload and optimize your image. This may take a few moments.";

            overlay.style.visibility = "visible";
            overlay.style.opacity = "1";
        }

        // Create AJAX request
        const xhr = new XMLHttpRequest();
        // Use absolute path to ensure it works from any admin page
        xhr.open('POST', '/admin/handlers/upload-image.php', true);

        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent);
            }
        });

        // Handle response
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.success) {
                        // Update the URL input
                        urlInput.value = response.url;

                        // Update the preview
                        this.updatePreview(component, response.url, response.dimensions);

                        // Hide progress bar after a delay
                        setTimeout(() => {
                            progressContainer.style.display = 'none';

                            // Hide the global progress overlay
                            const overlay = document.getElementById("progressOverlay");
                            if (overlay) {
                                overlay.style.visibility = "hidden";
                                overlay.style.opacity = "0";
                            }
                        }, 1000);
                    } else {
                        alert('Error: ' + response.message);
                        progressContainer.style.display = 'none';

                        // Hide the global progress overlay
                        const overlay = document.getElementById("progressOverlay");
                        if (overlay) {
                            overlay.style.visibility = "hidden";
                            overlay.style.opacity = "0";
                        }
                    }
                } catch (e) {
                    alert('Error parsing server response.');
                    progressContainer.style.display = 'none';

                    // Hide the global progress overlay
                    const overlay = document.getElementById("progressOverlay");
                    if (overlay) {
                        overlay.style.visibility = "hidden";
                        overlay.style.opacity = "0";
                    }
                }
            } else {
                alert('Upload failed. Please try again.');
                progressContainer.style.display = 'none';

                // Hide the global progress overlay
                const overlay = document.getElementById("progressOverlay");
                if (overlay) {
                    overlay.style.visibility = "hidden";
                    overlay.style.opacity = "0";
                }
            }
        });

        // Handle errors
        xhr.addEventListener('error', () => {
            alert('Upload failed. Please try again.');
            progressContainer.style.display = 'none';

            // Hide the global progress overlay
            const overlay = document.getElementById("progressOverlay");
            if (overlay) {
                overlay.style.visibility = "hidden";
                overlay.style.opacity = "0";
            }
        });

        // Send the request
        xhr.send(formData);
    }

    /**
     * Update the image preview
     * @param {HTMLElement} component - The component element
     * @param {string} url - The image URL
     * @param {string} dimensions - The image dimensions (optional)
     */
    updatePreview(component, url, dimensions = '') {
        const previewContainer = component.querySelector('.image-preview-container');
        const preview = component.querySelector('.image-preview');
        const urlInput = component.querySelector('.image-url-input');
        const entityType = component.querySelector('.entity-type')?.value;
        const entityId = component.querySelector('.entity-id')?.value;

        console.log('updatePreview called for field:', urlInput ? urlInput.name : 'unknown');
        console.log('New image URL:', url);
        console.log('Entity type:', entityType, 'Entity ID:', entityId);

        // Make sure the URL input is updated
        if (urlInput) {
            urlInput.value = url;
            console.log('Updated URL input value to:', url);

            // Find the form by looking for the closest form element
            // Make sure we're looking for an actual HTMLFormElement
            let form = null;
            let currentElement = component;

            // Walk up the DOM tree looking for a form element
            while (currentElement && !form) {
                currentElement = currentElement.parentElement;
                if (currentElement && currentElement.tagName === 'FORM') {
                    form = currentElement;
                }
            }

            // If we found a form, update the image_updated field
            if (form) {
                console.log('Found parent form:', form.id || 'unnamed form');

                // Look for the image_updated field
                let hiddenInput = form.querySelector('input[name="image_updated"]');
                if (!hiddenInput) {
                    // If not found, look for an element with ID 'image_updated_field'
                    hiddenInput = document.getElementById('image_updated_field');
                    if (!hiddenInput) {
                        // If still not found, create a new one
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'image_updated';
                        hiddenInput.id = 'image_updated_field';
                        form.appendChild(hiddenInput);
                        console.log('Created new image_updated hidden input');
                    }
                }

                // Set the value to 1 to indicate the image was updated
                hiddenInput.value = '1';
                console.log('Set image_updated flag to 1');

                // Log for debugging
                console.log('Form updated with image URL:', url);
                console.log('Form element:', form);
                console.log('URL input value:', urlInput.value);
            } else {
                console.log('No parent form found for this component');
            }
        } else {
            console.log('No URL input found in component');
        }

        // Add has-image class to container
        previewContainer.classList.add('has-image');

        // Clear existing preview content
        preview.innerHTML = '';
        preview.classList.remove('empty');

        // Create image element
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'Preview';
        preview.appendChild(img);

        // Create info div
        const infoDiv = document.createElement('div');
        infoDiv.className = 'image-info';

        // Add dimensions if available
        if (dimensions) {
            const dimensionsSpan = document.createElement('span');
            dimensionsSpan.className = 'dimensions';
            dimensionsSpan.textContent = dimensions;
            infoDiv.appendChild(dimensionsSpan);
        }

        // Add remove button
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-sm btn-danger remove-image';
        removeButton.innerHTML = '<i class="fas fa-times"></i> Remove';
        removeButton.addEventListener('click', () => {
            this.removeImage(component);
        });
        infoDiv.appendChild(removeButton);

        preview.appendChild(infoDiv);

        // Add a visible debug message
        const debugMsg = document.createElement('div');
        debugMsg.className = 'alert alert-info mt-2';
        debugMsg.innerHTML = '<strong>Debug:</strong> Image URL set to: ' + url;
        debugMsg.style.fontSize = '0.8rem';
        preview.appendChild(debugMsg);
    }

    /**
     * Remove the current image
     * @param {HTMLElement} component - The component element
     */
    removeImage(component) {
        const urlInput = component.querySelector('.image-url-input');
        const previewContainer = component.querySelector('.image-preview-container');
        const preview = component.querySelector('.image-preview');
        const entityType = component.querySelector('.entity-type')?.value;
        const entityId = component.querySelector('.entity-id')?.value;

        console.log('removeImage called for field:', urlInput ? urlInput.name : 'unknown');
        console.log('Current URL value:', urlInput ? urlInput.value : 'no input');

        // Clear the URL input
        if (urlInput) {
            urlInput.value = '';
            urlInput.removeAttribute('readonly');
        }

        // Remove has-image class from container
        if (previewContainer) {
            previewContainer.classList.remove('has-image');
        }

        // Reset preview
        if (preview) {
            preview.innerHTML = `
                <div class="placeholder">
                    <i class="fas fa-image"></i>
                    <span>No image selected</span>
                </div>
            `;
            preview.classList.add('empty');
        }

        // Find the form by looking for the closest form element
        // Make sure we're looking for an actual HTMLFormElement
        let form = null;
        let currentElement = component;

        // Walk up the DOM tree looking for a form element
        while (currentElement && !form) {
            currentElement = currentElement.parentElement;
            if (currentElement && currentElement.tagName === 'FORM') {
                form = currentElement;
            }
        }

        // If we found a form, update the image_updated field
        if (form) {
            console.log('Found parent form:', form.id || 'unnamed form');

            // Look for the image_updated field
            let hiddenInput = form.querySelector('input[name="image_updated"]');
            if (!hiddenInput) {
                // If not found, look for an element with ID 'image_updated_field'
                hiddenInput = document.getElementById('image_updated_field');
                if (!hiddenInput) {
                    // If still not found, create a new one
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'image_updated';
                    hiddenInput.id = 'image_updated_field';
                    form.appendChild(hiddenInput);
                    console.log('Created new image_updated hidden input');
                }
            }

            // Set the value to 1 to indicate the image was updated
            hiddenInput.value = '1';
            console.log('Image removed - Updated form with image_updated=1');
        } else {
            console.log('No parent form found for this component');
        }

        // Update the database if we have an entity ID
        if (entityId && entityId !== '0') {
            console.log('Updating database for entity type:', entityType, 'ID:', entityId);

            // Make an AJAX request to update the image URL to empty
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/admin/handlers/update-thumbnail.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            // Log the data being sent
            console.log('Removing image for:', {
                entityType: entityType,
                entityId: entityId,
                imageUrl: '',
                fieldName: urlInput ? urlInput.name : 'unknown'
            });

            xhr.onload = function() {
                console.log('AJAX response status:', xhr.status);
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('Image removal response:', response);
                    } catch (e) {
                        console.error('Error parsing image removal response:', e);
                        console.log('Raw response:', xhr.responseText);
                    }
                } else {
                    console.error('Error removing image, status:', xhr.status);
                }
            };

            xhr.onerror = function() {
                console.error('Network error during image removal');
            };

            const requestData = 'item_type=' + encodeURIComponent(entityType) + '&item_id=' + encodeURIComponent(entityId) + '&image_url=';
            console.log('Sending request data:', requestData);
            xhr.send(requestData);
        } else {
            console.log('No entity ID found, skipping database update');
        }
    }

    /**
     * Open the media library in a modal
     * @param {HTMLElement} component - The component element
     */
    openMediaLibrary(component) {
        // Create modal backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop';
        backdrop.style.position = 'fixed';
        backdrop.style.top = '0';
        backdrop.style.left = '0';
        backdrop.style.width = '100%';
        backdrop.style.height = '100%';
        backdrop.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        backdrop.style.zIndex = '1050';
        document.body.appendChild(backdrop);

        // Create modal dialog
        const modal = document.createElement('div');
        modal.className = 'modal-dialog modal-lg';
        modal.style.position = 'fixed';
        modal.style.top = '50%';
        modal.style.left = '50%';
        modal.style.transform = 'translate(-50%, -50%)';
        modal.style.maxWidth = '90%';
        modal.style.width = '800px';
        modal.style.backgroundColor = 'white';
        modal.style.borderRadius = '5px';
        modal.style.boxShadow = '0 0 10px rgba(0, 0, 0, 0.3)';
        modal.style.zIndex = '1051';
        modal.style.overflow = 'hidden';
        document.body.appendChild(modal);

        // Create modal content
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        modalContent.style.display = 'flex';
        modalContent.style.flexDirection = 'column';
        modalContent.style.height = '80vh';
        modal.appendChild(modalContent);

        // Create modal header
        const modalHeader = document.createElement('div');
        modalHeader.className = 'modal-header';
        modalHeader.style.padding = '15px';
        modalHeader.style.borderBottom = '1px solid #e9ecef';
        modalHeader.style.display = 'flex';
        modalHeader.style.justifyContent = 'space-between';
        modalHeader.style.alignItems = 'center';
        modalContent.appendChild(modalHeader);

        // Create modal title
        const modalTitle = document.createElement('h5');
        modalTitle.className = 'modal-title';
        modalTitle.textContent = 'Select from Media Library';
        modalHeader.appendChild(modalTitle);

        // Create close button
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'close';
        closeButton.innerHTML = '&times;';
        closeButton.style.border = 'none';
        closeButton.style.background = 'none';
        closeButton.style.fontSize = '1.5rem';
        closeButton.style.fontWeight = 'bold';
        closeButton.style.cursor = 'pointer';
        closeButton.addEventListener('click', () => {
            document.body.removeChild(backdrop);
            document.body.removeChild(modal);
        });
        modalHeader.appendChild(closeButton);

        // Create modal body
        const modalBody = document.createElement('div');
        modalBody.className = 'modal-body';
        modalBody.style.padding = '15px';
        modalBody.style.overflowY = 'auto';
        modalBody.style.flex = '1';
        modalContent.appendChild(modalBody);

        // Create iframe to load media library
        const iframe = document.createElement('iframe');
        // Use absolute path to ensure it works from any admin page
        iframe.src = '/admin/content/media-select.php';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
        iframe.style.border = 'none';
        modalBody.appendChild(iframe);

        // Handle message from iframe
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'media-selected') {
                const url = event.data.url;
                const dimensions = event.data.dimensions || '';

                // Update the component
                const urlInput = component.querySelector('.image-url-input');
                urlInput.value = url;

                // Update the preview
                this.updatePreview(component, url, dimensions);

                // Close the modal
                document.body.removeChild(backdrop);
                document.body.removeChild(modal);

                // Update thumbnail in database if we have an item ID
                const entityType = component.querySelector('.entity-type').value;
                const entityId = component.querySelector('.entity-id').value;

                if (entityId && entityId !== '0') {
                    // Make an AJAX request to update the thumbnail
                    const xhr = new XMLHttpRequest();
                    // Use absolute path to ensure it works from any admin page
                    xhr.open('POST', '/admin/handlers/update-thumbnail.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    // Log the data being sent
                    console.log('Updating thumbnail for:', {
                        entityType: entityType,
                        entityId: entityId,
                        imageUrl: url,
                        fieldName: urlInput.name
                    });

                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                console.log('Thumbnail update response:', response);

                                // If successful, update the form field
                                if (response.success) {
                                    // Make sure the form field is updated with the image URL
                                    urlInput.value = url;

                                    // If we're in a form, find the form and mark it as having unsaved changes
                                    const form = component.closest('form');
                                    if (form) {
                                        // Add a hidden input to indicate the image was updated
                                        let hiddenInput = form.querySelector('input[name="image_updated"]');
                                        if (!hiddenInput) {
                                            hiddenInput = document.createElement('input');
                                            hiddenInput.type = 'hidden';
                                            hiddenInput.name = 'image_updated';
                                            form.appendChild(hiddenInput);
                                            console.log('Created new image_updated hidden input');
                                        }
                                        hiddenInput.value = '1';
                                        console.log('Set image_updated to 1 because image was selected from media library');
                                    }
                                } else {
                                    console.error('Failed to update thumbnail:', response.message);
                                }
                            } catch (e) {
                                console.error('Error parsing thumbnail update response:', e);
                                console.error('Raw response:', xhr.responseText);
                            }
                        } else {
                            console.error('HTTP error updating thumbnail:', xhr.status);
                        }
                    };

                    xhr.onerror = function() {
                        console.error('Network error updating thumbnail');
                    };

                    xhr.send('item_type=' + encodeURIComponent(entityType) + '&item_id=' + encodeURIComponent(entityId) + '&image_url=' + encodeURIComponent(url));
                }
            }
        });
    }

    /**
     * Open the AI image generator
     * @param {HTMLElement} component - The component element
     */
    openAiGenerator(component) {
        // Find the nearest AI generate button and trigger a click on it
        const aiGenerateBtn = component.closest('.form-group').querySelector('.ai-generate-btn');
        if (aiGenerateBtn) {
            aiGenerateBtn.click();
        } else {
            console.warn('AI image generation button not found');
            alert('AI image generation is not available for this component');
        }
    }
}

// Initialize the image uploader when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.imageUploader = new ImageUploader();

    // Add form submission handler to ensure image URLs are properly submitted
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            console.log('Form submission handler triggered for form:', form.id || 'unnamed form');

            // Find all image upload components in this form
            const components = form.querySelectorAll('.image-upload-component');
            console.log(`Found ${components.length} image upload components in this form`);

            components.forEach(component => {
                const urlInput = component.querySelector('.image-url-input');
                const previewContainer = component.querySelector('.image-preview-container');
                const previewImg = component.querySelector('.image-preview img');

                console.log('Processing component with field:', urlInput ? urlInput.name : 'unknown');
                console.log('Current URL value:', urlInput ? urlInput.value : 'no input');
                console.log('Has preview container:', previewContainer ? 'yes' : 'no');
                console.log('Has preview image:', previewImg ? 'yes' : 'no');

                // Check if we have a preview image
                if (previewImg && previewImg.src && !previewImg.src.endsWith('placeholder.png') &&
                    previewImg.style.display !== 'none' && previewContainer &&
                    previewContainer.classList.contains('has-image')) {

                    // If the input is empty but there's an image in the preview, use that URL
                    if (!urlInput.value || urlInput.value === '') {
                        urlInput.value = previewImg.src;
                        console.log('Updated empty image URL from preview:', previewImg.src);
                    }

                    // Set the image_updated flag
                    let hiddenInput = form.querySelector('input[name="image_updated"]');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'image_updated';
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = '1';
                    console.log('Set image_updated flag to 1');
                } else if (urlInput && !urlInput.value) {
                    // If we have no preview image and no URL, make sure the image_updated flag is set
                    // This ensures that if the user removed an image, it stays removed
                    let hiddenInput = form.querySelector('input[name="image_updated"]');
                    if (!hiddenInput) {
                        hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'image_updated';
                        form.appendChild(hiddenInput);
                    }
                    hiddenInput.value = '1';
                    console.log('No image in preview, empty URL - set image_updated flag to 1');
                }
            });
        });
    });
});
