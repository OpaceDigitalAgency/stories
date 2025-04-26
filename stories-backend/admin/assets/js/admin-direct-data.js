/**
 * Admin Direct Data Loader
 * 
 * This script directly loads data from the API endpoints and injects it into the admin interface.
 * It's a temporary solution until the underlying issue with the admin interface is fixed.
 */

(function() {
    // Wait for the DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[DIRECT DATA] Script loaded');
        
        // Check if we're on the AI Tools or Directory Items page
        const isAiToolsPage = window.location.href.includes('ai-tools.php');
        const isDirectoryItemsPage = window.location.href.includes('directory-items.php');
        
        if (!isAiToolsPage && !isDirectoryItemsPage) {
            console.log('[DIRECT DATA] Not on AI Tools or Directory Items page, exiting');
            return;
        }
        
        // Get the endpoint based on the page
        const endpoint = isAiToolsPage ? 'ai-tools' : 'directory-items';
        console.log('[DIRECT DATA] Detected page:', endpoint);
        
        // Find the error message and list container
        const errorMessage = document.querySelector('.alert-danger');
        const listContainer = document.querySelector('.list-container') || document.querySelector('table').parentNode;
        
        if (!errorMessage || !listContainer) {
            console.log('[DIRECT DATA] Could not find error message or list container, exiting');
            return;
        }
        
        console.log('[DIRECT DATA] Found error message and list container, loading data');
        
        // Show loading message
        errorMessage.textContent = 'Loading data directly from API...';
        errorMessage.className = 'alert alert-info';
        
        // Fetch data from API
        fetch(`/api/v1/${endpoint}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('[DIRECT DATA] Data loaded:', data);
                
                if (!Array.isArray(data) || data.length === 0) {
                    errorMessage.textContent = 'No data found.';
                    return;
                }
                
                // Create table HTML
                let tableHtml = '<table class="table table-striped">';
                
                // Add table header based on endpoint
                if (endpoint === 'directory-items') {
                    tableHtml += `
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>URL</th>
                                <th>Published</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    `;
                } else if (endpoint === 'ai-tools') {
                    tableHtml += `
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Pricing</th>
                                <th>Featured</th>
                                <th>Published</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    `;
                }
                
                // Add table body
                tableHtml += '<tbody>';
                
                // Add rows for each item
                data.forEach(item => {
                    if (endpoint === 'directory-items') {
                        tableHtml += `
                            <tr>
                                <td>${item.id}</td>
                                <td>${item.name || ''}</td>
                                <td>${item.category || ''}</td>
                                <td><a href="${item.url || '#'}" target="_blank">${item.url || ''}</a></td>
                                <td>${item.isPublished ? 'Yes' : 'No'}</td>
                                <td>
                                    <a href="?action=edit&id=${item.id}" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="?action=delete&id=${item.id}" class="btn btn-sm btn-danger delete-confirm">Delete</a>
                                </td>
                            </tr>
                        `;
                    } else if (endpoint === 'ai-tools') {
                        tableHtml += `
                            <tr>
                                <td>${item.id}</td>
                                <td>${item.name || ''}</td>
                                <td>${item.category || ''}</td>
                                <td>${item.pricingType || ''}</td>
                                <td>${item.featured ? 'Yes' : 'No'}</td>
                                <td>${item.isPublished ? 'Yes' : 'No'}</td>
                                <td>
                                    <a href="?action=edit&id=${item.id}" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="?action=delete&id=${item.id}" class="btn btn-sm btn-danger delete-confirm">Delete</a>
                                </td>
                            </tr>
                        `;
                    }
                });
                
                tableHtml += '</tbody></table>';
                
                // Replace the list container with the new table
                listContainer.innerHTML = tableHtml;
                
                // Hide the error message
                errorMessage.style.display = 'none';
                
                // Re-initialize delete confirmations
                if (typeof initDeleteConfirmations === 'function') {
                    initDeleteConfirmations();
                }
                
                console.log('[DIRECT DATA] Data injected into the page');
            })
            .catch(error => {
                console.error('[DIRECT DATA] Error loading data:', error);
                errorMessage.textContent = `Error loading data: ${error.message}`;
                errorMessage.className = 'alert alert-danger';
            });
    });
})();