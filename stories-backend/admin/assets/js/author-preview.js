/**
 * Author Preview Lightbox
 *
 * This script provides functionality to preview authors in a lightbox
 * by loading the author details from the backend.
 */

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
            console.log('Loading author preview for ID:', authorId);

            // First, close any existing modals to prevent duplicates
            this.closeLightbox();

            // Show loading indicator
            this.showLoading();

            // Create a new modal container
            console.log('Creating new modal container');
            const modal = document.createElement('div');
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

            // Add event listener to close on escape key
            const escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    this.closeLightbox();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);

            // Add event listener to close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeLightbox();
                }
            });

            try {
                // Try to fetch author data via AJAX
                console.log('Fetching author data from:', `../handlers/get-author.php?id=${authorId}`);
                const response = await fetch(`../handlers/get-author.php?id=${authorId}`);
                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    console.log('Successfully loaded author data');
                    const previewContent = document.getElementById('author-preview-content');
                    const loading = modal.querySelector('.preview-loading');

                    // Create HTML content for the preview
                    previewContent.innerHTML = this.generateAuthorHTML(data.author, data.stories, data.posts);

                    // Show the preview
                    loading.style.display = 'none';
                    previewContent.style.display = 'block';
                } else {
                    console.error('Error from API:', data.message);
                    const errorMessage = data.message || 'Failed to load author details';
                    modal.querySelector('.preview-loading').innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Error:</strong> ${errorMessage}
                        </div>
                    `;
                }
            } catch (ajaxError) {
                console.error('AJAX error loading author preview:', ajaxError);
                modal.querySelector('.preview-loading').innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Error:</strong> ${ajaxError.message}
                    </div>
                `;
            }

            this.hideLoading();

        } catch (error) {
            console.error('Error loading author preview:', error);
            this.hideLoading();
            alert(`Error loading author preview: ${error.message}`);
        }
    }

    // Direct preview method removed as it's no longer needed and could cause issues

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
        // Find and remove all author preview modals to prevent duplicates
        const modals = document.querySelectorAll('#author-preview-modal, .preview-modal');
        modals.forEach(modal => {
            console.log('Removing modal:', modal);
            modal.remove();
        });

        // Also remove any loading indicators
        const loadingIndicators = document.querySelectorAll('#lightbox-loading, .lightbox-loading');
        loadingIndicators.forEach(indicator => {
            indicator.remove();
        });
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

// Initialize the author preview functionality when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Always create a new instance of AuthorPreview
    window.authorPreview = new AuthorPreview();
    console.log('AuthorPreview initialized');
});
