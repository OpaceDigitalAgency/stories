/**
 * Purchase Links Formatter
 *
 * This script formats the purchase links JSON field into a user-friendly interface
 * in the book edit form.
 */
$(document).ready(function() {
    console.log('Purchase links formatter loaded');

    // Format purchase links from JSON to UI
    // Look for the book purchase links field with various possible name attributes
    var purchaseLinksField = $('textarea[name="book_purchase_links"], textarea[name="purchase_links"], textarea#purchase_links');

    console.log('Purchase links field found:', purchaseLinksField.length);

    if (purchaseLinksField.length) {
        try {
            var linksJson = purchaseLinksField.val();
            if (linksJson) {
                var links = JSON.parse(linksJson);
                var linksHtml = '<div class="purchase-links-container">';

                // Create input fields for each link
                $.each(links, function(site, url) {
                    linksHtml += '<div class="purchase-link-row mb-2">' +
                        '<div class="row">' +
                        '<div class="col-md-3">' +
                        '<label>' + site.charAt(0).toUpperCase() + site.slice(1) + ':</label>' +
                        '</div>' +
                        '<div class="col-md-8">' +
                        '<input type="text" class="form-control purchase-link-url" data-site="' + site + '" value="' + url + '">' +
                        '</div>' +
                        '<div class="col-md-1">' +
                        '<button type="button" class="btn btn-sm btn-danger remove-link">×</button>' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                });

                // Add button to add new link
                linksHtml += '<button type="button" class="btn btn-sm btn-primary mt-2" id="add-purchase-link">Add Link</button>';
                linksHtml += '</div>';

                // Replace the textarea with our custom UI
                purchaseLinksField.after(linksHtml);
                purchaseLinksField.hide();

                // Update the hidden field when inputs change
                updatePurchaseLinksJson();
                $(document).on('change', '.purchase-link-url', updatePurchaseLinksJson);

                // Add new link handler
                $(document).on('click', '#add-purchase-link', function() {
                    var newRow = '<div class="purchase-link-row mb-2">' +
                        '<div class="row">' +
                        '<div class="col-md-3">' +
                        '<input type="text" class="form-control purchase-link-site" placeholder="e.g. amazon">' +
                        '</div>' +
                        '<div class="col-md-8">' +
                        '<input type="text" class="form-control purchase-link-url" data-site="" placeholder="https://...">' +
                        '</div>' +
                        '<div class="col-md-1">' +
                        '<button type="button" class="btn btn-sm btn-danger remove-link">×</button>' +
                        '</div>' +
                        '</div>' +
                        '</div>';

                    $('.purchase-links-container').find('#add-purchase-link').before(newRow);
                });

                // Remove link handler
                $(document).on('click', '.remove-link', function() {
                    $(this).closest('.purchase-link-row').remove();
                    updatePurchaseLinksJson();
                });

                // Update site attribute when site name changes
                $(document).on('change', '.purchase-link-site', function() {
                    var siteInput = $(this);
                    var urlInput = siteInput.closest('.row').find('.purchase-link-url');
                    urlInput.attr('data-site', siteInput.val());
                    updatePurchaseLinksJson();
                });
            }
        } catch (e) {
            console.error('Error parsing purchase links JSON:', e);
        }
    }

    // Function to update the hidden JSON field
    function updatePurchaseLinksJson() {
        var links = {};
        $('.purchase-link-url').each(function() {
            var site = $(this).data('site');
            var url = $(this).val();
            if (site && url) {
                links[site] = url;
            }
        });
        $('textarea[name="purchase_links"]').val(JSON.stringify(links));
    }
});
