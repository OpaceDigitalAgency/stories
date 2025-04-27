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
        try {
            // First try to get just the story without the join to ensure we can at least load the basic data
            $stmt = $db->prepare("SELECT * FROM stories WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $story = $stmt->fetch();
            
            if (!$story) {
                header("Location: stories.php");
                exit;
            }
            
            // Now try to get author information from story_authors table
            try {
                $stmt = $db->prepare("
                    SELECT a.id as author_id, a.name as author_name
                    FROM story_authors sa
                    JOIN authors a ON sa.author_id = a.id
                    WHERE sa.story_id = ?
                ");
                $stmt->execute([$story['id']]);
                $author = $stmt->fetch();
                
                if ($author) {
                    $story['author_name'] = $author['author_name'];
                    $story['author_id'] = $author['author_id'];
                    error_log("Found author for story: " . $author['author_name'] . " (ID: " . $author['author_id'] . ")");
                }
            } catch (Exception $e) {
                error_log("Error fetching author: " . $e->getMessage());
                // Continue even if author fetch fails
            }
            
            // Debug log for story and author information
            error_log("Story ID: " . $story['id']);
            error_log("Story author_id: " . ($story['author_id'] ?? 'null'));
            error_log("Story author_name: " . ($story['author_name'] ?? 'null'));
        } catch (Exception $e) {
            error_log("Error loading story: " . $e->getMessage());
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
    <link rel="stylesheet" href="../assets/css/modern-admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">S</div>
                <div class="logo-text">Stories Admin</div>
            </div>
            <div class="user-info">
                <span class="user-name">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <form method="POST" action="../logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="nav-menu">
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="submit" formaction="../dashboard.php" class="nav-link">Dashboard</button>
                <button type="submit" formaction="stories.php" class="nav-link active">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title"><?php echo $story ? 'Edit' : 'Add'; ?> Story</h1>
                <p class="page-description">
                    <a href="stories.php" class="text-primary">← Back to Stories</a>
                </p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-section mb-4">
            <div class="section-body">
                <form method="POST" action="save-story.php" class="content-form">
                    <?php if ($story): ?>
                        <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($story['title'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" class="form-control"
                               value="<?php echo htmlspecialchars($story['slug'] ?? ''); ?>">
                        <small class="form-text text-muted">Leave empty to auto-generate from title</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="author_id">Author</label>
                        <select id="author_id" name="author_id" class="form-control" required>
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo $author['id']; ?>"
                                        <?php echo isset($story['author_id']) && $story['author_id'] == $author['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($author['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($story['author_id']) && isset($story['author_name'])): ?>
                            <small class="form-text text-muted">Current author: <?php echo htmlspecialchars($story['author_name']); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="excerpt">Excerpt</label>
                        <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php 
                            echo htmlspecialchars($story['excerpt'] ?? ''); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="content">Content</label>
                        <textarea id="content" name="content" class="form-control" rows="10" required><?php 
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
                        <input type="text" id="<?php echo $coverImageField; ?>" name="<?php echo $coverImageField; ?>" class="form-control"
                               value="<?php echo htmlspecialchars($story[$coverImageField] ?? ''); ?>">
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="published_at">Publish Date</label>
                        <input type="datetime-local" id="published_at" name="published_at" class="form-control"
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
                    
                    // Add all boolean fields to the checkbox section
                    foreach ($booleanFields as $field):
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
                    
                    <!-- Display non-boolean fields -->
                    <?php foreach ($nonBooleanFields as $fieldData):
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
                            <input type="datetime-local" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
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
                            <select id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
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
                    <?php elseif ($field === 'average_rating'): ?>
                        <div class="form-group">
                            <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                            <div class="d-flex align-items-center">
                                <input type="range" id="<?php echo $field; ?>_slider" class="form-control w-75"
                                       min="0" max="5" step="0.1"
                                       value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                                       oninput="document.getElementById('<?php echo $field; ?>').value = this.value; document.getElementById('<?php echo $field; ?>_display').textContent = this.value;">
                                <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control w-25 ml-2"
                                       min="0" max="5" step="0.1"
                                       value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                                       oninput="document.getElementById('<?php echo $field; ?>_slider').value = this.value; document.getElementById('<?php echo $field; ?>_display').textContent = this.value;"
                                       <?php echo $isRequired ? 'required' : ''; ?>>
                            </div>
                            <div class="text-center mt-2">
                                <span id="<?php echo $field; ?>_display" class="text-lg font-bold"><?php echo htmlspecialchars($story[$field] ?? '0'); ?></span> / 5
                            </div>
                        </div>
                    <?php elseif ($field === 'review_count'): ?>
                        <div class="form-group">
                            <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                            <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                   min="0" step="1"
                                   value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                                   <?php echo $isRequired ? 'required' : ''; ?>>
                            <small class="form-text text-muted">Number of reviews for this story</small>
                        </div>
                    <?php elseif ($isIntField || $isDecimalField): ?>
                        <div class="form-group">
                            <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                            <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                   value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                                   <?php echo $isDecimalField ? 'step="0.01"' : ''; ?>
                                   <?php echo $isRequired ? 'required' : ''; ?>>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label class="form-label" for="<?php echo $field; ?>"><?php echo $label; ?></label>
                            <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-control"
                                   value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
                                   <?php echo $isRequired ? 'required' : ''; ?>>
                        </div>
                    <?php endif; endforeach; ?>
                    
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
                        <button type="submit" class="btn btn-primary">Save Story</button>
                        <a href="stories.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-section-title {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.25rem;
            color: var(--gray-800);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 5px;
        }
        
        .checkbox-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            background-color: var(--gray-50);
            padding: 15px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        
        .checkbox-group-item {
            margin-bottom: 0;
        }
        
        .content-form {
            background: white;
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .w-75 {
            width: 75%;
        }
        
        .w-25 {
            width: 25%;
        }
        
        .ml-2 {
            margin-left: 0.5rem;
        }
        
        .d-flex {
            display: flex;
        }
        
        .align-items-center {
            align-items: center;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .text-lg {
            font-size: 1.125rem;
        }
        
        .font-bold {
            font-weight: 700;
        }
    </style>
    
    <script>
        // Function to handle source_type changes
        function handleSourceTypeChange() {
            const sourceTypeSelect = document.getElementById('source_type');
            const allowReviewsCheckbox = document.getElementById('allow_reviews');
            
            if (!sourceTypeSelect || !allowReviewsCheckbox) {
                console.error('Required elements not found');
                return;
            }
            
            const sourceType = sourceTypeSelect.value;
            const allowReviewsLabel = allowReviewsCheckbox.closest('.form-group');
            
            console.log('Source type changed to:', sourceType);
            
            if (sourceType === 'child') {
                // Children's stories never get reviews
                allowReviewsCheckbox.checked = false;
                allowReviewsCheckbox.disabled = true;
                allowReviewsLabel.style.opacity = '0.5';
                allowReviewsLabel.title = 'Children\'s stories never get reviews';
            } else if (sourceType === 'classic') {
                // Classic works always get reviews
                allowReviewsCheckbox.checked = true;
                allowReviewsCheckbox.disabled = true;
                allowReviewsLabel.style.opacity = '0.5';
                allowReviewsLabel.title = 'Classic works always get reviews';
            } else {
                // Parent stories can choose
                allowReviewsCheckbox.disabled = false;
                allowReviewsLabel.style.opacity = '1';
                allowReviewsLabel.title = '';
            }
        }
        
        // Run when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const sourceTypeSelect = document.getElementById('source_type');
            if (sourceTypeSelect) {
                // Set initial state
                handleSourceTypeChange();
                
                // Add event listener for changes
                sourceTypeSelect.addEventListener('change', handleSourceTypeChange);
            }
        });
    </script>
    <script>
        // Auto-generate slug from title
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            
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
        });
    </script>
</body>
</html>
