// Function to update URL with current tab
function updateUrlWithTab(tab) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
}

// Function to activate tab from URL
function activateTabFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'existing';
    $(`a[href="#${tab}"]`).tab('show');
}

// Handle tab changes
$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    const tab = $(e.target).attr('href').substring(1); // Remove the #
    updateUrlWithTab(tab);
});

// Initialize correct tab on page load
$(document).ready(function() {
    activateTabFromUrl();
});