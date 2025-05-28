// Enhanced Table Component JavaScript
// Prevents multiple script loading with a guard
if (typeof window.enhancedTableLoaded === 'undefined') {
    window.enhancedTableLoaded = true;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize select all functionality
        const selectAllCheckboxes = document.querySelectorAll('.select-all-checkbox');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const selectedCountElement = document.querySelector('.premium-selected-count');

        selectAllCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const isChecked = this.checked;

                itemCheckboxes.forEach(itemCheckbox => {
                    itemCheckbox.checked = isChecked;
                });

                updateSelectedCount();
            });
        });

        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();

                // Update select all checkbox
                const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                selectAllCheckboxes.forEach(selectAll => {
                    selectAll.checked = allChecked;
                });
            });
        });

        // Initialize bulk actions
        const applyBulkActionButton = document.getElementById('apply-bulk-action');
        const bulkActionSelect = document.getElementById('bulk-action-select');

        if (applyBulkActionButton && bulkActionSelect) {
            applyBulkActionButton.addEventListener('click', function() {
                const selectedAction = bulkActionSelect.value;

                if (!selectedAction) {
                    alert('Please select an action');
                    return;
                }

                const selectedItems = Array.from(itemCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);

                if (selectedItems.length === 0) {
                    alert('Please select at least one item');
                    return;
                }

                // Confirm the action
                if (confirm(`Are you sure you want to ${selectedAction} the selected items?`)) {
                    // Get the item type from the table
                    const table = document.querySelector('table[data-item-type]');
                    const itemType = table ? table.getAttribute('data-item-type') : '';

                    if (!itemType) {
                        console.error('Could not determine item type for bulk action');
                        alert('Error: Could not determine item type for bulk action');
                        return;
                    }

                    console.log(`Performing ${selectedAction} on ${itemType} items:`, selectedItems);

                    // Show loading indicator
                    const loadingIndicator = document.createElement('div');
                    loadingIndicator.className = 'loading-indicator';
                    loadingIndicator.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
                    document.body.appendChild(loadingIndicator);

                    // Send the bulk action request
                    fetch('../handlers/bulk-action.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=${selectedAction}&item_type=${itemType}&selected_ids=${selectedItems.join(',')}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Remove loading indicator
                        loadingIndicator.remove();

                        if (data.success) {
                            // Show success message
                            alert(data.message || 'Bulk action completed successfully');

                            // Reload the page to update the table
                            window.location.reload();
                        } else {
                            // Show error message
                            alert(data.message || 'Failed to perform bulk action');
                        }
                    })
                    .catch(error => {
                        // Remove loading indicator
                        loadingIndicator.remove();

                        console.error('Error:', error);
                        alert('An error occurred while performing the bulk action');
                    });
                }
            });
        }

        // Initialize delete buttons
        const deleteButtons = document.querySelectorAll('.delete-item-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-id');

                if (confirm('Are you sure you want to delete this item?')) {
                    // Get the item type from the table
                    const table = button.closest('table');
                    const itemType = table ? table.getAttribute('data-item-type') : '';

                    if (!itemType) {
                        console.error('Could not determine item type for deletion');
                        alert('Error: Could not determine item type for deletion');
                        return;
                    }

                    // Determine the delete handler URL based on item type
                    const deleteHandlerUrl = `../handlers/delete-${itemType}.php`;

                    console.log(`Deleting ${itemType} with ID ${itemId} using ${deleteHandlerUrl}`);

                    // Show loading indicator
                    const loadingIndicator = document.createElement('div');
                    loadingIndicator.className = 'loading-indicator';
                    loadingIndicator.innerHTML = '<div class="spinner-border spinner-border-sm text-light" role="status"><span class="visually-hidden">Loading...</span></div>';
                    document.body.appendChild(loadingIndicator);

                    // Send the delete request
                    fetch(deleteHandlerUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `id=${itemId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Remove loading indicator
                        loadingIndicator.remove();

                        if (data.success) {
                            // Show success message
                            alert(data.message || 'Item deleted successfully');

                            // Remove the row from the table
                            const row = button.closest('tr');
                            if (row) {
                                row.remove();
                            }

                            // Reload the page to update counts and pagination
                            window.location.reload();
                        } else {
                            // Show error message
                            alert(data.message || 'Failed to delete item');
                        }
                    })
                    .catch(error => {
                        // Remove loading indicator
                        loadingIndicator.remove();

                        console.error('Error:', error);
                        alert('An error occurred while deleting the item');
                    });
                }
            });
        });

        // Helper function to update the selected count
        function updateSelectedCount() {
            if (selectedCountElement) {
                const selectedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
                selectedCountElement.textContent = `${selectedCount} item${selectedCount !== 1 ? 's' : ''} selected`;
            }
        }

        // Author preview buttons are now handled by author-preview.js
        // This prevents duplicate event listeners and multiple popups

        // Initialize contact preview buttons
        const contactPreviewButtons = document.querySelectorAll('.contact-preview-btn');
        contactPreviewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const contactId = this.getAttribute('data-contact-id');

                // Create modal container
                const modal = document.createElement('div');
                modal.className = 'preview-modal';
                modal.innerHTML = `
                    <div class="preview-modal-content">
                        <div class="preview-modal-header">
                            <h2>Contact Preview</h2>
                            <button class="preview-modal-close">&times;</button>
                        </div>
                        <div class="preview-modal-body">
                            <div class="preview-loading">Loading contact details...</div>
                            <div id="contact-preview" style="display:none;"></div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);

                // Add event listener to close button
                modal.querySelector('.preview-modal-close').addEventListener('click', function() {
                    modal.remove();
                });

                // Load contact details
                fetch(`../handlers/get-contact.php?id=${contactId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const previewDiv = modal.querySelector('#contact-preview');
                            const loading = modal.querySelector('.preview-loading');

                            // Create HTML content
                            previewDiv.innerHTML = `
                                <div class="card">
                                    <div class="card-header">
                                        <h3>${data.contact.name}</h3>
                                        <div>${data.contact.email}</div>
                                    </div>
                                    <div class="card-body">
                                        <h4>${data.contact.subject}</h4>
                                        <div class="message-content">${data.contact.message}</div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="status">
                                            Status: <span class="badge ${data.contact.is_responded ? 'bg-success' : 'bg-warning'}">
                                                ${data.contact.is_responded ? 'Responded' : 'Not Responded'}
                                            </span>
                                        </div>
                                        <div class="date">
                                            Received: ${new Date(data.contact.created_at).toLocaleString()}
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Show the preview
                            loading.style.display = 'none';
                            previewDiv.style.display = 'block';
                        } else {
                            modal.querySelector('.preview-loading').innerHTML = 'Error loading contact details.';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modal.querySelector('.preview-loading').innerHTML = 'Error loading contact details.';
                    });
            });
        });
    });
}
