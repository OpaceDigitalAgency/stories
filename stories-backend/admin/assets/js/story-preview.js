/**
 * Story Preview Lightbox
 *
 * This script provides functionality to preview stories in a lightbox
 * by loading the frontend Astro URL based on the story's slug.
 */

class StoryPreview {
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
            const target = event.target.closest('.story-preview-btn, [data-action="preview-story"]');
            if (target) {
                event.preventDefault();

                // Get story ID from the button
                const storyId = target.dataset.storyId || target.closest('[data-story-id]')?.dataset.storyId;

                if (storyId) {
                    this.loadStoryPreview(storyId);
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
     * Load story preview by fetching the slug and opening the lightbox
     * @param {string} storyId - The ID of the story to preview
     */
    async loadStoryPreview(storyId) {
        try {
            // Show loading indicator
            this.showLoading();

            // Fetch the story slug from the server
            const response = await fetch(`../handlers/get-story-slug.php?id=${storyId}`);
            const data = await response.json();

            if (data.success && data.slug) {
                // Construct the frontend URL
                const frontendUrl = `${this.frontendBaseUrl}/stories/${data.slug}`;

                console.log('Loading story preview from URL:', frontendUrl);

                // Open the lightbox with the frontend URL
                this.openLightbox(frontendUrl);

                // Add a small delay to check if the iframe loaded correctly
                setTimeout(() => {
                    const iframe = document.querySelector('#story-preview-lightbox iframe');
                    if (iframe) {
                        // Check if the iframe has loaded content
                        if (iframe.contentWindow) {
                            console.log('Iframe content window exists');

                            // Add a message listener to receive messages from the iframe
                            window.addEventListener('message', (event) => {
                                // Verify the origin of the message
                                if (event.data && event.data.type === 'preview-loaded') {
                                    console.log('Preview loaded successfully');
                                }

                                if (event.data && event.data.type === 'preview-error') {
                                    console.error('Preview error:', event.data.message);
                                    this.showError(event.data.message || 'Failed to load preview');
                                }
                            });
                        } else {
                            console.error('Iframe content window does not exist');
                        }
                    }
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to get story slug');
            }
        } catch (error) {
            console.error('Error loading story preview:', error);
            this.showError('Failed to load story preview. Please try again.');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Open the lightbox with the given URL
     * @param {string} url - The URL to load in the lightbox
     */
    openLightbox(url) {
        // Create lightbox elements if they don't exist
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
                        <div class="preview-actions">
                            <a href="${url}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-external-link-alt"></i> Open in New Tab
                            </a>
                        </div>
                        <div class="preview-iframe-container">
                            <iframe src="about:blank" frameborder="0" allowfullscreen allow="fullscreen" sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-modals allow-top-navigation"></iframe>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(lightbox);

            // Make sure the CSS file is loaded
            if (!document.getElementById('story-preview-css')) {
                const link = document.createElement('link');
                link.id = 'story-preview-css';
                link.rel = 'stylesheet';
                link.href = '../assets/css/story-preview.css';
                document.head.appendChild(link);
            }
        } else {
            // Update the "Open in New Tab" link
            const openInTabLink = lightbox.querySelector('.preview-actions a');
            if (openInTabLink) {
                openInTabLink.href = url;
            }
        }

        // Use our proxy handler to load the content with proper headers
        const iframe = lightbox.querySelector('iframe');

        // Create a proxy URL that will load the content through our backend
        // This ensures proper headers are set for cross-origin iframe loading
        const proxyUrl = `../handlers/story-preview-proxy.php?url=${encodeURIComponent(url)}`;

        // Set the iframe source to our proxy URL
        iframe.src = proxyUrl;

        // Log the URL for debugging
        console.log('Loading preview via proxy:', proxyUrl);

        // Show the lightbox
        lightbox.style.display = 'flex';

        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
    }

    /**
     * Close the lightbox
     */
    closeLightbox() {
        const lightbox = document.getElementById('story-preview-lightbox');
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

// Initialize the story preview functionality when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.storyPreview = new StoryPreview();
});
