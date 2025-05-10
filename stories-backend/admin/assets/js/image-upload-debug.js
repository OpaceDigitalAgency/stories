/**
 * Image Upload Debug Script
 *
 * This script adds debugging capabilities to the image upload process
 * to help diagnose issues with image uploads across different content types.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Image Upload Debug Script loaded');

    // Add debug info to the page
    const debugContainer = document.createElement('div');
    debugContainer.id = 'image-upload-debug';
    debugContainer.style.position = 'fixed';
    debugContainer.style.bottom = '50px'; // Positioned above the toggle button
    debugContainer.style.left = '10px'; // Changed from right to left
    debugContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    debugContainer.style.color = 'white';
    debugContainer.style.padding = '10px';
    debugContainer.style.borderRadius = '5px';
    debugContainer.style.zIndex = '9999';
    debugContainer.style.maxWidth = '400px';
    debugContainer.style.maxHeight = '300px';
    debugContainer.style.overflow = 'auto';
    debugContainer.style.fontSize = '12px';
    debugContainer.style.fontFamily = 'monospace';
    debugContainer.innerHTML = '<h4>Image Upload Debug</h4><div id="debug-log"></div>';

    // Add toggle button
    const toggleButton = document.createElement('button');
    toggleButton.textContent = 'Toggle Debug';
    toggleButton.style.position = 'fixed';
    toggleButton.style.bottom = '10px';
    toggleButton.style.left = '10px'; // Changed from right to left
    toggleButton.style.zIndex = '10000';
    toggleButton.style.padding = '5px 10px';
    toggleButton.style.backgroundColor = '#007bff';
    toggleButton.style.color = 'white';
    toggleButton.style.border = 'none';
    toggleButton.style.borderRadius = '3px';
    toggleButton.style.cursor = 'pointer';

    toggleButton.addEventListener('click', function() {
        if (debugContainer.style.display === 'none') {
            debugContainer.style.display = 'block';
            toggleButton.style.backgroundColor = '#dc3545';
        } else {
            debugContainer.style.display = 'none';
            toggleButton.style.backgroundColor = '#007bff';
        }
    });

    document.body.appendChild(toggleButton);
    document.body.appendChild(debugContainer);
    debugContainer.style.display = 'none'; // Hide by default

    // Function to log debug messages
    window.logDebug = function(message) {
        const logElement = document.getElementById('debug-log');
        if (logElement) {
            const timestamp = new Date().toLocaleTimeString();
            const logItem = document.createElement('div');
            logItem.innerHTML = `<span style="color: #aaa;">[${timestamp}]</span> ${message}`;
            logElement.appendChild(logItem);
            logElement.scrollTop = logElement.scrollHeight;
            console.log(`[DEBUG] ${message}`);
        }
    };

    // Monitor image upload components
    const imageComponents = document.querySelectorAll('.image-upload-component');
    logDebug(`Found ${imageComponents.length} image upload components`);

    imageComponents.forEach((component, index) => {
        const urlInput = component.querySelector('.image-url-input');
        const fieldName = urlInput ? urlInput.name : 'unknown';
        const entityType = component.querySelector('.entity-type')?.value || 'unknown';
        const entityId = component.querySelector('.entity-id')?.value || 'unknown';

        logDebug(`Component #${index+1}: ${fieldName} (${entityType}, ID: ${entityId})`);

        // Monitor URL input changes
        if (urlInput) {
            const originalValue = urlInput.value;
            logDebug(`Initial value for ${fieldName}: ${originalValue || '(empty)'}`);

            urlInput.addEventListener('change', function() {
                logDebug(`URL changed for ${fieldName}: ${this.value || '(empty)'}`);
            });
        }

        // Monitor remove button clicks
        const removeButton = component.querySelector('.remove-image');
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                logDebug(`Remove button clicked for ${fieldName}`);
            });
        }

        // Add debug actions
        const debugActions = document.createElement('div');
        debugActions.className = 'debug-actions mt-3';
        debugActions.innerHTML = `
            <div class="alert alert-secondary">
                <strong>Debug Actions:</strong>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-info check-image-state">
                        Check Image State
                    </button>
                    <button type="button" class="btn btn-sm btn-danger direct-update">
                        Direct DB Update
                    </button>
                </div>
            </div>
        `;
        component.appendChild(debugActions);

        // Add event listeners to the debug buttons

        // Add event listener to the check image state button
        const checkImageStateBtn = debugActions.querySelector('.check-image-state');
        if (checkImageStateBtn) {
            checkImageStateBtn.addEventListener('click', function() {
                // Get the current state
                const urlInput = component.querySelector('.image-url-input');
                const previewImg = component.querySelector('.image-preview img');
                const previewContainer = component.querySelector('.image-preview-container');

                const state = {
                    fieldName: urlInput ? urlInput.name : 'unknown',
                    urlValue: urlInput ? urlInput.value : 'no input',
                    hasPreviewImg: previewImg ? 'yes' : 'no',
                    previewImgSrc: previewImg ? previewImg.src : 'no image',
                    previewImgDisplay: previewImg ? previewImg.style.display : 'no image',
                    hasPreviewContainer: previewContainer ? 'yes' : 'no',
                    previewContainerClasses: previewContainer ? previewContainer.className : 'no container'
                };

                logDebug('Image state: ' + JSON.stringify(state, null, 2));
                alert(`Image state:\n${JSON.stringify(state, null, 2)}`);
            });
        }

        // Add event listener to the direct update button
        const directUpdateBtn = debugActions.querySelector('.direct-update');
        if (directUpdateBtn) {
            directUpdateBtn.addEventListener('click', function() {
                // Get the current URL value
                const urlInput = component.querySelector('.image-url-input');
                const url = urlInput ? urlInput.value : '';

                // Get the entity type and ID
                const entityType = component.querySelector('.entity-type')?.value;
                const entityId = component.querySelector('.entity-id')?.value;

                if (entityId && entityId !== '0') {
                    // Prompt for a new URL
                    const newUrl = prompt('Enter the image URL to set directly in the database:', url || 'https://api.storiesfromtheweb.org/uploads/optimized/test-image.jpg');

                    if (newUrl) {
                        // Make an AJAX request to update the image URL directly in the database
                        const xhr = new XMLHttpRequest();
                        xhr.open('GET', `/admin/content/direct-sql-update.php?id=${entityId}&url=${encodeURIComponent(newUrl)}`, true);

                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    logDebug('Direct update response: ' + JSON.stringify(response, null, 2));

                                    if (response.success) {
                                        alert(`Direct update successful!\n${response.message}`);

                                        // Reload the page to see the changes
                                        if (confirm('Reload the page to see the changes?')) {
                                            window.location.reload();
                                        }
                                    } else {
                                        alert(`Direct update failed: ${response.message}`);
                                    }
                                } catch (e) {
                                    logDebug('Error parsing direct update response: ' + e);
                                    alert('Error parsing direct update response');
                                }
                            } else {
                                alert(`HTTP error: ${xhr.status}`);
                            }
                        };

                        xhr.onerror = function() {
                            alert('Network error during direct update');
                        };

                        xhr.send();
                    }
                } else {
                    alert('No entity ID found. Save the author first.');
                }
            });
        }
    });

    // Monitor form submissions
    const forms = document.querySelectorAll('form');
    logDebug(`Found ${forms.length} forms`);

    // Also look for the specific directory-item-form by ID
    const directoryItemForm = document.getElementById('directory-item-form');
    if (directoryItemForm) {
        logDebug(`Found directory-item-form by ID`);

        // Add direct event listeners for the remove buttons
        const removeButtons = document.querySelectorAll('.remove-image');
        removeButtons.forEach((button, idx) => {
            logDebug(`Adding click listener to remove button #${idx+1}`);
            button.addEventListener('click', function() {
                logDebug(`Remove button #${idx+1} clicked`);
            });
        });
    }

    forms.forEach((form, index) => {
        // Skip if not a real form element (e.g., if it's an input element)
        if (!(form instanceof HTMLFormElement)) {
            logDebug(`Skipping non-form element at index ${index}: ${form.tagName}`);
            return;
        }

        const formId = form.id || `form-${index}`;
        const action = form.getAttribute('action') || 'unknown';
        logDebug(`Form #${index+1}: ${formId}, Action: ${action}`);

        // Check for cover_url field
        const coverUrlField = form.querySelector('input[name="cover_url"]');
        if (coverUrlField) {
            logDebug(`Form ${formId} has cover_url field with value: ${coverUrlField.value}`);
        } else {
            logDebug(`Form ${formId} does NOT have cover_url field`);
        }

        // Monitor form submission
        form.addEventListener('submit', function(e) {
            logDebug(`Form ${formId} is being submitted`);

            // Log all form data
            const formData = new FormData(this);
            for (let [key, value] of formData.entries()) {
                if (key.includes('image') || key.includes('cover') || key.includes('avatar')) {
                    logDebug(`Form data: ${key} = ${value}`);
                }
            }
        });
    });

    // Patch the original ImageUploader class methods
    if (window.imageUploader) {
        // Save original methods
        const originalUpdatePreview = window.imageUploader.updatePreview;
        const originalRemoveImage = window.imageUploader.removeImage;
        const originalOpenMediaLibrary = window.imageUploader.openMediaLibrary;

        // Override updatePreview
        window.imageUploader.updatePreview = function(component, url, dimensions) {
            logDebug(`updatePreview called with URL: ${url}`);
            return originalUpdatePreview.call(this, component, url, dimensions);
        };

        // Override removeImage
        window.imageUploader.removeImage = function(component) {
            const urlInput = component.querySelector('.image-url-input');
            logDebug(`removeImage called for field: ${urlInput ? urlInput.name : 'unknown'}`);
            return originalRemoveImage.call(this, component);
        };

        // Override openMediaLibrary
        window.imageUploader.openMediaLibrary = function(component) {
            const urlInput = component.querySelector('.image-url-input');
            logDebug(`openMediaLibrary called for field: ${urlInput ? urlInput.name : 'unknown'}`);
            return originalOpenMediaLibrary.call(this, component);
        };

        logDebug('Successfully patched ImageUploader methods');
    } else {
        logDebug('WARNING: imageUploader not found, cannot patch methods');
    }

    // Monitor AJAX requests
    const originalXHROpen = XMLHttpRequest.prototype.open;
    const originalXHRSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url) {
        this._url = url;
        this._method = method;
        return originalXHROpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function(data) {
        if (this._url && (this._url.includes('update-thumbnail') || this._url.includes('upload'))) {
            logDebug(`AJAX ${this._method} request to ${this._url}`);
            if (data) {
                logDebug(`AJAX data: ${data}`);
            }

            this.addEventListener('load', function() {
                logDebug(`AJAX response from ${this._url}: ${this.status}`);
                try {
                    const response = JSON.parse(this.responseText);
                    logDebug(`AJAX response data: ${JSON.stringify(response)}`);
                } catch (e) {
                    logDebug(`AJAX response is not JSON: ${this.responseText.substring(0, 100)}...`);
                }
            });
        }
        return originalXHRSend.apply(this, arguments);
    };

    logDebug('AJAX monitoring enabled');
});
