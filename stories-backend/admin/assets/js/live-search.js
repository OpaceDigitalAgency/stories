/**
 * Live Search JavaScript
 *
 * This file contains the functionality for live table filtering as users type
 * in the search input. It filters the table rows directly without page reloads.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Live Search JS loaded');

    // Initialize live search
    initLiveSearch();

    // Initialize clickable images
    initClickableImages();

    // Initialize inline editing
    initInlineEditing();
});

/**
 * Initialize live search functionality
 */
function initLiveSearch() {
    const searchInputs = document.querySelectorAll('.premium-search-input');

    if (!searchInputs.length) return;

    searchInputs.forEach(input => {
        // Get the table that this search input should filter
        const tableId = input.getAttribute('data-table-id');
        const table = document.getElementById(tableId);

        if (!table) return;

        // Get all rows in the table body
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr');

        // Store the original table data for resetting
        const originalTableData = Array.from(rows).map(row => {
            return {
                element: row,
                text: row.textContent.toLowerCase(),
                visible: true
            };
        });

        // Add event listener for input changes
        input.addEventListener('input', debounce(function() {
            const query = input.value.trim().toLowerCase();
            const searchField = input.closest('form').querySelector('[name="search_field"]')?.value || 'all';

            // If the query is empty, show all rows
            if (query === '') {
                originalTableData.forEach(row => {
                    row.element.style.display = '';
                    row.visible = true;
                });
                updateRowCount(originalTableData);
                return;
            }

            // Filter the rows based on the query
            originalTableData.forEach(row => {
                // If searching in a specific field, only search in that column
                if (searchField !== 'all') {
                    const cellIndex = getCellIndexForField(table, searchField);
                    if (cellIndex !== -1) {
                        const cell = row.element.cells[cellIndex];
                        const cellText = cell.textContent.toLowerCase();
                        row.visible = cellText.includes(query);
                    } else {
                        row.visible = row.text.includes(query);
                    }
                } else {
                    row.visible = row.text.includes(query);
                }

                // Show or hide the row
                row.element.style.display = row.visible ? '' : 'none';

                // Highlight the matching text if visible
                if (row.visible) {
                    highlightMatchingText(row.element, query);
                }
            });

            // Update the row count
            updateRowCount(originalTableData);
        }, 200));

        // Add event listener for search field changes
        const searchFieldSelect = input.closest('form').querySelector('[name="search_field"]');
        if (searchFieldSelect) {
            searchFieldSelect.addEventListener('change', function() {
                // Trigger the input event to re-filter the table
                const event = new Event('input', { bubbles: true });
                input.dispatchEvent(event);
            });
        }
    });
}

/**
 * Get the cell index for a specific field
 *
 * @param {HTMLElement} table - The table element
 * @param {string} field - The field to search in
 * @returns {number} - The cell index, or -1 if not found
 */
function getCellIndexForField(table, field) {
    const headers = table.querySelectorAll('thead th');

    for (let i = 0; i < headers.length; i++) {
        const headerText = headers[i].textContent.trim().toLowerCase();
        const headerField = headers[i].getAttribute('data-field');

        if (headerField === field || headerText === field) {
            return i;
        }
    }

    return -1;
}

/**
 * Highlight matching text in a table row
 *
 * @param {HTMLElement} row - The table row element
 * @param {string} query - The search query
 */
function highlightMatchingText(row, query) {
    const cells = row.querySelectorAll('td');

    cells.forEach(cell => {
        // Skip cells with images or complex content
        if (cell.querySelector('img') || cell.querySelector('.premium-table-actions')) {
            return;
        }

        const originalText = cell.getAttribute('data-original-text') || cell.textContent;

        // Store the original text if not already stored
        if (!cell.getAttribute('data-original-text')) {
            cell.setAttribute('data-original-text', originalText);
        }

        // Create a new text with highlighted matches
        const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
        const highlightedText = originalText.replace(regex, '<span class="highlight">$1</span>');

        // Only update if there's a match
        if (highlightedText !== originalText) {
            cell.innerHTML = highlightedText;
        }
    });
}

/**
 * Update the row count display
 *
 * @param {Array} tableData - The table data
 */
function updateRowCount(tableData) {
    const visibleCount = tableData.filter(row => row.visible).length;
    const totalCount = tableData.length;

    const rowCountElement = document.querySelector('.premium-row-count');
    if (rowCountElement) {
        rowCountElement.textContent = `Showing ${visibleCount} of ${totalCount} items`;
    }
}

/**
 * Initialize clickable images in tables
 */
function initClickableImages() {
    const thumbnails = document.querySelectorAll('.premium-table .thumbnail-image');

    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            const itemId = this.closest('tr').getAttribute('data-id');
            const itemType = this.closest('table').getAttribute('data-item-type');

            if (itemId && itemType) {
                // Redirect to the edit page
                let editUrl;
                let formFile;

                switch (itemType) {
                    case 'story':
                        formFile = 'story-form.php';
                        break;
                    case 'author':
                        formFile = 'author-form.php';
                        break;
                    case 'post':
                        formFile = 'post-form.php';
                        break;
                    case 'media':
                        formFile = 'media.php';
                        break;
                    default:
                        formFile = `${itemType}-form.php`;
                }

                // Directly redirect to the form file without checking if it exists
                // This avoids CORS issues with the fetch API
                window.location.href = `${formFile}?id=${itemId}`;
            }
        });
    });
}

/**
 * Initialize inline editing functionality
 */
function initInlineEditing() {
    const editableFields = document.querySelectorAll('.premium-editable');

    editableFields.forEach(field => {
        field.addEventListener('click', function() {
            // If already in edit mode, return
            if (field.classList.contains('premium-editable-active')) {
                return;
            }

            // Get the field data
            const fieldType = field.getAttribute('data-field-type') || 'text';
            const fieldName = field.getAttribute('data-field-name');
            const itemId = field.closest('tr').getAttribute('data-id');
            const itemType = field.closest('table').getAttribute('data-item-type');
            const originalValue = field.textContent.trim();

            // Store the original value
            field.setAttribute('data-original-value', originalValue);

            // Create the input element
            let inputElement;

            if (fieldType === 'textarea') {
                inputElement = document.createElement('textarea');
                inputElement.rows = 3;
            } else if (fieldType === 'select') {
                inputElement = document.createElement('select');

                // Get the options from the data attribute
                const options = field.getAttribute('data-options')?.split(',') || [];

                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.trim();
                    optionElement.textContent = option.trim();

                    if (option.trim() === originalValue) {
                        optionElement.selected = true;
                    }

                    inputElement.appendChild(optionElement);
                });
            } else {
                inputElement = document.createElement('input');
                inputElement.type = fieldType;
            }

            // Set common attributes
            inputElement.className = 'premium-editable-input';
            inputElement.value = originalValue;

            // Clear the field and add the input
            field.textContent = '';
            field.appendChild(inputElement);

            // Add the action buttons
            const actionsContainer = document.createElement('div');
            actionsContainer.className = 'premium-editable-actions';

            const saveButton = document.createElement('div');
            saveButton.className = 'premium-editable-action premium-editable-save';
            saveButton.innerHTML = '<i class="fas fa-check"></i>';
            saveButton.title = 'Save';

            const cancelButton = document.createElement('div');
            cancelButton.className = 'premium-editable-action premium-editable-cancel';
            cancelButton.innerHTML = '<i class="fas fa-times"></i>';
            cancelButton.title = 'Cancel';

            actionsContainer.appendChild(saveButton);
            actionsContainer.appendChild(cancelButton);
            field.appendChild(actionsContainer);

            // Mark as active
            field.classList.add('premium-editable-active');

            // Focus the input
            inputElement.focus();

            // Add event listeners for the actions
            saveButton.addEventListener('click', function(e) {
                e.stopPropagation();

                // Get the new value
                const newValue = inputElement.value.trim();

                // If the value hasn't changed, just cancel
                if (newValue === originalValue) {
                    cancelEditing(field);
                    return;
                }

                // Save the new value
                saveFieldValue(itemId, itemType, fieldName, newValue)
                    .then(response => {
                        if (response.success) {
                            // Update the field with the new value
                            field.textContent = newValue;

                            // Remove the active class
                            field.classList.remove('premium-editable-active');

                            // Show a success message
                            showNotification('Field updated successfully', 'success');
                        } else {
                            // Show an error message
                            showNotification(response.error || 'Failed to update field', 'error');

                            // Cancel editing
                            cancelEditing(field);
                        }
                    })
                    .catch(error => {
                        console.error('Error saving field value:', error);

                        // Show an error message
                        showNotification('Failed to update field', 'error');

                        // Cancel editing
                        cancelEditing(field);
                    });
            });

            cancelButton.addEventListener('click', function(e) {
                e.stopPropagation();
                cancelEditing(field);
            });

            // Add event listener for Enter key
            inputElement.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && fieldType !== 'textarea') {
                    e.preventDefault();
                    saveButton.click();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelButton.click();
                }
            });

            // Add event listener for clicking outside
            document.addEventListener('click', function handleOutsideClick(e) {
                if (!field.contains(e.target)) {
                    cancelEditing(field);
                    document.removeEventListener('click', handleOutsideClick);
                }
            });
        });
    });
}

/**
 * Cancel inline editing and restore the original value
 *
 * @param {HTMLElement} field - The editable field element
 */
function cancelEditing(field) {
    // Get the original value
    const originalValue = field.getAttribute('data-original-value');

    // Restore the original value
    field.textContent = originalValue;

    // Remove the active class
    field.classList.remove('premium-editable-active');
}

/**
 * Save a field value to the server
 *
 * @param {string} itemId - The ID of the item
 * @param {string} itemType - The type of item
 * @param {string} fieldName - The name of the field
 * @param {string} fieldValue - The new value of the field
 * @returns {Promise} - A promise that resolves to the server response
 */
function saveFieldValue(itemId, itemType, fieldName, fieldValue) {
    // In a real implementation, this would make an AJAX request to the server
    // For now, we'll simulate a successful response

    return new Promise((resolve) => {
        setTimeout(() => {
            resolve({ success: true });
        }, 500);
    });
}

/**
 * Show a notification message
 *
 * @param {string} message - The message to show
 * @param {string} type - The type of notification (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Create the notification element
    const notification = document.createElement('div');
    notification.className = `premium-notification premium-notification-${type}`;
    notification.textContent = message;

    // Add the notification to the page
    document.body.appendChild(notification);

    // Show the notification
    setTimeout(() => {
        notification.classList.add('premium-notification-show');
    }, 10);

    // Hide the notification after a delay
    setTimeout(() => {
        notification.classList.remove('premium-notification-show');

        // Remove the notification after the animation
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Escape special characters in a string for use in a regular expression
 *
 * @param {string} string - The string to escape
 * @returns {string} - The escaped string
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Debounce a function to prevent it from being called too frequently
 *
 * @param {Function} func - The function to debounce
 * @param {number} wait - The debounce wait time in milliseconds
 * @returns {Function} - The debounced function
 */
function debounce(func, wait) {
    let timeout;

    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };

        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
