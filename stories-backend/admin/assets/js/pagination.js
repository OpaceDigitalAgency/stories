// Pagination Component JavaScript
// Prevents multiple script loading with a guard
if (typeof window.paginationLoaded === 'undefined') {
    window.paginationLoaded = true;

    // Inline JavaScript to handle per-page dropdown changes
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Pagination component loaded');
        
        // Get the per-page select element
        const perPageSelect = document.getElementById('per-page');
        
        if (perPageSelect) {
            console.log('Per-page select found');
            
            // Add change event listener
            perPageSelect.addEventListener('change', function() {
                console.log('Per-page select changed');
                
                // Get the form
                const form = this.closest('form');
                
                // Get the current tab from URL
                const urlParams = new URLSearchParams(window.location.search);
                const currentTab = urlParams.get('tab') || 'existing';
                
                console.log('Current tab:', currentTab);
                
                // Make sure the form has the tab parameter
                let tabInput = form.querySelector('input[name="tab"]');
                if (!tabInput) {
                    console.log('Adding tab input');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tab';
                    input.value = currentTab;
                    form.appendChild(input);
                } else {
                    console.log('Setting tab input value');
                    tabInput.value = currentTab;
                }
                
                // Always reset to page 1 when changing items per page
                // Use the correct page parameter name based on the current tab
                let pageInput;
                let pageInputName = 'page'; // Default to standard parameter name
                
                // Get the page input
                pageInput = form.querySelector('input[name="page"]');
                
                if (!pageInput) {
                    console.log('Adding page input');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = pageInputName;
                    input.value = '1';
                    form.appendChild(input);
                } else {
                    console.log('Setting page input value');
                    pageInput.value = '1';
                }
                
                // Remove any old parameter names that might be in the form
                const oldParams = ['reviews_page', 'sources_page', 'isbn_page'];
                oldParams.forEach(param => {
                    const oldInput = form.querySelector(`input[name="${param}"]`);
                    if (oldInput) {
                        console.log(`Removing old parameter: ${param}`);
                        oldInput.remove();
                    }
                });
                
                // Submit the form
                console.log('Submitting form');
                form.submit();
            });
        } else {
            console.error('Per-page select not found');
        }
    });
}
