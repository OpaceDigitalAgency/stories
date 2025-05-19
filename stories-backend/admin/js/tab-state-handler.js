$(document).ready(function() {
    // Function to get current tab from URL
    function getCurrentTab() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('tab') || 'existing';
    }

    // Function to update URL with current tab
    function updateUrlWithTab(tab) {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
    }

    // Function to activate tab
    function activateTab(tab) {
        $(`a[href="#${tab}"]`).tab('show');
    }

    // Handle tab changes
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const tab = $(e.target).attr('href').substring(1); // Remove the #
        updateUrlWithTab(tab);
    });

    // Handle form submissions
    $('form').on('submit', function(e) {
        const currentTab = getCurrentTab();
        if (!$(this).find('input[name="tab"]').length) {
            $(this).append(`<input type="hidden" name="tab" value="${currentTab}">`);
        }
    });

    // Initialize correct tab on page load
    activateTab(getCurrentTab());
});