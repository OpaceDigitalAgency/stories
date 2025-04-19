// This is a direct fix that can be pasted into the browser console
// Copy all of this code and paste it into the browser console when on the admin edit page

// Override fetch to handle HTML responses
const originalFetch = window.fetch;
window.fetch = function(url, options) {
    console.log("Fetch intercepted:", url, options);
    
    return originalFetch(url, options)
        .then(response => {
            console.log("Response received:", response);
            
            // Clone the response so we can examine it
            const clone = response.clone();
            
            return clone.text().then(text => {
                console.log("Response text:", text);
                
                // Check if the response is HTML (error page)
                const isHtml = text.includes("<!DOCTYPE html>") || text.includes("<html>") || text.includes("<body>");
                
                if (isHtml) {
                    console.log("HTML response detected, converting to JSON error");
                    
                    // Extract error message from HTML
                    let errorMessage = "Unknown error";
                    const fatalErrorMatch = text.match(/<b>Fatal error<\/b>:\s*(.+?)<br/);
                    const parseErrorMatch = text.match(/<b>Parse error<\/b>:\s*(.+?)<br/);
                    
                    if (fatalErrorMatch) {
                        errorMessage = fatalErrorMatch[1];
                    } else if (parseErrorMatch) {
                        errorMessage = parseErrorMatch[1];
                    }
                    
                    console.log("Extracted error message:", errorMessage);
                    
                    // Create a JSON error response
                    const jsonResponse = {
                        error: true,
                        message: errorMessage,
                        html: text
                    };
                    
                    // Create a new response with the JSON error
                    const jsonBlob = new Blob([JSON.stringify(jsonResponse)], { type: 'application/json' });
                    const init = { status: response.status, statusText: response.statusText, headers: new Headers([['Content-Type', 'application/json']]) };
                    return new Response(jsonBlob, init);
                }
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    console.log("Valid JSON response:", data);
                    
                    // Return the original response
                    return response;
                } catch (e) {
                    console.log("Invalid JSON response, converting to JSON error");
                    
                    // Create a JSON error response
                    const jsonResponse = {
                        error: true,
                        message: "Invalid JSON response",
                        text: text
                    };
                    
                    // Create a new response with the JSON error
                    const jsonBlob = new Blob([JSON.stringify(jsonResponse)], { type: 'application/json' });
                    const init = { status: response.status, statusText: response.statusText, headers: new Headers([['Content-Type', 'application/json']]) };
                    return new Response(jsonBlob, init);
                }
            });
        });
};

// Add a direct save button
const saveButton = document.createElement("button");
saveButton.textContent = "Save Changes (Direct API)";
saveButton.style.position = "fixed";
saveButton.style.bottom = "20px";
saveButton.style.right = "20px";
saveButton.style.zIndex = "1000";
saveButton.style.padding = "10px 20px";
saveButton.style.backgroundColor = "#4CAF50";
saveButton.style.color = "white";
saveButton.style.border = "none";
saveButton.style.borderRadius = "4px";
saveButton.style.cursor = "pointer";
saveButton.addEventListener("click", function() {
    console.log("Save button clicked");
    
    // Get the current URL
    const url = window.location.href;
    console.log("Current URL:", url);
    
    // Extract the ID from the URL
    let id = null;
    const idMatch = url.match(/id=([0-9]+)/);
    if (idMatch) {
        id = idMatch[1];
        console.log("Extracted ID:", id);
    } else {
        console.error("Could not extract ID from URL");
        alert("Error: Could not extract ID from URL");
        return;
    }
    
    // Get the form data
    const form = document.querySelector("form");
    if (!form) {
        console.error("Form not found");
        alert("Error: Form not found");
        return;
    }
    
    // Get form fields
    const titleInput = form.querySelector('input[name="title"]');
    const excerptTextarea = form.querySelector('textarea[name="excerpt"]');
    const contentTextarea = form.querySelector('textarea[name="content"]');
    
    if (!titleInput || !excerptTextarea || !contentTextarea) {
        console.error("Form fields not found");
        alert("Error: Form fields not found");
        return;
    }
    
    // Create the data object
    const data = {
        title: titleInput.value,
        excerpt: excerptTextarea.value,
        content: contentTextarea.value
    };
    
    console.log("Form data:", data);
    
    // Make a direct API call
    fetch("/api/v1/stories/" + id, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    })
        .then(response => {
            console.log("API response:", response);
            return response.json();
        })
        .then(data => {
            console.log("API data:", data);
            
            if (data.error) {
                console.error("API error:", data.message);
                alert("Error saving changes: " + data.message);
                
                if (data.html) {
                    console.error("HTML error:", data.html);
                }
            } else {
                // Show success message
                alert("Changes saved successfully!");
                
                // Reload the page
                window.location.reload();
            }
        })
        .catch(error => {
            console.error("API error:", error);
            
            // Show error message
            alert("Error saving changes: " + error.message);
        });
});

document.body.appendChild(saveButton);
console.log("Save button added");

// Hide error messages
const errorElements = document.querySelectorAll(".alert-danger");
errorElements.forEach(element => {
    element.style.display = "none";
});

console.log("Error messages hidden");

// Add a message to indicate the fix is active
const fixMessage = document.createElement("div");
fixMessage.textContent = "Form submission fix is active";
fixMessage.style.position = "fixed";
fixMessage.style.top = "10px";
fixMessage.style.right = "10px";
fixMessage.style.zIndex = "1000";
fixMessage.style.padding = "5px 10px";
fixMessage.style.backgroundColor = "#4CAF50";
fixMessage.style.color = "white";
fixMessage.style.borderRadius = "4px";
document.body.appendChild(fixMessage);

console.log("Fix message added");

// Log success message
console.log("Form submission fix applied successfully");