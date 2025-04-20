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

    // Check if stories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'stories'");
    if ($stmt->rowCount() === 0) {
        // Create stories table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS stories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Get all columns from stories table
    $columns = [];
    $columnInfo = [];
    $stmt = $db->query("DESCRIBE stories");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
        $columnInfo[$row['Field']] = $row;
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
    $stmt = $db->query("SHOW TABLES LIKE 'authors'");
    if ($stmt->rowCount() === 0) {
        // Create authors table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            bio TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }
    $authors = $db->query("SELECT id, name FROM authors ORDER BY name")->fetchAll();

    // Get tags for dropdown
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    if ($stmt->rowCount() === 0) {
        // Create tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get story tags if editing
    $storyTags = [];
    if ($story) {
        $stmt = $db->prepare("SELECT tag_id FROM story_tags WHERE story_id = ?");
        $stmt->execute([$story['id']]);
        $storyTags = array_column($stmt->fetchAll(), 'tag_id');
    }

    // Get media files for featured image dropdown
    $mediaFiles = [];
    $stmt = $db->query("SHOW TABLES LIKE 'media'");
    if ($stmt->rowCount() > 0) {
        // Check if created_at column exists in media table
        $mediaColumns = [];
        $stmt = $db->query("DESCRIBE media");
        while ($row = $stmt->fetch()) {
            $mediaColumns[] = $row['Field'];
        }
        
        // Use created_at for ordering if it exists, otherwise use id
        if (in_array('created_at', $mediaColumns)) {
            $mediaFiles = $db->query("SELECT id, filename, file_path FROM media ORDER BY created_at DESC")->fetchAll();
        } else {
            $mediaFiles = $db->query("SELECT id, filename, file_path FROM media ORDER BY id DESC")->fetchAll();
        }
    }

    // Get additional fields from the database
    $additionalFields = [];
    foreach ($columns as $column) {
        if (!in_array($column, ['id', 'title', 'author', 'author_id', 'content', 'created_at', 'updated_at', 'featured', 'published_at', 'featured_image'])) {
            $additionalFields[] = $column;
        }
    }

    // Determine which fields are required
    $requiredFields = ['title', 'author_id', 'content'];
    foreach ($columnInfo as $field => $info) {
        if ($info['Null'] === 'NO' && $info['Default'] === null && !in_array($field, ['id', 'created_at', 'updated_at'])) {
            if (!in_array($field, $requiredFields)) {
                $requiredFields[] = $field;
            }
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

        <div class="form-info">
            <p><strong>Required fields:</strong> <?php echo implode(', ', array_map(function($field) { 
                return ucfirst(str_replace('_', ' ', $field)); 
            }, $requiredFields)); ?></p>
        </div>

        <form method="POST" action="save-story.php" class="content-form">
            <?php if ($story): ?>
                <input type="hidden" name="id" value="<?php echo $story['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-input" required
                       value="<?php echo htmlspecialchars($story['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="author_id">Author <span class="required">*</span></label>
                <select id="author_id" name="author_id" class="form-input" required>
                    <option value="">Select Author</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?php echo $author['id']; ?>"
                                <?php 
                                if (isset($story['author_id']) && $story['author_id'] == $author['id']) {
                                    echo 'selected';
                                } elseif (isset($story['author']) && $story['author'] == $author['name']) {
                                    echo 'selected';
                                }
                                ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="content">Content <span class="required">*</span></label>
                <textarea id="content" name="content" class="form-input" rows="15" required><?php 
                    echo htmlspecialchars($story['content'] ?? ''); 
                ?></textarea>
            </div>

            <?php 
            // Check if featured_image field exists or can be added
            $hasFeaturedImageField = in_array('featured_image', $columns);
            if ($hasFeaturedImageField || !empty($mediaFiles)): 
            ?>
            <div class="form-group">
                <label class="form-label" for="featured_image">Featured Image</label>
                <select id="featured_image" name="featured_image" class="form-input">
                    <option value="">None</option>
                    <?php foreach ($mediaFiles as $media): ?>
                        <option value="<?php echo $media['id']; ?>"
                                <?php echo (isset($story['featured_image']) && $story['featured_image'] == $media['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($media['filename']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-help">
                    <a href="media.php" target="_blank" class="form-link">Upload new image</a>
                </div>
            </div>
            <?php endif; ?>

            <?php 
            // Check if featured field exists
            $hasFeaturedField = in_array('featured', $columns);
            if ($hasFeaturedField): 
                $featuredType = isset($columnInfo['featured']) ? $columnInfo['featured']['Type'] : '';
                $isIntField = strpos($featuredType, 'int') !== false || strpos($featuredType, 'tinyint') !== false;
                $isRequired = isset($columnInfo['featured']) && $columnInfo['featured']['Null'] === 'NO' && $columnInfo['featured']['Default'] === null;
            ?>
            <div class="form-group">
                <label class="form-label" for="featured">
                    Featured
                    <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                </label>
                <select id="featured" name="featured" class="form-input" <?php echo $isRequired ? 'required' : ''; ?>>
                    <option value="0" <?php echo (isset($story['featured']) && $story['featured'] == 0) ? 'selected' : ''; ?>>No</option>
                    <option value="1" <?php echo (isset($story['featured']) && $story['featured'] == 1) ? 'selected' : ''; ?>>Yes</option>
                </select>
            </div>
            <?php endif; ?>

            <?php 
            // Check if published_at field exists
            $hasPublishedAtField = in_array('published_at', $columns);
            if ($hasPublishedAtField): 
                $isRequired = isset($columnInfo['published_at']) && $columnInfo['published_at']['Null'] === 'NO' && $columnInfo['published_at']['Default'] === null;
            ?>
            <div class="form-group">
                <label class="form-label" for="published_at">
                    Published at
                    <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                </label>
                <input type="datetime-local" id="published_at" name="published_at" class="form-input"
                       value="<?php echo isset($story['published_at']) ? date('Y-m-d\TH:i', strtotime($story['published_at'])) : date('Y-m-d\TH:i'); ?>"
                       <?php echo $isRequired ? 'required' : ''; ?>>
                <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
            </div>
            <?php endif; ?>

            <?php 
            // Check if slug field exists
            $hasSlugField = in_array('slug', $columns);
            if ($hasSlugField): 
                $isRequired = isset($columnInfo['slug']) && $columnInfo['slug']['Null'] === 'NO' && $columnInfo['slug']['Default'] === null;
            ?>
            <div class="form-group">
                <label class="form-label" for="slug">
                    Slug
                    <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                </label>
                <input type="text" id="slug" name="slug" class="form-input"
                       value="<?php echo htmlspecialchars($story['slug'] ?? ''); ?>"
                       <?php echo $isRequired ? 'required' : ''; ?>>
                <small>URL-friendly version of the title. Will be auto-generated if left empty.</small>
            </div>
            <?php endif; ?>

            <?php 
            // Check if review_count field exists
            $hasReviewCountField = in_array('review_count', $columns);
            if ($hasReviewCountField): 
                $isRequired = isset($columnInfo['review_count']) && $columnInfo['review_count']['Null'] === 'NO' && $columnInfo['review_count']['Default'] === null;
            ?>
            <div class="form-group">
                <label class="form-label" for="review_count">
                    Review Count
                    <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                </label>
                <input type="number" id="review_count" name="review_count" class="form-input" min="0" step="1"
                       value="<?php echo htmlspecialchars($story['review_count'] ?? '0'); ?>"
                       <?php echo $isRequired ? 'required' : ''; ?>>
                <small>Number of reviews for this story</small>
            </div>
            <?php endif; ?>

            <?php 
            // Check if average_rating field exists
            $hasAverageRatingField = in_array('average_rating', $columns);
            if ($hasAverageRatingField): 
                $isRequired = isset($columnInfo['average_rating']) && $columnInfo['average_rating']['Null'] === 'NO' && $columnInfo['average_rating']['Default'] === null;
            ?>
            <div class="form-group">
                <label class="form-label" for="average_rating">
                    Average Rating
                    <?php if ($isRequired): ?><span class="required">*</span><?php endif; ?>
                </label>
                <select id="average_rating" name="average_rating" class="form-input" <?php echo $isRequired ? 'required' : ''; ?>>
                    <option value="0" <?php echo (isset($story['average_rating']) && $story['average_rating'] == 0) ? 'selected' : ''; ?>>0 - No Rating</option>
                    <option value="1" <?php echo (isset($story['average_rating']) && $story['average_rating'] == 1) ? 'selected' : ''; ?>>1 - Poor</option>
                    <option value="2" <?php echo (isset($story['average_rating']) && $story['average_rating'] == 2) ? 'selected' : ''; ?>>2 - Fair</option>
                    <option value="3" <?php echo (isset($story['average_rating']) && $story['average_rating'] == 3) ? 'selected' : ''; ?>>3 - Average</option>
                    <option value="4" <?php echo (isset($story['average_rating']) && $story['average_rating'] == 4) ? 'selected' : ''; ?>>4 - Good</option>
                    <option value="5" <?php echo (isset($story['average_rating']) && $story['average_rating'] == 5) ? 'selected' : ''; ?>>5 - Excellent</option>
                </select>
                <small>Average rating from 0 to 5</small>
            </div>
            <?php endif; ?>
            
            <?php 
            // Display remaining additional fields, excluding ones we handled separately
            foreach ($additionalFields as $field): 
                if (in_array($field, ['featured', 'published_at', 'slug', 'review_count', 'average_rating', 'featured_image'])) continue;
                
                $isRequired = isset($columnInfo[$field]) && $columnInfo[$field]['Null'] === 'NO' && $columnInfo[$field]['Default'] === null;
                $isDateTime = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'datetime') !== false;
                $isIntField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'int') !== false || strpos($columnInfo[$field]['Type'], 'tinyint') !== false);
                $isDecimalField = isset($columnInfo[$field]) && (strpos($columnInfo[$field]['Type'], 'decimal') !== false || strpos($columnInfo[$field]['Type'], 'float') !== false || strpos($columnInfo[$field]['Type'], 'double') !== false);
                $isEnumField = isset($columnInfo[$field]) && strpos($columnInfo[$field]['Type'], 'enum') !== false;
                
                // Extract enum values if it's an enum field
                $enumValues = [];
                if ($isEnumField && preg_match('/enum\((.*)\)/', $columnInfo[$field]['Type'], $matches)) {
                    $enumString = $matches[1];
                    preg_match_all("/'([^']*)'/", $enumString, $enumMatches);
                    $enumValues = $enumMatches[1];
                }
            ?>
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
                    <?php elseif ($isEnumField && !empty($enumValues)): ?>
                        <select id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input" <?php echo $isRequired ? 'required' : ''; ?>>
                            <option value="">Select <?php echo ucfirst(str_replace('_', ' ', $field)); ?></option>
                            <?php foreach ($enumValues as $value): ?>
                                <option value="<?php echo $value; ?>"
                                        <?php echo (isset($story[$field]) && $story[$field] == $value) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($value); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($isIntField): ?>
                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input" min="0" step="1"
                               value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php elseif ($isDecimalField): ?>
                        <input type="number" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input" min="0" step="0.1"
                               value="<?php echo htmlspecialchars($story[$field] ?? '0'); ?>"
                               <?php echo $isRequired ? 'required' : ''; ?>>
                    <?php else: ?>
                        <input type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" class="form-input"
                               value="<?php echo htmlspecialchars($story[$field] ?? ''); ?>"
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
        .form-help {
            margin-top: 5px;
        }
        .form-link {
            color: #007bff;
            text-decoration: none;
        }
        .form-link:hover {
            text-decoration: underline;
        }
    </style>
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