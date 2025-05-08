/**
 * Author Preview Lightbox
 *
 * This script provides functionality to preview authors in a lightbox
 * by loading the author details from the backend.
 */

// Only define the class if it doesn't already exist
if (typeof AuthorPreview === 'undefined') {
class AuthorPreview {
    constructor() {
        this.initEventListeners();
    }

    /**
     * Initialize event listeners for preview buttons
     */
    initEventListeners() {
        // Use event delegation to handle clicks on preview buttons
        document.addEventListener('click', (event) => {
            const target = event.target.closest('.author-preview-btn, [data-action="preview-author"]');
            if (target) {
                event.preventDefault();

                // Get author ID from the button
                const authorId = target.dataset.authorId || target.closest('[data-author-id]')?.dataset.authorId;

                if (authorId) {
                    this.loadAuthorPreview(authorId);
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
     * Load author preview from our backend
     * @param {string} authorId - The ID of the author to preview
     */
    async loadAuthorPreview(authorId) {
        try {
            // Show loading indicator
            this.showLoading();

            // Create modal container if it doesn't exist
            let modal = document.getElementById('author-preview-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'author-preview-modal';
                modal.className = 'preview-modal';
                modal.innerHTML = `
                    <div class="preview-modal-content">
                        <div class="preview-modal-header">
                            <h2>Author Preview</h2>
                            <button class="preview-modal-close">&times;</button>
                        </div>
                        <div class="preview-modal-body">
                            <div class="preview-loading">Loading author details...</div>
                            <div id="author-preview-content" style="display:none;"></div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);

                // Add event listener to close button
                modal.querySelector('.preview-modal-close').addEventListener('click', () => {
                    this.closeLightbox();
                });
            } else {
                // Reset content
                modal.querySelector('#author-preview-content').style.display = 'none';
                modal.querySelector('.preview-loading').style.display = 'block';
                modal.style.display = 'flex';
            }

            try {
                // Try to fetch author data via AJAX
                const response = await fetch(`../handlers/get-author.php?id=${authorId}`);
                const data = await response.json();

                if (data.success) {
                    const previewContent = document.getElementById('author-preview-content');
                    const loading = modal.querySelector('.preview-loading');

                    // Create HTML content for the preview
                    previewContent.innerHTML = this.generateAuthorHTML(data.author, data.stories, data.posts);

                    // Show the preview
                    loading.style.display = 'none';
                    previewContent.style.display = 'block';
                } else {
                    // If AJAX fails with an error, use the direct preview as fallback
                    this.loadDirectPreview(authorId);
                }
            } catch (ajaxError) {
                console.error('AJAX error loading author preview:', ajaxError);
                // Use direct preview as fallback
                this.loadDirectPreview(authorId);
            }

            this.hideLoading();

        } catch (error) {
            console.error('Error loading author preview:', error);
            // Try direct preview as a last resort
            this.loadDirectPreview(authorId);
            this.hideLoading();
        }
    }

    /**
     * Load author preview directly using an iframe
     * @param {string} authorId - The ID of the author to preview
     */
    loadDirectPreview(authorId) {
        try {
            // Create or get the modal
            let modal = document.getElementById('author-preview-modal');
            if (!modal) {
                return; // Should not happen, but just in case
            }

            const loading = modal.querySelector('.preview-loading');
            const previewContent = document.getElementById('author-preview-content');

            // Create an iframe to load the direct preview
            const iframe = document.createElement('iframe');
            iframe.src = `../handlers/direct-author-preview.php?id=${authorId}`;
            iframe.style.width = '100%';
            iframe.style.height = '500px';
            iframe.style.border = 'none';
            iframe.style.overflow = 'auto';

            // Clear previous content and add the iframe
            previewContent.innerHTML = '';
            previewContent.appendChild(iframe);

            // Show the preview
            loading.style.display = 'none';
            previewContent.style.display = 'block';

        } catch (error) {
            console.error('Error loading direct author preview:', error);
            const modal = document.getElementById('author-preview-modal');
            if (modal) {
                modal.querySelector('.preview-loading').innerHTML = 'Error loading author details. Please try again.';
            }
        }
    }

    /**
     * Generate HTML for author preview
     */
    generateAuthorHTML(author, stories, posts) {
        return `
            <div class="author-card">
                <div class="author-header">
                    <img src="${author.avatar_url || '../assets/images/default-avatar.svg'}" alt="${author.name}" class="author-avatar">
                    <div class="author-info">
                        <h1 class="author-name">${author.name}</h1>
                        <div class="author-meta">
                            ${author.email ? `<div><strong>Email:</strong> ${author.email}</div>` : ''}
                            ${author.author_type ? `<div><strong>Type:</strong> ${author.author_type}</div>` : ''}
                            ${author.age ? `<div><strong>Age:</strong> ${author.age}</div>` : ''}
                            ${author.location ? `<div><strong>Location:</strong> ${author.location}</div>` : ''}
                        </div>
                    </div>
                </div>

                <div class="author-bio">
                    <h3>Biography</h3>
                    ${author.bio || '<p>No biography available.</p>'}
                </div>

                <div class="author-content">
                    <div class="author-stories">
                        <h3>Stories (${stories ? stories.length : 0})</h3>
                        ${stories && stories.length > 0 ?
                            `<ul>${stories.map(story => `<li>${story.title}</li>`).join('')}</ul>` :
                            '<p>No stories found.</p>'}
                    </div>

                    <div class="author-posts">
                        <h3>Blog Posts (${posts ? posts.length : 0})</h3>
                        ${posts && posts.length > 0 ?
                            `<ul>${posts.map(post => `<li>${post.title}</li>`).join('')}</ul>` :
                            '<p>No blog posts found.</p>'}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Close the lightbox
     */
    closeLightbox() {
        const modal = document.getElementById('author-preview-modal');
        if (modal) {
            modal.style.display = 'none';
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
        loading.style.display = 'flex';
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
}

} // End of AuthorPreview class

// Initialize the author preview functionality when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    try {
        if (typeof AuthorPreview !== 'undefined' && !window.authorPreview) {
            window.authorPreview = new AuthorPreview();
        }
    } catch (e) {
        console.warn('Error initializing AuthorPreview:', e);
    }
});
