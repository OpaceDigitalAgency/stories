/**
 * UI Components
 * 
 * JavaScript for UI components in the book validation interface.
 */

// Initialize UI components
function initUIComponents() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize popovers
    initPopovers();
    
    // Initialize modals
    initModals();
    
    console.log('UI components initialized');
}

// Initialize Bootstrap tooltips
function initTooltips() {
    // Check if Bootstrap's tooltip function exists
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    } else {
        console.warn('Bootstrap Tooltip not available');
    }
}

// Initialize Bootstrap popovers
function initPopovers() {
    // Check if Bootstrap's popover function exists
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Popover !== 'undefined') {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    } else {
        console.warn('Bootstrap Popover not available');
    }
}

// Initialize Bootstrap modals
function initModals() {
    // Check if Bootstrap's modal function exists
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
        // No need to initialize modals in Bootstrap 5, they're initialized automatically
        console.log('Bootstrap Modal available');
    } else {
        console.warn('Bootstrap Modal not available');
    }
}

// Create and show a modal
function showModal(title, content, buttons = []) {
    // Create modal element
    const modalId = 'dynamic-modal-' + Date.now();
    const modalElement = document.createElement('div');
    modalElement.className = 'modal fade';
    modalElement.id = modalId;
    modalElement.tabIndex = -1;
    modalElement.setAttribute('aria-labelledby', `${modalId}-label`);
    modalElement.setAttribute('aria-hidden', 'true');
    
    // Create modal content
    let buttonsHtml = '';
    buttons.forEach(button => {
        buttonsHtml += `
            <button type="button" class="btn ${button.class || 'btn-secondary'}" 
                    ${button.id ? `id="${button.id}"` : ''} 
                    ${button.dismiss ? 'data-bs-dismiss="modal"' : ''}>
                ${button.text}
            </button>
        `;
    });
    
    modalElement.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="${modalId}-label">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                <div class="modal-footer">
                    ${buttonsHtml}
                </div>
            </div>
        </div>
    `;
    
    // Add modal to document
    document.body.appendChild(modalElement);
    
    // Initialize modal
    const modal = new bootstrap.Modal(modalElement);
    
    // Show modal
    modal.show();
    
    // Add event listeners for buttons
    buttons.forEach(button => {
        if (button.id && button.onClick) {
            document.getElementById(button.id).addEventListener('click', button.onClick);
        }
    });
    
    // Add event listener for modal hidden
    modalElement.addEventListener('hidden.bs.modal', function () {
        // Remove modal from DOM when hidden
        modalElement.remove();
    });
    
    return modal;
}

// Show a confirmation modal
function showConfirmationModal(title, message, onConfirm, onCancel = null) {
    return showModal(
        title,
        `<p>${message}</p>`,
        [
            {
                text: 'Cancel',
                class: 'btn-secondary',
                dismiss: true,
                onClick: onCancel
            },
            {
                text: 'Confirm',
                id: 'confirm-button',
                class: 'btn-primary',
                dismiss: true,
                onClick: onConfirm
            }
        ]
    );
}

// Show a notification toast
function showToast(message, type = 'info', duration = 5000) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastElement = document.createElement('div');
    toastElement.className = `toast align-items-center text-white bg-${type}`;
    toastElement.id = toastId;
    toastElement.setAttribute('role', 'alert');
    toastElement.setAttribute('aria-live', 'assertive');
    toastElement.setAttribute('aria-atomic', 'true');
    
    toastElement.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Add toast to container
    toastContainer.appendChild(toastElement);
    
    // Initialize toast
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: duration
    });
    
    // Show toast
    toast.show();
    
    // Add event listener for toast hidden
    toastElement.addEventListener('hidden.bs.toast', function () {
        // Remove toast from DOM when hidden
        toastElement.remove();
    });
    
    return toast;
}

// Show a loading spinner
function showSpinner(container, message = 'Loading...') {
    // Create spinner element
    const spinnerId = 'spinner-' + Date.now();
    const spinnerElement = document.createElement('div');
    spinnerElement.className = 'text-center my-3';
    spinnerElement.id = spinnerId;
    
    spinnerElement.innerHTML = `
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2">${message}</div>
    `;
    
    // Add spinner to container
    container.appendChild(spinnerElement);
    
    // Return function to hide spinner
    return function hideSpinner() {
        const spinner = document.getElementById(spinnerId);
        if (spinner) {
            spinner.remove();
        }
    };
}

// Create a progress bar
function createProgressBar(container, value = 0, max = 100, label = '') {
    // Create progress bar element
    const progressId = 'progress-' + Date.now();
    const progressElement = document.createElement('div');
    progressElement.className = 'progress my-3';
    progressElement.id = progressId;
    
    progressElement.innerHTML = `
        <div class="progress-bar" role="progressbar" style="width: ${(value / max) * 100}%;" 
             aria-valuenow="${value}" aria-valuemin="0" aria-valuemax="${max}">
            ${label || `${Math.round((value / max) * 100)}%`}
        </div>
    `;
    
    // Add progress bar to container
    container.appendChild(progressElement);
    
    // Return functions to update and remove progress bar
    return {
        update: function(newValue, newLabel = '') {
            const progressBar = document.querySelector(`#${progressId} .progress-bar`);
            if (progressBar) {
                progressBar.style.width = `${(newValue / max) * 100}%`;
                progressBar.setAttribute('aria-valuenow', newValue);
                progressBar.textContent = newLabel || `${Math.round((newValue / max) * 100)}%`;
            }
        },
        remove: function() {
            const progress = document.getElementById(progressId);
            if (progress) {
                progress.remove();
            }
        }
    };
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', initUIComponents);
