/**
 * AJAX Validation Interface
 *
 * This file contains the JavaScript for handling AJAX updates in the validation interface.
 */

// Initialize the validation interface
function initAjaxValidation() {
    // Add event listeners for apply buttons
    document.querySelectorAll('.apply-button').forEach(button => {
        button.addEventListener('click', handleApplyButtonClick);
    });

    // Add event listeners for global action buttons
    document.getElementById('applyAllValid')?.addEventListener('click', handleApplyAllValid);

    document.querySelectorAll('.apply-all-source').forEach(button => {
        button.addEventListener('click', handleApplyAllSource);
    });

    document.getElementById('resetAll')?.addEventListener('click', handleResetAll);
    document.getElementById('validateAgain')?.addEventListener('click', handleValidateAgain);
    document.getElementById('refreshValidation')?.addEventListener('click', handleRefreshValidation);

    // Handle both the button and checkbox for bypass cache
    const bypassCacheElement = document.getElementById('bypassCache');
    if (bypassCacheElement) {
        if (bypassCacheElement.type === 'checkbox') {
            // It's a checkbox in the new interface
            bypassCacheElement.addEventListener('change', function() {
                console.log('Bypass cache checkbox changed:', this.checked);
            });
        } else {
            // It's a button in the old interface
            bypassCacheElement.addEventListener('click', function() {
                console.log('Bypass cache button clicked');
            });
        }
    }

    // Initialize Bootstrap collapse for the steps display
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                // Toggle the collapse class manually if Bootstrap JS isn't loaded
                if (typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
                    if (targetElement.classList.contains('show')) {
                        targetElement.classList.remove('show');
                    } else {
                        targetElement.classList.add('show');
                    }
                }
            }
        });
    });

    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    console.log('AJAX validation interface initialized');
}

// Handle apply button click
function handleApplyButtonClick(event) {
    const button = event.currentTarget;
    const field = button.dataset.field;
    const source = button.dataset.source;
    const status = button.dataset.status;

    // Don't do anything if the button is disabled or the status is 'match' or 'empty'
    if (button.classList.contains('disabled') || status === 'match' || status === 'empty') {
        return;
    }

    // Get the value from the source
    const sourceCell = button.closest('td');
    const valueContainer = sourceCell.querySelector('.value-container');
    const value = getSourceValue(field, valueContainer);

    // Get the book ID
    const bookId = document.querySelector('input[name="book_id"]').value;

    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
    button.disabled = true;

    // Make the AJAX request
    updateField(bookId, field, value, source)
        .then(response => {
            if (response.success) {
                // Update the current value cell
                const fieldRow = button.closest('tr');
                const currentValueCell = fieldRow.querySelector('.current-value');
                currentValueCell.innerHTML = response.data.formatted_value;

                // Update the button status
                button.dataset.status = 'match';
                button.classList.remove('btn-outline-warning', 'btn-outline-danger');
                button.classList.add('btn-outline-success', 'disabled');
                button.innerHTML = '<i class="fas fa-check"></i> Applied';

                // Show success message
                showNotification('success', `Successfully updated field '${field}'`);

                // Add to validation history
                addToValidationHistory(`Updated field '${field}' from source '${source}'`);
            } else {
                // Show error message
                showNotification('danger', response.message);

                // Reset button
                button.innerHTML = '<i class="fas fa-times"></i> Failed';
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-sync-alt"></i> Apply';
                    button.disabled = false;
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error updating field:', error);
            showNotification('danger', 'Error updating field: ' + error.message);

            // Reset button
            button.innerHTML = '<i class="fas fa-times"></i> Failed';
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-sync-alt"></i> Apply';
                button.disabled = false;
            }, 2000);
        });
}

// Handle apply all from source button click
function handleApplyAllSource(event) {
    const button = event.currentTarget;
    const source = button.dataset.source;
    const bookId = document.querySelector('input[name="book_id"]').value;

    // Show confirmation dialog
    if (!confirm(`Are you sure you want to apply all fields from ${source}?`)) {
        return;
    }

    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
    button.disabled = true;

    // Make the AJAX request
    applyAllFieldsFromSource(bookId, source)
        .then(response => {
            if (response.success) {
                // Show success message
                showNotification('success', response.message);

                // Reload the page to show all updated fields
                location.reload();
            } else {
                // Show error message
                showNotification('danger', response.message);

                // Reset button
                button.innerHTML = '<i class="fas fa-times"></i> Failed';
                setTimeout(() => {
                    button.innerHTML = `<i class="fas fa-cloud-download-alt"></i> Apply All from ${source}`;
                    button.disabled = false;
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error applying all fields:', error);
            showNotification('danger', 'Error applying all fields: ' + error.message);

            // Reset button
            button.innerHTML = '<i class="fas fa-times"></i> Failed';
            setTimeout(() => {
                button.innerHTML = `<i class="fas fa-cloud-download-alt"></i> Apply All from ${source}`;
                button.disabled = false;
            }, 2000);
        });
}

// Handle apply all valid button click
function handleApplyAllValid(event) {
    // This will still use the form submission for now
    // We'll implement AJAX for this in a future update
    const form = document.getElementById('validationActionForm');
    document.getElementById('actionType').value = 'apply_all_valid';
    form.submit();
}

// Handle reset all button click
function handleResetAll(event) {
    // This will still use the form submission for now
    const form = document.getElementById('validationActionForm');
    document.getElementById('actionType').value = 'reset_all';
    form.submit();
}

// Handle validate again button click
function handleValidateAgain(event) {
    // This will still use the form submission for now
    const form = document.getElementById('validationActionForm');
    document.getElementById('actionType').value = 'validate_again';

    // Check if bypass cache is checked
    const bypassCache = document.getElementById('bypassCache')?.checked || false;

    // Add bypass_cache parameter to the form
    let bypassInput = form.querySelector('input[name="bypass_cache"]');
    if (!bypassInput) {
        bypassInput = document.createElement('input');
        bypassInput.type = 'hidden';
        bypassInput.name = 'bypass_cache';
        form.appendChild(bypassInput);
    }
    bypassInput.value = bypassCache ? '1' : '0';

    form.submit();
}

// Handle refresh validation button click
function handleRefreshValidation(event) {
    // This will still use the form submission for now
    const form = document.getElementById('validationActionForm');
    document.getElementById('actionType').value = 'refresh_validation';

    // Check if bypass cache is checked
    const bypassCache = document.getElementById('bypassCache')?.checked || false;

    // Add bypass_cache parameter to the form
    let bypassInput = form.querySelector('input[name="bypass_cache"]');
    if (!bypassInput) {
        bypassInput = document.createElement('input');
        bypassInput.type = 'hidden';
        bypassInput.name = 'bypass_cache';
        form.appendChild(bypassInput);
    }
    bypassInput.value = bypassCache ? '1' : '0';

    form.submit();
}

// Get the value from a source cell
function getSourceValue(field, valueContainer) {
    // For most fields, we can just get the text content
    let value = valueContainer.textContent.trim();

    // For some fields, we need special handling
    switch (field) {
        case 'cover_url':
            // Get the image URL
            const img = valueContainer.querySelector('img');
            value = img ? img.src : '';
            break;

        case 'preview_link':
            // Get the link URL
            const link = valueContainer.querySelector('a');
            value = link ? link.href : '';
            break;
    }

    return value;
}

// Update a field via AJAX
async function updateField(bookId, field, value, source) {
    const formData = new FormData();
    formData.append('action', 'update_field');
    formData.append('book_id', bookId);
    formData.append('field', field);
    formData.append('value', value);
    formData.append('source', source);

    const response = await fetch('ajax-update-field.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    return await response.json();
}

// Apply all fields from a source via AJAX
async function applyAllFieldsFromSource(bookId, source) {
    const formData = new FormData();
    formData.append('action', 'apply_all_source');
    formData.append('book_id', bookId);
    formData.append('source', source);

    const response = await fetch('ajax-update-field.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    return await response.json();
}

// Show a notification
function showNotification(type, message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show notification-toast`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    // Add to the page
    const container = document.querySelector('.notification-container') || document.body;
    container.appendChild(notification);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 150);
    }, 5000);
}

// Add an entry to the validation history
function addToValidationHistory(action) {
    const historyContainer = document.querySelector('.history-container');
    if (!historyContainer) return;

    const now = new Date();
    const timestamp = now.toISOString().replace('T', ' ').substring(0, 19);

    const entry = document.createElement('div');
    entry.className = 'history-entry';
    entry.innerHTML = `
        <span class="history-timestamp">${timestamp}</span> -
        <span class="history-action">${action}</span>
    `;

    // Add to the top of the history
    if (historyContainer.firstChild) {
        historyContainer.insertBefore(entry, historyContainer.firstChild);
    } else {
        historyContainer.appendChild(entry);
    }

    // Remove "No validation history available" message if present
    const noHistoryMessage = historyContainer.querySelector('.text-muted');
    if (noHistoryMessage) {
        noHistoryMessage.remove();
    }
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', initAjaxValidation);
