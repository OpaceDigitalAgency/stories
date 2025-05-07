/**
 * Simple Source Editing Plugin for CKEditor 5
 * 
 * This plugin adds a source editing button to the CKEditor toolbar
 * allowing users to edit the HTML source directly.
 */

// Define the source editing plugin as a global variable
window.SimpleSourceEditing = {
    // Initialize the plugin
    init: function(editor) {
        // Track source editing mode state
        let isSourceEditingMode = false;
        let sourceTextarea = null;
        
        // Add the source editing button to the toolbar
        editor.ui.componentFactory.add('sourceEditing', locale => {
            // Create a button
            const button = new editor.ui.ButtonView(locale);
            
            button.set({
                label: 'Source',
                icon: this.getSourceIcon(),
                tooltip: true,
                isToggleable: true
            });
            
            // Toggle source editing mode when the button is clicked
            button.on('execute', () => {
                if (!isSourceEditingMode) {
                    // Enable source editing
                    this.enableSourceEditing(editor);
                } else {
                    // Disable source editing
                    this.disableSourceEditing(editor);
                }
                
                // Toggle the mode
                isSourceEditingMode = !isSourceEditingMode;
                button.set({ isOn: isSourceEditingMode });
            });
            
            return button;
        });
    },
    
    // Enable source editing mode
    enableSourceEditing: function(editor) {
        // Get the editor data
        const editorData = editor.getData();
        
        // Find the editor's editable element
        const editorElement = editor.ui.view.editable.element;
        const editorContainer = editorElement.parentElement;
        
        // Create a textarea for source editing
        sourceTextarea = document.createElement('textarea');
        sourceTextarea.className = 'ck-source-editing-area';
        sourceTextarea.value = editorData;
        sourceTextarea.style.width = '100%';
        sourceTextarea.style.height = '400px';
        sourceTextarea.style.padding = '10px';
        sourceTextarea.style.border = '1px solid #ccc';
        sourceTextarea.style.borderRadius = '4px';
        sourceTextarea.style.fontFamily = 'monospace';
        sourceTextarea.style.fontSize = '14px';
        sourceTextarea.style.lineHeight = '1.5';
        sourceTextarea.style.resize = 'vertical';
        
        // Hide the editor and show the textarea
        editorContainer.style.display = 'none';
        editorContainer.parentElement.insertBefore(sourceTextarea, editorContainer.nextSibling);
    },
    
    // Disable source editing mode
    disableSourceEditing: function(editor) {
        if (!sourceTextarea) return;
        
        // Get the updated HTML from the textarea
        const sourceData = sourceTextarea.value;
        
        // Find the editor's editable element
        const editorElement = editor.ui.view.editable.element;
        const editorContainer = editorElement.parentElement;
        
        // Remove the textarea
        sourceTextarea.remove();
        sourceTextarea = null;
        
        // Show the editor again
        editorContainer.style.display = '';
        
        // Set the editor data
        editor.setData(sourceData);
    },
    
    // Get the source icon SVG
    getSourceIcon: function() {
        return '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M7.657 4.993l-1.4 1.4 3.608 3.607-3.608 3.607 1.4 1.4 4.307-4.307a1 1 0 0 0 0-1.4l-4.307-4.307zm5.315 0l-4.307 4.307a1 1 0 0 0 0 1.4l4.307 4.307 1.4-1.4-3.608-3.607 3.608-3.607-1.4-1.4z"/></svg>';
    }
};
