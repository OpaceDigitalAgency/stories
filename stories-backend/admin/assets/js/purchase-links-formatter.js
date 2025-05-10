/**
 * Purchase Links Formatter
 * 
 * This script provides a user-friendly interface for managing purchase links
 * for books in the directory item form.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Purchase Links Formatter loaded');
    initPurchaseLinksManager();
});

/**
 * Initialize the purchase links manager
 */
function initPurchaseLinksManager() {
    const purchaseLinksField = document.getElementById('purchase_links');
    const container = document.getElementById('purchase-links-container');
    
    if (!purchaseLinksField || !container) {
        console.log('Purchase links elements not found');
        return;
    }
    
    console.log('Initializing purchase links manager');
    
    // Parse the current JSON value
    let purchaseLinks = {};
    try {
        if (purchaseLinksField.value && purchaseLinksField.value.trim() !== '') {
            const parsed = JSON.parse(purchaseLinksField.value);
            if (parsed && typeof parsed === 'object') {
                purchaseLinks = parsed;
            }
        }
    } catch (e) {
        console.error('Error parsing purchase links JSON:', e);
        console.log('Raw value:', purchaseLinksField.value);
    }
    
    // Render the current links
    renderPurchaseLinks();
    
    // Add event listener for the add button
    const addButton = document.getElementById('add-purchase-link-btn');
    if (addButton) {
        addButton.addEventListener('click', function() {
            // Create a modal for adding a new purchase link
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'add-purchase-link-modal';
            modal.setAttribute('tabindex', '-1');
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-labelledby', 'add-purchase-link-modal-title');
            modal.setAttribute('aria-hidden', 'true');
            
            modal.innerHTML = `
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="add-purchase-link-modal-title">Add Purchase Link</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="store-name">Store</label>
                                <select id="store-name" class="form-control">
                                    <option value="">Select Store</option>
                                    <option value="amazon">Amazon</option>
                                    <option value="barnes_noble">Barnes & Noble</option>
                                    <option value="bookshop">Bookshop.org</option>
                                    <option value="waterstones">Waterstones</option>
                                    <option value="goodreads">Goodreads</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group" id="custom-store-container" style="display: none;">
                                <label for="custom-store">Custom Store Name</label>
                                <input type="text" id="custom-store" class="form-control" placeholder="Enter store name">
                            </div>
                            <div class="form-group">
                                <label for="store-url">URL</label>
                                <input type="url" id="store-url" class="form-control" placeholder="https://example.com/book">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="save-purchase-link">Add Link</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Initialize the modal
            $(modal).modal('show');
            
            // Handle custom store selection
            const storeSelect = document.getElementById('store-name');
            const customStoreContainer = document.getElementById('custom-store-container');
            
            storeSelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    customStoreContainer.style.display = 'block';
                } else {
                    customStoreContainer.style.display = 'none';
                }
            });
            
            // Handle save button
            const saveButton = document.getElementById('save-purchase-link');
            saveButton.addEventListener('click', function() {
                const storeSelect = document.getElementById('store-name');
                const customStore = document.getElementById('custom-store');
                const storeUrl = document.getElementById('store-url');
                
                let store = storeSelect.value;
                if (store === 'other') {
                    store = customStore.value.trim();
                }
                
                const url = storeUrl.value.trim();
                
                if (!store) {
                    alert('Please select or enter a store name');
                    return;
                }
                
                if (!url) {
                    alert('Please enter a URL');
                    return;
                }
                
                // Add the link
                purchaseLinks[store] = url;
                
                // Update the field and render
                updatePurchaseLinksField();
                renderPurchaseLinks();
                
                // Close the modal
                $(modal).modal('hide');
                $(modal).on('hidden.bs.modal', function() {
                    modal.remove();
                });
            });
        });
    }
    
    // Function to render the purchase links
    function renderPurchaseLinks() {
        container.innerHTML = '';
        
        if (Object.keys(purchaseLinks).length === 0) {
            container.innerHTML = '<p class="text-muted">No purchase links added yet.</p>';
            return;
        }
        
        const table = document.createElement('table');
        table.className = 'table table-sm';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Store</th>
                    <th>URL</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        `;
        
        const tbody = table.querySelector('tbody');
        
        for (const [store, url] of Object.entries(purchaseLinks)) {
            const tr = document.createElement('tr');
            
            const storeName = store.charAt(0).toUpperCase() + store.slice(1).replace('_', ' ');
            
            tr.innerHTML = `
                <td>${storeName}</td>
                <td><a href="${url}" target="_blank">${url}</a></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-link" data-store="${store}">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(tr);
        }
        
        container.appendChild(table);
        
        // Add event listeners for remove buttons
        const removeButtons = container.querySelectorAll('.remove-link');
        removeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const store = this.getAttribute('data-store');
                if (confirm(`Are you sure you want to remove the ${store} purchase link?`)) {
                    delete purchaseLinks[store];
                    updatePurchaseLinksField();
                    renderPurchaseLinks();
                }
            });
        });
    }
    
    // Function to update the hidden field with the JSON
    function updatePurchaseLinksField() {
        purchaseLinksField.value = JSON.stringify(purchaseLinks);
    }
}
