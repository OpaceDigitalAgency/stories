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

// Initialize variables
$post = null;
$authors = [];
$tags = [];
$postTags = [];
$error = null;
$additionalFields = [];
$columns = [];
$columnInfo = [];

// Determine blog table name
$blogTableName = 'blog_posts';
$postTagsTableName = 'post_tags';

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
    
    // Get table columns
    $stmt = $db->query("SHOW TABLES LIKE '$blogTableName'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM $blogTableName");
        $columns = array_column($stmt->fetchAll(), 'Field');
        
        // Get column information for validation
        $stmt = $db->query("SHOW COLUMNS FROM $blogTableName");
        $columnInfo = [];
        while ($row = $stmt->fetch()) {
            $columnInfo[$row['Field']] = $row;
        }
    } else {
        $error = "Blog posts table not found. Please check your database setup.";
    }
    
    // Check for specific columns
    $hasSlugColumn = in_array('slug', $columns);
    $hasAuthorIdColumn = in_array('author_id', $columns);
    $hasExcerptColumn = in_array('excerpt', $columns);
    
    // Get post if editing
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
        if (!in_array($column, ['id', 'title', 'author_id', 'content', 'excerpt', 'status', 'is_published', 'created_at', 'updated_at'])) {
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
    <div class="admin-container">
        <nav class="admin-nav">
            <form method="GET" action="dashboard.php" style="display: inline;">
                <button type="submit" class="nav-link">Dashboard</button>
            </form>
            <form method="GET" action="stories.php" style="display: inline;">
                <button type="submit" class="nav-link">Stories</button>
            </form>
            <form method="GET" action="blog-posts.php" style="display: inline;">
                <button type="submit" class="nav-link active">Blog</button>
            </form>
            <form method="GET" action="authors.php" style="display: inline;">
                <button type="submit" class="nav-link">Authors</button>
            </form>
            <form method="GET" action="games.php" style="display: inline;">
                <button type="submit" class="nav-link">Games</button>
            </form>
            <form method="GET" action="directory-items.php" style="display: inline;">
                <button type="submit" class="nav-link">Directory</button>
            </form>
            <form method="GET" action="ai-tools.php" style="display: inline;">
                <button type="submit" class="nav-link">AI Tools</button>
            </form>
            <form method="GET" action="tags.php" style="display: inline;">
                <button type="submit" class="nav-link">Tags</button>
            </form>
            <form method="GET" action="media.php" style="display: inline;">
                <button type="submit" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="content-header">
            <h1><?php echo $post ? 'Edit' : 'Add'; ?> Blog Post</h1>
            <form method="GET" action="blog-posts.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Posts</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-info">
            <p><strong>Required fields:</strong> Title, Content<?php echo $hasAuthorIdColumn ? ', Author' : ''; ?></p>
        </div>

        <form method="POST" action="save-post.php" class="content-form">
            <?php if ($post): ?>
                <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-input" required
                       value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>"
                       onkeyup="generateSlug(this.value)">
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
                <?php 
                $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                $isBooleanField = isset($columnInfo[$field]) && (
                    (strpos($columnInfo[$field]['Type'], 'tinyint(1)') !== false) || 
                    (strpos($field, 'is_') === 0) || 
                    (strpos($field, 'has_') === 0) || 
                    (strpos($field, 'needs_') === 0)
                );
                ?>
                
                <?php if ($isBooleanField): ?>
                <div class="form-group checkbox-field">
                    <label class="checkbox-label">
                        <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="1"
                               <?php echo (isset($post[$field]) && $post[$field] == 1) ? 'checked' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                        <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                    </label>
                </div>
                <?php else: ?>
                <div class="form-group">
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
                <?php endif; ?>
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
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
        }
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
        .nav-link.active {
            background: #007bff;
            color: white;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .form-input:focus {
            border-color: #007bff;
            outline: none;
        }
        .form-submit {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .form-submit:hover {
            background: #0069d9;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .form-info {
            background: #e2f3ff;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .required {
            color: #dc3545;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .form-metadata {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-size: 14px;
            color: #6c757d;
        }
        small {
            color: #6c757d;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
    </style>
    <script>
        function generateSlug(title) {
            const slugInput = document.getElementById('slug');
            if (slugInput && !slugInput.value) {
                const slug = title.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slugInput.value = slug;
            }
        }
    </script>
</body>
</html>
