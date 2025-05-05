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

        // Create AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../handlers/upload-image.php', true);

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
                        }, 1000);
                    } else {
                        alert('Error: ' + response.message);
                        progressContainer.style.display = 'none';
                    }
                } catch (e) {
                    alert('Error parsing server response.');
                    progressContainer.style.display = 'none';
                }
            } else {
                alert('Upload failed. Please try again.');
                progressContainer.style.display = 'none';
            }
        });

        // Handle errors
        xhr.addEventListener('error', () => {
            alert('Upload failed. Please try again.');
            progressContainer.style.display = 'none';
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
    }

    /**
     * Remove the current image
     * @param {HTMLElement} component - The component element
     */
    removeImage(component) {
        const urlInput = component.querySelector('.image-url-input');
        const previewContainer = component.querySelector('.image-preview-container');
        const preview = component.querySelector('.image-preview');

        // Clear the URL input
        urlInput.value = '';
        urlInput.removeAttribute('readonly');

        // Remove has-image class from container
        previewContainer.classList.remove('has-image');

        // Reset preview
        preview.innerHTML = `
            <div class="placeholder">
                <i class="fas fa-image"></i>
                <span>No image selected</span>
            </div>
        `;
        preview.classList.add('empty');
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
        iframe.src = '../content/media-select.php';
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
            }
        });
    }

    /**
     * Open the AI image generator (placeholder)
     * @param {HTMLElement} component - The component element
     */
    openAiGenerator(component) {
        alert('AI image generation is coming soon!');
    }
}

// Initialize the image uploader when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.imageUploader = new ImageUploader();
});
