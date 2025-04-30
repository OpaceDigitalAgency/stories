<?php
// Include the generic list template for basic structure
require_once __DIR__ . '/../generic/list.php';
?>

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

<?php
// Override the delete button in the generic template
$deleteButton = function($item) {
    // Get story count
    $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
    $stmt->execute([$item['id']]);
    $storyCount = $stmt->fetchColumn();

    return sprintf(
        '<a href="#" class="btn btn-sm btn-danger delete-confirm" 
            data-author-id="%d" 
            data-author-name="%s" 
            data-story-count="%d" 
            data-bs-toggle="tooltip" 
            title="Delete">
            <i class="fas fa-trash me-1"></i> Delete
        </a>',
        $item['id'],
        htmlspecialchars($item['name']),
        $storyCount
    );
};
?>