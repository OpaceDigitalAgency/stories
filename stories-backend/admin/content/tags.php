<?php
require_once '../../simple_auth.php';

// Database configuration
$config = [
    'host' => 'localhost',
    'name' => 'stories_db',
    'user' => 'stories_user',
    'password' => '$tw1cac3*sOt',
    'charset' => 'utf8mb4',
    'port' => 3306
];

// Initialize SimpleAuth
SimpleAuth::initDB($config);

// Check if user is logged in
if (!$user = SimpleAuth::check()) {
    header("Location: ../login.php");
    exit;
}

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

    // Check if tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    if ($stmt->rowCount() === 0) {
        // Create tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Check if story_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
    if ($stmt->rowCount() === 0) {
        // Create story_tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS story_tags (
            story_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (story_id, tag_id)
        )");
    }

    // Check if post_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
    if ($stmt->rowCount() === 0) {
        // Create post_tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (post_id, tag_id)
        )");
    }

    // Get all tags with usage counts
    $query = "SELECT t.*, 
              (SELECT COUNT(*) FROM story_tags WHERE tag_id = t.id) as story_count,
              (SELECT COUNT(*) FROM post_tags WHERE tag_id = t.id) as post_count
              FROM tags t 
              ORDER BY t.name ASC";
    $tags = $db->query($query)->fetchAll();

} catch (PDOException $e) {
    error_log("Tags page error: " . $e->getMessage());
    $error = "Error loading tags. Please try again.";
    $tags = [];
}

// Check for success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <div class="container">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['name']); ?> |
            <form method="POST" action="../logout.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #dc3545;">Logout</button>
            </form>
        </div>

        <nav class="nav-menu">
            <form method="GET" style="display: inline;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="content-header">
            <h1>Tags</h1>
            <form method="GET" action="tag-form.php" style="display: inline;">
                <button type="submit" class="form-submit">Add New Tag</button>
            </form>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Stories</th>
                    <th>Blog Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tags)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No tags found. Add your first tag!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tag['name']); ?></td>
                            <td><?php echo htmlspecialchars($tag['slug']); ?></td>
                            <td><?php echo htmlspecialchars(substr($tag['description'] ?? '', 0, 100) . (strlen($tag['description'] ?? '') > 100 ? '...' : '')); ?></td>
                            <td><?php echo $tag['story_count']; ?></td>
                            <td><?php echo $tag['post_count']; ?></td>
                            <td>
                                <form method="GET" action="tag-form.php" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                    <button type="submit" class="form-submit">Edit</button>
                                </form>
                                <?php if ($tag['story_count'] == 0 && $tag['post_count'] == 0): ?>
                                    <form method="POST" action="delete-tag.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $tag['id']; ?>">
                                        <button type="submit" class="form-submit" style="background: #dc3545;"
                                                onclick="return confirm('Are you sure you want to delete this tag?')">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <style>
        .nav-link {
            background: none;
            border: none;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .nav-link:hover {
            background: #f5f5f5;
        }
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .content-header h1 {
            margin: 0;
        }
        .text-center {
            text-align: center;
            padding: 20px;
        }
    </style>
</body>
</html>