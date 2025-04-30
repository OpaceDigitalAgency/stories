<div class="container">
    <?php
    // Start output buffering to capture the generic list template
    ob_start();
    require_once __DIR__ . '/../generic/list.php';
    $genericList = ob_get_clean();

    // Replace the default delete button with our custom one
    $customDeleteButtons = '';
    foreach ($items as $item) {
        // Get story count
        $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
        $stmt->execute([$item['id']]);
        $storyCount = $stmt->fetchColumn();

        $customDeleteButtons .= sprintf(
            '<button type="button" class="btn btn-sm btn-danger delete-author"
                data-bs-toggle="modal"
                data-bs-target="#deleteAuthorModal"
                data-author-id="%d"
                data-author-name="%s"
                data-story-count="%d">
                <i class="fas fa-trash me-1"></i> Delete
            </button>',
            $item['id'],
            htmlspecialchars($item['name']),
            $storyCount
        );
    }

    // Replace delete buttons in the generic list
    $pattern = '/<a[^>]*class="[^"]*\bdelete-confirm\b[^"]*"[^>]*>.*?<\/a>/s';
    $genericList = preg_replace($pattern, $customDeleteButtons, $genericList);

    // Output the modified list
    echo $genericList;
    ?>

    <!-- Delete Author Modal -->
    <div class="modal fade" id="deleteAuthorModal" tabindex="-1" aria-labelledby="deleteAuthorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAuthorModalLabel">Delete Author</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <span id="authorName"></span>?</p>
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
</div>

<!-- Include Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Include Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Initialize modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the modal
    const modalElement = document.getElementById('deleteAuthorModal');
    const modal = new bootstrap.Modal(modalElement);

    // Get form elements
    const deleteForm = document.getElementById('deleteForm');
    const storyOptions = document.getElementById('storyOptions');
    const authorNameSpan = document.getElementById('authorName');
    const storyCountSpan = document.getElementById('storyCount');
    const authorIdInput = document.getElementById('authorId');
    const newAuthorSelect = document.getElementById('new_author_select');
    const confirmDeleteBtn = document.getElementById('confirmDelete');

    // Handle radio button changes
    document.querySelectorAll('input[name="action"]').forEach(radio => {
        radio.addEventListener('change', function() {
            newAuthorSelect.disabled = this.value !== 'reassign';
        });
    });

    // Handle delete button clicks
    document.querySelectorAll('.delete-author').forEach(button => {
        button.addEventListener('click', function() {
            const authorId = this.dataset.authorId;
            const authorName = this.dataset.authorName;
            const storyCount = parseInt(this.dataset.storyCount);

            // Set modal content
            authorNameSpan.textContent = authorName;
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
                    })
                    .catch(error => {
                        console.error('Error loading authors:', error);
                    });
            } else {
                storyOptions.style.display = 'none';
            }
        });
    });

    // Handle confirm delete
    confirmDeleteBtn.addEventListener('click', function() {
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

    // Override the default delete behavior
    const deleteConfirmButtons = document.querySelectorAll('.delete-confirm');
    deleteConfirmButtons.forEach(button => {
        // Remove default click handler
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Get the author ID from the href
            const href = button.getAttribute('href');
            const idMatch = href.match(/id=(\d+)/);
            if (idMatch && idMatch[1]) {
                const authorId = idMatch[1];

                // Find the corresponding delete-author button and click it
                const deleteAuthorBtn = document.querySelector(`.delete-author[data-author-id="${authorId}"]`);
                if (deleteAuthorBtn) {
                    deleteAuthorBtn.click();
                }
            }

            return false;
        });
    });
});
</script>

