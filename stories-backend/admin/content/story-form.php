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
$story = null;
$authors = [];
$tags = [];
$storyTags = [];
$error = null;
$additionalFields = [];
$columns = [];
$columnInfo = [];

// Determine story table name
$storyTableName = 'stories';
$storyTagsTableName = 'story_tags';

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
    $stmt = $db->query("SHOW TABLES LIKE '$storyTableName'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SHOW COLUMNS FROM $storyTableName");
        $columns = array_column($stmt->fetchAll(), 'Field');
        
        // Get column information for validation
        $stmt = $db->query("SHOW COLUMNS FROM $storyTableName");
        $columnInfo = [];
        while ($row = $stmt->fetch()) {
            $columnInfo[$row['Field']] = $row;
        }
    } else {
        $error = "Stories table not found. Please check your database setup.";
    }
    
    // Check for specific columns
    $hasSlugColumn = in_array('slug', $columns);
    $hasAuthorIdColumn = in_array('author_id', $columns);
    $hasExcerptColumn = in_array('excerpt', $columns);
    $hasFeaturedColumn = in_array('featured', $columns);
    $hasSponsoredColumn = in_array('is_sponsored', $columns);
    
    // Get story if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM $storyTableName WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $story = $stmt->fetch();
        
        if (!$story) {
            header("Location: stories.php");
            exit;
        }
    }

    // Get authors for dropdown
    $authors = $db->query("SELECT id, name FROM authors ORDER BY name")->fetchAll();

    // Get tags for dropdown
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get story tags if editing
    $storyTags = [];
    if ($story) {
        $stmt = $db->prepare("SELECT tag_id FROM $storyTagsTableName WHERE story_id = ?");
        $stmt->execute([$story['id']]);
        $storyTags = array_column($stmt->fetchAll(), 'tag_id');
    }
    
    // Get additional fields from the database
    $additionalFields = [];
    foreach ($columns as $column) {
        if (!in_array($column, ['id', 'title', 'author_id', 'content', 'excerpt', 'slug', 'featured', 'is_sponsored', 'published_at', 'created_at', 'updated_at'])) {
            $additionalFields[] = $column;
        }
    }

} catch (PDOException $e) {
    error_log("Story form error: " . $e->getMessage());
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
    <title><?php echo $story ? 'Edit' : 'Add'; ?> Story - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <form method="GET" action="dashboard.php" style="display: inline;">
                <button type="submit" class="nav-link">Dashboard</button>
            </form>
            <form method="GET" action="stories.php" style="display: inline;">
                <button type="submit" class="nav-link active">Stories</button>
            </form>
            <form method="GET" action="blog-posts.php" style="display: inline;">
                <button type="submit" class="nav-link">Blog</button>
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
            <h1><?php echo $story ? 'Edit' : 'Add'; ?> Story</h1>
            <form method="GET" action="stories.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Stories</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-info">
            <p><strong>Required fields:</strong> Title, Content<?php echo $hasAuthorIdColumn ? ', Author' : ''; ?></p>
        </div>

        <form method="POST" action="save-story.php" class="content-form">
            <?php if ($story): ?>
                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-input" required
                       value="<?php echo htmlspecialchars($story['title'] ?? ''); ?>"
                       onkeyup="generateSlug(this.value)">
            </div>

            <?php if ($hasSlugColumn): ?>
            <div class="form-group">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="form-input"
                       value="<?php echo htmlspecialchars($story['slug'] ?? ''); ?>"
                       placeholder="story-title-in-lowercase">
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
                                <?php echo isset($story['author_id']) && $story['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="content">Content <span class="required">*</span></label>
                <textarea id="content" name="content" class="form-input" rows="10" required><?php 
                    echo htmlspecialchars($story['content'] ?? ''); 
                ?></textarea>
            </div>

            <?php if ($hasExcerptColumn): ?>
            <div class="form-group">
                <label class="form-label" for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" class="form-input" rows="3"><?php 
                    echo htmlspecialchars($story['excerpt'] ?? ''); 
                ?></textarea>
                <small>A short summary of the story. If left empty, it will be auto-generated from the content.</small>
            </div>
            <?php endif; ?>

            <?php if ($hasFeaturedColumn): ?>
            <div class="form-group checkbox-field">
                <label class="checkbox-label">
                    <input type="checkbox" name="featured" value="1"
                           <?php echo (isset($story['featured']) && $story['featured'] == 1) ? 'checked' : ''; ?>>
                    Featured
                </label>
            </div>
            <?php endif; ?>

            <?php if ($hasSponsoredColumn): ?>
            <div class="form-group checkbox-field">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_sponsored" value="1"
                           <?php echo (isset($story['is_sponsored']) && $story['is_sponsored'] == 1) ? 'checked' : ''; ?>>
                    Sponsored
                </label>
            </div>
            <?php endif; ?>

            <div class="form-group checkbox-field">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_published" value="1"
                           <?php echo (!isset($story['is_published']) || $story['is_published'] == 1) ? 'checked' : ''; ?>>
                    Published
                </label>
            </div>

            <div class="form-group">
                <label class="form-label" for="published_at">Published at</label>
                <input type="datetime-local" id="published_at" name="published_at" class="form-input"
                       value="<?php echo isset($story['published_at']) ? date('Y-m-d\TH:i', strtotime($story['published_at'])) : date('Y-m-d\TH:i'); ?>">
                <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
            </div>
            
            <?php 
            // Display remaining additional fields
            foreach ($additionalFields as $field): 
                
                $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                $isIntField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'int') !== false || strpos($columnInfo[$field]['Type'], 'tinyint') !== false);
                $isDecimalField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'decimal') !== false || strpos($columnInfo[$field]['Type'], 'float') !== false || strpos($columnInfo[$field]['Type'], 'double') !== false);
                $isEnumField = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'enum') !== false;
                $isBooleanField = isset($columnInfo[$field]) && (
                    (strpos($columnInfo[$field]['Type'], 'tinyint(1)') !== false) || 
                    (strpos($field, 'is_') === 0) || 
                    (strpos($field, 'has_') === 0) || 
                    (strpos($field, 'needs_') === 0)
                );
                
                // Extract enum values if it's an enum field
                $enumValues = [];
                if ($isEnumField && preg_match('/enum\((.*)\)/', $columnInfo[$field]['Type'], $matches)) {
                    $enumString = $matches[1];
                    preg_match_all("/'([^']*)'/", $enumString, $enumMatches);
                    $enumValues = $enumMatches[1];
                }
                
                if ($isBooleanField): ?>
                <div class="form-group checkbox-field">
                    <label class="checkbox-label">
                        <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="1"
                               <?php echo (isset($story[$field]) && $story[$field] == 1) ? 'checked' : ''; ?>>
                        <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                        <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                    </label>
                </div>
                <?php elseif ($isEnumField): ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                        <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                    </label>
                    <select id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                            <?php echo $isRequired ? 'required' : ''; ?>>
                        <option value="">Select <?php echo ucfirst(str_replace('_', ' ', $field)); ?></option>
                        <?php foreach ($enumValues as $value): ?>
                            <option value="<?php echo $value; ?>"
                                    <?php echo isset($story[$field]) && $story[$field] == $value ? 'selected' : ''; ?>>
                                <?php echo ucfirst($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $field)); ?>
                        <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                    </label>
                    
                    <?php if ($isDateTime): ?>
                        <input type="datetime-local" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo isset($story[$field]) ? date('Y-m-d\TH:i', strtotime($story[$field])) : date('Y-m-d\TH:i'); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                        <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
                    <?php elseif ($isIntField): ?>
                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php elseif ($isDecimalField): ?>
                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input" step="0.01"
                               value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php else: ?>
                        <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
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
                                   <?php echo in_array($tag['id'], $storyTags) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="form-submit">Save Story</button>
            </div>
        </form>
        
        <?php if ($story): ?>
            <div class="form-metadata">
                <p>Created: <?php echo date('M j, Y g:i A', strtotime($story['created_at'])); ?></p>
                <p>Last Updated: <?php echo date('M j, Y g:i A', strtotime($story['updated_at'])); ?></p>
                <p>ID: <?php echo $story['id']; ?></p>
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
