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

    // Get story if editing
    $story = null;
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM stories WHERE id = ?");
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
        $stmt = $db->prepare("SELECT tag_id FROM story_tags WHERE story_id = ?");
        $stmt->execute([$story['id']]);
        $storyTags = array_column($stmt->fetchAll(), 'tag_id');
    }

    // Get table column information for dynamic form fields
    $stmt = $db->prepare("DESCRIBE stories");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    // Organize column info for easier access
    $columnInfo = [];
    $additionalFields = [];
    
    foreach ($columns as $column) {
        $columnInfo[$column['Field']] = $column;
        
        // Skip standard fields that are handled explicitly
        if (!in_array($column['Field'], ['id', 'title', 'content', 'author_id', 'created_at', 'updated_at'])) {
            $additionalFields[] = $column['Field'];
        }
    }

} catch (PDOException $e) {
    error_log("Story form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
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
            <h1><?php echo $story ? 'Edit' : 'Add'; ?> Story</h1>
            <form method="GET" action="stories.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Stories</button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="save-story.php" class="content-form">
            <?php if ($story): ?>
                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="title">Title</label>
                <input type="text" id="title" name="title" class="form-input" required
                       value="<?php echo htmlspecialchars($story['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="form-input"
                       value="<?php echo htmlspecialchars($story['slug'] ?? ''); ?>">
                <small>Leave empty to auto-generate from title</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="author_id">Author</label>
                <select id="author_id" name="author_id" class="form-input" required>
                    <option value="">Select Author</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?php echo $author['id']; ?>"
                                <?php echo ($story['author_id'] ?? '') == $author['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" class="form-input" rows="3"><?php 
                    echo htmlspecialchars($story['excerpt'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="content">Content</label>
                <textarea id="content" name="content" class="form-input" rows="10" required><?php 
                    echo htmlspecialchars($story['content'] ?? ''); 
                ?></textarea>
            </div>

            <?php
            // Handle cover image fields - use cover_image if it exists, otherwise use cover_url
            $coverImageField = in_array('cover_image', $additionalFields) ? 'cover_image' :
                              (in_array('cover_url', $additionalFields) ? 'cover_url' : '');
            
            if ($coverImageField):
            ?>
            <div class="form-group">
                <label class="form-label" for="<?php echo $coverImageField; ?>">Cover Image URL</label>
                <input type="text" id="<?php echo $coverImageField; ?>" name="<?php echo $coverImageField; ?>" class="form-input"
                       value="<?php echo htmlspecialchars($story[$coverImageField] ?? ''); ?>">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="published_at">Publish Date</label>
                <input type="datetime-local" id="published_at" name="published_at" class="form-input"
                       value="<?php echo isset($story['published_at']) ? date('Y-m-d\TH:i', strtotime($story['published_at'])) : ''; ?>">
            </div>
            
            <!-- Group all checkboxes together -->
            <h3 class="form-section-title">Options</h3>
            <div class="checkbox-section">
                <div class="form-group checkbox-group-item">
                    <label class="form-check-label" for="is_published">Published</label>
                    <input type="checkbox" id="is_published" name="is_published" value="1"
                           <?php echo (isset($story['is_published']) && $story['is_published']) ? "checked" : ""; ?>
                           class="form-check-input">
                </div>
                
                <div class="form-group checkbox-group-item">
                    <label class="form-check-label" for="featured">Featured</label>
                    <input type="checkbox" id="featured" name="featured" value="1"
                           <?php echo (isset($story['featured']) && $story['featured']) ? "checked" : ""; ?>
                           class="form-check-input">
                </div>
                
                <div class="form-group checkbox-group-item">
                    <label class="form-check-label" for="is_sponsored">Sponsored</label>
                    <input type="checkbox" id="is_sponsored" name="is_sponsored" value="1"
                           <?php echo (isset($story['is_sponsored']) && $story['is_sponsored']) ? "checked" : ""; ?>
                           class="form-check-input">
                </div>

            <?php
            // Collect boolean fields and non-boolean fields separately
            $booleanFields = [];
            $nonBooleanFields = [];
            
            foreach ($additionalFields as $field) {
                // Skip fields that are already handled above or will be handled below
                if (in_array($field, ['featured', 'is_sponsored', 'is_published', 'published', 'published_at', 'cover_image', 'cover_url', 'slug', 'excerpt'])) continue;
                
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
                
                if ($isBooleanField) {
                    $booleanFields[] = $field;
                } else {
                    $nonBooleanFields[] = [
                        'field' => $field,
                        'isRequired' => $isRequired,
                        'isDateTime' => $isDateTime,
                        'isIntField' => $isIntField,
                        'isDecimalField' => $isDecimalField,
                        'isEnumField' => $isEnumField
                    ];
                }
            }
            
            // Display non-boolean fields first
            foreach ($nonBooleanFields as $fieldData):
                $field = $fieldData['field'];
                $isRequired = $fieldData['isRequired'];
                $isDateTime = $fieldData['isDateTime'];
                $isIntField = $fieldData['isIntField'];
                $isDecimalField = $fieldData['isDecimalField'];
                $isEnumField = $fieldData['isEnumField'];
                
                // Format field label
                $label = ucwords(str_replace('_', ' ', $field));
                
                if ($isDateTime):
            ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                    <input type="datetime-local" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                           value="<?php echo isset($story[$field]) ? date('Y-m-d\TH:i', strtotime($story[$field])) : ''; ?>"
                           <?php echo $isRequired ? 'required' : ''; ?>>
                </div>
            <?php elseif ($isEnumField):
                // Extract enum values
                preg_match("/enum\(([^)]+)\)/", $columnInfo[$field]['Type'], $matches);
                $enumValues = $matches[1] ? str_getcsv($matches[1], ',', "'") : [];
            ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                    <select id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                            <?php echo $isRequired ? 'required' : ''; ?>>
                        <option value="">Select <?php echo $label; ?></option>
                        <?php foreach ($enumValues as $value): ?>
                            <option value="<?php echo $value; ?>"
                                    <?php echo ($story[$field] ?? '') == $value ? 'selected' : ''; ?>>
                                <?php echo ucfirst($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($isIntField || $isDecimalField): ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                    <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                           value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                           <?php echo $isDecimalField ? 'step="0.01"' : ''; ?>
                           <?php echo $isRequired ? 'required' : ''; ?>>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                    <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                           value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                           <?php echo $isRequired ? 'required' : ''; ?>>
                </div>
            <?php endif; endforeach; ?>
            
            <!-- Add all boolean fields to the checkbox section -->
            <?php if (!empty($booleanFields)): ?>
            <div class="checkbox-section">
                <?php foreach ($booleanFields as $field):
                    // Format field label
                    $label = ucwords(str_replace('_', ' ', $field));
                ?>
                <div class="form-group checkbox-group-item">
                    <label class="form-check-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                    <input type="checkbox" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="1"
                           <?php echo (isset($story[$field]) && $story[$field]) ? "checked" : ""; ?>
                           class="form-check-input">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Tags section moved to the bottom -->
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
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-group-half {
            flex: 1;
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
        .form-check-input {
            width: 20px;
            height: 20px;
        }
        .form-check-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-section-title {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 18px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .checkbox-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .checkbox-group-item {
            margin-bottom: 0;
        }
    </style>
</body>
</html>
