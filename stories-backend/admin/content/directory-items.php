<?php
require_once '../includes/auth.php';
$auth = new Auth($db);
$auth->requireLogin();

// Get action from URL
$action = $_GET['action'] ?? 'list';

// Define valid directory categories
$directoryCategories = [
    'publisher' => 'Publishers',
    'writing_tool' => 'Writing Tools',
    'community' => 'Writing Communities',
    'resource' => 'Writing Resources',
    'education' => 'Educational Resources',
    'other' => 'Other'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    
    switch ($_POST['action']) {
        case 'create':
            // Validate URL format
            if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
                $message = '<div class="error">Please enter a valid URL</div>';
            } else {
                $stmt = $db->prepare("INSERT INTO directory_items (name, description, url, category) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['url'],
                    $_POST['category']
                ])) {
                    $message = '<div class="success">Directory item created successfully</div>';
                    header('Location: /admin/content/directory-items.php?message=' . urlencode($message));
                    exit;
                }
            }
            break;
            
        case 'update':
            // Validate URL format
            if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
                $message = '<div class="error">Please enter a valid URL</div>';
            } else {
                $stmt = $db->prepare("UPDATE directory_items SET name = ?, description = ?, url = ?, category = ? WHERE id = ?");
                if ($stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['url'],
                    $_POST['category'],
                    $_POST['id']
                ])) {
                    $message = '<div class="success">Directory item updated successfully</div>';
                    header('Location: /admin/content/directory-items.php?message=' . urlencode($message));
                    exit;
                }
            }
            break;
            
        case 'delete':
            $stmt = $db->prepare("DELETE FROM directory_items WHERE id = ?");
            if ($stmt->execute([$_POST['id']])) {
                $message = '<div class="success">Directory item deleted successfully</div>';
                header('Location: /admin/content/directory-items.php?message=' . urlencode($message));
                exit;
            }
            break;
    }
    
    if (!$message) {
        $message = '<div class="error">Operation failed</div>';
    }
}

// Get directory item for edit form
$item = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM directory_items WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        header('Location: /admin/content/directory-items.php?message=' . urlencode('<div class="error">Directory item not found</div>'));
        exit;
    }
}

// Get all directory items for list view
$items = [];
if ($action === 'list') {
    $stmt = $db->query("SELECT * FROM directory_items ORDER BY category ASC, name ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Directory - Admin</title>
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
                <h1 class="card-title">Directory Items</h1>
                <a href="?action=add" class="form-submit" style="display: inline-block; margin-bottom: 20px;">Add New Item</a>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $currentCategory = '';
                        foreach ($items as $item): 
                            // Add category header row when category changes
                            if ($item['category'] !== $currentCategory):
                                $currentCategory = $item['category'];
                        ?>
                            <tr>
                                <td colspan="3" style="background: #f8f9fa; font-weight: bold;">
                                    <?php echo htmlspecialchars($directoryCategories[$currentCategory] ?? $currentCategory); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($directoryCategories[$item['category']] ?? $item['category']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank" class="form-submit" style="background: #28a745;">Visit</a>
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
                <h1 class="card-title"><?php echo $action === 'add' ? 'Add New Directory Item' : 'Edit Directory Item'; ?></h1>
                
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'create' : 'update'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="name">Name</label>
                        <input type="text" id="name" name="name" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($item['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="5"><?php 
                            echo $action === 'edit' ? htmlspecialchars($item['description']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="url">Website URL</label>
                        <input type="url" id="url" name="url" class="form-input" required 
                               value="<?php echo $action === 'edit' ? htmlspecialchars($item['url']) : ''; ?>">
                        <small class="form-help">Full URL including https://</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="category">Category</label>
                        <select id="category" name="category" class="form-input" required>
                            <?php foreach ($directoryCategories as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php 
                                    echo ($action === 'edit' && $item['category'] === $value) ? 'selected' : ''; 
                                ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="form-submit">Save Directory Item</button>
                    <a href="/admin/content/directory-items.php" class="form-submit" style="background: #6c757d;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>