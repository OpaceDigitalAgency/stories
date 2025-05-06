/**
 * Inline Editing JavaScript
 * 
 * This file contains the functionality for inline editing of table fields
 * without requiring page reloads.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inline Editing JS loaded');
    
    // Initialize inline editing
    initInlineEditing();
});

/**
 * Initialize inline editing functionality
 */
function initInlineEditing() {
    const editableFields = document.querySelectorAll('.premium-editable');
    
    if (!editableFields.length) return;
    
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
    // Create the form data
    const formData = new FormData();
    formData.append('id', itemId);
    formData.append('type', itemType);
    formData.append('field', fieldName);
    formData.append('value', fieldValue);
    formData.append('action', 'update_field');
    
    // Send the request
    return fetch('ajax/update-field.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .catch(error => {
        console.error('Error:', error);
        return { success: false, error: 'Network error' };
    });
}

/**
 * Show a notification message
 * 
 * @param {string} message - The message to show
 * @param {string} type - The type of notification (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Create the notification element if it doesn't exist
    let notificationContainer = document.querySelector('.premium-notifications-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'premium-notifications-container';
        document.body.appendChild(notificationContainer);
    }
    
    // Create the notification
    const notification = document.createElement('div');
    notification.className = `premium-notification premium-notification-${type}`;
    
    // Add the icon
    let icon;
    switch (type) {
        case 'success':
            icon = 'fa-check-circle';
            break;
        case 'error':
            icon = 'fa-exclamation-circle';
            break;
        case 'warning':
            icon = 'fa-exclamation-triangle';
            break;
        default:
            icon = 'fa-info-circle';
    }
    
    notification.innerHTML = `
        <div class="premium-notification-icon">
            <i class="fas ${icon}"></i>
        </div>
        <div class="premium-notification-content">
            ${message}
        </div>
        <div class="premium-notification-close">
            <i class="fas fa-times"></i>
        </div>
    `;
    
    // Add the notification to the container
    notificationContainer.appendChild(notification);
    
    // Add event listener for the close button
    const closeButton = notification.querySelector('.premium-notification-close');
    closeButton.addEventListener('click', function() {
        notification.classList.add('premium-notification-hiding');
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    });
    
    // Show the notification
    setTimeout(() => {
        notification.classList.add('premium-notification-visible');
    }, 10);
    
    // Auto-hide the notification after a delay
    setTimeout(() => {
        notification.classList.add('premium-notification-hiding');
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}
