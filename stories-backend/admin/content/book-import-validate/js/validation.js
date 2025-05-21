/**
 * Book Validation Logic
 *
 * Main JavaScript for the book validation interface.
 */

// Initialize validation interface
function initValidationInterface() {
    // Set up event listeners
    setupEventListeners();

    // Initialize tooltips
    initTooltips();

    console.log('Validation interface initialized');
}

// Set up event listeners for the validation interface
function setupEventListeners() {
    // Apply button click
    document.querySelectorAll('.apply-button').forEach(button => {
        if (!button.disabled) {
            button.addEventListener('click', handleApplyButtonClick);
        }
    });

    // Global action buttons
    document.getElementById('applyAllValid')?.addEventListener('click', handleApplyAllValid);
    document.getElementById('resetAll')?.addEventListener('click', handleResetAll);
    document.getElementById('validateAgain')?.addEventListener('click', handleValidateAgain);
    document.getElementById('exportChanges')?.addEventListener('click', handleExportChanges);
    document.getElementById('refreshValidation')?.addEventListener('click', handleRefreshValidation);
    document.getElementById('bypassCache')?.addEventListener('click', handleBypassCache);

    // Source-specific apply all buttons
    document.querySelectorAll('.apply-all-source').forEach(button => {
        button.addEventListener('click', handleApplyAllFromSource);
    });

    // Search by title button (when no data found)
    document.getElementById('searchByTitle')?.addEventListener('click', handleSearchByTitle);

    // Manual entry button (when no data found)
    document.getElementById('manualEntry')?.addEventListener('click', handleManualEntry);
}

// Initialize Bootstrap tooltips
function initTooltips() {
    // Check if Bootstrap's tooltip function exists
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    } else {
        console.warn('Bootstrap Tooltip not available');
    }
}

// Handle apply button click
function handleApplyButtonClick(event) {
    const button = event.currentTarget;
    const field = button.dataset.field;
    const source = button.dataset.source;
    const status = button.dataset.status;

    // Get the value from the source cell
    const sourceCell = button.closest('.source-value');
    const valueContainer = sourceCell.querySelector('.value-container');
    let value = '';

    // Extract the value based on the field type
    if (field === 'cover_url') {
        // For cover images, get the src attribute
        const img = valueContainer.querySelector('img');
        value = img ? img.src : '';
    } else if (field === 'preview_link') {
        // For preview links, get the href attribute
        const link = valueContainer.querySelector('a');
        value = link ? link.href : '';
    } else {
        // For other fields, get the text content
        value = valueContainer.textContent.trim();

        // Remove "Not available" text if present
        if (value === 'Not available') {
            value = '';
        }
    }

    // Confirm the update
    if (confirm(`Are you sure you want to update the ${field} with the value from ${source}?`)) {
        updateField(field, value, source);
    }
}

// Handle apply all valid button click
function handleApplyAllValid() {
    if (confirm('Are you sure you want to apply all valid values from all sources?')) {
        showLoadingOverlay();

        // Get the book ID from the hidden form
        const bookId = document.querySelector('#validationActionForm input[name="book_id"]').value;

        // Create form data
        const formData = new FormData();
        formData.append('action', 'apply_all_valid');
        formData.append('book_id', bookId);

        // Add cache-busting parameter to URL
        const cacheBuster = new Date().getTime();
        const url = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_cb=' + cacheBuster;

        // Send the request
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Replace the current page content with the new HTML
            document.documentElement.innerHTML = html;

            // Re-initialize the validation interface
            initValidationInterface();

            // Show success message
            showNotification('All valid values have been applied successfully.', 'success');
        })
        .catch(error => {
            console.error('Error applying all valid values:', error);
            showNotification('Error applying values. Please try again.', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
    }
}

// Handle apply all from source button click
function handleApplyAllFromSource(event) {
    const button = event.currentTarget;
    const source = button.dataset.source;

    if (confirm(`Are you sure you want to apply all values from ${source}?`)) {
        showLoadingOverlay();

        // Get the book ID from the hidden form
        const bookId = document.querySelector('#validationActionForm input[name="book_id"]').value;

        // Create form data
        const formData = new FormData();
        formData.append('action', 'apply_all_from_source');
        formData.append('book_id', bookId);
        formData.append('source', source);

        // Add cache-busting parameter to URL
        const cacheBuster = new Date().getTime();
        const url = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_cb=' + cacheBuster;

        // Send the request
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Replace the current page content with the new HTML
            document.documentElement.innerHTML = html;

            // Re-initialize the validation interface
            initValidationInterface();

            // Show success message
            showNotification(`All values from ${source} have been applied successfully.`, 'success');
        })
        .catch(error => {
            console.error(`Error applying values from ${source}:`, error);
            showNotification('Error applying values. Please try again.', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
    }
}

// Handle reset all button click
function handleResetAll() {
    if (confirm('Are you sure you want to reset all changes?')) {
        // Simply reload the page
        window.location.reload();
    }
}

// Handle validate again button click
function handleValidateAgain() {
    if (confirm('Are you sure you want to validate this book again? This may take a moment.')) {
        showLoadingOverlay();

        // Get the book ID from the hidden form
        const bookId = document.querySelector('#validationActionForm input[name="book_id"]').value;

        // Create form data
        const formData = new FormData();
        formData.append('action', 'validate_again');
        formData.append('book_id', bookId);

        // Add cache-busting parameter to URL
        const cacheBuster = new Date().getTime();
        const url = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_cb=' + cacheBuster;

        // Send the request
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Replace the current page content with the new HTML
            document.documentElement.innerHTML = html;

            // Re-initialize the validation interface
            initValidationInterface();

            // Show success message
            showNotification('Book has been validated successfully.', 'success');
        })
        .catch(error => {
            console.error('Error validating book:', error);
            showNotification('Error validating book. Please try again.', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
    }
}

// Handle export changes button click
function handleExportChanges() {
    alert('Export functionality will be implemented in a future update.');
}

// Handle refresh validation button click
function handleRefreshValidation() {
    if (confirm('Are you sure you want to refresh the data? This will clear the cache and fetch fresh data from all sources.')) {
        showLoadingOverlay();

        // Get the book ID from the hidden form
        const bookId = document.querySelector('#validationActionForm input[name="book_id"]').value;

        // Create form data
        const formData = new FormData();
        formData.append('action', 'refresh_data');
        formData.append('book_id', bookId);

        // Add cache-busting parameter to URL
        const cacheBuster = new Date().getTime();
        const url = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_cb=' + cacheBuster;

        // Send the request
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Replace the current page content with the new HTML
            document.documentElement.innerHTML = html;

            // Re-initialize the validation interface
            initValidationInterface();

            // Show success message
            showNotification('Data has been refreshed successfully.', 'success');
        })
        .catch(error => {
            console.error('Error refreshing data:', error);
            showNotification('Error refreshing data. Please try again.', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
    }
}

// Handle search by title button click
function handleSearchByTitle() {
    const bookTitle = prompt('Enter the book title to search for:');

    if (bookTitle) {
        showLoadingOverlay();

        // Get the book ID from the hidden form
        const bookId = document.querySelector('#validationActionForm input[name="book_id"]').value;

        // Create form data
        const formData = new FormData();
        formData.append('action', 'search_by_title');
        formData.append('book_id', bookId);
        formData.append('title', bookTitle);

        // Add cache-busting parameter to URL
        const cacheBuster = new Date().getTime();
        const url = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_cb=' + cacheBuster;

        // Send the request
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Replace the current page content with the new HTML
            document.documentElement.innerHTML = html;

            // Re-initialize the validation interface
            initValidationInterface();
        })
        .catch(error => {
            console.error('Error searching by title:', error);
            showNotification('Error searching by title. Please try again.', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
    }
}

// Handle manual entry button click
function handleManualEntry() {
    alert('Manual entry functionality will be implemented in a future update.');
}

// Handle bypass cache button click
function handleBypassCache() {
    if (confirm('Are you sure you want to bypass all caches? This will force a fresh load from all sources and may take longer to complete.')) {
        showLoadingOverlay('Bypassing all caches and fetching fresh data...');

        // Get the book ID from the hidden form
        const bookId = document.querySelector('#validationActionForm input[name="book_id"]').value;

        // Create form data
        const formData = new FormData();
        formData.append('action', 'bypass_all_caches');
        formData.append('book_id', bookId);

        // Add cache-busting parameter to URL
        const cacheBuster = new Date().getTime();
        const url = window.location.href + (window.location.href.includes('?') ? '&' : '?') + '_cb=' + cacheBuster;

        // Send the request
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Replace the current page content with the new HTML
            document.documentElement.innerHTML = html;

            // Re-initialize the validation interface
            initValidationInterface();

            // Show success message
            showNotification('All caches have been bypassed and fresh data has been fetched.', 'success');
        })
        .catch(error => {
            console.error('Error bypassing caches:', error);
            showNotification('Error bypassing caches. Please try again.', 'error');
        })
        .finally(() => {
            hideLoadingOverlay();
        });
    }
}

// Update a field with a new value
function updateField(field, value, source) {
    showLoadingOverlay();

    // Get the book ID from the hidden form
    const bookId = document.querySelector('#validationActionForm input[name="book_id"]').value;

    // Update the hidden form values
    document.getElementById('actionType').value = 'update_field';
    document.getElementById('actionField').value = field;
    document.getElementById('actionValue').value = value;
    document.getElementById('actionSource').value = source;

    // Add a cache-busting parameter to the form
    const cacheBuster = new Date().getTime();
    let cacheBustInput = document.getElementById('cacheBuster');
    if (!cacheBustInput) {
        cacheBustInput = document.createElement('input');
        cacheBustInput.type = 'hidden';
        cacheBustInput.id = 'cacheBuster';
        cacheBustInput.name = '_cb';
        document.getElementById('validationActionForm').appendChild(cacheBustInput);
    }
    cacheBustInput.value = cacheBuster;

    // Submit the form
    document.getElementById('validationActionForm').submit();
}

// Show loading overlay
function showLoadingOverlay(message = 'Loading...') {
    // Create loading overlay if it doesn't exist
    let overlay = document.querySelector('.loading-overlay');

    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner-container">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="loading-message mt-3">${message}</div>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        // Update message if overlay already exists
        const messageEl = overlay.querySelector('.loading-message');
        if (messageEl) {
            messageEl.textContent = message;
        }
        overlay.style.display = 'flex';
    }

    // Add CSS to make the overlay centered
    const style = document.createElement('style');
    if (!document.querySelector('#loading-overlay-style')) {
        style.id = 'loading-overlay-style';
        style.textContent = `
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }
            .loading-spinner-container {
                background-color: white;
                padding: 30px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            }
            .loading-message {
                margin-top: 15px;
                font-weight: bold;
            }
        `;
        document.head.appendChild(style);
    }
}

// Hide loading overlay
function hideLoadingOverlay() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Check if the notification container exists
    let container = document.querySelector('.notification-container');

    // Create container if it doesn't exist
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add to container
    container.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 150);
    }, 5000);
}
