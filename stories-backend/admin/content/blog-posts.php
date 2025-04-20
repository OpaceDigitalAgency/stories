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
            $stmt = $db->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, published_at) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([
                $_POST['title'],
                $_POST['slug'],
                $_POST['excerpt'],
                $_POST['content'],
                $_POST['published_at']
            ])) {
                $message = '<div class="success">Blog post created successfully</div>';
                header('Location: /admin/content/blog-posts.php?message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'update':
            $stmt = $db->prepare("UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, published_at = ? WHERE id = ?");
            if ($stmt->execute([
                $_POST['title'],
                $_POST['slug'],
                $_POST['excerpt'],
                $_POST['content'],
                $_POST['published_at'],
                $_POST['id']
            ])) {
                $message = '<div class="success">Blog post updated successfully</div>';
                header('Location: /admin/content/blog-posts.php?message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                $message = '<div class="success">Blog post deleted successfully</div>';
                header('Location: /admin/content/blog-posts.php?message=' . urlencode($message));
                exit;
            }
            break;
    }
    
    if (!$message) {
        $message = '<div class="error">Operation failed</div>';
    }
}

// Get blog post for edit form
$post = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        header('Location: /admin/content/blog-posts.php?message=' . urlencode('<div class="error">Blog post not found</div>'));
        exit;
    }
}

// Get all blog posts for list view
$posts = [];
if ($action === 'list') {
    $stmt = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blog Posts - Admin</title>
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
                <h1 class="card-title">Blog Posts</h1>
                <a href="?action=add" class="form-submit" style="display: inline-block; margin-bottom: 20px;">Add New Blog Post</a>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Published</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($item['published_at']); ?></td>
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
                <h1 class="card-title"><?php echo $action === 'add' ? 'Add New Blog Post' : 'Edit Blog Post'; ?></h1>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($post['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($post['slug']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="excerpt">Excerpt</label>
                        <textarea id="excerpt" name="excerpt" class="form-input" rows="3"><?php 
                            echo $action === 'edit' ? htmlspecialchars($post['excerpt']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="content">Content</label>
                        <textarea id="content" name="content" class="form-input" rows="10" required><?php 
                            echo $action === 'edit' ? htmlspecialchars($post['content']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="published_at">Publish Date</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="form-input" required
                               value="<?php echo $action === 'edit' ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="form-submit">Save Blog Post</button>
                    <a href="/admin/content/blog-posts.php" class="form-submit" style="background: #6c757d;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>