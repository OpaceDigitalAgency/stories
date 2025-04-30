<?php
// Define custom delete button before including generic template
$deleteButton = function($item) use ($db) {
    // Get story count
    $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
    $stmt->execute([$item['id']]);
    $storyCount = $stmt->fetchColumn();

    return sprintf(
        '<a href="#" class="btn btn-sm btn-danger delete-confirm"
            data-author-id="%d"
            data-author-name="%s"
            data-story-count="%d">
            <i class="fas fa-trash me-1"></i> Delete
        </a>',
        $item['id'],
        htmlspecialchars($item['name']),
        $storyCount
    );
};

// Include the generic list template
require_once __DIR__ . '/../generic/list.php';
?>

<!-- Modal -->
<div class="modal fade" id="deleteAuthorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Author</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this author?</p>
                <div id="storyOptions" style="display: none;">
                    <p>This author has <span id="storyCount"></span> associated stories. Please choose how to handle them:</p>
                    <form id="deleteForm">
                        <input type="hidden" name="id" id="authorId">
                        <div class="form-check mb-3">
                            <input type="radio" id="delete_all" name="action" value="delete_all" class="form-check-input">
                            <label for="delete_all" class="form-check-label">
                                Delete all associated stories
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="radio" id="reassign" name="action" value="reassign" class="form-check-input">
                            <label for="reassign" class="form-check-label">
                                Reassign stories to another author:
                            </label>
                            <select name="new_author_id" class="form-control mt-2" id="new_author_select" disabled>
                                <option value="">Select an author</option>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input type="radio" id="cancel" name="action" value="cancel" class="form-check-input" checked>
                            <label for="cancel" class="form-check-label">
                                Cancel deletion
                            </label>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Bootstrap Modal CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Include jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Include author delete script -->
<script src="../admin/js/author-delete.js"></script>

<script>
// Override the default delete behavior
document.addEventListener('DOMContentLoaded', function() {
    // Remove existing click handlers
    $('.delete-confirm').off('click');

    // Add our custom handler
    $('.delete-confirm').on('click', function(e) {
        e.preventDefault();
        
        const authorId = $(this).data('author-id');
        const authorName = $(this).data('author-name');
        const storyCount = $(this).data('story-count');
        
        showDeleteAuthorModal(authorId, authorName, storyCount);
    });
});
</script>

<script>
// Initialize modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('deleteAuthorModal'));
    const deleteForm = document.getElementById('deleteForm');
    const storyOptions = document.getElementById('storyOptions');
    const storyCountSpan = document.getElementById('storyCount');
    const authorIdInput = document.getElementById('authorId');
    const newAuthorSelect = document.getElementById('new_author_select');

    // Handle radio button changes
    document.querySelectorAll('input[name="action"]').forEach(radio => {
        radio.addEventListener('change', function() {
            newAuthorSelect.disabled = this.value !== 'reassign';
        });
    });

    // Handle delete button clicks
    document.querySelectorAll('.delete-confirm').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const authorId = this.dataset.authorId;
            const authorName = this.dataset.authorName;
            const storyCount = parseInt(this.dataset.storyCount);

            authorIdInput.value = authorId;

            if (storyCount > 0) {
                storyCountSpan.textContent = storyCount;
                storyOptions.style.display = 'block';
                
                // Load other authors
                fetch(`../admin/content/get-authors.php?exclude=${authorId}`)
                    .then(response => response.json())
                    .then(authors => {
                        newAuthorSelect.innerHTML = '<option value="">Select an author</option>';
                        authors.forEach(author => {
                            const option = document.createElement('option');
                            option.value = author.id;
                            option.textContent = author.name;
                            newAuthorSelect.appendChild(option);
                        });
                    });
            } else {
                storyOptions.style.display = 'none';
            }

            modal.show();
        });
    });

    // Handle confirm delete
    document.getElementById('confirmDelete').addEventListener('click', function() {
        const action = document.querySelector('input[name="action"]:checked').value;
        
        if (action === 'cancel') {
            modal.hide();
            return;
        }

        if (action === 'reassign' && !newAuthorSelect.value) {
            alert('Please select an author to reassign the stories to.');
            return;
        }

        const formData = new FormData(deleteForm);
        
        fetch('../admin/content/delete-author.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.location.reload();
            } else {
                alert(result.error || 'Failed to delete author');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete author');
        });
    });
});
</script>
?>