/**
 * Contact Preview Lightbox
 *
 * This script provides functionality to preview contacts in a lightbox
 * by loading the contact details from the backend.
 */

// Only define the class if it doesn't already exist
if (typeof ContactPreview === 'undefined') {
class ContactPreview {
    constructor() {
        this.initEventListeners();
    }

    /**
     * Initialize event listeners for preview buttons
     */
    initEventListeners() {
        // Use event delegation to handle clicks on preview buttons
        document.addEventListener('click', (event) => {
            const target = event.target.closest('.contact-preview-btn, [data-action="preview-contact"]');
            if (target) {
                event.preventDefault();

                // Get contact ID from the button
                const contactId = target.dataset.contactId || target.closest('[data-contact-id]')?.dataset.contactId;

                if (contactId) {
                    this.loadContactPreview(contactId);
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
     * Load contact preview from our backend
     * @param {string} contactId - The ID of the contact to preview
     */
    async loadContactPreview(contactId) {
        try {
            // Show loading indicator
            this.showLoading();

            // Create modal container if it doesn't exist
            let modal = document.getElementById('contact-preview-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'contact-preview-modal';
                modal.className = 'preview-modal';
                modal.innerHTML = `
                    <div class="preview-modal-content">
                        <div class="preview-modal-header">
                            <h2>Contact Preview</h2>
                            <button class="preview-modal-close">&times;</button>
                        </div>
                        <div class="preview-modal-body">
                            <div class="preview-loading">Loading contact details...</div>
                            <div id="contact-preview-content" style="display:none;"></div>
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
                modal.querySelector('#contact-preview-content').style.display = 'none';
                modal.querySelector('.preview-loading').style.display = 'block';
                modal.style.display = 'flex';
            }

            try {
                // Try to fetch contact data via AJAX
                const response = await fetch(`../handlers/get-contact.php?id=${contactId}`);
                const data = await response.json();

                if (data.success) {
                    const previewContent = document.getElementById('contact-preview-content');
                    const loading = modal.querySelector('.preview-loading');

                    // Create HTML content for the preview
                    previewContent.innerHTML = this.generateContactHTML(data.contact);

                    // Show the preview
                    loading.style.display = 'none';
                    previewContent.style.display = 'block';
                } else {
                    // If AJAX fails with an error, use the direct preview as fallback
                    this.loadDirectPreview(contactId);
                }
            } catch (ajaxError) {
                console.error('AJAX error loading contact preview:', ajaxError);
                // Use direct preview as fallback
                this.loadDirectPreview(contactId);
            }

            this.hideLoading();

        } catch (error) {
            console.error('Error loading contact preview:', error);
            // Try direct preview as a last resort
            this.loadDirectPreview(contactId);
            this.hideLoading();
        }
    }

    /**
     * Load contact preview directly using an iframe
     * @param {string} contactId - The ID of the contact to preview
     */
    loadDirectPreview(contactId) {
        try {
            // Create or get the modal
            let modal = document.getElementById('contact-preview-modal');
            if (!modal) {
                return; // Should not happen, but just in case
            }

            const loading = modal.querySelector('.preview-loading');
            const previewContent = document.getElementById('contact-preview-content');

            // Create an iframe to load the direct preview
            const iframe = document.createElement('iframe');
            iframe.src = `../handlers/direct-contact-preview.php?id=${contactId}`;
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
            console.error('Error loading direct contact preview:', error);
            const modal = document.getElementById('contact-preview-modal');
            if (modal) {
                modal.querySelector('.preview-loading').innerHTML = 'Error loading contact details. Please try again.';
            }
        }
    }

    /**
     * Generate HTML for contact preview
     */
    generateContactHTML(contact) {
        // Format date
        const date = new Date(contact.created_at);
        const formattedDate = date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        return `
            <div class="contact-card">
                <div class="card">
                    <div class="card-header">
                        <h3>${contact.name}</h3>
                        <div>${contact.email}</div>
                    </div>
                    <div class="card-body">
                        <h4>${contact.subject}</h4>
                        <div class="message-content">${contact.message}</div>
                        ${contact.admin_notes ? `
                            <div class="admin-notes">
                                <h5>Admin Notes</h5>
                                <div>${contact.admin_notes}</div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-footer">
                        <div class="status">
                            Status: <span class="badge ${contact.is_responded ? 'bg-success' : 'bg-warning'}">
                                ${contact.is_responded ? 'Responded' : 'Not Responded'}
                            </span>
                        </div>
                        <div class="date">
                            Received: ${formattedDate}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Close the lightbox
     */
    closeLightbox() {
        const modal = document.getElementById('contact-preview-modal');
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

} // End of ContactPreview class

// Initialize the contact preview functionality when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Always create a new instance of ContactPreview
    window.contactPreview = new ContactPreview();
    console.log('ContactPreview initialized');
});
