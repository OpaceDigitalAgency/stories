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

    // Check if we're on a page with book fields
    const bookFields = document.querySelector('.book-fields');
    const typeSelect = document.getElementById('type');

    // Force initialization for book type regardless of display state
    if (bookFields && typeSelect && typeSelect.value === 'book') {
        console.log('Book type detected, initializing enhancements');

        // Make sure book fields are visible
        bookFields.style.display = 'block';

        // Initialize all enhancements with a slight delay to ensure DOM is ready
        setTimeout(() => {
            initTagSelection();
            initAuthorDropdown();
            // Series is now a text field with datalist, no initialization needed
            initPurchaseLinksManager();
            // Disable JavaScript enhancement for these fields - using PHP-generated dropdowns instead
            // initAgeRangeDropdown();
            // initGenreDropdown();
            // initReadingLevelDropdown();
            initPublisherDropdown();
            console.log('Book form enhancements initialized');
        }, 100);
    } else if (bookFields && getComputedStyle(bookFields).display !== 'none') {
        // If book fields are visible by computed style (not just inline style)
        console.log('Book fields visible, initializing enhancements');

        // Initialize all enhancements
        initTagSelection();
        initAuthorDropdown();
        // Series is now a text field with datalist, no initialization needed
        initPurchaseLinksManager();
        // Disable JavaScript enhancement for these fields - using PHP-generated dropdowns instead
        // initAgeRangeDropdown();
        // initGenreDropdown();
        // initReadingLevelDropdown();
        initPublisherDropdown();
    } else {
        console.log('Book fields not visible or not a book type, skipping initialization');
    }
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
    console.log('Book form enhancements: initPurchaseLinksManager called');
    // This is now handled by purchase-links-formatter.js
    // We'll just make sure the purchase links field exists
    const purchaseLinksField = document.getElementById('purchase_links');
    if (!purchaseLinksField) {
        console.log('Purchase links field not found');
        return;
    }

    console.log('Purchase links field found');
}

/**
 * Initialize age range dropdown
 */
function initAgeRangeDropdown() {
    const ageRangeSelect = document.getElementById('age_range');
    if (!ageRangeSelect) return;

    // Log the initial state
    console.log('Initializing age range dropdown');
    console.log('Initial value:', ageRangeSelect.value);
    console.log('Initial selected index:', ageRangeSelect.selectedIndex);

    // Store the current value before we modify the dropdown
    const currentValue = ageRangeSelect.value;
    console.log('Current age range value:', currentValue);

    // Also check if there's a selected option already
    let initialSelectedOption = null;
    for (let i = 0; i < ageRangeSelect.options.length; i++) {
        if (ageRangeSelect.options[i].selected) {
            initialSelectedOption = ageRangeSelect.options[i];
            console.log('Found initially selected option:', initialSelectedOption.value, initialSelectedOption.text);
            break;
        }
    }

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
    console.log('Existing age range options:', existingOptions);

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
        console.log('Trying to select age range value:', currentValue);

        // First try exact match
        let found = false;
        for (let i = 0; i < ageRangeSelect.options.length; i++) {
            const option = ageRangeSelect.options[i];
            if (option.value === currentValue) {
                console.log('Found exact match for age range:', option.value);
                option.selected = true;
                found = true;
                break;
            }
        }

        // If no exact match, try case-insensitive match
        if (!found) {
            for (let i = 0; i < ageRangeSelect.options.length; i++) {
                const option = ageRangeSelect.options[i];
                if (option.value.toLowerCase() === currentValue.toLowerCase()) {
                    console.log('Found case-insensitive match for age range:', option.value);
                    option.selected = true;
                    found = true;
                    break;
                }
            }
        }

        // If still no match, add a new option
        if (!found) {
            console.log('No match found for age range, adding new option:', currentValue);
            const newOption = document.createElement('option');
            newOption.value = currentValue;
            newOption.text = currentValue;
            newOption.selected = true;
            ageRangeSelect.appendChild(newOption);
        }
    } else if (initialSelectedOption) {
        // If there was a selected option but no value, restore the selection
        console.log('Restoring initial selection for age range:', initialSelectedOption.value);
        for (let i = 0; i < ageRangeSelect.options.length; i++) {
            if (ageRangeSelect.options[i].value === initialSelectedOption.value) {
                ageRangeSelect.options[i].selected = true;
                break;
            }
        }
    }

    // Log the final state
    console.log('Final age range selected index:', ageRangeSelect.selectedIndex);
    console.log('Final age range value:', ageRangeSelect.value);
}

/**
 * Initialize genre dropdown
 */
function initGenreDropdown() {
    const genreSelect = document.getElementById('genre');
    if (!genreSelect) return;

    // Log the initial state
    console.log('Initializing genre dropdown');
    console.log('Initial value:', genreSelect.value);
    console.log('Initial selected index:', genreSelect.selectedIndex);

    // Store the current value before we modify the dropdown
    const currentValue = genreSelect.value;
    console.log('Current genre value:', currentValue);

    // Also check if there's a selected option already
    let initialSelectedOption = null;
    for (let i = 0; i < genreSelect.options.length; i++) {
        if (genreSelect.options[i].selected) {
            initialSelectedOption = genreSelect.options[i];
            console.log('Found initially selected option:', initialSelectedOption.value, initialSelectedOption.text);
            break;
        }
    }

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
        'Non-fiction',
        'Fiction',
        'Chapter Book',
        'Middle Grade',
        'Young Adult'
    ];

    // Get existing options
    const existingOptions = Array.from(genreSelect.options).map(option => option.value.toLowerCase());
    console.log('Existing genre options:', existingOptions);

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
        console.log('Trying to select genre value:', currentValue);

        // First try exact match
        let found = false;
        for (let i = 0; i < genreSelect.options.length; i++) {
            const option = genreSelect.options[i];
            if (option.value === currentValue) {
                console.log('Found exact match for genre:', option.value);
                option.selected = true;
                found = true;
                break;
            }
        }

        // If no exact match, try case-insensitive match
        if (!found) {
            for (let i = 0; i < genreSelect.options.length; i++) {
                const option = genreSelect.options[i];
                if (option.value.toLowerCase() === currentValue.toLowerCase()) {
                    console.log('Found case-insensitive match for genre:', option.value);
                    option.selected = true;
                    found = true;
                    break;
                }
            }
        }

        // If still no match, add a new option
        if (!found) {
            console.log('No match found for genre, adding new option:', currentValue);
            const newOption = document.createElement('option');
            newOption.value = currentValue;
            newOption.text = currentValue.charAt(0).toUpperCase() + currentValue.slice(1).replace(/-/g, ' ');
            newOption.selected = true;
            genreSelect.appendChild(newOption);
        }
    } else if (initialSelectedOption) {
        // If there was a selected option but no value, restore the selection
        console.log('Restoring initial selection for genre:', initialSelectedOption.value);
        for (let i = 0; i < genreSelect.options.length; i++) {
            if (genreSelect.options[i].value === initialSelectedOption.value) {
                genreSelect.options[i].selected = true;
                break;
            }
        }
    }

    // Log the final state
    console.log('Final genre selected index:', genreSelect.selectedIndex);
    console.log('Final genre value:', genreSelect.value);
}

/**
 * Initialize reading level dropdown
 */
function initReadingLevelDropdown() {
    const readingLevelSelect = document.getElementById('reading_level');
    if (!readingLevelSelect) return;

    // Log the initial state
    console.log('Initializing reading level dropdown');
    console.log('Initial value:', readingLevelSelect.value);
    console.log('Initial selected index:', readingLevelSelect.selectedIndex);

    // Store the current value before we modify the dropdown
    const currentValue = readingLevelSelect.value;
    console.log('Current reading level value:', currentValue);

    // Also check if there's a selected option already
    let initialSelectedOption = null;
    for (let i = 0; i < readingLevelSelect.options.length; i++) {
        if (readingLevelSelect.options[i].selected) {
            initialSelectedOption = readingLevelSelect.options[i];
            console.log('Found initially selected option:', initialSelectedOption.value, initialSelectedOption.text);
            break;
        }
    }

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
    console.log('Existing reading level options:', existingOptions);

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
        console.log('Trying to select reading level value:', currentValue);

        // First try exact match
        let found = false;
        for (let i = 0; i < readingLevelSelect.options.length; i++) {
            const option = readingLevelSelect.options[i];
            if (option.value === currentValue) {
                console.log('Found exact match for reading level:', option.value);
                option.selected = true;
                found = true;
                break;
            }
        }

        // If no exact match, try case-insensitive match
        if (!found) {
            for (let i = 0; i < readingLevelSelect.options.length; i++) {
                const option = readingLevelSelect.options[i];
                if (option.value.toLowerCase() === currentValue.toLowerCase()) {
                    console.log('Found case-insensitive match for reading level:', option.value);
                    option.selected = true;
                    found = true;
                    break;
                }
            }
        }

        // If still no match, add a new option
        if (!found) {
            console.log('No match found for reading level, adding new option:', currentValue);
            const newOption = document.createElement('option');
            newOption.value = currentValue;
            newOption.text = currentValue.charAt(0).toUpperCase() + currentValue.slice(1).replace(/-/g, ' ');
            newOption.selected = true;
            readingLevelSelect.appendChild(newOption);
        }
    } else if (initialSelectedOption) {
        // If there was a selected option but no value, restore the selection
        console.log('Restoring initial selection for reading level:', initialSelectedOption.value);
        for (let i = 0; i < readingLevelSelect.options.length; i++) {
            if (readingLevelSelect.options[i].value === initialSelectedOption.value) {
                readingLevelSelect.options[i].selected = true;
                break;
            }
        }
    }

    // Log the final state
    console.log('Final reading level selected index:', readingLevelSelect.selectedIndex);
    console.log('Final reading level value:', readingLevelSelect.value);
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
        'HarperCollins Children\'s Books',
        'Simon & Schuster',
        'Hachette Book Group',
        'Macmillan Publishers',
        'Scholastic',
        'Oxford University Press',
        'Cambridge University Press',
        'Bloomsbury Publishing',
        'Usborne Publishing',
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
