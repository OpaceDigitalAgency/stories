function showDeleteAuthorModal(authorId, authorName, storyCount) {
    // Create modal HTML
    const modalHtml = `
        <div class="modal fade" id="deleteAuthorModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Author</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the author "${authorName}"?</p>
                        ${storyCount > 0 ? `
                            <div class="alert alert-warning">
                                <p>This author has ${storyCount} associated stories. Please choose how to handle them:</p>
                                
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
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" onclick="deleteAuthor(${authorId})">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Initialize modal
    const modal = $('#deleteAuthorModal');
    
    // Load other authors for reassignment if needed
    if (storyCount > 0) {
        fetch('../admin/content/get-authors.php?exclude=' + authorId)
            .then(response => response.json())
            .then(authors => {
                const select = document.getElementById('new_author_select');
                authors.forEach(author => {
                    const option = document.createElement('option');
                    option.value = author.id;
                    option.textContent = author.name;
                    select.appendChild(option);
                });
            });

        // Handle radio button changes
        document.querySelectorAll('input[name="action"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('new_author_select').disabled = this.value !== 'reassign';
            });
        });
    }

    // Show modal
    modal.modal('show');

    // Remove modal from DOM when hidden
    modal.on('hidden.bs.modal', function() {
        modal.remove();
    });
}

function deleteAuthor(authorId) {
    const action = document.querySelector('input[name="action"]:checked')?.value || 'delete_all';
    const newAuthorId = action === 'reassign' ? document.getElementById('new_author_select').value : null;

    if (action === 'cancel') {
        $('#deleteAuthorModal').modal('hide');
        return;
    }

    if (action === 'reassign' && !newAuthorId) {
        alert('Please select an author to reassign the stories to.');
        return;
    }

    // Send delete request
    fetch('../admin/content/delete-author.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${authorId}&action=${action}${newAuthorId ? `&new_author_id=${newAuthorId}` : ''}`
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
}