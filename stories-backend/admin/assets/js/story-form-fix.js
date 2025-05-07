/**
 * Story Form Fix
 *
 * This script fixes issues with the story form:
 * 1. Fixes the WYSIWYG editor initialization
 * 2. Fixes the preview button functionality
 * 3. Converts numeric boolean fields to checkboxes
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Story Form Fix loaded');

    // Add a small delay to ensure the DOM is fully loaded
    setTimeout(function() {
        // Fix 1: Convert numeric boolean fields to checkboxes
        fixBooleanFields();

        // Fix 2: Initialize WYSIWYG editor properly
        initializeEditor();

        // Fix 3: Fix preview button
        setupPreviewButton();

        // Fix 4: Setup form submission handler to ensure content is saved
        setupFormSubmissionHandler();

        console.log('All fixes applied');
    }, 100);
});

/**
 * Convert numeric boolean fields to proper checkboxes
 */
function fixBooleanFields() {
    // List of known boolean fields
    const booleanFields = [
        'is_published', 'is_featured', 'is_sponsored', 'allow_reviews',
        'is_self_published', 'is_ai_enhanced', 'needs_moderation'
    ];

    // Find all number inputs with values 0 or 1
    const numericInputs = document.querySelectorAll('input[type="number"]');

    console.log('Found numeric inputs:', numericInputs.length);

    numericInputs.forEach(input => {
        const fieldName = input.getAttribute('name');
        const fieldId = input.getAttribute('id');

        console.log('Checking field:', fieldName, 'with value:', input.value);

        // Check if this is a boolean field (either by name or by having values limited to 0/1)
        const isBooleanByName = booleanFields.includes(fieldName);
        const isBooleanByValue = (input.min === '0' && input.max === '1') ||
                                (input.value === '0' || input.value === '1');

        // Additional check for fields that start with "is_" or have "published" in the name
        const isBooleanByPattern = fieldName && (
            fieldName.startsWith('is_') ||
            fieldName.includes('published') ||
            fieldName.includes('featured') ||
            fieldName.includes('sponsored') ||
            fieldName.includes('moderation')
        );

        if (isBooleanByName || isBooleanByValue || isBooleanByPattern) {
            console.log('Converting boolean field:', fieldName);

            // Create a checkbox to replace the numeric input
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = fieldName;
            checkbox.id = fieldId;
            checkbox.className = 'form-check-input';
            checkbox.value = '1';
            checkbox.checked = input.value === '1';

            // Create a hidden input to ensure the field is submitted even when unchecked
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = fieldName + '_submitted';
            hiddenInput.value = '1';

            // Create a label for the checkbox
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = fieldId;

            // Get the original label text
            const originalLabel = document.querySelector(`label[for="${fieldId}"]`);
            if (originalLabel) {
                label.textContent = originalLabel.textContent;

                // Don't remove the original label if it's in a different layout
                // Just update the text to indicate it's now a checkbox
                if (originalLabel.parentElement !== input.parentElement) {
                    originalLabel.innerHTML = '<span style="color: #666;">(Checkbox)</span> ' + originalLabel.textContent;
                } else {
                    originalLabel.remove();
                }
            } else {
                // Create a label from the field name if no label exists
                label.textContent = fieldName.replace(/_/g, ' ')
                    .replace(/\b\w/g, l => l.toUpperCase());
            }

            // Create a wrapper div with Bootstrap form-check class
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check form-switch';

            // Add the elements to the wrapper
            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);
            wrapper.appendChild(hiddenInput);

            // Replace the numeric input with the checkbox
            const parentElement = input.parentElement;

            // If the parent is a form-group, we need to modify the layout
            if (parentElement.classList.contains('form-group') ||
                parentElement.parentElement.classList.contains('form-group')) {

                // Get the form-group element
                const formGroup = parentElement.classList.contains('form-group') ?
                    parentElement : parentElement.parentElement;

                // Create a new layout with the label and checkbox side by side
                const newLayout = document.createElement('div');
                newLayout.className = 'form-check form-switch d-flex align-items-center';
                newLayout.style.marginTop = '0.5rem';

                // Add the checkbox and label to the new layout
                newLayout.appendChild(checkbox);
                newLayout.appendChild(label);
                newLayout.appendChild(hiddenInput);

                // Replace the input with the new layout
                parentElement.replaceChild(newLayout, input);

                // Add some styling to make it look better
                formGroup.style.marginBottom = '1rem';
            } else {
                // Standard replacement
                parentElement.replaceChild(wrapper, input);
            }
        }
    });

    // Also look for any fields that might be boolean but are not number inputs
    // This is for fields that are already rendered as text inputs but should be checkboxes
    const textInputs = document.querySelectorAll('input[type="text"]');

    textInputs.forEach(input => {
        const fieldName = input.getAttribute('name');

        // Skip if no name attribute
        if (!fieldName) return;

        // Check if this is a boolean field by name pattern
        const isBooleanByPattern =
            fieldName.startsWith('is_') ||
            fieldName.includes('published') ||
            fieldName.includes('featured') ||
            fieldName.includes('sponsored') ||
            fieldName.includes('moderation');

        // Also check if the value is 0 or 1
        const isBooleanByValue = input.value === '0' || input.value === '1';

        if ((isBooleanByPattern && isBooleanByValue) || booleanFields.includes(fieldName)) {
            console.log('Converting text boolean field:', fieldName);

            // Similar conversion logic as above
            // [Code similar to the above block]
            // Create a checkbox to replace the text input
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = fieldName;
            checkbox.id = input.id;
            checkbox.className = 'form-check-input';
            checkbox.value = '1';
            checkbox.checked = input.value === '1';

            // Create a hidden input to ensure the field is submitted even when unchecked
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = fieldName + '_submitted';
            hiddenInput.value = '1';

            // Create a label for the checkbox
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = input.id;

            // Get the original label text
            const originalLabel = document.querySelector(`label[for="${input.id}"]`);
            if (originalLabel) {
                label.textContent = originalLabel.textContent;

                // Don't remove the original label if it's in a different layout
                if (originalLabel.parentElement !== input.parentElement) {
                    originalLabel.innerHTML = '<span style="color: #666;">(Checkbox)</span> ' + originalLabel.textContent;
                } else {
                    originalLabel.remove();
                }
            } else {
                // Create a label from the field name if no label exists
                label.textContent = fieldName.replace(/_/g, ' ')
                    .replace(/\b\w/g, l => l.toUpperCase());
            }

            // Create a wrapper div with Bootstrap form-check class
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check form-switch';

            // Add the elements to the wrapper
            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);
            wrapper.appendChild(hiddenInput);

            // Replace the text input with the checkbox
            const parentElement = input.parentElement;

            // If the parent is a form-group, we need to modify the layout
            if (parentElement.classList.contains('form-group') ||
                parentElement.parentElement.classList.contains('form-group')) {

                // Get the form-group element
                const formGroup = parentElement.classList.contains('form-group') ?
                    parentElement : parentElement.parentElement;

                // Create a new layout with the label and checkbox side by side
                const newLayout = document.createElement('div');
                newLayout.className = 'form-check form-switch d-flex align-items-center';
                newLayout.style.marginTop = '0.5rem';

                // Add the checkbox and label to the new layout
                newLayout.appendChild(checkbox);
                newLayout.appendChild(label);
                newLayout.appendChild(hiddenInput);

                // Replace the input with the new layout
                parentElement.replaceChild(newLayout, input);

                // Add some styling to make it look better
                formGroup.style.marginBottom = '1rem';
            } else {
                // Standard replacement
                parentElement.replaceChild(wrapper, input);
            }
        }
    });
}

/**
 * Initialize the WYSIWYG editor properly
 */
function initializeEditor() {
    // Check if CKEditor is loaded
    if (typeof ClassicEditor !== 'undefined') {
        console.log('CKEditor is loaded, initializing...');

        const editorElement = document.getElementById('story_content');
        if (editorElement) {
            // Make sure the summary isn't in the story content
            const summaryField = document.getElementById('summary');
            if (summaryField && summaryField.value && editorElement.value.includes(summaryField.value)) {
                console.log('Removing summary from story content before initializing editor');
                editorElement.value = editorElement.value.replace(summaryField.value, '').trim();

                // Also remove with paragraph tags
                const summaryWithPTags = '<p>' + summaryField.value + '</p>';
                editorElement.value = editorElement.value.replace(summaryWithPTags, '').trim();

                // Clean up empty paragraphs
                editorElement.value = editorElement.value.replace(/<p>\s*<\/p>/g, '').trim();
            }

            // Initialize CKEditor
            ClassicEditor
                .create(editorElement, {
                    toolbar: [
                        'heading', '|',
                        'bold', 'italic', 'link', '|',
                        'bulletedList', 'numberedList', '|',
                        'insertTable', '|',
                        'imageUpload', 'mediaEmbed', '|',
                        'sourceEditing', '|',
                        'undo', 'redo'
                    ],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                        ]
                    },
                    image: {
                        toolbar: [
                            'imageStyle:inline',
                            'imageStyle:block',
                            'imageStyle:side',
                            '|',
                            'toggleImageCaption',
                            'imageTextAlternative'
                        ]
                    },
                    // Register the custom upload adapter plugin - this is defined in ckeditor-upload-adapter.js
                    extraPlugins: [window.MediaLibraryUploadAdapterPlugin || function(editor) {
                        console.log('Using fallback upload adapter plugin');
                        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                            return {
                                upload: function() {
                                    return Promise.reject('Upload adapter not properly initialized');
                                },
                                abort: function() {}
                            };
                        };
                    }]
                })
                .then(editor => {
                    console.log('CKEditor initialized successfully');
                    window.storyEditor = editor;

                    // Set up HTML toggle button
                    setupHtmlToggle(editor);
                })
                .catch(error => {
                    console.error('Error initializing CKEditor:', error);
                    setupFallbackEditor();
                });
        }
    } else {
        console.error('CKEditor is not loaded, setting up fallback editor');
        setupFallbackEditor();
    }
}

/**
 * Set up a fallback plain textarea editor
 */
function setupFallbackEditor() {
    const storyContentTextarea = document.getElementById('story_content');
    const htmlContentTextarea = document.getElementById('html_content');

    if (storyContentTextarea) {
        // Make the textarea visible and styled
        storyContentTextarea.style.display = 'block';
        storyContentTextarea.style.minHeight = '300px';
        storyContentTextarea.style.width = '100%';
        storyContentTextarea.style.padding = '10px';
        storyContentTextarea.style.fontFamily = 'inherit';
        storyContentTextarea.style.fontSize = 'inherit';
        storyContentTextarea.style.lineHeight = '1.5';

        // Set up HTML toggle button
        setupHtmlToggleForFallback();
    }
}

/**
 * Set up HTML toggle button for CKEditor
 */
function setupHtmlToggle(editor) {
    const toggleHtmlButton = document.getElementById('toggle-html-view');
    const htmlContentTextarea = document.getElementById('html_content');
    let isHtmlMode = false;

    if (toggleHtmlButton && htmlContentTextarea) {
        toggleHtmlButton.addEventListener('click', () => {
            if (!isHtmlMode) {
                // Switch to HTML mode
                htmlContentTextarea.value = editor.getData();
                htmlContentTextarea.style.display = 'block';

                // Get the CKEditor root element and hide it
                const editorRoot = editor.ui.getEditableElement().parentElement;
                if (editorRoot) {
                    editorRoot.style.display = 'none';
                }
            } else {
                // Switch back to WYSIWYG mode
                editor.setData(htmlContentTextarea.value);
                htmlContentTextarea.style.display = 'none';

                // Show the CKEditor root element again
                const editorRoot = editor.ui.getEditableElement().parentElement;
                if (editorRoot) {
                    editorRoot.style.display = '';
                }
            }
            isHtmlMode = !isHtmlMode;
        });
    }
}

/**
 * Set up HTML toggle button for fallback editor
 */
function setupHtmlToggleForFallback() {
    const toggleHtmlButton = document.getElementById('toggle-html-view');
    const htmlContentTextarea = document.getElementById('html_content');
    const storyContentTextarea = document.getElementById('story_content');
    let isHtmlMode = false;

    if (toggleHtmlButton && htmlContentTextarea && storyContentTextarea) {
        toggleHtmlButton.addEventListener('click', () => {
            if (!isHtmlMode) {
                // Switch to HTML mode
                htmlContentTextarea.value = storyContentTextarea.value;
                htmlContentTextarea.style.display = 'block';
                storyContentTextarea.style.display = 'none';
            } else {
                // Switch back to normal mode
                storyContentTextarea.value = htmlContentTextarea.value;
                htmlContentTextarea.style.display = 'none';
                storyContentTextarea.style.display = 'block';
            }
            isHtmlMode = !isHtmlMode;
        });
    }
}

/**
 * Set up the preview button
 */
function setupPreviewButton() {
    const previewButton = document.getElementById('preview-story');
    if (previewButton) {
        // Remove any existing event listeners
        const newPreviewButton = previewButton.cloneNode(true);
        previewButton.parentNode.replaceChild(newPreviewButton, previewButton);

        // Add new event listener
        newPreviewButton.addEventListener('click', function(e) {
            e.preventDefault();
            showPreview();
        });
    }
}

/**
 * Show the story preview
 */
function showPreview() {
    // Get the content for preview
    let storyContent = '';
    const htmlContentTextarea = document.getElementById('html_content');
    const storyContentTextarea = document.getElementById('story_content');
    const title = document.getElementById('title').value || 'Story Preview';
    const summary = document.getElementById('summary').value || '';
    const storyId = document.querySelector('input[name="id"]')?.value;

    // If we have a story ID, use the same preview method as the story list page
    if (storyId) {
        // Check if the StoryPreview class is available (from story-preview.js)
        if (window.storyPreview) {
            // Use the existing preview functionality
            window.storyPreview.loadStoryPreview(storyId);
            return;
        }
    }

    // If we don't have a story ID or the StoryPreview class isn't available,
    // we need to create a temporary preview

    // Get the content based on the current editor mode
    if (htmlContentTextarea && htmlContentTextarea.style.display !== 'none') {
        // We're in HTML mode, get content from the HTML textarea
        storyContent = htmlContentTextarea.value;
    } else if (window.storyEditor) {
        // We're in WYSIWYG mode, get content from CKEditor
        storyContent = window.storyEditor.getData();
    } else if (storyContentTextarea) {
        // We're using the fallback textarea
        storyContent = storyContentTextarea.value;
    }

    // Get author info if available
    const authorSelect = document.querySelector('#author_id');
    let authorName = '';
    let authorAge = '';
    let authorLocation = '';

    if (authorSelect && authorSelect.selectedIndex > 0) {
        const selectedOption = authorSelect.options[authorSelect.selectedIndex];
        authorName = selectedOption.text || '';

        // Try to get author age and location from the displayed info
        const authorAgeSpan = document.getElementById('author-age');
        const authorLocationSpan = document.getElementById('author-location');

        if (authorAgeSpan) {
            authorAge = authorAgeSpan.textContent;
        }

        if (authorLocationSpan) {
            authorLocation = authorLocationSpan.textContent;
        }

        // If we don't have age/location from spans, try to get from form fields
        if (!authorAge) {
            const ageField = document.getElementById('author_age');
            if (ageField) {
                authorAge = ageField.value;
            }
        }

        if (!authorLocation) {
            const locationField = document.getElementById('author_location');
            if (locationField) {
                authorLocation = locationField.value;
            }
        }
    }

    // If we still don't have author details, try to extract from content
    if ((!authorAge || !authorLocation) && storyContent) {
        const ageMatch = storyContent.match(/Age:\s*(\d+)/i);
        const locationMatch = storyContent.match(/From:\s*([^,\n<]+)/i);

        if (ageMatch && ageMatch[1] && !authorAge) {
            authorAge = ageMatch[1];
        }

        if (locationMatch && locationMatch[1] && !authorLocation) {
            authorLocation = locationMatch[1].trim();
        }
    }

    // Clean up the story content to remove markdown headings and any duplicate content
    storyContent = storyContent.replace(/^## .*$/gm, '').trim();

    // More aggressively remove any duplicate paragraphs that might be in both summary and content
    if (summary) {
        // Try exact match first
        if (storyContent.includes(summary)) {
            storyContent = storyContent.replace(summary, '').trim();
        }

        // Try with HTML tags (in case the editor added them)
        const summaryWithPTags = '<p>' + summary + '</p>';
        if (storyContent.includes(summaryWithPTags)) {
            storyContent = storyContent.replace(summaryWithPTags, '').trim();
        }

        // Try with line breaks
        const summaryWithBreaks = summary.replace(/\n/g, '<br>');
        if (storyContent.includes(summaryWithBreaks)) {
            storyContent = storyContent.replace(summaryWithBreaks, '').trim();
        }

        // Try with different paragraph formatting
        const summaryWithDifferentPTags = summary.replace(/\n/g, '</p><p>');
        if (storyContent.includes(summaryWithDifferentPTags)) {
            storyContent = storyContent.replace(summaryWithDifferentPTags, '').trim();
        }

        // Try with the summary at the beginning of the content with or without HTML
        const summaryRegex = new RegExp('^\\s*' + escapeRegExp(summary) + '\\s*', 'i');
        if (summaryRegex.test(storyContent)) {
            storyContent = storyContent.replace(summaryRegex, '').trim();
        }

        // Try with the summary at the beginning with HTML tags
        const summaryWithPTagsRegex = new RegExp('^\\s*<p[^>]*>\\s*' + escapeRegExp(summary) + '\\s*</p>\\s*', 'i');
        if (summaryWithPTagsRegex.test(storyContent)) {
            storyContent = storyContent.replace(summaryWithPTagsRegex, '').trim();
        }
    }

    // Clean up any empty paragraphs that might be left after removing content
    storyContent = storyContent.replace(/<p>\s*<\/p>/g, '').trim();

    // Create a temporary preview using a lightbox similar to the story list page
    createPreviewLightbox(title, authorName, authorAge, authorLocation, summary, storyContent);
}

/**
 * Create a preview lightbox for unsaved stories
 */
function createPreviewLightbox(title, authorName, authorAge, authorLocation, summary, storyContent) {
    // Check if we need to load the story-preview.css file
    if (!document.getElementById('story-preview-css')) {
        const link = document.createElement('link');
        link.id = 'story-preview-css';
        link.rel = 'stylesheet';
        link.href = '../assets/css/story-preview.css';
        document.head.appendChild(link);
    }

    // Create the lightbox container if it doesn't exist
    let lightbox = document.getElementById('story-preview-lightbox');
    if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'story-preview-lightbox';
        lightbox.className = 'lightbox-overlay';

        lightbox.innerHTML = `
            <div class="lightbox-container">
                <div class="lightbox-header">
                    <h3>Story Preview</h3>
                    <button class="lightbox-close">&times;</button>
                </div>
                <div class="lightbox-content">
                    <div class="preview-iframe-container">
                        <div class="preview-content">
                            <div class="story-header">
                                <h1 class="story-title">${escapeHtml(title)}</h1>
                            </div>

                            ${authorName ? `
                            <div class="story-author">
                                <div class="story-author-name">By ${escapeHtml(authorName)}</div>
                                ${(authorAge || authorLocation) ? `
                                <div class="story-author-details">
                                    ${authorAge ? `<span>Age: ${escapeHtml(authorAge)}</span>` : ''}
                                    ${authorAge && authorLocation ? '<span> • </span>' : ''}
                                    ${authorLocation ? `<span>From: ${escapeHtml(authorLocation)}</span>` : ''}
                                </div>
                                ` : ''}
                            </div>
                            ` : ''}

                            ${summary ? `
                            <div class="story-summary">
                                <p>${summary.replace(/\n/g, '<br>')}</p>
                            </div>
                            ` : ''}

                            <div class="story-content">
                                ${storyContent}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(lightbox);

        // Add event listeners for closing the lightbox
        const closeButton = lightbox.querySelector('.lightbox-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                lightbox.style.display = 'none';
                document.body.style.overflow = '';
            });
        }

        // Close on click outside the content
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                lightbox.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightbox.style.display === 'flex') {
                lightbox.style.display = 'none';
                document.body.style.overflow = '';
            }
        });
    } else {
        // Update the content of the existing lightbox
        const titleElement = lightbox.querySelector('.story-title');
        if (titleElement) {
            titleElement.textContent = title;
        }

        // Update author info
        const authorNameElement = lightbox.querySelector('.story-author-name');
        if (authorNameElement) {
            authorNameElement.innerHTML = authorName ? `By ${escapeHtml(authorName)}` : '';
        }

        // Update author details
        const authorDetailsElement = lightbox.querySelector('.story-author-details');
        if (authorDetailsElement) {
            authorDetailsElement.innerHTML = '';
            if (authorAge) {
                const ageSpan = document.createElement('span');
                ageSpan.textContent = `Age: ${authorAge}`;
                authorDetailsElement.appendChild(ageSpan);
            }

            if (authorAge && authorLocation) {
                const separator = document.createElement('span');
                separator.textContent = ' • ';
                authorDetailsElement.appendChild(separator);
            }

            if (authorLocation) {
                const locationSpan = document.createElement('span');
                locationSpan.textContent = `From: ${authorLocation}`;
                authorDetailsElement.appendChild(locationSpan);
            }
        }

        // Update summary
        const summaryElement = lightbox.querySelector('.story-summary');
        if (summaryElement) {
            summaryElement.innerHTML = summary ? `<p>${summary.replace(/\n/g, '<br>')}</p>` : '';
        }

        // Update content
        const contentElement = lightbox.querySelector('.story-content');
        if (contentElement) {
            contentElement.innerHTML = storyContent;
        }
    }

    // Show the lightbox
    lightbox.style.display = 'flex';

    // Prevent body scrolling
    document.body.style.overflow = 'hidden';
}

/**
 * Helper function to escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Helper function to escape special characters in a string for use in a regular expression
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
}

/**
 * Fix image URLs in content to ensure they're absolute and properly formatted
 * This is critical for images to display correctly when the story is reloaded
 */
function fixImageUrls(content) {
    // Skip if content is empty or doesn't contain any images
    if (!content || content.indexOf('<img') === -1) {
        return content;
    }

    console.log('Fixing image URLs in content');

    // Create a temporary div to parse the HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;

    // Find all images
    const images = tempDiv.querySelectorAll('img');

    // Process each image
    images.forEach(img => {
        const src = img.getAttribute('src');

        // Skip if no src attribute
        if (!src) {
            console.warn('Image without src attribute found');
            return;
        }

        // Make sure the URL is absolute
        if (src.indexOf('http') !== 0) {
            const host = window.location.host || 'api.storiesfromtheweb.org';
            const protocol = window.location.protocol || 'https:';
            const newSrc = `${protocol}//${host}${src.startsWith('/') ? '' : '/'}${src}`;

            console.log(`Converting relative URL: ${src} to absolute: ${newSrc}`);
            img.setAttribute('src', newSrc);
        }

        // Ensure alt attribute exists (even if empty)
        if (!img.hasAttribute('alt')) {
            img.setAttribute('alt', '');
        }
    });

    // Return the fixed HTML
    return tempDiv.innerHTML;
}

/**
 * Reference to the MediaLibraryUploadAdapterPlugin from ckeditor-upload-adapter.js
 * We're not redefining it here to avoid conflicts
 */

/**
 * Setup event listeners to keep summary and story content separate
 */
function setupSummaryAndStoryContentSeparation() {
    const summaryField = document.getElementById('summary');

    if (summaryField) {
        // When the summary changes, make sure it's not in the story content
        summaryField.addEventListener('change', function() {
            if (!window.storyEditor) return;

            const summary = summaryField.value.trim();
            if (!summary) return;

            // Get current editor content
            const editorContent = window.storyEditor.getData();

            // Check if the summary is in the editor content
            if (editorContent.includes(summary)) {
                console.log('Summary found in editor content, removing it');

                // Remove the summary from the editor content
                const cleanedContent = editorContent
                    .replace(summary, '')
                    .replace('<p>' + summary + '</p>', '')
                    .replace(/<p>\s*<\/p>/g, '')
                    .trim();

                // Set the cleaned content back to the editor
                window.storyEditor.setData(cleanedContent);
            }
        });
    }
}

/**
 * Setup form submission handler to ensure content is properly saved
 */
function setupFormSubmissionHandler() {
    const storyForm = document.querySelector('form[action="save-story.php"]');

    if (storyForm) {
        console.log('Setting up form submission handler');

        // Setup the summary and story content separation
        setupSummaryAndStoryContentSeparation();

        storyForm.addEventListener('submit', function(e) {
            // Prevent the default form submission temporarily
            e.preventDefault();

            console.log('Form submission intercepted');

            // Get the content field
            const contentField = document.getElementById('content');
            const summaryField = document.getElementById('summary');

            if (!contentField) {
                console.error('Content field not found!');
                storyForm.submit();
                return;
            }

            // Get the content based on the current editor mode
            let storyContent = '';
            const htmlContentTextarea = document.getElementById('html_content');
            const storyContentTextarea = document.getElementById('story_content');

            if (htmlContentTextarea && htmlContentTextarea.style.display !== 'none') {
                // We're in HTML mode, get content from the HTML textarea
                storyContent = htmlContentTextarea.value;
                console.log('Getting content from HTML textarea');
            } else if (window.storyEditor) {
                // We're in WYSIWYG mode, get content from CKEditor
                storyContent = window.storyEditor.getData();
                console.log('Getting content from CKEditor');
            } else if (storyContentTextarea) {
                // We're using the fallback textarea
                storyContent = storyContentTextarea.value;
                console.log('Getting content from fallback textarea');
            }

            // Get the summary
            const summary = summaryField ? summaryField.value : '';

            // Format the content for storage
            let formattedContent = '';

            // Clean up the story content to ensure summary isn't duplicated
            let cleanedStoryContent = storyContent;

            // Fix image URLs in the content to ensure they're absolute
            cleanedStoryContent = fixImageUrls(cleanedStoryContent);

            // Remove any instances of the summary from the story content
            if (summary) {
                // Try exact match
                if (cleanedStoryContent.includes(summary)) {
                    cleanedStoryContent = cleanedStoryContent.replace(summary, '').trim();
                }

                // Try with HTML tags (in case the editor added them)
                const summaryWithPTags = '<p>' + summary + '</p>';
                if (cleanedStoryContent.includes(summaryWithPTags)) {
                    cleanedStoryContent = cleanedStoryContent.replace(summaryWithPTags, '').trim();
                }

                // Try with line breaks
                const summaryWithBreaks = summary.replace(/\n/g, '<br>');
                if (cleanedStoryContent.includes(summaryWithBreaks)) {
                    cleanedStoryContent = cleanedStoryContent.replace(summaryWithBreaks, '').trim();
                }

                // Try with different paragraph formatting
                const summaryWithDifferentPTags = summary.replace(/\n/g, '</p><p>');
                if (cleanedStoryContent.includes(summaryWithDifferentPTags)) {
                    cleanedStoryContent = cleanedStoryContent.replace(summaryWithDifferentPTags, '').trim();
                }

                // Try with the summary at the beginning of the content with or without HTML
                const summaryRegex = new RegExp('^\\s*' + escapeRegExp(summary) + '\\s*', 'i');
                if (summaryRegex.test(cleanedStoryContent)) {
                    cleanedStoryContent = cleanedStoryContent.replace(summaryRegex, '').trim();
                }

                // Try with the summary at the beginning with HTML tags
                const summaryWithPTagsRegex = new RegExp('^\\s*<p[^>]*>\\s*' + escapeRegExp(summary) + '\\s*</p>\\s*', 'i');
                if (summaryWithPTagsRegex.test(cleanedStoryContent)) {
                    cleanedStoryContent = cleanedStoryContent.replace(summaryWithPTagsRegex, '').trim();
                }

                // Clean up any empty paragraphs that might be left after removing content
                cleanedStoryContent = cleanedStoryContent.replace(/<p>\s*<\/p>/g, '').trim();

                // If we have a summary, include it in the content
                formattedContent += '## Summary\n\n' + summary + '\n\n';
            }

            // Add the story content
            formattedContent += '## Story\n\n' + cleanedStoryContent;

            // Set the content field value
            contentField.value = formattedContent;

            console.log('Content field updated, submitting form');

            // Submit the form
            storyForm.submit();
        });
    } else {
        console.error('Story form not found!');
    }
}
