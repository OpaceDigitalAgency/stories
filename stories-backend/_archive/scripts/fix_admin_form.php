<?php
/**
 * Fix Admin Form
 * 
 * This script creates a direct fix for the admin form submission.
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin Form</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .code { font-family: monospace; background: #f5f5f5; padding: 10px; }
        .button { display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Admin Form</h1>
        
        <p>This page provides a direct fix for the admin form submission issue.</p>
        
        <h2>The Problem</h2>
        <p>The admin interface shows "Processing your request..." and never completes the save operation, even though the API is working correctly.</p>
        
        <h2>The Solution</h2>
        <p>We need to directly modify the admin interface\'s JavaScript to fix the form submission.</p>
        
        <h3>Option 1: Bookmarklet (Easiest)</h3>
        <p>Drag this link to your bookmarks bar:</p>
        <p><a href="javascript:(function(){
            console.log(\'Admin form fix loaded\');
            
            // Create a direct save button
            const saveButton = document.createElement(\'button\');
            saveButton.textContent = \'Save Changes (Direct)\';
            saveButton.style.position = \'fixed\';
            saveButton.style.bottom = \'20px\';
            saveButton.style.right = \'20px\';
            saveButton.style.zIndex = \'1000\';
            saveButton.style.padding = \'10px 20px\';
            saveButton.style.backgroundColor = \'#4CAF50\';
            saveButton.style.color = \'white\';
            saveButton.style.border = \'none\';
            saveButton.style.borderRadius = \'4px\';
            saveButton.style.cursor = \'pointer\';
            
            saveButton.addEventListener(\'click\', function() {
                console.log(\'Save button clicked\');
                
                // Get the current URL
                const url = window.location.href;
                console.log(\'Current URL:\', url);
                
                // Extract the ID from the URL
                let id = null;
                const idMatch = url.match(/id=([0-9]+)/);
                if (idMatch) {
                    id = idMatch[1];
                    console.log(\'Extracted ID:\', id);
                } else {
                    console.error(\'Could not extract ID from URL\');
                    alert(\'Error: Could not extract ID from URL\');
                    return;
                }
                
                // Get the form data
                const form = document.querySelector(\'form\');
                if (!form) {
                    console.error(\'Form not found\');
                    alert(\'Error: Form not found\');
                    return;
                }
                
                // Get form fields
                const titleInput = form.querySelector(\'input[name=\"title\"]\');
                const excerptTextarea = form.querySelector(\'textarea[name=\"excerpt\"]\');
                const contentTextarea = form.querySelector(\'textarea[name=\"content\"]\');
                
                if (!titleInput || !excerptTextarea || !contentTextarea) {
                    console.error(\'Form fields not found\');
                    alert(\'Error: Form fields not found\');
                    return;
                }
                
                // Create the data object
                const data = {
                    title: titleInput.value,
                    excerpt: excerptTextarea.value,
                    content: contentTextarea.value
                };
                
                console.log(\'Form data:\', data);
                
                // Show loading message
                const loadingMessage = document.createElement(\'div\');
                loadingMessage.textContent = \'Saving changes...\';
                loadingMessage.style.position = \'fixed\';
                loadingMessage.style.top = \'50%\';
                loadingMessage.style.left = \'50%\';
                loadingMessage.style.transform = \'translate(-50%, -50%)\';
                loadingMessage.style.padding = \'20px\';
                loadingMessage.style.backgroundColor = \'#f9f9f9\';
                loadingMessage.style.border = \'1px solid #ddd\';
                loadingMessage.style.borderRadius = \'4px\';
                loadingMessage.style.zIndex = \'1001\';
                document.body.appendChild(loadingMessage);
                
                // Make a direct API call
                fetch(\'/api/v1/stories/\' + id, {
                    method: \'PUT\',
                    headers: {
                        \'Content-Type\': \'application/json\'
                    },
                    body: JSON.stringify(data)
                })
                    .then(response => {
                        console.log(\'API response:\', response);
                        return response.text();
                    })
                    .then(text => {
                        console.log(\'Response text:\', text);
                        
                        try {
                            const data = JSON.parse(text);
                            console.log(\'Parsed JSON:\', data);
                            
                            // Remove loading message
                            document.body.removeChild(loadingMessage);
                            
                            // Show success message
                            alert(\'Changes saved successfully!\');
                            
                            // Reload the page
                            window.location.reload();
                        } catch (e) {
                            console.error(\'Error parsing JSON:\', e);
                            
                            // Remove loading message
                            document.body.removeChild(loadingMessage);
                            
                            // Show error message
                            alert(\'Error saving changes: \' + e.message + \'\n\nResponse: \' + text);
                        }
                    })
                    .catch(error => {
                        console.error(\'API error:\', error);
                        
                        // Remove loading message
                        document.body.removeChild(loadingMessage);
                        
                        // Show error message
                        alert(\'Error saving changes: \' + error.message);
                    });
            });
            
            document.body.appendChild(saveButton);
            console.log(\'Save button added\');
            
            // Add a message to indicate the fix is active
            const fixMessage = document.createElement(\'div\');
            fixMessage.textContent = \'Form fix is active\';
            fixMessage.style.position = \'fixed\';
            fixMessage.style.top = \'10px\';
            fixMessage.style.right = \'10px\';
            fixMessage.style.zIndex = \'1000\';
            fixMessage.style.padding = \'5px 10px\';
            fixMessage.style.backgroundColor = \'#4CAF50\';
            fixMessage.style.color = \'white\';
            fixMessage.style.borderRadius = \'4px\';
            document.body.appendChild(fixMessage);
            
            console.log(\'Fix message added\');
        })();" class="button">Fix Admin Form</a></p>
        <p>Then click the bookmark when you\'re on the admin edit page.</p>
        
        <h3>Option 2: Copy and Paste into Console</h3>
        <p>Copy this code and paste it into your browser\'s console when on the admin edit page:</p>
        <pre>
// Admin form fix
console.log('Admin form fix loaded');

// Create a direct save button
const saveButton = document.createElement('button');
saveButton.textContent = 'Save Changes (Direct)';
saveButton.style.position = 'fixed';
saveButton.style.bottom = '20px';
saveButton.style.right = '20px';
saveButton.style.zIndex = '1000';
saveButton.style.padding = '10px 20px';
saveButton.style.backgroundColor = '#4CAF50';
saveButton.style.color = 'white';
saveButton.style.border = 'none';
saveButton.style.borderRadius = '4px';
saveButton.style.cursor = 'pointer';

saveButton.addEventListener('click', function() {
    console.log('Save button clicked');
    
    // Get the current URL
    const url = window.location.href;
    console.log('Current URL:', url);
    
    // Extract the ID from the URL
    let id = null;
    const idMatch = url.match(/id=([0-9]+)/);
    if (idMatch) {
        id = idMatch[1];
        console.log('Extracted ID:', id);
    } else {
        console.error('Could not extract ID from URL');
        alert('Error: Could not extract ID from URL');
        return;
    }
    
    // Get the form data
    const form = document.querySelector('form');
    if (!form) {
        console.error('Form not found');
        alert('Error: Form not found');
        return;
    }
    
    // Get form fields
    const titleInput = form.querySelector('input[name="title"]');
    const excerptTextarea = form.querySelector('textarea[name="excerpt"]');
    const contentTextarea = form.querySelector('textarea[name="content"]');
    
    if (!titleInput || !excerptTextarea || !contentTextarea) {
        console.error('Form fields not found');
        alert('Error: Form fields not found');
        return;
    }
    
    // Create the data object
    const data = {
        title: titleInput.value,
        excerpt: excerptTextarea.value,
        content: contentTextarea.value
    };
    
    console.log('Form data:', data);
    
    // Show loading message
    const loadingMessage = document.createElement('div');
    loadingMessage.textContent = 'Saving changes...';
    loadingMessage.style.position = 'fixed';
    loadingMessage.style.top = '50%';
    loadingMessage.style.left = '50%';
    loadingMessage.style.transform = 'translate(-50%, -50%)';
    loadingMessage.style.padding = '20px';
    loadingMessage.style.backgroundColor = '#f9f9f9';
    loadingMessage.style.border = '1px solid #ddd';
    loadingMessage.style.borderRadius = '4px';
    loadingMessage.style.zIndex = '1001';
    document.body.appendChild(loadingMessage);
    
    // Make a direct API call
    fetch('/api/v1/stories/' + id, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => {
            console.log('API response:', response);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON:', data);
                
                // Remove loading message
                document.body.removeChild(loadingMessage);
                
                // Show success message
                alert('Changes saved successfully!');
                
                // Reload the page
                window.location.reload();
            } catch (e) {
                console.error('Error parsing JSON:', e);
                
                // Remove loading message
                document.body.removeChild(loadingMessage);
                
                // Show error message
                alert('Error saving changes: ' + e.message + '\n\nResponse: ' + text);
            }
        })
        .catch(error => {
            console.error('API error:', error);
            
            // Remove loading message
            document.body.removeChild(loadingMessage);
            
            // Show error message
            alert('Error saving changes: ' + error.message);
        });
});

document.body.appendChild(saveButton);
console.log('Save button added');

// Add a message to indicate the fix is active
const fixMessage = document.createElement('div');
fixMessage.textContent = 'Form fix is active';
fixMessage.style.position = 'fixed';
fixMessage.style.top = '10px';
fixMessage.style.right = '10px';
fixMessage.style.zIndex = '1000';
fixMessage.style.padding = '5px 10px';
fixMessage.style.backgroundColor = '#4CAF50';
fixMessage.style.color = 'white';
fixMessage.style.borderRadius = '4px';
document.body.appendChild(fixMessage);

console.log('Fix message added');
        </pre>
        
        <h2>How to Use</h2>
        <ol>
            <li>Go to the admin edit page (e.g., <code>https://api.storiesfromtheweb.org/admin/stories.php?action=edit&id=1</code>)</li>
            <li>Apply the fix using either Option 1 or Option 2 above</li>
            <li>You\'ll see a green "Form fix is active" message in the top-right corner</li>
            <li>Make your changes to the story</li>
            <li>Click the green "Save Changes (Direct)" button in the bottom-right corner</li>
            <li>Your changes will be saved directly to the database</li>
        </ol>
        
        <h2>Why This Works</h2>
        <p>This fix bypasses the problematic form submission handler in the admin interface and makes a direct API call to update the story. The API is working correctly (as evidenced by the JSON response), but the admin interface\'s form submission handler is not handling the response correctly.</p>
        
        <h2>Long-Term Solution</h2>
        <p>For a more permanent solution, we would need to fix the admin interface\'s form submission handler. This would involve modifying the admin interface\'s JavaScript code to properly handle the API response.</p>
    </div>
</body>
</html>';