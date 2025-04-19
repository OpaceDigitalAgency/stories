/**
 * Form Submission Fix
 * 
 * This script fixes the form submission issue in the admin interface.
 * It replaces the problematic form submission handler with a direct API call.
 */

(function() {
    console.log('[FORM FIX] Form submission fix loaded');

    // Function to handle form submissions
    function handleFormSubmission(form) {
        console.log('[FORM FIX] Form submission handler activated');
        
        // Get the form action
        const action = form.getAttribute('action');
        console.log('[FORM FIX] Form action:', action);
        
        // Determine the API endpoint and method
        let endpoint = '';
        let method = 'POST';
        
        // Extract content type from URL
        let contentType = '';
        if (action.includes('stories.php')) {
            contentType = 'stories';
        } else if (action.includes('authors.php')) {
            contentType = 'authors';
        } else if (action.includes('tags.php')) {
            contentType = 'tags';
        } else if (action.includes('blog-posts.php')) {
            contentType = 'blog-posts';
        } else if (action.includes('games.php')) {
            contentType = 'games';
        } else if (action.includes('directory-items.php')) {
            contentType = 'directory-items';
        } else if (action.includes('ai-tools.php')) {
            contentType = 'ai-tools';
        }
        
        console.log('[FORM FIX] Content type:', contentType);
        
        // Extract ID if editing
        let id = null;
        if (action.includes('action=edit') || window.location.href.includes('action=edit')) {
            const idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log('[FORM FIX] Extracted ID:', id);
                endpoint = '/api/v1/' + contentType + '/' + id;
                method = 'PUT';
            }
        } else if (action.includes('action=create') || window.location.href.includes('action=create')) {
            endpoint = '/api/v1/' + contentType;
            method = 'POST';
        } else if (action.includes('action=delete') || window.location.href.includes('action=delete')) {
            const idMatch = window.location.href.match(/id=([0-9]+)/);
            if (idMatch) {
                id = idMatch[1];
                console.log('[FORM FIX] Extracted ID for deletion:', id);
                endpoint = '/api/v1/' + contentType + '/' + id;
                method = 'DELETE';
            }
        }
        
        console.log('[FORM FIX] API endpoint:', endpoint);
        console.log('[FORM FIX] HTTP method:', method);
        
        // If we couldn't determine the endpoint, fall back to the original form submission
        if (!endpoint) {
            console.log('[FORM FIX] Could not determine API endpoint, falling back to original form submission');
            return true;
        }
        
        // Get form data
        const formData = new FormData(form);
        const formObject = {};
        formData.forEach((value, key) => {
            formObject[key] = value;
        });
        
        console.log('[FORM FIX] Form data:', formObject);
        
        // Show loading message (already shown by the original form handler)
        
        // Make a direct API call
        fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formObject)
        })
            .then(response => {
                console.log('[FORM FIX] API response:', response);
                return response.text();
            })
            .then(text => {
                console.log('[FORM FIX] Response text:', text);
                
                try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);
                    console.log('[FORM FIX] Parsed JSON:', data);
                    
                    // Hide the loading overlay
                    const loadingOverlay = document.querySelector('.loading-overlay');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                    
                    // Hide any processing messages
                    const processingMessages = document.querySelectorAll('.mt-2.loading-message');
                    processingMessages.forEach(message => {
                        message.style.display = 'none';
                    });
                    
                    // Show success message
                    alert('Changes saved successfully!');
                    
                    // Redirect to the list page
                    if (action.includes('action=create') || action.includes('action=edit')) {
                        // Extract the base URL without the query parameters
                        const baseUrl = window.location.href.split('?')[0];
                        window.location.href = baseUrl;
                    } else {
                        // Reload the current page
                        window.location.reload();
                    }
                } catch (e) {
                    console.error('[FORM FIX] Error parsing JSON:', e);
                    
                    // Hide the loading overlay
                    const loadingOverlay = document.querySelector('.loading-overlay');
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'none';
                    }
                    
                    // Hide any processing messages
                    const processingMessages = document.querySelectorAll('.mt-2.loading-message');
                    processingMessages.forEach(message => {
                        message.style.display = 'none';
                    });
                    
                    // Show error message
                    alert('Error saving changes: ' + e.message + '\n\nResponse: ' + text);
                }
            })
            .catch(error => {
                console.error('[FORM FIX] API error:', error);
                
                // Hide the loading overlay
                const loadingOverlay = document.querySelector('.loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                }
                
                // Hide any processing messages
                const processingMessages = document.querySelectorAll('.mt-2.loading-message');
                processingMessages.forEach(message => {
                    message.style.display = 'none';
                });
                
                // Show error message
                alert('Error saving changes: ' + error.message);
            });
        
        // Prevent the default form submission
        return false;
    }
    
    // Function to override the form submission
    function overrideFormSubmission() {
        console.log('[FORM FIX] Overriding form submission');
        
        // Find all forms
        const forms = document.querySelectorAll('form');
        console.log('[FORM FIX] Found ' + forms.length + ' forms');
        
        forms.forEach((form, index) => {
            console.log('[FORM FIX] Processing form ' + index);
            
            // Add submit event listener
            form.addEventListener('submit', function(event) {
                console.log('[FORM FIX] Form submit event triggered');
                
                // Prevent the default form submission
                event.preventDefault();
                
                // Handle the form submission
                if (handleFormSubmission(form) === true) {
                    // If handleFormSubmission returns true, submit the form normally
                    form.submit();
                }
            });
            
            console.log('[FORM FIX] Added submit event listener to form ' + index);
        });
    }
    
    // Run when the DOM is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', overrideFormSubmission);
    } else {
        // DOM is already loaded
        overrideFormSubmission();
    }
})();