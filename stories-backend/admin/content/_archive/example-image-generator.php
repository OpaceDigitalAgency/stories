<?php
/**
 * Example Image Generator Page
 * 
 * This page demonstrates how to use the AI Image Generator button component
 * in an admin interface.
 */

// Include necessary files
require_once '../includes/header.php';
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';

// Set page variables
$pageTitle = 'Example Image Generator';
$currentPage = 'example-image-generator';
$pageDescription = 'Example of using the AI Image Generator component';

?>

<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">
            <i class="fas fa-image" aria-hidden="true"></i> 
            Image Generator Example
        </h2>
        <p class="section-description">
            This example shows how to integrate the AI Image Generator into your admin pages.
        </p>
    </div>

    <div class="section-body">
        <!-- Example form with image field -->
        <form id="exampleForm" class="admin-form">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Cover Image</label>
                <div class="image-field">
                    <img id="previewImage" 
                         src="/images/placeholder.png" 
                         alt="Preview" 
                         class="preview-image">
                    
                    <div class="image-actions">
                        <!-- AI Image Generator Button -->
                        <div id="aiImageGenerator"></div>
                        
                        <!-- Regular upload button -->
                        <button type="button" class="btn btn-secondary" id="uploadBtn">
                            <i class="fas fa-upload" aria-hidden="true"></i>
                            Upload Image
                        </button>
                    </div>

                    <input type="hidden" id="imageUrl" name="image_url">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<style>
.image-field {
    border: 2px dashed var(--border-color);
    padding: 1rem;
    border-radius: var(--radius-2);
    background: var(--surface-1);
}

.preview-image {
    max-width: 100%;
    max-height: 300px;
    margin-bottom: 1rem;
    border-radius: var(--radius-1);
}

.image-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.admin-form {
    max-width: 800px;
}
</style>

<script type="module">
    // Import the ImageGeneratorButton component
    import ImageGeneratorButton from '../../../src/components/ai/ImageGeneratorButton.astro';

    // Mount the component
    const container = document.getElementById('aiImageGenerator');
    if (container) {
        new ImageGeneratorButton({
            target: container,
            props: {
                buttonText: 'Generate with AI',
                buttonClass: 'btn btn-primary'
            }
        });
    }

    // Handle selected AI image
    document.addEventListener('aiImageSelected', (event) => {
        const { url } = event.detail;
        document.getElementById('previewImage').src = url;
        document.getElementById('imageUrl').value = url;
    });

    // Handle regular file upload
    document.getElementById('uploadBtn').addEventListener('click', () => {
        // Implement your regular file upload logic here
        alert('Regular file upload not implemented in this example');
    });

    // Handle form submission
    document.getElementById('exampleForm').addEventListener('submit', (e) => {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        
        // Log the data that would be submitted
        console.log('Form data:', data);
        alert('Form submission example - check console for data');
    });
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>