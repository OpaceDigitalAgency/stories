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

    // Handle per_page changes
    $('.per-page-select').on('change', function() {
        // Make sure the form has the tab parameter
        const form = this.closest('form');
        let tabInput = form.querySelector('input[name="tab"]');

        if (!tabInput) {
            $(form).append(`<input type="hidden" name="tab" value="${currentTab}">`);
        } else {
            tabInput.value = currentTab;
        }

        // Submit the form
        form.submit();
    });
});