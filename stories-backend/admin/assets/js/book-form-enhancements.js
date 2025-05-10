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
 * Initialize series dropdown
 */
function initSeriesDropdown() {
    const seriesInput = document.getElementById('series');
    if (!seriesInput) return;

    // Get the parent element
    const parentElement = seriesInput.parentElement;

    // Create a new select element
    const seriesSelect = document.createElement('select');
    seriesSelect.id = 'series-select';
    seriesSelect.className = 'form-control';
    seriesSelect.name = 'book_series';

    // Add a default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Select a series or enter a new one';
    seriesSelect.appendChild(defaultOption);

    // Common series options
    const seriesOptions = [
        'Harry Potter',
        'Percy Jackson',
        'Chronicles of Narnia',
        'Diary of a Wimpy Kid',
        'The Hunger Games',
        'A Series of Unfortunate Events',
        'Magic Tree House',
        'Wings of Fire',
        'Goosebumps',
        'Dork Diaries'
    ];

    // Add options to the select
    seriesOptions.forEach(series => {
        const option = document.createElement('option');
        option.value = series;
        option.text = series;
        seriesSelect.appendChild(option);
    });

    // Create a hidden input to store the value
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.id = 'series';
    hiddenInput.name = 'book_series';
    hiddenInput.value = seriesInput.value;

    // Replace the input with the select and hidden input
    parentElement.replaceChild(seriesSelect, seriesInput);
    parentElement.appendChild(hiddenInput);

    // Set the selected option if there's a value
    if (hiddenInput.value) {
        // Check if the value exists in the options
        const existingOption = Array.from(seriesSelect.options).find(option => option.value === hiddenInput.value);
        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = hiddenInput.value;
            newOption.text = hiddenInput.value;
            newOption.selected = true;
            seriesSelect.appendChild(newOption);
        }
    }

    // Add event listener to update the hidden input
    seriesSelect.addEventListener('change', function() {
        hiddenInput.value = this.value;
    });
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
    const ageRangeInput = document.getElementById('age_range');
    if (!ageRangeInput) return;

    // Get the parent element
    const parentElement = ageRangeInput.parentElement;

    // Create a new select element
    const ageRangeSelect = document.createElement('select');
    ageRangeSelect.id = 'age-range-select';
    ageRangeSelect.className = 'form-control';
    ageRangeSelect.name = 'book_age_range';

    // Add a default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Select an age range';
    ageRangeSelect.appendChild(defaultOption);

    // Common age range options
    const ageRangeOptions = [
        '0-3',
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

    // Add options to the select
    ageRangeOptions.forEach(ageRange => {
        const option = document.createElement('option');
        option.value = ageRange;
        option.text = ageRange;
        ageRangeSelect.appendChild(option);
    });

    // Replace the input with the select
    parentElement.replaceChild(ageRangeSelect, ageRangeInput);

    // Set the selected option if there's a value
    if (ageRangeInput.value) {
        // Check if the value exists in the options
        const existingOption = Array.from(ageRangeSelect.options).find(option => option.value === ageRangeInput.value);
        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = ageRangeInput.value;
            newOption.text = ageRangeInput.value;
            newOption.selected = true;
            ageRangeSelect.appendChild(newOption);
        }
    }
}

/**
 * Initialize genre dropdown
 */
function initGenreDropdown() {
    const genreInput = document.querySelector('input[name="book_genre"]');
    if (!genreInput) return;

    // Get the parent element
    const parentElement = genreInput.parentElement;

    // Create a new select element
    const genreSelect = document.createElement('select');
    genreSelect.id = 'genre-select';
    genreSelect.className = 'form-control';
    genreSelect.name = 'book_genre';

    // Add a default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Select a genre';
    genreSelect.appendChild(defaultOption);

    // Common genre options
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

    // Add options to the select
    genreOptions.forEach(genre => {
        const option = document.createElement('option');
        option.value = genre;
        option.text = genre;
        genreSelect.appendChild(option);
    });

    // Replace the input with the select
    parentElement.replaceChild(genreSelect, genreInput);

    // Set the selected option if there's a value
    if (genreInput.value) {
        // Check if the value exists in the options
        const existingOption = Array.from(genreSelect.options).find(option => option.value === genreInput.value);
        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = genreInput.value;
            newOption.text = genreInput.value;
            newOption.selected = true;
            genreSelect.appendChild(newOption);
        }
    }
}

/**
 * Initialize reading level dropdown
 */
function initReadingLevelDropdown() {
    const readingLevelInput = document.getElementById('reading_level');
    if (!readingLevelInput) return;

    // Get the parent element
    const parentElement = readingLevelInput.parentElement;

    // Create a new select element
    const readingLevelSelect = document.createElement('select');
    readingLevelSelect.id = 'reading-level-select';
    readingLevelSelect.className = 'form-control';
    readingLevelSelect.name = 'book_reading_level';

    // Add a default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Select a reading level';
    readingLevelSelect.appendChild(defaultOption);

    // Common reading level options
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

    // Add options to the select
    readingLevelOptions.forEach(readingLevel => {
        const option = document.createElement('option');
        option.value = readingLevel;
        option.text = readingLevel;
        readingLevelSelect.appendChild(option);
    });

    // Replace the input with the select
    parentElement.replaceChild(readingLevelSelect, readingLevelInput);

    // Set the selected option if there's a value
    if (readingLevelInput.value) {
        // Check if the value exists in the options
        const existingOption = Array.from(readingLevelSelect.options).find(option => option.value === readingLevelInput.value);
        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = readingLevelInput.value;
            newOption.text = readingLevelInput.value;
            newOption.selected = true;
            readingLevelSelect.appendChild(newOption);
        }
    }
}

/**
 * Initialize publisher dropdown
 */
function initPublisherDropdown() {
    const publisherInput = document.querySelector('input[name="book_publisher"]');
    if (!publisherInput) return;

    // Get the parent element
    const parentElement = publisherInput.parentElement;

    // Create a new select element
    const publisherSelect = document.createElement('select');
    publisherSelect.id = 'publisher-select';
    publisherSelect.className = 'form-control';
    publisherSelect.name = 'book_publisher';

    // Add a default option
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = 'Select a publisher or enter a new one';
    publisherSelect.appendChild(defaultOption);

    // Common publisher options
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

    // Add options to the select
    publisherOptions.forEach(publisher => {
        const option = document.createElement('option');
        option.value = publisher;
        option.text = publisher;
        publisherSelect.appendChild(option);
    });

    // Create a hidden input to store the value
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.id = 'publisher';
    hiddenInput.name = 'book_publisher';
    hiddenInput.value = publisherInput.value;

    // Replace the input with the select and hidden input
    parentElement.replaceChild(publisherSelect, publisherInput);
    parentElement.appendChild(hiddenInput);

    // Set the selected option if there's a value
    if (hiddenInput.value) {
        // Check if the value exists in the options
        const existingOption = Array.from(publisherSelect.options).find(option => option.value === hiddenInput.value);
        if (existingOption) {
            existingOption.selected = true;
        } else {
            // Add a new option for the current value
            const newOption = document.createElement('option');
            newOption.value = hiddenInput.value;
            newOption.text = hiddenInput.value;
            newOption.selected = true;
            publisherSelect.appendChild(newOption);
        }
    }

    // Add event listener to update the hidden input
    publisherSelect.addEventListener('change', function() {
        hiddenInput.value = this.value;
    });
}
