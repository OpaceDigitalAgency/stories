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

    // Fix 1: Convert numeric boolean fields to checkboxes
    fixBooleanFields();

    // Fix 2: Initialize WYSIWYG editor properly
    initializeEditor();

    // Fix 3: Fix preview button
    setupPreviewButton();
});

/**
 * Convert numeric boolean fields to proper checkboxes
 */
function fixBooleanFields() {
    // List of known boolean fields
    const booleanFields = [
        'is_published', 'is_featured', 'is_sponsored', 'allow_reviews'
    ];

    // Find all number inputs with values 0 or 1
    const numericInputs = document.querySelectorAll('input[type="number"]');

    numericInputs.forEach(input => {
        const fieldName = input.getAttribute('name');
        const fieldId = input.getAttribute('id');

        // Check if this is a boolean field (either by name or by having values limited to 0/1)
        const isBooleanByName = booleanFields.includes(fieldName);
        const isBooleanByValue = (input.min === '0' && input.max === '1') ||
                                (input.value === '0' || input.value === '1');

        if (isBooleanByName || isBooleanByValue) {
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
                originalLabel.remove();
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
            parentElement.replaceChild(wrapper, input);
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
                    // Register the custom upload adapter plugin
                    extraPlugins: [MediaLibraryUploadAdapterPlugin]
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

    // Create a form to post the data
    const form = document.createElement('form');
    form.method = 'post';
    form.action = 'preview-story.php';
    form.target = '_blank';

    // Add the title
    const titleInput = document.createElement('input');
    titleInput.type = 'hidden';
    titleInput.name = 'title';
    titleInput.value = title;
    form.appendChild(titleInput);

    // Add the summary
    const summaryInput = document.createElement('input');
    summaryInput.type = 'hidden';
    summaryInput.name = 'summary';
    summaryInput.value = summary;
    form.appendChild(summaryInput);

    // Add the content
    const contentInput = document.createElement('input');
    contentInput.type = 'hidden';
    contentInput.name = 'content';
    contentInput.value = storyContent;
    form.appendChild(contentInput);

    // Submit the form
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
