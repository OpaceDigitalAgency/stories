// Cache DOM elements and URL parameters
const urlParams = new URLSearchParams(window.location.search);
const currentTab = urlParams.get('tab') || 'existing';

// Initialize on DOM ready
$(function() {
    // Show initial tab without animation
    $(`a[href="#${currentTab}"]`).tab('show');

    // Handle tab changes
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tab = e.target.getAttribute('href').substring(1);
        if (tab !== currentTab) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            history.replaceState({}, '', url);
        }
    });

    // Add tab parameter to forms that don't have it
    $('form:not(:has([name="tab"]))').append(`<input type="hidden" name="tab" value="${currentTab}">`);

    // Handle pagination with minimal DOM operations
    $('.pagination').on('click', 'a', function(e) {
        e.preventDefault();
        const url = new URL(this.href, window.location.origin);
        url.searchParams.set('tab', currentTab);
        window.location.href = url.toString();
    });

    // Handle per_page changes - use event delegation for dynamically added elements
    $(document).ready(function() {
        console.log('Document ready, setting up per-page-select handler');
        
        // Add a click handler to the select element to ensure it's clickable
        $(document).on('click', '.per-page-select', function() {
            console.log('Per page select clicked');
        });
        
        // Add the change handler
        $(document).on('change', '.per-page-select', function() {
            console.log('Per page select changed');
            // Make sure the form has the tab parameter and reset page to 1
            const form = this.closest('form');
            console.log('Form found:', form);
            
            let tabInput = form.querySelector('input[name="tab"]');
            let pageInput = form.querySelector('input[name="page"]');
            
            console.log('Current tab:', currentTab);
            console.log('Tab input:', tabInput);
            console.log('Page input:', pageInput);

            if (!tabInput) {
                console.log('Adding tab input');
                $(form).append(`<input type="hidden" name="tab" value="${currentTab}">`);
            } else {
                console.log('Setting tab input value');
                tabInput.value = currentTab;
            }

            // Always reset to page 1 when changing items per page
            if (!pageInput) {
                console.log('Adding page input');
                $(form).append('<input type="hidden" name="page" value="1">');
            } else {
                console.log('Setting page input value');
                pageInput.value = '1';
            }

            // Submit the form
            console.log('Submitting form');
            form.submit();
        });
        
        // Check if the elements exist
        console.log('Per-page-select elements found:', $('.per-page-select').length);
    });
});