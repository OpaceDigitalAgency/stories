// Show progress indicator when optimizing images
document.addEventListener("DOMContentLoaded", function() {
    // Handle image upload component
    const imageUploadComponents = document.querySelectorAll('.image-upload-component');

    imageUploadComponents.forEach(component => {
        const dropzone = component.querySelector('.dropzone');
        const fileInput = component.querySelector('.file-input');
        const urlInput = component.querySelector('.image-url-input');
        const previewContainer = component.querySelector('.image-preview-container');
        const previewImage = previewContainer.querySelector('img');
        const removeButton = component.querySelector('.remove-image');
        const selectFromMediaButton = component.querySelector('.select-from-media');
        const generateAiButton = component.querySelector('.generate-ai');
        const entityType = component.querySelector('.entity-type').value;
        const entityId = component.querySelector('.entity-id').value;

        // Handle file selection
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        // Update preview
                        if (!previewContainer.querySelector('.image-preview')) {
                            previewContainer.innerHTML = `
                                <div class="image-preview">
                                    <img src="${e.target.result}" alt="Preview">
                                    <div class="image-info">
                                        <span class="dimensions"></span>
                                        <button type="button" class="btn btn-sm btn-danger remove-image">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            `;

                            // Add event listener to the new remove button
                            const newRemoveButton = previewContainer.querySelector('.remove-image');
                            if (newRemoveButton) {
                                newRemoveButton.addEventListener('click', function() {
                                    resetImageUpload();
                                });
                            }
                        } else {
                            const img = previewContainer.querySelector('img');
                            img.src = e.target.result;
                        }

                        previewContainer.classList.add('has-image');

                        // Get image dimensions when loaded
                        const img = new Image();
                        img.onload = function() {
                            const dimensionsSpan = previewContainer.querySelector('.dimensions');
                            if (dimensionsSpan) {
                                dimensionsSpan.textContent = `${this.width}x${this.height}`;
                            }
                        };
                        img.src = e.target.result;
                    };

                    reader.readAsDataURL(file);
                }
            });
        }

        // Handle drag and drop
        if (dropzone) {
            dropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');

                if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                    fileInput.files = e.dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        // Handle remove button
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                resetImageUpload();
            });
        }

        // Handle select from media button
        if (selectFromMediaButton) {
            selectFromMediaButton.addEventListener('click', function() {
                // Open media library in a popup
                const mediaUrl = '../content/media.php?select_mode=true';
                const mediaWindow = window.open(mediaUrl, 'MediaLibrary', 'width=1000,height=600');

                // Listen for message from the popup
                window.addEventListener('message', function(event) {
                    if (event.data && event.data.type === 'media-selected') {
                        // Update the input value
                        urlInput.value = event.data.url;

                        // Update preview
                        if (!previewContainer.querySelector('.image-preview')) {
                            previewContainer.innerHTML = `
                                <div class="image-preview">
                                    <img src="${event.data.url}" alt="Preview">
                                    <div class="image-info">
                                        <span class="dimensions">${event.data.dimensions || ''}</span>
                                        <button type="button" class="btn btn-sm btn-danger remove-image">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            `;

                            // Add event listener to the new remove button
                            const newRemoveButton = previewContainer.querySelector('.remove-image');
                            if (newRemoveButton) {
                                newRemoveButton.addEventListener('click', function() {
                                    resetImageUpload();
                                });
                            }
                        } else {
                            const img = previewContainer.querySelector('img');
                            img.src = event.data.url;

                            const dimensionsSpan = previewContainer.querySelector('.dimensions');
                            if (dimensionsSpan) {
                                dimensionsSpan.textContent = event.data.dimensions || '';
                            }
                        }

                        previewContainer.classList.add('has-image');

                        // Close the popup
                        if (mediaWindow) {
                            mediaWindow.close();
                        }
                    }
                });
            });
        }

        // Handle generate AI button
        if (generateAiButton) {
            generateAiButton.addEventListener('click', function() {
                alert('AI image generation is coming soon!');
            });
        }

        // Function to reset the image upload
        function resetImageUpload() {
            if (fileInput) {
                fileInput.value = '';
            }

            if (urlInput) {
                urlInput.value = '';
                urlInput.removeAttribute('readonly');
            }

            previewContainer.classList.remove('has-image');
            previewContainer.innerHTML = `
                <div class="image-preview empty">
                    <div class="placeholder">
                        <i class="fas fa-image"></i>
                        <span>No image selected</span>
                    </div>
                </div>
            `;
        }
    });
    // Get all buttons that trigger optimization
    const optimizeButtons = document.querySelectorAll("a[href*=\"optimize_image.php\"]");

    optimizeButtons.forEach(button => {
        button.addEventListener("click", function(e) {
            // Show the progress overlay
            const overlay = document.getElementById("progressOverlay");
            overlay.style.visibility = "visible";
            overlay.style.opacity = "1";

            // Set appropriate message based on button text
            const title = document.getElementById("progressTitle");
            const message = document.getElementById("progressMessage");

            if (this.textContent.includes("All Media")) {
                title.textContent = "Optimizing All Media";
                message.textContent = "This may take several minutes. Please do not close this page.";
            } else {
                title.textContent = "Optimizing Image";
                message.textContent = "Please wait while we optimize your image.";
            }
        });
    });

    // Also add progress indicator to file upload
    const uploadForm = document.querySelector("form.upload-form");
    if (uploadForm) {
        uploadForm.addEventListener("submit", function(e) {
            // Only show progress if a file is selected
            const fileInput = document.getElementById("media_file");
            if (fileInput && fileInput.files.length > 0) {
                const overlay = document.getElementById("progressOverlay");
                overlay.style.visibility = "visible";
                overlay.style.opacity = "1";

                const title = document.getElementById("progressTitle");
                const message = document.getElementById("progressMessage");

                title.textContent = "Uploading and Optimizing";
                message.textContent = "Please wait while we upload and optimize your image.";
            }
        });
    }

    // Handle upload tabs
    const uploadTabs = document.querySelectorAll('.upload-tab');
    const tabContents = document.querySelectorAll('.upload-tab-content');

    uploadTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            uploadTabs.forEach(t => t.classList.remove('active'));

            // Add active class to clicked tab
            this.classList.add('active');

            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });

            // Show the selected tab content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId + '-upload').style.display = 'block';
        });
    });

    // Handle bulk upload
    const bulkDropzone = document.getElementById('bulk-dropzone');
    const bulkFileInput = document.getElementById('bulk-file-input');
    const progressBar = document.querySelector('.bulk-upload-progress .progress-bar');
    const progressContainer = document.querySelector('.bulk-upload-progress');
    const resultsContainer = document.querySelector('.bulk-upload-results');
    const resultsList = document.querySelector('.results-list');
    const currentFileSpan = document.querySelector('.current-file');
    const uploadCountSpan = document.querySelector('.upload-count');

    // Handle drag and drop
    if (bulkDropzone) {
        bulkDropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            bulkDropzone.classList.add('dragover');
        });

        bulkDropzone.addEventListener('dragleave', () => {
            bulkDropzone.classList.remove('dragover');
        });

        bulkDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            bulkDropzone.classList.remove('dragover');

            if (e.dataTransfer.files.length) {
                handleBulkUpload(e.dataTransfer.files);
            }
        });
    }

    // Handle file input change
    if (bulkFileInput) {
        bulkFileInput.addEventListener('change', () => {
            if (bulkFileInput.files.length) {
                handleBulkUpload(bulkFileInput.files);
            }
        });
    }

    // Function to handle bulk upload
    function handleBulkUpload(files) {
        // Reset UI
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressContainer.style.display = 'block';
        resultsContainer.style.display = 'none';
        resultsList.innerHTML = '';

        // Create FormData
        const formData = new FormData();

        // Add each file to FormData
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        // Add entity type
        formData.append('entity_type', 'media');

        // Update status
        uploadCountSpan.textContent = `0/${files.length} files uploaded`;

        // Create AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../handlers/bulk-upload.php', true);

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

                    // Show results
                    resultsContainer.style.display = 'block';

                    // Process each file result
                    if (response.files && response.files.length) {
                        response.files.forEach(file => {
                            const resultItem = document.createElement('div');
                            resultItem.className = `upload-result-item ${file.success ? 'success' : 'error'}`;

                            const iconClass = file.success ? 'success' : 'error';
                            const iconName = file.success ? 'check-circle' : 'times-circle';

                            resultItem.innerHTML = `
                                <div class="upload-result-icon ${iconClass}">
                                    <i class="fas fa-${iconName}"></i>
                                </div>
                                <div class="upload-result-info">
                                    <div class="upload-result-name">${file.name}</div>
                                    <div class="upload-result-message">${file.success ? 'Uploaded successfully' : file.message}</div>
                                </div>
                            `;

                            resultsList.appendChild(resultItem);
                        });

                        // Update count
                        const successCount = response.files.filter(f => f.success).length;
                        uploadCountSpan.textContent = `${successCount}/${response.files.length} files uploaded`;
                    }

                    // If any files were uploaded successfully, refresh the page after a delay
                    if (response.success) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }
                } catch (e) {
                    alert('Error parsing server response.');
                }
            } else {
                alert('Upload failed. Please try again.');
            }
        });

        // Handle errors
        xhr.addEventListener('error', () => {
            alert('Upload failed. Please try again.');
        });

        // Send the request
        xhr.send(formData);
    }
});

// Handle media selection in select mode
if (document.querySelector('.select-media-item')) {
    document.addEventListener('DOMContentLoaded', function() {
        const selectButtons = document.querySelectorAll('.select-media-item');

        selectButtons.forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const dimensions = this.getAttribute('data-dimensions');

                // Send message to parent window
                window.parent.postMessage({
                    type: 'media-selected',
                    url: url,
                    dimensions: dimensions
                }, '*');
            });
        });

        // Also allow clicking on the image to select
        const thumbnails = document.querySelectorAll('.media-thumbnail');
        thumbnails.forEach(thumbnail => {
            thumbnail.style.cursor = 'pointer';
            thumbnail.addEventListener('click', function() {
                const card = this.closest('.media-card');
                const selectButton = card.querySelector('.select-media-item');
                if (selectButton) {
                    selectButton.click();
                }
            });
        });
    });
}
