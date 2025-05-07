/**
 * Loading Overlay
 * 
 * Provides functionality for displaying a loading overlay during long operations
 */

class LoadingOverlay {
    constructor() {
        this.overlay = null;
        this.spinner = null;
        this.message = null;
        this.progressBar = null;
        this.createOverlay();
    }

    /**
     * Create the overlay elements
     */
    createOverlay() {
        // Create overlay if it doesn't exist
        if (!this.overlay) {
            this.overlay = document.createElement('div');
            this.overlay.className = 'loading-overlay';
            
            // Create spinner
            this.spinner = document.createElement('div');
            this.spinner.className = 'loading-spinner';
            this.overlay.appendChild(this.spinner);
            
            // Create message
            this.message = document.createElement('div');
            this.message.className = 'loading-message';
            this.message.textContent = 'Loading...';
            this.overlay.appendChild(this.message);
            
            // Create progress container
            const progressContainer = document.createElement('div');
            progressContainer.className = 'loading-progress';
            
            // Create progress bar
            this.progressBar = document.createElement('div');
            this.progressBar.className = 'loading-progress-bar';
            progressContainer.appendChild(this.progressBar);
            
            this.overlay.appendChild(progressContainer);
            
            // Add to document
            document.body.appendChild(this.overlay);
        }
    }

    /**
     * Show the loading overlay
     * @param {string} message - Optional message to display
     */
    show(message = 'Loading...') {
        this.message.textContent = message;
        this.progressBar.style.width = '0%';
        this.overlay.classList.add('active');
    }

    /**
     * Hide the loading overlay
     */
    hide() {
        this.overlay.classList.remove('active');
    }

    /**
     * Update the progress bar
     * @param {number} percent - Progress percentage (0-100)
     * @param {string} message - Optional new message
     */
    updateProgress(percent, message = null) {
        if (percent < 0) percent = 0;
        if (percent > 100) percent = 100;
        
        this.progressBar.style.width = `${percent}%`;
        
        if (message) {
            this.message.textContent = message;
        }
    }

    /**
     * Simulate progress for operations with unknown duration
     * @param {number} duration - Approximate duration in milliseconds
     * @param {string} message - Message to display
     * @param {Function} onComplete - Callback when simulation reaches 90%
     */
    simulateProgress(duration = 5000, message = 'Processing...', onComplete = null) {
        this.show(message);
        
        let progress = 0;
        const interval = 100; // Update every 100ms
        const increment = 90 / (duration / interval); // Max out at 90%
        
        const timer = setInterval(() => {
            progress += increment;
            
            if (progress >= 90) {
                clearInterval(timer);
                this.updateProgress(90);
                
                if (onComplete && typeof onComplete === 'function') {
                    onComplete();
                }
            } else {
                this.updateProgress(progress);
            }
        }, interval);
        
        return timer;
    }
}

// Create a global instance
window.loadingOverlay = new LoadingOverlay();
