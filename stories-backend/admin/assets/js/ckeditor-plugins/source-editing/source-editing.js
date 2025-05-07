/**
 * CKEditor 5 Source Editing Plugin
 *
 * This plugin adds a source editing button to the CKEditor toolbar
 * allowing users to edit the HTML source directly.
 */

// Create a simple plugin that can be used directly without requiring the CKEditor build system
const SourceEditing = (function() {
    // Plugin class
    class SourceEditingPlugin {
        constructor(editor) {
            this.editor = editor;
            this.isSourceEditingMode = false;
            this.sourceTextarea = null;
            this.editorElement = null;
            this.editorData = null;

            // Register the button
            editor.ui.componentFactory.add('sourceEditing', locale => {
                // Create a button - we need to handle the case where ButtonView might not be directly accessible
                const button = editor.ui.view.toolbar._items.find(item => item.name === 'bold')?.clone() ||
                              this.createFallbackButton(locale);

                button.set({
                    name: 'sourceEditing',
                    label: 'Source',
                    icon: this.getSourceIcon(),
                    tooltip: true,
                    isToggleable: true,
                    isOn: false
                });

                // Toggle source editing mode when the button is clicked
                button.on('execute', () => {
                    this.toggleSourceEditing();
                    button.set({ isOn: this.isSourceEditingMode });
                });

                return button;
            });
        }

        // Create a fallback button if we can't clone an existing one
        createFallbackButton(locale) {
            const button = {
                set: function(props) {
                    Object.assign(this, props);
                },
                on: function(event, callback) {
                    this._callbacks = this._callbacks || {};
                    this._callbacks[event] = callback;
                },
                fire: function(event) {
                    if (this._callbacks && this._callbacks[event]) {
                        this._callbacks[event]();
                    }
                },
                render: function() {
                    const element = document.createElement('button');
                    element.innerHTML = this.icon;
                    element.title = this.label;
                    element.className = 'ck ck-button';
                    element.addEventListener('click', () => this.fire('execute'));
                    return element;
                }
            };

            return button;
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

    // Return a function that creates a new plugin instance
    return function(editor) {
        return {
            init() {
                new SourceEditingPlugin(editor);
            }
        };
    };
})();
