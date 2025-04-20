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

    // Check if blog_posts table exists
    $blogTableName = 'blog_posts';
    $stmt = $db->query("SHOW TABLES LIKE 'blog_posts'");
    if ($stmt->rowCount() === 0) {
        // Check if blog table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog'");
        if ($stmt->rowCount() > 0) {
            $blogTableName = 'blog';
        } else {
            // Create blog_posts table if neither exists
            $db->exec("CREATE TABLE IF NOT EXISTS blog_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                author_id INT NOT NULL,
                content TEXT NOT NULL,
                excerpt TEXT,
                status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )");
        }
    }

    // Check if post_tags table exists
    $postTagsTableName = 'post_tags';
    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
    if ($stmt->rowCount() === 0) {
        // Check if blog_tags table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog_tags'");
        if ($stmt->rowCount() > 0) {
            $postTagsTableName = 'blog_tags';
        } else {
            // Create post_tags table if neither exists
            $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
                post_id INT NOT NULL,
                tag_id INT NOT NULL,
                PRIMARY KEY (post_id, tag_id)
            )");
        }
    }

    // Get all columns from the blog table
    $columns = [];
    $stmt = $db->query("DESCRIBE $blogTableName");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Check if author_id column exists
    $hasAuthorIdColumn = in_array('author_id', $columns);
    
    // Check if excerpt column exists
    $hasExcerptColumn = in_array('excerpt', $columns);
    
    // Check if status column exists
    $hasStatusColumn = in_array('status', $columns);

    // Get post if editing
    $post = null;
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM $blogTableName WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $post = $stmt->fetch();
        
        if (!$post) {
            header("Location: blog-posts.php");
            exit;
        }
    }

    // Get authors for dropdown
    $authors = $db->query("SELECT id, name FROM authors ORDER BY name")->fetchAll();

    // Get tags for dropdown
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get post tags if editing
    $postTags = [];
    if ($post) {
        $stmt = $db->prepare("SELECT tag_id FROM $postTagsTableName WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $postTags = array_column($stmt->fetchAll(), 'tag_id');
    }

} catch (PDOException $e) {
    error_log("Post form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Check for error messages
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
    <title><?php echo $post ? 'Edit' : 'Add'; ?> Blog Post - Admin</title>
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
            <h1><?php echo $post ? 'Edit' : 'Add'; ?> Blog Post</h1>
            <form method="GET" action="blog-posts.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Posts</button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="save-post.php" class="content-form">
            <?php if ($post): ?>
                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="title">Title</label>
                <input type="text" id="title" name="title" class="form-input" required
                       value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>">
            </div>

            <?php if ($hasAuthorIdColumn): ?>
            <div class="form-group">
                <label class="form-label" for="author_id">Author</label>
                <select id="author_id" name="author_id" class="form-input" required>
                    <option value="">Select Author</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?php echo $author['id']; ?>"
                                <?php echo isset($post['author_id']) && $post['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="content">Content</label>
                <textarea id="content" name="content" class="form-input" rows="10" required><?php 
                    echo htmlspecialchars($post['content'] ?? ''); 
                ?></textarea>
            </div>

            <?php if ($hasExcerptColumn): ?>
            <div class="form-group">
                <label class="form-label" for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" class="form-input" rows="3"><?php 
                    echo htmlspecialchars($post['excerpt'] ?? ''); 
                ?></textarea>
            </div>
            <?php endif; ?>

            <?php if ($hasStatusColumn): ?>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-input" required>
                    <option value="draft" <?php echo isset($post['status']) && $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo isset($post['status']) && $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Tags</label>
                <div class="checkbox-group">
                    <?php foreach ($tags as $tag): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>"
                                   <?php echo in_array($tag['id'], $postTags) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="form-submit">Save Post</button>
            </div>
        </form>
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
        .content-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</body>
</html>