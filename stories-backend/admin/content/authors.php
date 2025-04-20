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
            $stmt = $db->prepare("INSERT INTO authors (name, slug, bio, featured, twitter, instagram, website) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([
                $_POST['name'],
                $_POST['slug'],
                $_POST['bio'],
                isset($_POST['featured']) ? 1 : 0,
                $_POST['twitter'],
                $_POST['instagram'],
                $_POST['website']
            ])) {
                $message = '<div class="success">Author created successfully</div>';
                header('Location: /admin/content/authors.php?message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'update':
            $stmt = $db->prepare("UPDATE authors SET name = ?, slug = ?, bio = ?, featured = ?, twitter = ?, instagram = ?, website = ? WHERE id = ?");
            if ($stmt->execute([
                $_POST['name'],
                $_POST['slug'],
                $_POST['bio'],
                isset($_POST['featured']) ? 1 : 0,
                $_POST['twitter'],
                $_POST['instagram'],
                $_POST['website'],
                $_POST['id']
            ])) {
                $message = '<div class="success">Author updated successfully</div>';
                header('Location: /admin/content/authors.php?message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'delete':
            // First check if author has any stories
            $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
            $stmt->execute([$_POST['id']]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $message = '<div class="error">Cannot delete author with associated stories</div>';
            } else {
                $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
                if ($stmt->execute([$_POST['id']])) {
                    $message = '<div class="success">Author deleted successfully</div>';
                    header('Location: /admin/content/authors.php?message=' . urlencode($message));
                    exit;
                }
            }
            break;
    }
    
    if (!$message) {
        $message = '<div class="error">Operation failed</div>';
    }
}

// Get author for edit form
$author = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $author = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$author) {
        header('Location: /admin/content/authors.php?message=' . urlencode('<div class="error">Author not found</div>'));
        exit;
    }
}

// Get all authors for list view
$authors = [];
if ($action === 'list') {
    $stmt = $db->query("SELECT a.*, COUNT(sa.story_id) as story_count 
                       FROM authors a 
                       LEFT JOIN story_authors sa ON a.id = sa.author_id 
                       GROUP BY a.id 
                       ORDER BY a.name ASC");
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Authors - Admin</title>
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
                <h1 class="card-title">Authors</h1>
                <a href="?action=add" class="form-submit" style="display: inline-block; margin-bottom: 20px;">Add New Author</a>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Stories</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($authors as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo (int)$item['story_count']; ?></td>
                                <td><?php echo $item['featured'] ? 'Yes' : 'No'; ?></td>
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
                <h1 class="card-title"><?php echo $action === 'add' ? 'Add New Author' : 'Edit Author'; ?></h1>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($author['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($author['slug']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="bio">Biography</label>
                        <textarea id="bio" name="bio" class="form-input" rows="5"><?php 
                            echo $action === 'edit' ? htmlspecialchars($author['bio']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="featured" value="1" 
                                   <?php echo ($action === 'edit' && $author['featured']) ? 'checked' : ''; ?>>
                            Featured Author
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="twitter">Twitter</label>
                        <input type="url" id="twitter" name="twitter" class="form-input" 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($author['twitter']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="instagram">Instagram</label>
                        <input type="url" id="instagram" name="instagram" class="form-input" 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($author['instagram']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="website">Website</label>
                        <input type="url" id="website" name="website" class="form-input" 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($author['website']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="form-submit">Save Author</button>
                    <a href="/admin/content/authors.php" class="form-submit" style="background: #6c757d;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>