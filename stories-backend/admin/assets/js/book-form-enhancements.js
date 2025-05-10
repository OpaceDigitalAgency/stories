/**
 * Book Form Enhancements
 *
 * This script enhances the book form with:
 * - Improved tag selection (filtering out age tags)
 * - Author dropdown without "**" prefixes
 * - Series dropdown with common values
 * - User-friendly purchase links manager
 * - Age range dropdown
 * - Genre dropdown
 * - Reading level dropdown
 * - Publisher dropdown
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Book Form Enhancements loaded');

    // Initialize all enhancements
    initTagSelection();
    initAuthorDropdown();
    initSeriesDropdown();
    initPurchaseLinksManager();
    initAgeRangeDropdown();
    initGenreDropdown();
    initReadingLevelDropdown();
    initPublisherDropdown();
});

/**
 * Initialize tag selection with filtering
 */
function initTagSelection() {
    const tagSelect = document.getElementById('tag-select');
    if (!tagSelect) return;

    // Get all options
    const options = Array.from(tagSelect.options);

    // Filter out options with "**" prefix
    options.forEach(option => {
        if (option.text.startsWith('**')) {
            option.text = option.text.replace(/^\*\*\s*/, '');
        }
    });

    // Filter out age-related tags
    const ageRelatedPatterns = [
        /^\d+\+$/, // e.g., "12+"
        /^\d+-\d+$/, // e.g., "7-10"
        /^\d+ and up$/, // e.g., "12 and up"
    ];

    options.forEach(option => {
        const isAgeRelated = ageRelatedPatterns.some(pattern => pattern.test(option.text));
        if (isAgeRelated && option.value) {
            option.dataset.ageTag = 'true';
            option.style.display = 'none';
        }
    });
}

/**
 * Initialize author dropdown
 */
function initAuthorDropdown() {
    const authorSelect = document.getElementById('author');
    if (!authorSelect) return;

    // Get all options
    const options = Array.from(authorSelect.options);

    // Remove "**" prefix from author names
    options.forEach(option => {
        if (option.text.startsWith('**')) {
            option.text = option.text.replace(/^\*\*\s*/, '');
        }
    });
}

/**
 * Initialize series field
 *
 * Note: Series is specific to each book, so we keep it as a text field
 * rather than converting it to a dropdown.
 */
function initSeriesDropdown() {
    // No need to modify the series field as it should remain a text input
    console.log('Series field kept as text input');
}

/**
 * Initialize purchase links manager
 */
function initPurchaseLinksManager() {
    const purchaseLinksField = document.getElementById('purchase_links');
    if (!purchaseLinksField) return;

    // Get the parent element
    const parentElement = purchaseLinksField.parentElement;

    // Create a container for the purchase links manager
    const managerContainer = document.createElement('div');
    managerContainer.id = 'purchase-links-manager';
    managerContainer.className = 'purchase-links-manager';

    // Parse the current JSON value
    let purchaseLinks = {};
    try {
        if (purchaseLinksField.value.trim()) {
            purchaseLinks = JSON.parse(purchaseLinksField.value);
        }
    } catch (e) {
        console.error('Invalid JSON format in purchase links:', e);
    }

    // Create the manager UI
    managerContainer.innerHTML = `
        <div class="purchase-links-list">
            <!-- Links will be added here dynamically -->
        </div>
        <div class="purchase-links-form">
            <div class="form-row">
                <div class="col">
                    <select id="store-select" class="form-control">
                        <option value="">Select Store</option>
                        <option value="amazon">Amazon</option>
                        <option value="goodreads">Goodreads</option>
                        <option value="barnes_noble">Barnes & Noble</option>
                        <option value="waterstones">Waterstones</option>
                        <option value="bookshop">Bookshop.org</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col">
                    <input type="text" id="store-url" class="form-control" placeholder="Enter URL">
                </div>
                <div class="col-auto">
                    <button type="button" id="add-store" class="btn btn-primary">Add</button>
                </div>
            </div>
        </div>
    `;

    // Hide the original textarea
    purchaseLinksField.style.display = 'none';

    // Insert the manager before the textarea
    parentElement.insertBefore(managerContainer, purchaseLinksField);

    // Function to update the purchase links list
    function updatePurchaseLinks() {
        const linksList = managerContainer.querySelector('.purchase-links-list');
        linksList.innerHTML = '';

        // Add each link to the list
        Object.entries(purchaseLinks).forEach(([store, url]) => {
            const linkItem = document.createElement('div');
            linkItem.className = 'purchase-link-item';
            linkItem.innerHTML = `
                <div class="store-name">${store.charAt(0).toUpperCase() + store.slice(1).replace('_', ' ')}</div>
                <div class="store-url"><a href="${url}" target="_blank">${url}</a></div>
                <div class="store-actions">
                    <button type="button" class="btn btn-sm btn-danger remove-store" data-store="${store}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            linksList.appendChild(linkItem);
        });

        // Update the JSON in the textarea
        purchaseLinksField.value = JSON.stringify(purchaseLinks, null, 2);
    }

    // Initialize the list
    updatePurchaseLinks();

    // Add event listener for adding a store
    const addStoreButton = managerContainer.querySelector('#add-store');
    addStoreButton.addEventListener('click', function() {
        const storeSelect = managerContainer.querySelector('#store-select');
        const storeUrl = managerContainer.querySelector('#store-url');

        let store = storeSelect.value;
        const url = storeUrl.value.trim();

        if (!store || !url) {
            alert('Please select a store and enter a URL');
            return;
        }

        // Validate URL
        if (!url.startsWith('http://') && !url.startsWith('https://')) {
            alert('Please enter a valid URL starting with http:// or https://');
            return;
        }

        // Add the store to the purchase links
        purchaseLinks[store] = url;

        // Update the list
        updatePurchaseLinks();

        // Reset the form
        storeSelect.value = '';
        storeUrl.value = '';
    });

    // Add event delegation for removing a store
    managerContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-store') || e.target.closest('.remove-store')) {
            const button = e.target.classList.contains('remove-store') ? e.target : e.target.closest('.remove-store');
            const store = button.dataset.store;

            // Remove the store from the purchase links
            delete purchaseLinks[store];

            // Update the list
            updatePurchaseLinks();
        }
    });
}

/**
 * Initialize age range dropdown
 */
function initAgeRangeDropdown() {
    const ageRangeSelect = document.getElementById('age_range');
    if (!ageRangeSelect) return;

    // Get the current value
    const currentValue = ageRangeSelect.value;

    // Common age range options to add if they don't exist
    const ageRangeOptions = [
        '0-3',
        '3-5',
        '4-6',
        '5-7',
        '6-8',
        '7-9',
        '7-10',
        '8-10',
        '8-12',
        '9-12',
        '10-12',
        '10+',
        '12+',
        '13+',
        '14+',
        '16+',
        'All ages'
    ];

    // Get existing options
    const existingOptions = Array.from(ageRangeSelect.options).map(option => option.value.toLowerCase());

    // Add missing options
    ageRangeOptions.forEach(ageRange => {
        if (!existingOptions.includes(ageRange.toLowerCase())) {
            const option = document.createElement('option');
            option.value = ageRange;
            option.text = ageRange;
            ageRangeSelect.appendChild(option);
        }
    });

    // Set the selected option if there's a value
    if (currentValue) {
        // Check if the value exists in the options
        const existingOption = Array.from(ageRangeSelect.options).find(option =>
            option.value.toLowerCase() === currentValue.toLowerCase() ||
            option.text.toLowerCase() === currentValue.toLowerCase()
        );

        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = currentValue;
            newOption.text = currentValue;
            newOption.selected = true;
            ageRangeSelect.appendChild(newOption);
        }
    }
}

/**
 * Initialize genre dropdown
 */
function initGenreDropdown() {
    const genreSelect = document.getElementById('genre');
    if (!genreSelect) return;

    // Get the current value
    const currentValue = genreSelect.value;

    // Common genre options to add if they don't exist
    const genreOptions = [
        'Adventure',
        'Fantasy',
        'Science Fiction',
        'Mystery',
        'Horror',
        'Thriller',
        'Historical Fiction',
        'Romance',
        'Contemporary',
        'Dystopian',
        'Fairy Tale',
        'Humor',
        'Educational',
        'Picture Book',
        'Poetry',
        'Biography',
        'Non-fiction'
    ];

    // Get existing options
    const existingOptions = Array.from(genreSelect.options).map(option => option.value.toLowerCase());

    // Add missing options
    genreOptions.forEach(genre => {
        if (!existingOptions.includes(genre.toLowerCase())) {
            const option = document.createElement('option');
            option.value = genre.toLowerCase().replace(/\s+/g, '-');
            option.text = genre;
            genreSelect.appendChild(option);
        }
    });

    // Set the selected option if there's a value
    if (currentValue) {
        // Check if the value exists in the options
        const existingOption = Array.from(genreSelect.options).find(option =>
            option.value.toLowerCase() === currentValue.toLowerCase() ||
            option.text.toLowerCase() === currentValue.toLowerCase()
        );

        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = currentValue;
            newOption.text = currentValue.charAt(0).toUpperCase() + currentValue.slice(1).replace(/-/g, ' ');
            newOption.selected = true;
            genreSelect.appendChild(newOption);
        }
    }
}

/**
 * Initialize reading level dropdown
 */
function initReadingLevelDropdown() {
    const readingLevelSelect = document.getElementById('reading_level');
    if (!readingLevelSelect) return;

    // Get the current value
    const currentValue = readingLevelSelect.value;

    // Common reading level options to add if they don't exist
    const readingLevelOptions = [
        'Early Reader',
        'First Reader',
        'Chapter Book',
        'Middle Grade',
        'Young Adult',
        'Adult',
        'Level 1',
        'Level 2',
        'Level 3',
        'Level 4',
        'Level 5',
        'Beginner',
        'Intermediate',
        'Advanced',
        'Lexile 200-300',
        'Lexile 300-400',
        'Lexile 400-500',
        'Lexile 500-600',
        'Lexile 600-700',
        'Lexile 700-800',
        'Lexile 800-900',
        'Lexile 900-1000',
        'Lexile 1000+'
    ];

    // Get existing options
    const existingOptions = Array.from(readingLevelSelect.options).map(option => option.value.toLowerCase());

    // Add missing options
    readingLevelOptions.forEach(readingLevel => {
        if (!existingOptions.includes(readingLevel.toLowerCase())) {
            const option = document.createElement('option');
            option.value = readingLevel.toLowerCase().replace(/\s+/g, '-');
            option.text = readingLevel;
            readingLevelSelect.appendChild(option);
        }
    });

    // Set the selected option if there's a value
    if (currentValue) {
        // Check if the value exists in the options
        const existingOption = Array.from(readingLevelSelect.options).find(option =>
            option.value.toLowerCase() === currentValue.toLowerCase() ||
            option.text.toLowerCase() === currentValue.toLowerCase()
        );

        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = currentValue;
            newOption.text = currentValue.charAt(0).toUpperCase() + currentValue.slice(1).replace(/-/g, ' ');
            newOption.selected = true;
            readingLevelSelect.appendChild(newOption);
        }
    }
}

/**
 * Initialize publisher dropdown
 */
function initPublisherDropdown() {
    const publisherSelect = document.getElementById('publisher');
    if (!publisherSelect) return;

    // Get the current value
    const currentValue = publisherSelect.value;

    // Common publisher options to add if they don't exist
    const publisherOptions = [
        'Penguin Random House',
        'HarperCollins',
        'Simon & Schuster',
        'Hachette Book Group',
        'Macmillan Publishers',
        'Scholastic',
        'Oxford University Press',
        'Cambridge University Press',
        'Bloomsbury',
        'Usborne',
        'Walker Books',
        'Nosy Crow',
        'Puffin Books',
        'Ladybird Books',
        'Orion Children\'s Books',
        'Andersen Press',
        'Egmont Books',
        'Chicken House',
        'Little Tiger Press',
        'Barrington Stoke'
    ];

    // Get existing options
    const existingOptions = Array.from(publisherSelect.options).map(option => option.value.toLowerCase());

    // Add missing options
    publisherOptions.forEach(publisher => {
        if (!existingOptions.includes(publisher.toLowerCase())) {
            const option = document.createElement('option');
            option.value = publisher;
            option.text = publisher;
            publisherSelect.appendChild(option);
        }
    });

    // Set the selected option if there's a value
    if (currentValue && currentValue !== 'custom') {
        // Check if the value exists in the options
        const existingOption = Array.from(publisherSelect.options).find(option =>
            option.value.toLowerCase() === currentValue.toLowerCase() ||
            option.text.toLowerCase() === currentValue.toLowerCase()
        );

        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = currentValue;
            newOption.text = currentValue;
            newOption.selected = true;
            publisherSelect.appendChild(newOption);
        }
    }
}
