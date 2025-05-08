/**
 * Game Preview Lightbox
 *
 * This script provides functionality to preview games in a lightbox
 * by loading the direct preview handler.
 */

// Only define the class if it doesn't already exist
if (typeof GamePreview === 'undefined') {
class GamePreview {
    constructor() {
        this.frontendBaseUrl = this.getFrontendBaseUrl();
        this.initEventListeners();
    }

    /**
     * Get the frontend base URL from configuration or use default
     */
    getFrontendBaseUrl() {
        // Try to get from a global variable if it exists
        if (typeof window.FRONTEND_BASE_URL !== 'undefined') {
            return window.FRONTEND_BASE_URL;
        }

        // Default URLs for different environments
        const hostname = window.location.hostname;

        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            return 'http://localhost:3000'; // Local development
        } else if (hostname.includes('staging') || hostname.includes('test')) {
            return 'https://staging.storiesfromtheweb.org'; // Staging
        } else {
            return 'https://storiesfromtheweb.netlify.app'; // Production
        }
    }

    /**
     * Initialize event listeners for preview buttons
     */
    initEventListeners() {
        // Use event delegation to handle clicks on preview buttons
        document.addEventListener('click', (event) => {
            const target = event.target.closest('.game-preview-btn, [data-action="preview-game"], #preview-game');
            if (target) {
                event.preventDefault();

                // Get game ID from the button
                const gameId = target.dataset.gameId || target.closest('[data-game-id]')?.dataset.gameId ||
                               document.querySelector('input[name="id"]')?.value;

                if (gameId) {
                    this.loadGamePreview(gameId);
                }
            }

            // Close lightbox when clicking on the close button or overlay
            if (event.target.matches('.lightbox-close, .lightbox-overlay')) {
                this.closeLightbox();
            }
        });

        // Handle escape key to close the lightbox
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeLightbox();
            }
        });
    }

    /**
     * Load game preview directly from our backend
     * @param {string} gameId - The ID of the game to preview
     */
    async loadGamePreview(gameId) {
        try {
            // Show loading indicator
            this.showLoading();

            // Use our direct preview handler
            const previewUrl = `../handlers/direct-game-preview.php?id=${gameId}`;

            console.log('Loading game preview directly:', previewUrl);

            // Open the lightbox with the direct preview URL
            this.openLightbox(previewUrl);

            // Add a small delay to hide the loading indicator
            setTimeout(() => {
                this.hideLoading();
            }, 1000);

        } catch (error) {
            console.error('Error loading game preview:', error);
            this.showError('Failed to load game preview. Please try again.');
            this.hideLoading();
        }
    }

    /**
     * Open the lightbox with the given URL
     * @param {string} url - The URL to load in the lightbox
     */
    openLightbox(url) {
        // Create lightbox elements if they don't exist
        let lightbox = document.getElementById('game-preview-lightbox');

        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'game-preview-lightbox';
            lightbox.className = 'lightbox-overlay';
            lightbox.innerHTML = `
                <div class="lightbox-container">
                    <div class="lightbox-header">
                        <h3>Game Preview</h3>
                        <button class="lightbox-close">&times;</button>
                    </div>
                    <div class="lightbox-content">
                        <div class="preview-actions">
                            <a href="${url}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-external-link-alt"></i> Open in New Tab
                            </a>
                        </div>
                        <div class="preview-iframe-container">
                            <iframe src="about:blank" frameborder="0" allowfullscreen></iframe>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(lightbox);

            // Make sure the CSS file is loaded
            if (!document.getElementById('game-preview-css')) {
                const link = document.createElement('link');
                link.id = 'game-preview-css';
                link.rel = 'stylesheet';
                link.href = '../assets/css/story-preview.css'; // Reuse the same CSS
                document.head.appendChild(link);
            }
        } else {
            // Update the "Open in New Tab" link
            const openInTabLink = lightbox.querySelector('.preview-actions a');
            if (openInTabLink) {
                openInTabLink.href = url;
            }
        }

        // Get the iframe and set its source directly to our preview URL
        const iframe = lightbox.querySelector('iframe');
        iframe.src = url;

        // Log the URL for debugging
        console.log('Loading preview directly:', url);

        // Show the lightbox
        lightbox.style.display = 'flex';

        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close the lightbox
     */
    closeLightbox() {
        const lightbox = document.getElementById('game-preview-lightbox');
        if (lightbox) {
            lightbox.style.display = 'none';

            // Reset iframe src to prevent continued audio/video playback
            const iframe = lightbox.querySelector('iframe');
            if (iframe) {
                iframe.src = 'about:blank';
            }

            // Restore body scrolling
            document.body.style.overflow = '';
        }
    }

    /**
     * Show loading indicator
     */
    showLoading() {
        let loading = document.getElementById('lightbox-loading');
        if (!loading) {
            loading = document.createElement('div');
            loading.id = 'lightbox-loading';
            loading.className = 'lightbox-loading';
            loading.innerHTML = `
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading preview...</p>
            `;
            document.body.appendChild(loading);
        }
        loading.style.display = 'block';
    }

    /**
     * Hide loading indicator
     */
    hideLoading() {
        const loading = document.getElementById('lightbox-loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    /**
     * Show error message
     * @param {string} message - The error message to display
     */
    showError(message) {
        alert(message);
    }
}

} // End of GamePreview class

// Initialize the game preview functionality when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (!window.gamePreview) {
        window.gamePreview = new GamePreview();
    }
});
