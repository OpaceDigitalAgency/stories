/**
 * Preview Loader
 * 
 * This script ensures that all preview functionality is properly initialized
 * by checking if the preview classes exist and creating them if needed.
 * It helps prevent JavaScript errors when preview scripts are loaded in the wrong order
 * or when multiple instances of the same page are loaded.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Preview loader initialized');
    
    // Initialize StoryPreview if it exists
    if (typeof StoryPreview !== 'undefined' && !window.storyPreview) {
        console.log('Initializing StoryPreview');
        window.storyPreview = new StoryPreview();
    }
    
    // Initialize AuthorPreview if it exists
    if (typeof AuthorPreview !== 'undefined' && !window.authorPreview) {
        console.log('Initializing AuthorPreview');
        window.authorPreview = new AuthorPreview();
    }
    
    // Initialize PostPreview if it exists
    if (typeof PostPreview !== 'undefined' && !window.postPreview) {
        console.log('Initializing PostPreview');
        window.postPreview = new PostPreview();
    }
    
    // Initialize ContactPreview if it exists
    if (typeof ContactPreview !== 'undefined' && !window.contactPreview) {
        console.log('Initializing ContactPreview');
        window.contactPreview = new ContactPreview();
    }
    
    // Initialize GamePreview if it exists
    if (typeof GamePreview !== 'undefined' && !window.gamePreview) {
        console.log('Initializing GamePreview');
        window.gamePreview = new GamePreview();
    }
    
    // Initialize DirectoryItemPreview if it exists
    if (typeof DirectoryItemPreview !== 'undefined' && !window.directoryItemPreview) {
        console.log('Initializing DirectoryItemPreview');
        window.directoryItemPreview = new DirectoryItemPreview();
    }
    
    // Initialize AiToolPreview if it exists
    if (typeof AiToolPreview !== 'undefined' && !window.aiToolPreview) {
        console.log('Initializing AiToolPreview');
        window.aiToolPreview = new AiToolPreview();
    }
});
