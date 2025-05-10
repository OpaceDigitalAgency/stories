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
    });

    // Monitor form submissions
    const forms = document.querySelectorAll('form');
    logDebug(`Found ${forms.length} forms`);

    forms.forEach((form, index) => {
        // Skip if not a real form element (e.g., if it's an input element)
        if (!(form instanceof HTMLFormElement)) {
            logDebug(`Skipping non-form element at index ${index}: ${form.tagName}`);
            return;
        }

        const formId = form.id || `form-${index}`;
        const action = form.getAttribute('action') || 'unknown';
        logDebug(`Form #${index+1}: ${formId}, Action: ${action}`);

        // Check for image_updated field
        const imageUpdatedField = form.querySelector('input[name="image_updated"]');
        if (imageUpdatedField) {
            logDebug(`Form ${formId} has image_updated field with value: ${imageUpdatedField.value}`);
        } else {
            logDebug(`Form ${formId} does NOT have image_updated field`);
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
