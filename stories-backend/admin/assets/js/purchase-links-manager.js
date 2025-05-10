/**
 * Purchase Links Manager
 *
 * This script provides a user-friendly interface for managing purchase links
 * for books in the directory item form.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const purchaseLinksField = document.getElementById('purchase_links');
    const purchaseLinksContainer = document.getElementById('purchase-links-container');
    const addPurchaseLinkBtn = document.getElementById('add-purchase-link-btn');

    // Remove any duplicate purchase links containers that might exist
    const duplicateContainers = document.querySelectorAll('#purchase-links-container');
    if (duplicateContainers.length > 1) {
        for (let i = 1; i < duplicateContainers.length; i++) {
            duplicateContainers[i].remove();
        }
    }

    // Common book store options
    const commonStores = [
        'amazon',
        'goodreads',
        'barnes-noble',
        'waterstones',
        'book-depository',
        'indiebound',
        'kobo',
        'apple-books',
        'google-play'
    ];

    // Initialize if all required elements exist
    if (purchaseLinksField && purchaseLinksContainer && addPurchaseLinkBtn) {
        // Initialize purchase links from JSON
        initializePurchaseLinks();

        // Add new purchase link
        addPurchaseLinkBtn.addEventListener('click', function() {
            addPurchaseLinkRow('', '');
        });

        // Event delegation for remove buttons
        purchaseLinksContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-purchase-link')) {
                e.target.closest('.purchase-link-row').remove();
                updatePurchaseLinksJson();
            }
        });

        // Update JSON when inputs change
        purchaseLinksContainer.addEventListener('change', function(e) {
            if (e.target.classList.contains('purchase-link-site') ||
                e.target.classList.contains('purchase-link-url')) {
                updatePurchaseLinksJson();
            }
        });
    }

    /**
     * Initialize purchase links from JSON field
     */
    function initializePurchaseLinks() {
        try {
            if (purchaseLinksField.value.trim()) {
                const links = JSON.parse(purchaseLinksField.value);

                // Create UI elements for each link
                for (const [site, url] of Object.entries(links)) {
                    addPurchaseLinkRow(site, url);
                }
            }
        } catch (e) {
            console.error('Error parsing purchase links JSON:', e);
        }
    }

    /**
     * Add a new purchase link row to the container
     * @param {string} site - The store/site name
     * @param {string} url - The purchase URL
     */
    function addPurchaseLinkRow(site, url) {
        const row = document.createElement('div');
        row.className = 'purchase-link-row mb-2';

        // Create store dropdown options
        let storeOptions = '<option value="">Select store</option>';
        commonStores.forEach(store => {
            const displayName = store.split('-').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');

            const selected = store === site ? 'selected' : '';
            storeOptions += `<option value="${store}" ${selected}>${displayName}</option>`;
        });

        // Add custom option if site is not in common stores
        if (site && !commonStores.includes(site)) {
            storeOptions += `<option value="${site}" selected>${site}</option>`;
        }

        row.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <select class="form-control purchase-link-site">
                        ${storeOptions}
                        <option value="custom" ${site && !commonStores.includes(site) ? 'selected' : ''}>Custom...</option>
                    </select>
                    <input type="text" class="form-control purchase-link-custom mt-1 ${site && !commonStores.includes(site) ? '' : 'd-none'}"
                           value="${site && !commonStores.includes(site) ? site : ''}" placeholder="Enter store name">
                </div>
                <div class="col-md-7">
                    <input type="url" class="form-control purchase-link-url"
                           value="${url}" placeholder="https://...">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger remove-purchase-link">×</button>
                </div>
            </div>
        `;

        purchaseLinksContainer.appendChild(row);

        // Add event listener for custom store option
        const siteSelect = row.querySelector('.purchase-link-site');
        const customInput = row.querySelector('.purchase-link-custom');

        siteSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customInput.classList.remove('d-none');
                customInput.focus();
            } else {
                customInput.classList.add('d-none');
            }
            updatePurchaseLinksJson();
        });

        customInput.addEventListener('input', updatePurchaseLinksJson);
    }

    /**
     * Update the hidden JSON field with current purchase links
     */
    function updatePurchaseLinksJson() {
        const links = {};
        const rows = purchaseLinksContainer.querySelectorAll('.purchase-link-row');

        rows.forEach(row => {
            const siteSelect = row.querySelector('.purchase-link-site');
            const customInput = row.querySelector('.purchase-link-custom');
            const urlInput = row.querySelector('.purchase-link-url');

            let site = siteSelect.value;
            if (site === 'custom') {
                site = customInput.value.trim();
            }

            const url = urlInput.value.trim();

            if (site && url) {
                links[site] = url;
            }
        });

        purchaseLinksField.value = JSON.stringify(links);
    }
});
