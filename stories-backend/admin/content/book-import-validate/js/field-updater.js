/**
 * Field Updater
 * 
 * JavaScript for handling field updates in the book validation interface.
 */

// Initialize field updater
function initFieldUpdater() {
    // Set up event listeners for field editing
    setupFieldEditListeners();
    
    console.log('Field updater initialized');
}

// Set up event listeners for field editing
function setupFieldEditListeners() {
    // Double-click on current value to edit
    document.querySelectorAll('.current-value').forEach(cell => {
        cell.addEventListener('dblclick', handleFieldEdit);
    });
    
    // Use source value buttons
    document.querySelectorAll('.use-source-value').forEach(button => {
        button.addEventListener('click', handleUseSourceValue);
    });
    
    // Cancel edit buttons
    document.querySelectorAll('.cancel-edit').forEach(button => {
        button.addEventListener('click', handleCancelEdit);
    });
    
    // Save field forms
    document.querySelectorAll('.field-edit-form').forEach(form => {
        form.addEventListener('submit', handleSaveField);
    });
}

// Handle field edit
function handleFieldEdit(event) {
    const cell = event.currentTarget;
    const row = cell.closest('.field-row');
    const field = row.dataset.field;
    
    // Get current value
    const currentValue = cell.textContent.trim();
    
    // Get source values
    const sourceValues = {};
    row.querySelectorAll('.source-value').forEach(sourceCell => {
        const source = sourceCell.dataset.source;
        const valueContainer = sourceCell.querySelector('.value-container');
        
        if (valueContainer) {
            let value = '';
            
            // Extract value based on field type
            if (field === 'cover_url') {
                const img = valueContainer.querySelector('img');
                value = img ? img.src : '';
            } else if (field === 'preview_link') {
                const link = valueContainer.querySelector('a');
                value = link ? link.href : '';
            } else {
                value = valueContainer.textContent.trim();
                
                // Remove "Not available" text if present
                if (value === 'Not available') {
                    value = '';
                }
            }
            
            if (value) {
                sourceValues[source] = value;
            }
        }
    });
    
    // Show field editor
    showFieldEditor(field, currentValue, sourceValues);
}

// Show field editor
function showFieldEditor(field, currentValue, sourceValues) {
    // Create field editor container if it doesn't exist
    let editorContainer = document.getElementById(`field-editor-${field}`);
    
    if (!editorContainer) {
        editorContainer = document.createElement('div');
        editorContainer.id = `field-editor-${field}`;
        editorContainer.className = 'field-editor';
        document.querySelector('.validation-interface').appendChild(editorContainer);
    }
    
    // Create field editor content
    const editorContent = createFieldEditorContent(field, currentValue, sourceValues);
    
    // Set editor content
    editorContainer.innerHTML = editorContent;
    
    // Scroll to editor
    editorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Set up event listeners
    setupFieldEditListeners();
}

// Create field editor content
function createFieldEditorContent(field, currentValue, sourceValues) {
    // Define field types and validation rules
    const fieldTypes = {
        'title': { type: 'text', required: true, maxlength: 255 },
        'author': { type: 'text', required: true, maxlength: 255 },
        'isbn': { type: 'text', pattern: '[0-9X]{10}', maxlength: 10 },
        'isbn13': { type: 'text', pattern: '[0-9]{13}', maxlength: 13 },
        'publisher': { type: 'text', maxlength: 255 },
        'publication_date': { type: 'date' },
        'page_count': { type: 'number', min: 1, max: 10000 },
        'language': { type: 'text', maxlength: 50 },
        'format': { type: 'text', maxlength: 50 },
        'series': { type: 'text', maxlength: 255 },
        'awards': { type: 'textarea', rows: 3 },
        'characters': { type: 'textarea', rows: 3 },
        'settings': { type: 'textarea', rows: 3 },
        'preview_link': { type: 'url', maxlength: 255 },
        'cover_url': { type: 'url', maxlength: 255 },
        'rating': { type: 'number', min: 0, max: 5, step: 0.01 },
        'rating_count': { type: 'number', min: 0 },
        'review_count': { type: 'number', min: 0 },
        'maturity_rating': { type: 'text', maxlength: 50 }
    };
    
    // Get field configuration
    const fieldConfig = fieldTypes[field] || { type: 'text' };
    
    // Format current value for input
    let formattedValue = currentValue;
    
    if (field === 'publication_date' && currentValue) {
        // Format date for input
        const date = new Date(currentValue);
        if (!isNaN(date.getTime())) {
            formattedValue = date.toISOString().split('T')[0];
        }
    }
    
    // Create HTML for field editor
    let html = `
        <div class="card">
            <div class="card-header">
                <h5>Edit ${field.charAt(0).toUpperCase() + field.slice(1)}</h5>
            </div>
            <div class="card-body">
                <form class="field-edit-form" data-field="${field}">
                    <div class="mb-3">
                        <label for="current-value-${field}" class="form-label">Current Value</label>
    `;
    
    // Add input or textarea based on field type
    if (fieldConfig.type === 'textarea') {
        html += `
            <textarea id="current-value-${field}" name="current_value" class="form-control"
                     rows="${fieldConfig.rows || 3}"
                     ${fieldConfig.required ? 'required' : ''}
                     ${fieldConfig.maxlength ? `maxlength="${fieldConfig.maxlength}"` : ''}
            >${formattedValue}</textarea>
        `;
    } else {
        html += `
            <input type="${fieldConfig.type}" 
                   id="current-value-${field}" 
                   name="current_value" 
                   class="form-control"
                   value="${formattedValue}"
                   ${fieldConfig.required ? 'required' : ''}
                   ${fieldConfig.pattern ? `pattern="${fieldConfig.pattern}"` : ''}
                   ${fieldConfig.min !== undefined ? `min="${fieldConfig.min}"` : ''}
                   ${fieldConfig.max !== undefined ? `max="${fieldConfig.max}"` : ''}
                   ${fieldConfig.step ? `step="${fieldConfig.step}"` : ''}
                   ${fieldConfig.maxlength ? `maxlength="${fieldConfig.maxlength}"` : ''}
            >
        `;
    }
    
    html += `
                    </div>
    `;
    
    // Add source values if available
    if (Object.keys(sourceValues).length > 0) {
        html += `
            <div class="mb-3">
                <label class="form-label">Available Values from Sources</label>
                <div class="source-values-list">
        `;
        
        for (const [source, value] of Object.entries(sourceValues)) {
            html += `
                <div class="source-value-item mb-2">
                    <div class="d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary me-2 use-source-value"
                                data-value="${value}"
                                data-source="${source}">
                            <i class="fas fa-check"></i> Use
                        </button>
                        <span class="source-label">${source.charAt(0).toUpperCase() + source.slice(1)}:</span>
                        <span class="source-value ms-2">${value}</span>
                    </div>
                </div>
            `;
        }
        
        html += `
                </div>
            </div>
        `;
    }
    
    html += `
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary cancel-edit">Cancel</button>
                        <button type="submit" class="btn btn-primary save-field">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    return html;
}

// Handle use source value
function handleUseSourceValue(event) {
    event.preventDefault();
    
    const button = event.currentTarget;
    const value = button.dataset.value;
    const form = button.closest('form');
    const field = form.dataset.field;
    
    // Set the value in the input
    const input = form.querySelector(`#current-value-${field}`);
    if (input) {
        input.value = value;
    }
}

// Handle cancel edit
function handleCancelEdit(event) {
    event.preventDefault();
    
    const button = event.currentTarget;
    const editorContainer = button.closest('.field-editor');
    
    // Remove the editor
    if (editorContainer) {
        editorContainer.remove();
    }
}

// Handle save field
function handleSaveField(event) {
    event.preventDefault();
    
    const form = event.currentTarget;
    const field = form.dataset.field;
    const value = form.querySelector(`#current-value-${field}`).value;
    
    // Update the field
    updateField(field, value, 'manual');
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', initFieldUpdater);
