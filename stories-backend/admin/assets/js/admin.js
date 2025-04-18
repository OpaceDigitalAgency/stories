/**
 * Stories Admin UI JavaScript
 * 
 * This file contains JavaScript functions for the admin UI.
 * Version: 2.0.0
 */

// Check if jQuery is loaded
function jQueryLoaded() {
    return (typeof jQuery !== 'undefined');
}

// Initialize jQuery-dependent features
function initJQueryFeatures() {
    console.log("Initializing jQuery-dependent features");
    
    // Ensure we wait a moment for jQuery plugins to load
    setTimeout(function() {
        initTagInputs();
        initDataTables();
        // Add any other jQuery-dependent initializations here
    }, 100);
}

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded, initializing non-jQuery features");
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize rich text editors
    initRichTextEditors();
    
    // Initialize media upload preview
    initMediaUploadPreview();
    
    // Initialize delete confirmations
    initDeleteConfirmations();
    
    // Initialize date pickers
    initDatePickers();
    
    // Initialize modals
    initModals();
    
    // Initialize form feedback
    initFormFeedback();
    
    // Initialize loading indicators
    initLoadingIndicators();
    
    // Initialize dropdowns
    // initDropdowns();  // Commented out as this function is not properly defined
    
    // Check if jQuery is loaded before initializing jQuery-dependent features
    if (jQueryLoaded()) {
        console.log("jQuery already loaded on DOM ready");
        // Delay initialization slightly to ensure all jQuery plugins are loaded
        setTimeout(function() {
            initJQueryFeatures();
        }, 200);
    } else {
        console.warn("jQuery is not loaded on DOM ready. Waiting for jQuery...");
        
        // Listen for the custom jqueryLoaded event from the fallback loader
        document.addEventListener('jqueryLoaded', function() {
            console.log("jQuery loaded event received");
            // Delay initialization slightly to ensure all jQuery plugins are loaded
            setTimeout(function() {
                initJQueryFeatures();
            }, 200);
        });
        
        // Also set up a fallback timer in case the event doesn't fire
        var jQueryRetryCount = 0;
        var maxJQueryRetries = 10; // Maximum number of retries
        
        setTimeout(function checkJQuery() {
            if (jQueryLoaded()) {
                console.log("jQuery detected by timer. Initializing jQuery-dependent features.");
                initJQueryFeatures();
            } else {
                jQueryRetryCount++;
                if (jQueryRetryCount < maxJQueryRetries) {
                    console.warn("jQuery still not loaded. Trying again... (Attempt " + jQueryRetryCount + "/" + maxJQueryRetries + ")");
                    setTimeout(checkJQuery, 500);
                } else {
                    console.error("Failed to load jQuery after " + maxJQueryRetries + " attempts. Some features may not work properly.");
                    // Try to load jQuery one last time using a different CDN
                    var lastResortScript = document.createElement('script');
                    lastResortScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js';
                    document.head.appendChild(lastResortScript);
                }
            }
        }, 1000);
    }
});

/**
 * Initialize Bootstrap tooltips
 */
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover'
        });
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    // Get all forms with the 'needs-validation' class
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Find the first invalid field and focus it
                const invalidField = form.querySelector(':invalid');
                if (invalidField) {
                    invalidField.focus();
                    
                    // Show a notification for the error
                    showNotification('Please correct the errors in the form before submitting.', 'danger');
                }
            } else {
                // Prevent default form submission
                event.preventDefault();
                
                // Show loading overlay for form submissions
                showLoading('Processing your request...');
                
                // Get form data
                const formData = new FormData(form);
                
                // Add form identifier for debugging
                formData.append('_form_id', form.id || 'unnamed_form');
                
                // Log form data for debugging
                console.log('[FORM] Submitting form:', form.id || 'unnamed_form');
                console.log('[FORM] Form data:', Array.from(formData.entries()));
                
                // Get form action URL
                const actionUrl = form.getAttribute('action');
                
                // Determine method (POST for create, PUT for edit)
                let method = 'POST';
                if (actionUrl.includes('action=edit')) {
                    method = 'PUT';
                }
                
                // Use our custom AJAX function to submit the form
                console.log('[FORM] Submitting to:', actionUrl, 'with method:', method);
                
                // Use our custom AJAX function instead of fetch
                ajaxRequest(
                    actionUrl,
                    method,
                    formData,  // Pass FormData directly
                    function(response) {
                        // Success callback
                        hideLoading();
                        console.log('[FORM] Success response:', response);
                        
                        // Get success message from response or use default
                        let successMessage = 'Form submitted successfully';
                        if (response && response.message) {
                            successMessage = response.message;
                        }
                        
                        showNotification(successMessage, 'success');
                        
                        // Redirect to the list page
                        const listUrl = actionUrl.split('?')[0];
                        console.log('[FORM] Redirecting to:', listUrl);
                        window.location.href = listUrl;
                    },
                    function(error) {
                        // Error callback
                        hideLoading();
                        console.error('[FORM] Error response:', error);
                        
                        // Create a more detailed error message
                        let errorMessage = 'Error: ' + error.message;
                        
                        // If we have detailed error information, display it
                        if (error.details && error.details.errors) {
                            const errorDetails = error.details.errors;
                            errorMessage += '<ul class="mt-2 mb-0">';
                            
                            // Handle array of errors or object of errors
                            if (Array.isArray(errorDetails)) {
                                errorDetails.forEach(err => {
                                    errorMessage += `<li>${err}</li>`;
                                });
                            } else {
                                for (const field in errorDetails) {
                                    if (Array.isArray(errorDetails[field])) {
                                        errorDetails[field].forEach(err => {
                                            errorMessage += `<li>${field}: ${err}</li>`;
                                        });
                                    } else {
                                        errorMessage += `<li>${field}: ${errorDetails[field]}</li>`;
                                    }
                                }
                            }
                            
                            errorMessage += '</ul>';
                        }
                        
                        showNotification(errorMessage, 'danger');
                        
                        // Highlight fields with errors if we have field-specific errors
                        if (error.details && error.details.errors && typeof error.details.errors === 'object') {
                            for (const field in error.details.errors) {
                                const inputField = form.querySelector(`[name="${field}"]`);
                                if (inputField) {
                                    inputField.classList.add('is-invalid');
                                    
                                    // Add error message below the field
                                    const feedbackDiv = document.createElement('div');
                                    feedbackDiv.className = 'invalid-feedback';
                                    feedbackDiv.textContent = Array.isArray(error.details.errors[field])
                                        ? error.details.errors[field].join(', ')
                                        : error.details.errors[field];
                                    
                                    // Remove any existing feedback
                                    const existingFeedback = inputField.parentNode.querySelector('.invalid-feedback');
                                    if (existingFeedback) {
                                        existingFeedback.remove();
                                    }
                                    
                                    inputField.parentNode.appendChild(feedbackDiv);
                                }
                            }
                        }
                    }
                );
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Add real-time validation feedback
    document.querySelectorAll('.form-control, .form-select').forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else if (this.value !== '') {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });
    });
}

/**
 * Initialize CKEditor for rich text fields
 */
function initRichTextEditors() {
    // Check if CKEditor is loaded
    if (typeof ClassicEditor !== 'undefined') {
        // Get all textareas with the 'rich-text-editor' class
        document.querySelectorAll('.rich-text-editor').forEach(function(element) {
            ClassicEditor
                .create(element, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'mediaEmbed', 'undo', 'redo'],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                        ]
                    }
                })
                .then(editor => {
                    // Store editor instance for later use
                    element.ckeditor = editor;
                    
                    // Add change event listener
                    editor.model.document.on('change:data', () => {
                        // Trigger change event on the original textarea
                        const event = new Event('change', { bubbles: true });
                        element.dispatchEvent(event);
                    });
                })
                .catch(error => {
                    console.error('Error initializing CKEditor:', error);
                    showNotification('Error initializing rich text editor. Please try reloading the page.', 'danger');
                });
        });
    }
}

/**
 * Initialize media upload preview
 */
function initMediaUploadPreview() {
    // Get all file inputs with the 'media-upload' class
    document.querySelectorAll('.media-upload').forEach(function(input) {
        // Create preview container if it doesn't exist
        let previewContainer = document.querySelector(input.dataset.previewContainer || '#preview-container');
        if (!previewContainer && input.dataset.preview) {
            previewContainer = document.createElement('div');
            previewContainer.id = 'preview-container';
            previewContainer.className = 'mt-3';
            input.parentNode.appendChild(previewContainer);
        }
        
        // Add file selection event listener
        input.addEventListener('change', function() {
            // Get the preview element
            var preview = document.querySelector(this.dataset.preview);
            
            if (preview) {
                // Check if a file is selected
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const fileType = file.type.split('/')[0];
                    
                    // Show loading indicator
                    preview.src = '';
                    preview.style.display = 'none';
                    
                    if (previewContainer) {
                        previewContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Processing file...</p></div>';
                    }
                    
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Clear loading indicator
                        if (previewContainer) {
                            previewContainer.innerHTML = '';
                        }
                        
                        // Handle different file types
                        if (fileType === 'image') {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                            
                            // Add file info
                            if (previewContainer) {
                                const fileInfo = document.createElement('div');
                                fileInfo.className = 'mt-2 text-muted small';
                                fileInfo.innerHTML = `<strong>File:</strong> ${file.name}<br><strong>Size:</strong> ${formatFileSize(file.size)}<br><strong>Type:</strong> ${file.type}`;
                                previewContainer.appendChild(preview);
                                previewContainer.appendChild(fileInfo);
                            }
                        } else {
                            // For non-image files, show file info
                            if (previewContainer) {
                                let icon = 'fa-file';
                                if (fileType === 'video') icon = 'fa-file-video';
                                else if (fileType === 'audio') icon = 'fa-file-audio';
                                else if (file.type.includes('pdf')) icon = 'fa-file-pdf';
                                else if (file.type.includes('word')) icon = 'fa-file-word';
                                else if (file.type.includes('excel')) icon = 'fa-file-excel';
                                
                                previewContainer.innerHTML = `
                                    <div class="text-center p-4 border rounded">
                                        <i class="fas ${icon} fa-3x mb-3 text-primary"></i>
                                        <h5>${file.name}</h5>
                                        <p class="mb-0 text-muted">${formatFileSize(file.size)} - ${file.type || 'Unknown type'}</p>
                                    </div>
                                `;
                            }
                        }
                    };
                    
                    reader.onerror = function() {
                        if (previewContainer) {
                            previewContainer.innerHTML = '<div class="alert alert-danger">Error loading file preview</div>';
                        }
                    };
                    
                    reader.readAsDataURL(file);
                } else {
                    // No file selected, clear preview
                    preview.src = '';
                    preview.style.display = 'none';
                    
                    if (previewContainer) {
                        previewContainer.innerHTML = '';
                    }
                }
            }
        });
    });
}

/**
 * Format file size in human-readable format
 * 
 * @param {number} bytes - File size in bytes
 * @returns {string} Formatted file size
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Initialize delete confirmations
 */
function initDeleteConfirmations() {
    // Get all delete buttons with the 'delete-confirm' class
    document.querySelectorAll('.delete-confirm').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Get confirmation message
            const message = this.dataset.confirmMessage || 'Are you sure you want to delete this item? This action cannot be undone.';
            const itemName = this.dataset.itemName || 'this item';
            
            // Set confirmation modal content
            const modal = document.getElementById('confirmationModal');
            if (modal) {
                const confirmMessage = modal.querySelector('#confirmationMessage');
                if (confirmMessage) {
                    confirmMessage.innerHTML = message;
                }
                
                const confirmButton = modal.querySelector('#confirmAction');
                if (confirmButton) {
                    // Store the original href
                    confirmButton.dataset.href = this.href;
                    
                    // Set up the confirm action
                    confirmButton.onclick = function() {
                        showLoading(`Deleting ${itemName}...`);
                        
                        // Use AJAX to submit the delete request
                        const url = this.dataset.href;
                        console.log('[DELETE] Delete URL:', url);
                        
                        // Add a timestamp to prevent caching
                        const timestampedUrl = url + (url.includes('?') ? '&' : '?') + '_t=' + new Date().getTime() + '&_ajax=1';
                        
                        // Use our custom AJAX function instead of fetch for consistent handling
                        ajaxRequest(
                            timestampedUrl,
                            'DELETE',  // Use proper DELETE method instead of GET
                            null,
                            function(response) {
                                // Success callback
                                console.log('[DELETE] Success response:', response);
                                hideLoading();
                                
                                // Get success message from response or use default
                                let successMessage = 'Item deleted successfully';
                                if (response && response.message) {
                                    successMessage = response.message;
                                }
                                
                                showNotification(successMessage, 'success');
                                
                                // Redirect to the list page
                                const listUrl = url.split('?')[0];
                                console.log('[DELETE] Redirecting to:', listUrl);
                                window.location.href = listUrl;
                            },
                            function(error) {
                                // Error callback
                                console.error('[DELETE] Error response:', error);
                                hideLoading();
                                
                                // Create a more detailed error message
                                let errorMessage = 'Error deleting item: ' + error.message;
                                
                                // If we have detailed error information, display it
                                if (error.details && error.details.errors) {
                                    const errorDetails = error.details.errors;
                                    errorMessage += '<ul class="mt-2 mb-0">';
                                    
                                    // Handle array of errors or object of errors
                                    if (Array.isArray(errorDetails)) {
                                        errorDetails.forEach(err => {
                                            errorMessage += `<li>${err}</li>`;
                                        });
                                    } else {
                                        for (const field in errorDetails) {
                                            errorMessage += `<li>${field}: ${errorDetails[field]}</li>`;
                                        }
                                    }
                                    
                                    errorMessage += '</ul>';
                                }
                                
                                showNotification(errorMessage, 'danger');
                            }
                        );
                    };
                }
                
                // Show the modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            } else {
                // Fallback to standard confirm dialog
                if (confirm(message)) {
                    showLoading(`Deleting ${itemName}...`);
                    
                    const url = this.href;
                    console.log('[DELETE] Delete URL (fallback):', url);
                    
                    // Add a timestamp to prevent caching
                    const timestampedUrl = url + (url.includes('?') ? '&' : '?') + '_t=' + new Date().getTime() + '&_ajax=1';
                    
                    // Use our custom AJAX function instead of fetch for consistent handling
                    ajaxRequest(
                        timestampedUrl,
                        'DELETE',  // Use proper DELETE method instead of GET
                        null,
                        function(response) {
                            // Success callback
                            console.log('[DELETE] Success response (fallback):', response);
                            hideLoading();
                            
                            // Get success message from response or use default
                            let successMessage = 'Item deleted successfully';
                            if (response && response.message) {
                                successMessage = response.message;
                            }
                            
                            showNotification(successMessage, 'success');
                            
                            // Redirect to the list page
                            const listUrl = url.split('?')[0];
                            console.log('[DELETE] Redirecting to (fallback):', listUrl);
                            window.location.href = listUrl;
                        },
                        function(error) {
                            // Error callback
                            console.error('[DELETE] Error response (fallback):', error);
                            hideLoading();
                            
                            // Create a more detailed error message
                            let errorMessage = 'Error deleting item: ' + error.message;
                            
                            // If we have detailed error information, display it
                            if (error.details && error.details.errors) {
                                const errorDetails = error.details.errors;
                                errorMessage += '<ul class="mt-2 mb-0">';
                                
                                // Handle array of errors or object of errors
                                if (Array.isArray(errorDetails)) {
                                    errorDetails.forEach(err => {
                                        errorMessage += `<li>${err}</li>`;
                                    });
                                } else {
                                    for (const field in errorDetails) {
                                        errorMessage += `<li>${field}: ${errorDetails[field]}</li>`;
                                    }
                                }
                                
                                errorMessage += '</ul>';
                            }
                            
                            showNotification(errorMessage, 'danger');
                        }
                    );
                }
            }
        });
    });
}

/**
 * Initialize modals
 */
function initModals() {
    // Handle dynamic content loading in modals
    document.querySelectorAll('[data-bs-toggle="modal"][data-remote]').forEach(function(button) {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            const remote = this.getAttribute('data-remote');
            const modal = document.querySelector(target);
            
            if (modal && remote) {
                const modalBody = modal.querySelector('.modal-body');
                if (modalBody) {
                    // Show loading indicator
                    modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-3">Loading content...</p></div>';
                    
                    // Load remote content
                    fetch(remote)
                        .then(response => response.text())
                        .then(html => {
                            modalBody.innerHTML = html;
                            
                            // Initialize any form elements in the modal
                            initFormValidation();
                            initTooltips();
                            
                            // Trigger contentLoaded event
                            modal.dispatchEvent(new CustomEvent('contentLoaded'));
                        })
                        .catch(error => {
                            modalBody.innerHTML = '<div class="alert alert-danger">Error loading content: ' + error.message + '</div>';
                        });
                }
            }
        });
    });
}

/**
 * Initialize form feedback
 */
function initFormFeedback() {
    // Add inline validation feedback for form fields
    document.querySelectorAll('.form-control, .form-select').forEach(function(input) {
        // Create feedback elements if they don't exist
        if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
            const invalidFeedback = document.createElement('div');
            invalidFeedback.className = 'invalid-feedback';
            input.parentNode.insertBefore(invalidFeedback, input.nextSibling);
        }
        
        if (!input.nextElementSibling.nextElementSibling || !input.nextElementSibling.nextElementSibling.classList.contains('valid-feedback')) {
            const validFeedback = document.createElement('div');
            validFeedback.className = 'valid-feedback';
            validFeedback.textContent = 'Looks good!';
            input.parentNode.insertBefore(validFeedback, input.nextElementSibling.nextSibling);
        }
        
        // Update invalid feedback message based on validation state
        input.addEventListener('invalid', function() {
            const invalidFeedback = this.nextElementSibling;
            if (invalidFeedback && invalidFeedback.classList.contains('invalid-feedback')) {
                if (this.validity.valueMissing) {
                    invalidFeedback.textContent = 'This field is required.';
                } else if (this.validity.typeMismatch) {
                    invalidFeedback.textContent = 'Please enter a valid format.';
                } else if (this.validity.patternMismatch) {
                    invalidFeedback.textContent = this.dataset.errorPattern || 'Please match the requested format.';
                } else if (this.validity.tooShort) {
                    invalidFeedback.textContent = `Please enter at least ${this.minLength} characters.`;
                } else if (this.validity.tooLong) {
                    invalidFeedback.textContent = `Please enter no more than ${this.maxLength} characters.`;
                } else if (this.validity.rangeUnderflow) {
                    invalidFeedback.textContent = `Please enter a value greater than or equal to ${this.min}.`;
                } else if (this.validity.rangeOverflow) {
                    invalidFeedback.textContent = `Please enter a value less than or equal to ${this.max}.`;
                } else if (this.validity.stepMismatch) {
                    invalidFeedback.textContent = `Please enter a valid value.`;
                } else {
                    invalidFeedback.textContent = this.dataset.errorMessage || 'Please enter a valid value.';
                }
            }
        });
    });
}

/**
 * Initialize loading indicators
 */
function initLoadingIndicators() {
    // Add loading indicators to buttons with the 'btn-loading' class
    document.querySelectorAll('.btn-loading').forEach(function(button) {
        button.addEventListener('click', function() {
            // Don't show loading for buttons that open modals or have other special behaviors
            if (this.getAttribute('data-bs-toggle') || this.getAttribute('type') === 'button') {
                return;
            }
            
            // Store original content
            if (!this.dataset.originalHtml) {
                this.dataset.originalHtml = this.innerHTML;
            }
            
            // Show loading spinner
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
            this.disabled = true;
            
            // For form submissions, show the loading overlay
            const form = this.closest('form');
            if (form && form.checkValidity()) {
                showLoading();
            }
        });
    });
    
    // Add loading indicators to forms with the 'form-loading' class
    document.querySelectorAll('form.form-loading').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (this.checkValidity()) {
                showLoading();
                
                // Disable all buttons
                this.querySelectorAll('button[type="submit"]').forEach(function(button) {
                    if (!button.dataset.originalHtml) {
                        button.dataset.originalHtml = button.innerHTML;
                    }
                    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                    button.disabled = true;
                });
            }
        });
    });
}

/**
 * Initialize tag inputs
 */
function initTagInputs() {
    // First check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded. Cannot initialize tag inputs.');
        return;
    }
    
    // Use jQuery safely with a local $ variable to avoid reference errors
    jQuery(function($) {
        try {
            // Check if Bootstrap Tags Input is loaded
            if (typeof $.fn.tagsinput !== 'undefined') {
                // Initialize Bootstrap Tags Input
                $('.tags-input').tagsinput({
                    trimValue: true,
                    confirmKeys: [13, 44, 32], // Enter, comma, space
                    tagClass: 'badge bg-primary'
                });
                console.log('Tag inputs initialized successfully');
            } else {
                console.warn('Bootstrap Tags Input plugin is not loaded');
            }
        } catch (e) {
            console.error('Error initializing tag inputs:', e);
        }
    });
}

/**
 * Initialize DataTables
 */
function initDataTables() {
    // First check if jQuery is loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded. Cannot initialize DataTables.');
        return;
    }
    
    // Check if DataTables is loaded
    if (typeof jQuery.fn.DataTable === 'undefined') {
        console.warn('DataTables is not loaded. Tables will use default styling.');
        return;
    }
    
    // Use jQuery safely with a local $ variable
    jQuery(function($) {
        try {
            // Initialize DataTables for tables with the 'datatable' class
            $('.datatable').each(function() {
                $(this).DataTable({
                    responsive: true,
                    language: {
                        search: '<i class="fas fa-search"></i>',
                        searchPlaceholder: 'Search...',
                        paginate: {
                            first: '<i class="fas fa-angle-double-left"></i>',
                            previous: '<i class="fas fa-angle-left"></i>',
                            next: '<i class="fas fa-angle-right"></i>',
                            last: '<i class="fas fa-angle-double-right"></i>'
                        }
                    }
                });
            });
            
            console.log('DataTables initialized successfully');
        } catch (e) {
            console.error('Error initializing DataTables:', e);
        }
    });
}

/**
 * Initialize date pickers
 */
function initDatePickers() {
    // Check if Flatpickr is loaded
    if (typeof flatpickr !== 'undefined') {
        // Initialize Flatpickr for date inputs
        flatpickr('.date-picker', {
            enableTime: false,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'F j, Y',
            animate: true
        });
        
        // Initialize Flatpickr for datetime inputs
        flatpickr('.datetime-picker', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            altInput: true,
            altFormat: 'F j, Y at h:i K',
            animate: true
        });
    }
}

/**
 * Show loading overlay
 * 
 * @param {string} message - Optional message to display
 */
function showLoading(message = 'Processing your request...') {
    // Create loading overlay if it doesn't exist
    let overlay = document.querySelector('.loading-overlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="spinner-container">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 loading-message">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        // Update message if overlay already exists
        const messageEl = overlay.querySelector('.loading-message');
        if (messageEl) {
            messageEl.textContent = message;
        }
        
        // Make sure overlay is visible
        overlay.classList.remove('d-none');
    }
}

/**
 * Hide loading overlay
 */
function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.classList.add('d-none');
    }
}

/**
 * Make an AJAX request
 * 
 * @param {string} url - The URL to send the request to
 * @param {string} method - The HTTP method (GET, POST, PUT, DELETE)
 * @param {object} data - The data to send with the request
 * @param {function} successCallback - The function to call on success
 * @param {function} errorCallback - The function to call on error
 */
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    // Show loading overlay
    showLoading();
    
    console.log(`[AJAX] ${method} request to ${url}`);
    
    // Create XMLHttpRequest object
    var xhr = new XMLHttpRequest();
    
    // Configure it
    xhr.open(method, url, true);
    
    // Add common headers
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        xhr.setRequestHeader('X-CSRF-Token', csrfToken);
    }
    
    // Handle data based on type and method
    if (data instanceof FormData) {
        // For FormData, add method override for PUT/DELETE
        if (method === 'PUT' || method === 'DELETE') {
            data.append('_method', method);
            // Actually send as POST to handle FormData properly
            xhr.open('POST', url, true);
        }
        console.log('[AJAX] Sending FormData:', Array.from(data.entries()));
    } else if (data !== null && typeof data === 'object') {
        xhr.setRequestHeader('Content-Type', 'application/json');
        // For PUT/DELETE with JSON, send normally
        data = JSON.stringify(data);
        console.log('[AJAX] Sending JSON data:', data);
    } else {
        // For GET requests or null data
        console.log('[AJAX] Sending request without body data');
    }
    
    // Set up callback
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            // Hide loading overlay
            hideLoading();
            
            console.log(`[AJAX] Response status: ${xhr.status}`);
            console.log('[AJAX] Response headers:', xhr.getAllResponseHeaders());
            console.log('[AJAX] Response text:', xhr.responseText);
            
            if (xhr.status >= 200 && xhr.status < 300) {
                // Success
                var response;
                try {
                    response = JSON.parse(xhr.responseText);
                    console.log('[AJAX] Parsed JSON response:', response);
                } catch (e) {
                    response = xhr.responseText;
                    console.log('[AJAX] Response is not JSON:', e);
                }
                
                if (typeof successCallback === 'function') {
                    console.log('[AJAX] Calling success callback');
                    successCallback(response);
                }
            } else {
                // Error
                var error = {
                    status: xhr.status,
                    message: xhr.statusText || 'Unknown error',
                    responseText: xhr.responseText
                };
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    console.log('[AJAX] Parsed error response:', response);
                    if (response.error) {
                        error.message = response.error;
                    } else if (response.message) {
                        error.message = response.message;
                    }
                    error.details = response;
                } catch (e) {
                    // Ignore parsing error
                    console.log('[AJAX] Error response is not JSON:', e);
                }
                
                console.error('[AJAX] Error:', error);
                
                if (typeof errorCallback === 'function') {
                    console.log('[AJAX] Calling error callback');
                    errorCallback(error);
                } else {
                    showNotification('Error: ' + error.message, 'danger');
                }
            }
        }
    };
    
    // Send the request
    xhr.send(data);
}

/**
 * Show a notification
 * 
 * @param {string} message - The message to display
 * @param {string} type - The type of notification (success, danger, warning, info)
 * @param {number} duration - The duration in milliseconds
 */
function showNotification(message, type = 'success', duration = 5000) {
    // Create notification element
    var notification = document.createElement('div');
    notification.className = 'alert alert-' + type + ' alert-dismissible fade show notification';
    
    // Add icon based on type
    let icon = 'fa-check-circle';
    if (type === 'danger') icon = 'fa-exclamation-circle';
    else if (type === 'warning') icon = 'fa-exclamation-triangle';
    else if (type === 'info') icon = 'fa-info-circle';
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${icon} me-2"></i>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add notification to the container
    var container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    /**
     * Initialize Bootstrap dropdowns
     */
    function initDropdowns() {
        // Get all dropdown toggle elements
        var dropdownToggleList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
        
        // Initialize each dropdown
        dropdownToggleList.forEach(function(dropdownToggle) {
            // Create dropdown instance
            var dropdown = new bootstrap.Dropdown(dropdownToggle);
            
            // Add click event listener
            dropdownToggle.addEventListener('click', function(e) {
                e.preventDefault();
                dropdown.toggle();
            });
            
            // Log initialization
            console.log('Dropdown initialized:', dropdownToggle.id || 'unnamed dropdown');
        });
        
        // Specifically handle the Features dropdown
        var featuresDropdown = document.getElementById('featuresDropdown');
        if (featuresDropdown) {
            console.log('Features dropdown found, adding special handling');
            
            // Ensure dropdown items work correctly
            var featuresDropdownItems = document.querySelectorAll('[aria-labelledby="featuresDropdown"] .dropdown-item');
            featuresDropdownItems.forEach(function(item) {
                item.addEventListener('click', function(e) {
                    // Prevent default only if needed for special handling
                    // e.preventDefault();
                    
                    // Navigate to the href
                    window.location.href = this.getAttribute('href');
                });
            });
        }
    }
    
    container.appendChild(notification);
    
    // Initialize Bootstrap alert
    var alert = new bootstrap.Alert(notification);
    
    // Auto-close after duration
    setTimeout(function() {
        alert.close();
    }, duration);
    
    // Remove from DOM after animation
    notification.addEventListener('closed.bs.alert', function() {
        notification.remove();
    });
}