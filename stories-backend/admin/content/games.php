<?php
require_once '../includes/auth.php';
$auth = new Auth($db);
$auth->requireLogin();

// Get action from URL
$action = $_GET['action'] ?? 'list';

// Define valid game categories
$gameCategories = [
    'educational' => 'Educational',
    'adventure' => 'Adventure',
    'puzzle' => 'Puzzle',
    'interactive' => 'Interactive Story',
    'other' => 'Other'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    
    switch ($_POST['action']) {
        case 'create':
            $stmt = $db->prepare("INSERT INTO games (title, slug, description, url, category) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([
                $_POST['title'],
                $_POST['slug'],
                $_POST['description'],
                $_POST['url'],
                $_POST['category']
            ])) {
                $message = '<div class="success">Game created successfully</div>';
                header('Location: /admin/content/games.php?message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'update':
            $stmt = $db->prepare("UPDATE games SET title = ?, slug = ?, description = ?, url = ?, category = ? WHERE id = ?");
            if ($stmt->execute([
                $_POST['title'],
                $_POST['slug'],
                $_POST['description'],
                $_POST['url'],
                $_POST['category'],
                $_POST['id']
            ])) {
                $message = '<div class="success">Game updated successfully</div>';
                header('Location: /admin/content/games.php?message=' . urlencode($message));
                exit;
            }
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM games WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                $message = '<div class="success">Game deleted successfully</div>';
                header('Location: /admin/content/games.php?message=' . urlencode($message));
                exit;
            }
            break;
    }
    
    if (!$message) {
        $message = '<div class="error">Operation failed</div>';
    }
}

// Get game for edit form
$game = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game) {
        header('Location: /admin/content/games.php?message=' . urlencode('<div class="error">Game not found</div>'));
        exit;
    }
}

// Get all games for list view
$games = [];
if ($action === 'list') {
    $stmt = $db->query("SELECT * FROM games ORDER BY title ASC");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Games - Admin</title>
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
                <h1 class="card-title">Games</h1>
                <a href="?action=add" class="form-submit" style="display: inline-block; margin-bottom: 20px;">Add New Game</a>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($games as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars($gameCategories[$item['category']] ?? $item['category']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank" class="form-submit" style="background: #28a745;">View</a>
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
                <h1 class="card-title"><?php echo $action === 'add' ? 'Add New Game' : 'Edit Game'; ?></h1>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $game['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($game['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($game['slug']) : ''; ?>">
                        <small class="form-help">URL-friendly version of title (lowercase, no spaces)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="5"><?php 
                            echo $action === 'edit' ? htmlspecialchars($game['description']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="url">Game URL</label>
                        <input type="url" id="url" name="url" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($game['url']) : ''; ?>">
                        <small class="form-help">Full URL where the game can be played</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="category">Category</label>
                        <select id="category" name="category" class="form-input" required>
                            <?php foreach ($gameCategories as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php 
                                    echo ($action === 'edit' && $game['category'] === $value) ? 'selected' : ''; 
                                ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="form-submit">Save Game</button>
                    <a href="/admin/content/games.php" class="form-submit" style="background: #6c757d;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>