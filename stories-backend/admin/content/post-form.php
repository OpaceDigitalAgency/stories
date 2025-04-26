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
    $columnInfo = [];
    $stmt = $db->query("DESCRIBE $blogTableName");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
        $columnInfo[$row['Field']] = $row;
    }

    // Check if author_id column exists
    $hasAuthorIdColumn = in_array('author_id', $columns);
    
    // Check if excerpt column exists
    $hasExcerptColumn = in_array('excerpt', $columns);
    
    // Check if status column exists
    $hasStatusColumn = in_array('status', $columns);
    
    // Check if slug column exists
    $hasSlugColumn = in_array('slug', $columns);

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
    
    // Get additional fields from the database
    $additionalFields = [];
    foreach ($columns as $column) {
        if (!in_array($column, ['id', 'title', 'author_id', 'content', 'excerpt', 'status', 'created_at', 'updated_at'])) {
            $additionalFields[] = $column;
        }
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

        <div class="form-info">
            <p><strong>Required fields:</strong> Title<?php echo $hasAuthorIdColumn ? ', Author' : ''; ?>, Content</p>
        </div>

        <form method="POST" action="save-post.php" class="content-form">
            <?php if ($post): ?>
                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-input" required
                       value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>">
            </div>

            <?php if ($hasSlugColumn): ?>
            <div class="form-group">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="form-input"
                       value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>"
                       placeholder="post-title-in-lowercase">
                <small>Use lowercase letters, numbers, and hyphens only. No spaces. Will be auto-generated from title if left empty.</small>
            </div>
            <?php endif; ?>

            <?php if ($hasAuthorIdColumn): ?>
            <div class="form-group">
                <label class="form-label" for="author_id">Author <span class="required">*</span></label>
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
                <label class="form-label" for="content">Content <span class="required">*</span></label>
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
                <small>A short summary of the post. If left empty, it will be auto-generated from the content.</small>
            </div>
            <?php endif; ?>

            <div class="form-group checkbox-field">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_published" value="1"
                           <?php echo (!isset($post['is_published']) || $post['is_published'] == 1) ? 'checked' : ''; ?>>
                    Published
                </label>
            </div>
            
            <?php foreach ($additionalFields as $field): ?>
                <div class="form-group">
                    <?php 
                    $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                    $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                    ?>
                    <label class="form-label" for="<?php echo $field; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                        <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                    </label>
                    
                    <?php if ($isDateTime): ?>
                        <input type="datetime-local" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo isset($post[$field]) ? date('Y-m-d\TH:i', strtotime($post[$field])) : date('Y-m-d\TH:i'); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                        <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
                    <?php else: ?>
                        <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo htmlspecialchars($post[$field] ?? ''); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

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
        
        <?php if ($post): ?>
            <div class="form-metadata">
                <p>Created: <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?></p>
                <p>Last Updated: <?php echo date('M j, Y g:i A', strtotime($post['updated_at'])); ?></p>
                <p>ID: <?php echo $post['id']; ?></p>
            </div>
        <?php endif; ?>
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
            margin-bottom: 20px;
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
        .form-metadata {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        .form-metadata p {
            margin: 5px 0;
        }
        .form-info {
            background: #e7f3ff;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .form-info p {
            margin: 5px 0;
        }
        .required {
            color: #dc3545;
            margin-left: 3px;
        }
        small {
            color: #666;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
    </style>
    <script>
        // Auto-generate slug from title
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            const excerptInput = document.getElementById('excerpt');
            const contentInput = document.getElementById('content');
            
            if (titleInput && slugInput) {
                titleInput.addEventListener('input', function() {
                    // Only auto-generate if slug is empty or hasn't been manually edited
                    if (!slugInput.value || slugInput._autoGenerated) {
                        const slug = titleInput.value
                            .toLowerCase()
                            .replace(/[^\w\s-]/g, '') // Remove special characters
                            .replace(/\s+/g, '-')     // Replace spaces with hyphens
                            .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen
                        
                        slugInput.value = slug;
                        slugInput._autoGenerated = true;
                    }
                });
                
                // Mark when user manually edits the slug
                slugInput.addEventListener('input', function() {
                    slugInput._autoGenerated = false;
                });
            }
            
            // Auto-generate excerpt from content
            if (contentInput && excerptInput) {
                contentInput.addEventListener('input', function() {
                    // Only auto-generate if excerpt is empty or hasn't been manually edited
                    if (!excerptInput.value || excerptInput._autoGenerated) {
                        // Get first 150 characters of content
                        let excerpt = contentInput.value.replace(/<[^>]*>/g, '').trim();
                        if (excerpt.length > 150) {
                            excerpt = excerpt.substring(0, 150) + '...';
                        }
                        
                        excerptInput.value = excerpt;
                        excerptInput._autoGenerated = true;
                    }
                });
                
                // Mark when user manually edits the excerpt
                excerptInput.addEventListener('input', function() {
                    excerptInput._autoGenerated = false;
                });
            }
        });
    </script>
</body>
</html>