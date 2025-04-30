<?php
// Start output buffering to capture the generic list template
ob_start();
require_once __DIR__ . '/../generic/list.php';
$genericList = ob_get_clean();

// Output the generic list without modifications
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

<script>
// Use jQuery since it's already loaded in the admin template
$(document).ready(function() {
    // Initialize the modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteAuthorModal'));

    // Override the default delete confirmation
    $('.delete-confirm').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Get the author ID from the href
        var href = $(this).attr('href');
        var authorId = href.match(/id=(\d+)/)[1];

        // Get author name
        var authorName = $(this).closest('tr').find('td:first').text().trim();

        // Get story count via AJAX
        $.ajax({
            url: '../admin/content/get-author-story-count.php',
            type: 'GET',
            data: { id: authorId },
            dataType: 'json',
            success: function(response) {
                var storyCount = response.count || 0;

                // Set modal content
                $('#authorName').text(authorName);
                $('#authorId').val(authorId);

                if (storyCount > 0) {
                    $('#storyCount').text(storyCount);
                    $('#storyOptions').show();

                    // Load other authors
                    $.ajax({
                        url: '../admin/content/get-authors.php',
                        type: 'GET',
                        data: { exclude: authorId },
                        dataType: 'json',
                        success: function(authors) {
                            var select = $('#new_author_select');
                            select.empty().append('<option value="">Select an author</option>');

                            $.each(authors, function(i, author) {
                                select.append($('<option></option>').val(author.id).text(author.name));
                            });
                        }
                    });
                } else {
                    $('#storyOptions').hide();
                }

                // Show the modal
                deleteModal.show();
            }
        });

        return false;
    });

    // Handle radio button changes
    $('input[name="action"]').on('change', function() {
        $('#new_author_select').prop('disabled', $(this).val() !== 'reassign');
    });

    // Handle confirm delete
    $('#confirmDelete').on('click', function() {
        var action = $('input[name="action"]:checked').val();

        if (action === 'cancel') {
            deleteModal.hide();
            return;
        }

        if (action === 'reassign' && !$('#new_author_select').val()) {
            alert('Please select an author to reassign the stories to.');
            return;
        }

        // Submit the form via AJAX
        $.ajax({
            url: '../admin/content/delete-author.php',
            type: 'POST',
            data: $('#deleteForm').serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.error || 'Failed to delete author');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Failed to delete author: ' + error);
            }
        });
    });
});
</script>

