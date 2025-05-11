<?php
/**
 * Fix Author Images - SIMPLIFIED VERSION
 *
 * This script allows direct editing of author avatar URLs
 */

// Include auth check
require_once '../includes/auth-check.php';

// Check if we have an author ID and image URL for direct update
if (isset($_GET['id']) && isset($_GET['url'])) {
    try {
        // Connect to database
        $db = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
            $config['user'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        // Update the author's avatar_url directly
        $stmt = $db->prepare("UPDATE authors SET avatar_url = ? WHERE id = ?");
        $result = $stmt->execute([$_GET['url'], $_GET['id']]);

        // Return JSON response
        header('Content-Type: application/json');
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => "Author {$_GET['id']} updated with image URL: {$_GET['url']}"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Failed to update author {$_GET['id']}"
            ]);
        }
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Error: " . $e->getMessage()
        ]);
        exit;
    }
}

// Include header for the main page view
require_once '../includes/header.php';

// Connect to database
$db = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
    $config['user'],
    $config['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
);

// Get all authors
$stmt = $db->query("SELECT id, name, slug, avatar_url FROM authors ORDER BY name");
$authors = $stmt->fetchAll();

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="content-header">
                <h1>Fix Author Images</h1>
                <p class="text-muted">Directly edit author avatar URLs</p>
            </div>

            <div class="alert alert-info">
                <strong>Instructions:</strong> Enter an image URL and click "Save" to update an author's avatar.
                Or click "Set Default Avatar" to use the default avatar image.
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Authors</h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Current Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($authors as $author): ?>
                                <tr>
                                    <td><?php echo $author['id']; ?></td>
                                    <td><?php echo htmlspecialchars($author['name']); ?></td>
                                    <td>
                                        <?php if (!empty($author['avatar_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($author['avatar_url']); ?>" alt="Avatar" style="max-width: 100px; max-height: 100px;">
                                            <div class="small text-muted"><?php echo htmlspecialchars($author['avatar_url']); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">No image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control image-url-input" placeholder="Enter image URL" value="<?php echo htmlspecialchars($author['avatar_url'] ?? ''); ?>">
                                            <button class="btn btn-primary save-image" data-author-id="<?php echo $author['id']; ?>">Save</button>
                                        </div>
                                        <a href="author-form.php?id=<?php echo $author['id']; ?>" class="btn btn-sm btn-secondary">Edit Author</a>
                                        <button class="btn btn-sm btn-info set-default-avatar" data-author-id="<?php echo $author['id']; ?>">Set Default Avatar</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Handle save button click
        $('.save-image').click(function() {
            const authorId = $(this).data('author-id');
            const imageUrl = $(this).closest('td').find('.image-url-input').val();

            // Make AJAX request to update the author
            $.ajax({
                url: 'fix-author-images.php',
                method: 'GET',
                data: {
                    id: authorId,
                    url: imageUrl
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Success: ' + response.message);
                        // Reload the page to show the updated image
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error: Failed to communicate with the server');
                }
            });
        });

        // Handle set default avatar button click
        $('.set-default-avatar').click(function() {
            const authorId = $(this).data('author-id');
            const defaultAvatarUrl = 'https://api.storiesfromtheweb.org/uploads/default-avatar.svg';

            // Set the URL in the input field
            $(this).closest('td').find('.image-url-input').val(defaultAvatarUrl);

            // Trigger the save button click
            $(this).closest('td').find('.save-image').click();
        });
    });
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
