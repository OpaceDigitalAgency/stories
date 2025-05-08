/**
 * Blog Post Preview Lightbox
 *
 * This script provides functionality to preview blog posts in a lightbox
 * by loading the frontend Astro URL based on the post's slug.
 */

// Only define the class if it doesn't already exist
if (typeof PostPreview === 'undefined') {
class PostPreview {
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
            const target = event.target.closest('.post-preview-btn, [data-action="preview-post"], #preview-post');
            if (target) {
                event.preventDefault();

                // Get post ID from the button
                const postId = target.dataset.postId || target.closest('[data-post-id]')?.dataset.postId ||
                               document.querySelector('input[name="id"]')?.value;

                if (postId) {
                    this.loadPostPreview(postId);
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
     * Load post preview directly from our backend
     * @param {string} postId - The ID of the post to preview
     */
    async loadPostPreview(postId) {
        try {
            // Show loading indicator
            this.showLoading();

            // Use our direct preview handler
            const previewUrl = `../handlers/direct-post-preview.php?id=${postId}`;

            console.log('Loading post preview directly:', previewUrl);

            // Open the lightbox with the direct preview URL
            this.openLightbox(previewUrl);

            // Add a small delay to hide the loading indicator
            setTimeout(() => {
                this.hideLoading();
            }, 1000);

        } catch (error) {
            console.error('Error loading post preview:', error);
            this.showError('Failed to load post preview. Please try again.');
            this.hideLoading();
        }
    }

    /**
     * Open the lightbox with the given URL
     * @param {string} url - The URL to load in the lightbox
     */
    openLightbox(url) {
        // Create lightbox elements if they don't exist
        let lightbox = document.getElementById('post-preview-lightbox');

        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'post-preview-lightbox';
            lightbox.className = 'lightbox-overlay';
            lightbox.innerHTML = `
                <div class="lightbox-container">
                    <div class="lightbox-header">
                        <h3>Blog Post Preview</h3>
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
            if (!document.getElementById('post-preview-css')) {
                const link = document.createElement('link');
                link.id = 'post-preview-css';
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
        const lightbox = document.getElementById('post-preview-lightbox');
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

} // End of PostPreview class

// Initialize the post preview functionality when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    try {
        if (typeof PostPreview !== 'undefined' && !window.postPreview) {
            window.postPreview = new PostPreview();
        }
    } catch (e) {
        console.warn('Error initializing PostPreview:', e);
    }
});
