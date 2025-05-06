/**
 * Enhanced Admin JavaScript
 * 
 * This file contains enhanced functionality for the admin interface,
 * including predictive search, live data filtering, and dashboard charts.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Enhanced Admin JS loaded');
    
    // Initialize predictive search
    initPredictiveSearch();
    
    // Initialize dashboard charts if we're on the dashboard page
    if (document.querySelector('.dashboard-cards')) {
        initDashboardCharts();
    }
    
    // Initialize enhanced form validation
    initEnhancedFormValidation();
    
    // Initialize keyboard accessibility
    initKeyboardAccessibility();
});

/**
 * Initialize predictive search functionality
 */
function initPredictiveSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    if (!searchInputs.length) return;
    
    searchInputs.forEach(input => {
        // Create predictive search results container if it doesn't exist
        let resultsContainer = input.parentElement.querySelector('.predictive-search-results');
        
        if (!resultsContainer) {
            resultsContainer = document.createElement('div');
            resultsContainer.className = 'predictive-search-results';
            input.parentElement.appendChild(resultsContainer);
        }
        
        // Add event listeners for input changes
        input.addEventListener('input', debounce(function() {
            const query = input.value.trim();
            
            if (query.length < 2) {
                resultsContainer.classList.remove('active');
                return;
            }
            
            // Get the content type from the form or data attribute
            const form = input.closest('form');
            const contentType = form.getAttribute('data-content-type') || 
                                form.querySelector('[name="content_type"]')?.value || 
                                window.location.pathname.split('/').pop().split('.')[0];
            
            // Get the search field from the form
            const searchField = form.querySelector('[name="search_field"]')?.value || 'all';
            
            // Show loading state
            resultsContainer.innerHTML = '<div class="predictive-search-empty">Searching...</div>';
            resultsContainer.classList.add('active');
            
            // Fetch search results
            fetchPredictiveSearchResults(query, contentType, searchField)
                .then(results => {
                    renderPredictiveSearchResults(results, query, resultsContainer);
                })
                .catch(error => {
                    console.error('Error fetching search results:', error);
                    resultsContainer.innerHTML = '<div class="predictive-search-empty">Error fetching results. Please try again.</div>';
                });
        }, 300));
        
        // Hide results when clicking outside
        document.addEventListener('click', function(event) {
            if (!input.contains(event.target) && !resultsContainer.contains(event.target)) {
                resultsContainer.classList.remove('active');
            }
        });
        
        // Add keyboard navigation
        input.addEventListener('keydown', function(event) {
            if (!resultsContainer.classList.contains('active')) return;
            
            const items = resultsContainer.querySelectorAll('.predictive-search-item');
            const activeItem = resultsContainer.querySelector('.predictive-search-item.active');
            let activeIndex = -1;
            
            // Find the index of the active item
            if (activeItem) {
                for (let i = 0; i < items.length; i++) {
                    if (items[i] === activeItem) {
                        activeIndex = i;
                        break;
                    }
                }
            }
            
            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    if (activeIndex < items.length - 1) {
                        if (activeItem) activeItem.classList.remove('active');
                        items[activeIndex + 1].classList.add('active');
                        items[activeIndex + 1].scrollIntoView({ block: 'nearest' });
                    }
                    break;
                    
                case 'ArrowUp':
                    event.preventDefault();
                    if (activeIndex > 0) {
                        if (activeItem) activeItem.classList.remove('active');
                        items[activeIndex - 1].classList.add('active');
                        items[activeIndex - 1].scrollIntoView({ block: 'nearest' });
                    }
                    break;
                    
                case 'Enter':
                    if (activeItem) {
                        event.preventDefault();
                        activeItem.click();
                    }
                    break;
                    
                case 'Escape':
                    event.preventDefault();
                    resultsContainer.classList.remove('active');
                    break;
            }
        });
    });
}

/**
 * Fetch predictive search results from the server
 * 
 * @param {string} query - The search query
 * @param {string} contentType - The type of content to search
 * @param {string} searchField - The field to search in
 * @returns {Promise} - A promise that resolves to the search results
 */
function fetchPredictiveSearchResults(query, contentType, searchField) {
    // For demonstration purposes, we'll simulate a server response
    // In a real implementation, this would make an AJAX request to the server
    
    return new Promise((resolve) => {
        // Simulate network delay
        setTimeout(() => {
            // This is where you would normally fetch from the server
            // For now, we'll return mock data based on the content type
            
            let results = [];
            
            switch (contentType) {
                case 'stories':
                    results = [
                        { id: 1, title: 'The Adventure Begins', author: 'John Smith', created_at: '2023-05-15', type: 'story' },
                        { id: 2, title: 'Mystery of the Lost Key', author: 'Jane Doe', created_at: '2023-06-20', type: 'story' },
                        { id: 3, title: 'The Magical Forest', author: 'Alice Johnson', created_at: '2023-07-10', type: 'story' }
                    ].filter(item => {
                        if (searchField === 'all') {
                            return item.title.toLowerCase().includes(query.toLowerCase()) || 
                                   item.author.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'title') {
                            return item.title.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'author') {
                            return item.author.toLowerCase().includes(query.toLowerCase());
                        }
                        return true;
                    });
                    break;
                    
                case 'authors':
                    results = [
                        { id: 1, name: 'John Smith', email: 'john@example.com', type: 'author' },
                        { id: 2, name: 'Jane Doe', email: 'jane@example.com', type: 'author' },
                        { id: 3, name: 'Alice Johnson', email: 'alice@example.com', type: 'author' }
                    ].filter(item => {
                        if (searchField === 'all') {
                            return item.name.toLowerCase().includes(query.toLowerCase()) || 
                                   item.email.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'name') {
                            return item.name.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'email') {
                            return item.email.toLowerCase().includes(query.toLowerCase());
                        }
                        return true;
                    });
                    break;
                    
                case 'blog-posts':
                case 'posts':
                    results = [
                        { id: 1, title: 'Getting Started with Stories', author: 'Admin', created_at: '2023-05-10', type: 'post' },
                        { id: 2, title: 'Writing Tips for Young Authors', author: 'Editor', created_at: '2023-06-15', type: 'post' },
                        { id: 3, title: 'The Importance of Reading', author: 'Guest Author', created_at: '2023-07-20', type: 'post' }
                    ].filter(item => {
                        if (searchField === 'all') {
                            return item.title.toLowerCase().includes(query.toLowerCase()) || 
                                   item.author.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'title') {
                            return item.title.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'author') {
                            return item.author.toLowerCase().includes(query.toLowerCase());
                        }
                        return true;
                    });
                    break;
                    
                case 'media':
                    results = [
                        { id: 1, filename: 'hero-image.jpg', alt_text: 'Hero image for homepage', created_at: '2023-05-05', type: 'media' },
                        { id: 2, filename: 'author-profile.jpg', alt_text: 'Author profile picture', created_at: '2023-06-10', type: 'media' },
                        { id: 3, filename: 'story-cover.jpg', alt_text: 'Story cover image', created_at: '2023-07-15', type: 'media' }
                    ].filter(item => {
                        if (searchField === 'all') {
                            return item.filename.toLowerCase().includes(query.toLowerCase()) || 
                                   item.alt_text.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'filename') {
                            return item.filename.toLowerCase().includes(query.toLowerCase());
                        } else if (searchField === 'alt_text') {
                            return item.alt_text.toLowerCase().includes(query.toLowerCase());
                        }
                        return true;
                    });
                    break;
                    
                default:
                    // Generic results for other content types
                    results = [
                        { id: 1, title: 'Item 1', description: 'Description 1', created_at: '2023-05-01', type: contentType },
                        { id: 2, title: 'Item 2', description: 'Description 2', created_at: '2023-06-01', type: contentType },
                        { id: 3, title: 'Item 3', description: 'Description 3', created_at: '2023-07-01', type: contentType }
                    ].filter(item => {
                        return item.title.toLowerCase().includes(query.toLowerCase()) || 
                               item.description.toLowerCase().includes(query.toLowerCase());
                    });
            }
            
            resolve(results);
        }, 300);
    });
}

/**
 * Render predictive search results in the results container
 * 
 * @param {Array} results - The search results to render
 * @param {string} query - The search query
 * @param {HTMLElement} container - The container to render the results in
 */
function renderPredictiveSearchResults(results, query, container) {
    if (!results.length) {
        container.innerHTML = '<div class="predictive-search-empty">No results found. Try a different search term.</div>';
        return;
    }
    
    // Group results by type
    const groupedResults = results.reduce((acc, result) => {
        const type = result.type || 'other';
        if (!acc[type]) acc[type] = [];
        acc[type].push(result);
        return acc;
    }, {});
    
    let html = '';
    
    // Render each group
    for (const [type, items] of Object.entries(groupedResults)) {
        html += `<div class="predictive-search-category">${formatContentType(type)}</div>`;
        
        items.forEach(item => {
            const title = item.title || item.name || item.filename || 'Untitled';
            const subtitle = item.author || item.email || item.alt_text || formatDate(item.created_at) || '';
            
            // Highlight the matching text
            const highlightedTitle = highlightText(title, query);
            const highlightedSubtitle = highlightText(subtitle, query);
            
            html += `
                <div class="predictive-search-item" data-id="${item.id}" data-type="${type}">
                    <div class="predictive-search-item-title">${highlightedTitle}</div>
                    <div class="predictive-search-item-meta">${highlightedSubtitle}</div>
                </div>
            `;
        });
    }
    
    html += `<div class="predictive-search-footer">Press Enter to view all results</div>`;
    
    container.innerHTML = html;
    container.classList.add('active');
    
    // Add click event listeners to items
    container.querySelectorAll('.predictive-search-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            
            // Redirect to the appropriate page based on the item type
            let url;
            
            switch (type) {
                case 'story':
                    url = `view-story.php?id=${id}`;
                    break;
                case 'author':
                    url = `author-form.php?id=${id}`;
                    break;
                case 'post':
                    url = `post-form.php?id=${id}`;
                    break;
                case 'media':
                    url = `media.php?id=${id}`;
                    break;
                default:
                    // Generic URL for other content types
                    url = `${type}-form.php?id=${id}`;
            }
            
            window.location.href = url;
        });
    });
}

/**
 * Highlight matching text in a string
 * 
 * @param {string} text - The text to highlight
 * @param {string} query - The query to highlight
 * @returns {string} - The highlighted text
 */
function highlightText(text, query) {
    if (!text || !query) return text || '';
    
    const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
    return text.replace(regex, '<span class="highlight">$1</span>');
}

/**
 * Escape special characters in a string for use in a regular expression
 * 
 * @param {string} string - The string to escape
 * @returns {string} - The escaped string
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Format a content type for display
 * 
 * @param {string} type - The content type
 * @returns {string} - The formatted content type
 */
function formatContentType(type) {
    const typeMap = {
        'story': 'Stories',
        'author': 'Authors',
        'post': 'Blog Posts',
        'media': 'Media',
        'tag': 'Tags',
        'game': 'Games',
        'ai_tool': 'AI Tools',
        'directory_item': 'Directory Items'
    };
    
    return typeMap[type] || type.charAt(0).toUpperCase() + type.slice(1);
}

/**
 * Format a date for display
 * 
 * @param {string} dateString - The date string
 * @returns {string} - The formatted date
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Debounce a function to prevent it from being called too frequently
 * 
 * @param {Function} func - The function to debounce
 * @param {number} wait - The debounce wait time in milliseconds
 * @returns {Function} - The debounced function
 */
function debounce(func, wait) {
    let timeout;
    
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize dashboard charts
 */
function initDashboardCharts() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded. Charts will not be displayed.');
        return;
    }
    
    // Create content statistics chart
    const contentStatsCtx = document.getElementById('content-stats-chart');
    if (contentStatsCtx) {
        // Get data from dashboard cards
        const labels = [];
        const data = [];
        const colors = [];
        
        document.querySelectorAll('.dashboard-card').forEach(card => {
            const title = card.querySelector('h3').textContent.trim();
            const value = parseInt(card.querySelector('.stat-number').textContent.trim(), 10);
            
            if (!isNaN(value)) {
                labels.push(title);
                data.push(value);
                
                // Assign colors based on card type
                if (card.classList.contains('user-card')) {
                    colors.push('#10b981'); // success color
                } else if (card.classList.contains('media-card')) {
                    colors.push('#3b82f6'); // info color
                } else if (card.classList.contains('notification-card')) {
                    colors.push('#f59e0b'); // warning color
                } else {
                    colors.push('#4361ee'); // primary color
                }
            }
        });
        
        new Chart(contentStatsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Content Count',
                    data: data,
                    backgroundColor: colors,
                    borderColor: colors.map(color => color),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' items';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Create activity timeline chart
    const activityTimelineCtx = document.getElementById('activity-timeline-chart');
    if (activityTimelineCtx) {
        // Sample data - in a real implementation, this would come from the server
        const dates = [];
        const now = new Date();
        
        for (let i = 6; i >= 0; i--) {
            const date = new Date(now);
            date.setDate(date.getDate() - i);
            dates.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        }
        
        new Chart(activityTimelineCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Stories',
                    data: [3, 5, 2, 7, 4, 6, 8],
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Blog Posts',
                    data: [1, 2, 4, 2, 3, 5, 4],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }
}

/**
 * Initialize enhanced form validation
 */
function initEnhancedFormValidation() {
    const forms = document.querySelectorAll('form:not(.search-form)');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            // Add real-time validation on blur
            input.addEventListener('blur', function() {
                validateInput(this);
            });
            
            // Add real-time validation on input for certain fields
            if (input.type === 'email' || input.type === 'url' || input.type === 'number') {
                input.addEventListener('input', debounce(function() {
                    validateInput(this);
                }, 500));
            }
        });
        
        // Add form submission validation
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                
                // Scroll to the first invalid input
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
        });
    });
}

/**
 * Validate a form input
 * 
 * @param {HTMLElement} input - The input to validate
 * @returns {boolean} - Whether the input is valid
 */
function validateInput(input) {
    // Skip disabled or readonly inputs
    if (input.disabled || input.readOnly) return true;
    
    // Skip inputs without validation rules
    if (!input.required && !input.pattern && !input.minLength && !input.maxLength && 
        input.type !== 'email' && input.type !== 'url' && input.type !== 'number') {
        return true;
    }
    
    let isValid = true;
    let errorMessage = '';
    
    // Check if the input is required and empty
    if (input.required && !input.value.trim()) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    // Check if the input matches its pattern
    else if (input.pattern && !new RegExp(input.pattern).test(input.value)) {
        isValid = false;
        errorMessage = input.title || 'Please match the requested format';
    }
    // Check if the input meets its length requirements
    else if (input.minLength && input.value.length < input.minLength) {
        isValid = false;
        errorMessage = `Please enter at least ${input.minLength} characters`;
    }
    else if (input.maxLength && input.value.length > input.maxLength) {
        isValid = false;
        errorMessage = `Please enter no more than ${input.maxLength} characters`;
    }
    // Check specific input types
    else if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address';
    }
    else if (input.type === 'url' && input.value && !isValidUrl(input.value)) {
        isValid = false;
        errorMessage = 'Please enter a valid URL';
    }
    else if (input.type === 'number') {
        if (input.min !== '' && Number(input.value) < Number(input.min)) {
            isValid = false;
            errorMessage = `Please enter a value greater than or equal to ${input.min}`;
        }
        else if (input.max !== '' && Number(input.value) > Number(input.max)) {
            isValid = false;
            errorMessage = `Please enter a value less than or equal to ${input.max}`;
        }
    }
    
    // Update the input's validation state
    if (isValid) {
        input.classList.remove('is-invalid');
        if (input.value.trim()) {
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
        }
        
        // Remove any existing error message
        const errorElement = input.parentElement.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.remove();
        }
    } else {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        
        // Add or update the error message
        let errorElement = input.parentElement.querySelector('.invalid-feedback');
        
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            input.parentElement.appendChild(errorElement);
        }
        
        errorElement.textContent = errorMessage;
    }
    
    return isValid;
}

/**
 * Check if a string is a valid email address
 * 
 * @param {string} email - The email address to validate
 * @returns {boolean} - Whether the email is valid
 */
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Check if a string is a valid URL
 * 
 * @param {string} url - The URL to validate
 * @returns {boolean} - Whether the URL is valid
 */
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * Initialize keyboard accessibility
 */
function initKeyboardAccessibility() {
    // Add keyboard navigation for buttons and links
    const interactiveElements = document.querySelectorAll('a, button, [role="button"], [tabindex="0"]');
    
    interactiveElements.forEach(element => {
        element.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                element.click();
            }
        });
    });
    
    // Add keyboard navigation for dropdowns
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown') {
                event.preventDefault();
                
                // Toggle the dropdown
                const dropdown = toggle.parentElement;
                const menu = dropdown.querySelector('.dropdown-menu');
                
                if (menu) {
                    const isOpen = menu.classList.contains('show');
                    
                    if (!isOpen) {
                        // Open the dropdown
                        menu.classList.add('show');
                        toggle.setAttribute('aria-expanded', 'true');
                        
                        // Focus the first item
                        const firstItem = menu.querySelector('a, button');
                        if (firstItem) {
                            firstItem.focus();
                        }
                    } else {
                        // Close the dropdown
                        menu.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                }
            }
        });
    });
}
