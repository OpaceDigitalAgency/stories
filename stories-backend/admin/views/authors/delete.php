<?php
// Get author details
$authorId = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT name FROM authors WHERE id = ?");
$stmt->execute([$authorId]);
$author = $stmt->fetch();

// Get story count
$stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
$stmt->execute([$authorId]);
$storyCount = $stmt->fetchColumn();

// Get other authors for reassignment
$stmt = $db->prepare("SELECT id, name FROM authors WHERE id != ? ORDER BY name");
$stmt->execute([$authorId]);
$otherAuthors = $stmt->fetchAll();
?>

<div class="container">
    <h1>Delete Author</h1>
    
    <?php if ($author): ?>
        <p>Are you sure you want to delete the author "<?php echo htmlspecialchars($author['name']); ?>"?</p>
        
        <?php if ($storyCount > 0): ?>
            <div class="alert alert-warning">
                <p>This author has <?php echo $storyCount; ?> associated stories. Please choose how to handle them:</p>
                
                <form action="content/delete-author.php" method="post" class="mt-3">
                    <input type="hidden" name="id" value="<?php echo $authorId; ?>">
                    
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
                            <?php foreach ($otherAuthors as $otherAuthor): ?>
                                <option value="<?php echo $otherAuthor['id']; ?>">
                                    <?php echo htmlspecialchars($otherAuthor['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="radio" id="cancel" name="action" value="cancel" class="form-check-input" checked>
                        <label for="cancel" class="form-check-label">
                            Cancel deletion
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">Confirm</button>
                    <a href="authors.php" class="btn btn-secondary">Back</a>
                </form>
            </div>
            
            <script>
            document.querySelectorAll('input[name="action"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    document.getElementById('new_author_select').disabled = this.value !== 'reassign';
                });
            });
            </script>
        <?php else: ?>
            <form action="content/delete-author.php" method="post">
                <input type="hidden" name="id" value="<?php echo $authorId; ?>">
                <input type="hidden" name="action" value="delete_all">
                <button type="submit" class="btn btn-danger">Delete Author</button>
                <a href="authors.php" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-danger">Author not found.</div>
        <a href="authors.php" class="btn btn-secondary">Back</a>
    <?php endif; ?>
</div>