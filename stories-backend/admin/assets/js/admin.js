/**
 * Stories Admin UI JavaScript
 * 
 * This file contains JavaScript functions for the admin UI.
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
        return new bootstrap.Tooltip(tooltipTriggerEl);
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
            }
            
            form.classList.add('was-validated');
        }, false);
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
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'undo', 'redo'],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                        ]
                    }
                })
                .catch(error => {
                    console.error(error);
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
        input.addEventListener('change', function() {
            // Get the preview element
            var preview = document.querySelector(this.dataset.preview);
            
            if (preview) {
                // Check if a file is selected
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                }
            }
        });
    });
}

/**
 * Initialize delete confirmations
 */
function initDeleteConfirmations() {
    // Get all delete buttons with the 'delete-confirm' class
    document.querySelectorAll('.delete-confirm').forEach(function(button) {
        button.addEventListener('click', function(event) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                event.preventDefault();
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
 * Initialize date pickers
 */
function initDatePickers() {
    // Check if Flatpickr is loaded
    if (typeof flatpickr !== 'undefined') {
        // Initialize Flatpickr for date inputs
        flatpickr('.date-picker', {
            enableTime: false,
            dateFormat: 'Y-m-d'
        });
        
        // Initialize Flatpickr for datetime inputs
        flatpickr('.datetime-picker', {
            enableTime: true,
            dateFormat: 'Y-m-d H:i'
        });
    }
}

/**
 * Show loading spinner
 */
function showSpinner() {
    var spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(spinner);
}

/**
 * Hide loading spinner
 */
function hideSpinner() {
    var spinner = document.querySelector('.spinner-overlay');
    if (spinner) {
        spinner.remove();
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
    // Show loading spinner
    showSpinner();
    
    // Create XMLHttpRequest object
    var xhr = new XMLHttpRequest();
    
    // Configure it
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    // Set up callback
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            // Hide loading spinner
            hideSpinner();
            
            if (xhr.status >= 200 && xhr.status < 300) {
                // Success
                var response = JSON.parse(xhr.responseText);
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            } else {
                // Error
                var error = {
                    status: xhr.status,
                    message: xhr.statusText
                };
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        error.message = response.error;
                    }
                } catch (e) {
                    // Ignore parsing error
                }
                
                if (typeof errorCallback === 'function') {
                    errorCallback(error);
                } else {
                    alert('Error: ' + error.message);
                }
            }
        }
    };
    
    // Send the request
    xhr.send(data ? JSON.stringify(data) : null);
}

/**
 * Show a notification
 * 
 * @param {string} message - The message to display
 * @param {string} type - The type of notification (success, danger, warning, info)
 * @param {number} duration - The duration in milliseconds
 */
function showNotification(message, type = 'success', duration = 3000) {
    // Create notification element
    var notification = document.createElement('div');
    notification.className = 'alert alert-' + type + ' alert-dismissible fade show notification';
    notification.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    
    // Add notification to the container
    var container = document.querySelector('.notification-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    container.appendChild(notification);
    
    // Initialize Bootstrap alert
    var alert = new bootstrap.Alert(notification);
    
    // Auto-close after duration
    setTimeout(function() {
        alert.close();
    }, duration);
}