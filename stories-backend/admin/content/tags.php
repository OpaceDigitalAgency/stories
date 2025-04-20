<?php
require_once '../includes/auth.php';
$auth = new Auth($db);
$auth->requireLogin();

// Get action from URL
$action = $_GET['action'] ?? 'list';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    
    switch ($_POST['action']) {
        case 'create':
            // Check if tag already exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM tags WHERE name = ? OR slug = ?");
            $stmt->execute([$_POST['name'], $_POST['slug']]);
            if ($stmt->fetchColumn() > 0) {
                $message = '<div class="error">A tag with this name or slug already exists</div>';
            } else {
                $stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                if ($stmt->execute([
                    $_POST['name'],
                    $_POST['slug']
                ])) {
                    $message = '<div class="success">Tag created successfully</div>';
                    header('Location: /admin/content/tags.php?message=' . urlencode($message));
                    exit;
                }
            }
            break;
            
        case 'update':
            // Check if new name/slug conflicts with other tags
            $stmt = $db->prepare("SELECT COUNT(*) FROM tags WHERE (name = ? OR slug = ?) AND id != ?");
            $stmt->execute([$_POST['name'], $_POST['slug'], $_POST['id']]);
            if ($stmt->fetchColumn() > 0) {
                $message = '<div class="error">Another tag with this name or slug already exists</div>';
            } else {
                $stmt = $db->prepare("UPDATE tags SET name = ?, slug = ? WHERE id = ?");
                if ($stmt->execute([
                    $_POST['name'],
                    $_POST['slug'],
                    $_POST['id']
                ])) {
                    $message = '<div class="success">Tag updated successfully</div>';
                    header('Location: /admin/content/tags.php?message=' . urlencode($message));
                    exit;
                }
            }
            break;
            
        case 'delete':
            // Check if tag is in use
            $stmt = $db->prepare("SELECT COUNT(*) FROM story_tags WHERE tag_id = ?");
            $stmt->execute([$_POST['id']]);
            if ($stmt->fetchColumn() > 0) {
                $message = '<div class="error">Cannot delete tag that is assigned to stories</div>';
            } else {
                $stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    $message = '<div class="success">Tag deleted successfully</div>';
                    header('Location: /admin/content/tags.php?message=' . urlencode($message));
                    exit;
                }
            }
            break;
    }
    
    if (!$message) {
        $message = '<div class="error">Operation failed</div>';
    }
}

// Get tag for edit form
$tag = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM tags WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tag) {
        header('Location: /admin/content/tags.php?message=' . urlencode('<div class="error">Tag not found</div>'));
        exit;
    }
}

// Get all tags for list view
$tags = [];
if ($action === 'list') {
    $stmt = $db->query("SELECT t.*, COUNT(st.story_id) as story_count 
                       FROM tags t 
                       LEFT JOIN story_tags st ON t.id = st.tag_id 
                       GROUP BY t.id 
                       ORDER BY t.name ASC");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tags - Admin</title>
    <link rel="stylesheet" href="/admin/assets/css/main.css">
</head>
<body>
    <nav class="nav">
        <ul class="nav-list">
            <li class="nav-item"><a href="/admin/index.php" class="nav-link">Dashboard</a></li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link">Content</a>
                <div class="dropdown-content">
                    <a href="/admin/content/stories.php" class="nav-link">Stories</a>
                    <a href="/admin/content/blog-posts.php" class="nav-link">Blog Posts</a>
                    <a href="/admin/content/games.php" class="nav-link">Games</a>
                </div>
            </li>
            <li class="nav-item"><a href="/admin/logout.php" class="nav-link">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if (isset($_GET['message'])): ?>
            <?php echo $_GET['message']; ?>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="card">
                <h1 class="card-title">Tags</h1>
                <a href="?action=add" class="form-submit" style="display: inline-block; margin-bottom: 20px;">Add New Tag</a>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Stories</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tags as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['slug']); ?></td>
                                <td><?php echo (int)$item['story_count']; ?></td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $item['id']; ?>" class="form-submit">Edit</a>
                                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="form-submit" style="background: #dc3545;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <div class="card">
                <h1 class="card-title"><?php echo $action === 'add' ? 'Add New Tag' : 'Edit Tag'; ?></h1>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($tag['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($tag['slug']) : ''; ?>">
                        <small class="form-help">URL-friendly version of name (lowercase, no spaces)</small>
                    </div>
                    
                    <button type="submit" class="form-submit">Save Tag</button>
                    <a href="/admin/content/tags.php" class="form-submit" style="background: #6c757d;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>