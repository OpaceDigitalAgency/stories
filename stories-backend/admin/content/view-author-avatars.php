<?php
// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Page variables
$pageTitle = 'View Author Avatars';
$currentPage = 'view-author-avatars';

// Include header
require_once '../includes/header.php';

// Get all authors
$stmt = $db->query("SELECT id, name, avatar_url FROM authors ORDER BY id DESC LIMIT 20");
$authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-section mb-4">
    <div class="section-header">
        <h2 class="section-title">Author Avatars</h2>
        <p class="text-muted">Showing the latest 20 authors and their avatar URLs</p>
    </div>
    <div class="section-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Avatar URL</th>
                    <th>Avatar Preview</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($authors as $author): ?>
                <tr>
                    <td><?php echo $author['id']; ?></td>
                    <td><?php echo htmlspecialchars($author['name']); ?></td>
                    <td>
                        <?php if (empty($author['avatar_url'])): ?>
                            <span class="text-muted">NULL</span>
                        <?php else: ?>
                            <code><?php echo htmlspecialchars($author['avatar_url']); ?></code>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($author['avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($author['avatar_url']); ?>" alt="Avatar" style="max-width: 100px; max-height: 100px;">
                        <?php else: ?>
                            <span class="text-muted">No image</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="author-form.php?id=<?php echo $author['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="test-update-avatar.php?id=<?php echo $author['id']; ?>&url=https://api.storiesfromtheweb.org/uploads/optimized/test-image-<?php echo $author['id']; ?>.jpg" class="btn btn-sm btn-warning">Test Update</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>
