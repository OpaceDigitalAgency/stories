/**
 * CKEditor 5 Source Editing Plugin
 * 
 * This plugin adds a source editing button to the CKEditor toolbar
 * allowing users to edit the HTML source directly.
 */

class SourceEditingPlugin {
    constructor(editor) {
        this.editor = editor;
        this.isSourceEditingMode = false;
        this.sourceTextarea = null;
        this.editorElement = null;
        this.editorData = null;
        
        // Register the button
        editor.ui.componentFactory.add('sourceEditing', locale => {
            const button = new window.ckeditorClassic.ButtonView(locale);
            
            button.set({
                label: 'Source',
                icon: this.getSourceIcon(),
                tooltip: true,
                isToggleable: true
            });
            
            // Toggle source editing mode when the button is clicked
            button.on('execute', () => {
                this.toggleSourceEditing();
                button.set({ isOn: this.isSourceEditingMode });
            });
            
            return button;
        });
    }
    
    toggleSourceEditing() {
        if (!this.isSourceEditingMode) {
            this.enableSourceEditing();
        } else {
            this.disableSourceEditing();
        }
        
        this.isSourceEditingMode = !this.isSourceEditingMode;
    }
    
    enableSourceEditing() {
        // Store the editor element and data
        this.editorElement = this.editor.sourceElement;
        this.editorData = this.editor.getData();
        
        // Create a textarea for source editing
        this.sourceTextarea = document.createElement('textarea');
        this.sourceTextarea.className = 'ck-source-editing-area';
        this.sourceTextarea.value = this.editorData;
        this.sourceTextarea.style.width = '100%';
        this.sourceTextarea.style.height = '400px';
        this.sourceTextarea.style.padding = '10px';
        this.sourceTextarea.style.border = '1px solid #ccc';
        this.sourceTextarea.style.borderRadius = '4px';
        this.sourceTextarea.style.fontFamily = 'monospace';
        this.sourceTextarea.style.fontSize = '14px';
        this.sourceTextarea.style.lineHeight = '1.5';
        this.sourceTextarea.style.resize = 'vertical';
        
        // Hide the editor and show the textarea
        const editorRoot = this.editor.ui.view.editable.element.parentElement;
        editorRoot.style.display = 'none';
        editorRoot.parentElement.insertBefore(this.sourceTextarea, editorRoot.nextSibling);
    }
    
    disableSourceEditing() {
        if (!this.sourceTextarea) return;
        
        // Get the updated HTML from the textarea
        const sourceData = this.sourceTextarea.value;
        
        // Remove the textarea
        this.sourceTextarea.remove();
        this.sourceTextarea = null;
        
        // Show the editor again
        const editorRoot = this.editor.ui.view.editable.element.parentElement;
        editorRoot.style.display = '';
        
        // Set the editor data
        this.editor.setData(sourceData);
    }
    
    getSourceIcon() {
        return '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M7.657 4.993l-1.4 1.4 3.608 3.607-3.608 3.607 1.4 1.4 4.307-4.307a1 1 0 0 0 0-1.4l-4.307-4.307zm5.315 0l-4.307 4.307a1 1 0 0 0 0 1.4l4.307 4.307 1.4-1.4-3.608-3.607 3.608-3.607-1.4-1.4z"/></svg>';
    }
}

// Plugin that registers the source editing feature
function SourceEditing(editor) {
    return new SourceEditingPlugin(editor);
}
