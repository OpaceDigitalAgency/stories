<?php
require_once '../simple_auth.php';

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
    header("Location: login.php");
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

    // Get authors for dropdown with their types
    $authors = $db->query("SELECT id, name, author_type FROM authors ORDER BY name")->fetchAll();

    // Get tags for dropdown
    $tags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

    // Get story tags if editing
    $storyTags = [];
    if ($story) {
        $stmt = $db->prepare("SELECT tag_id FROM story_tags WHERE story_id = ?");
        $stmt->execute([$story['id']]);
        $storyTags = array_column($stmt->fetchAll(), 'tag_id');
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
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="container">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['name']); ?> |
            <form method="POST" action="logout.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #dc3545;">Logout</button>
            </form>
        </div>

        <nav class="nav-menu">
            <form method="GET" style="display: inline;">
                <button type="submit" formaction="dashboard.php" class="nav-link">Dashboard</button>
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
                <label class="form-label" for="source_type">Source Type</label>
                <select id="source_type" name="source_type" class="form-input" required onchange="updateAllowReviewsVisibility()">
                    <option value="child" <?php echo ($story['source_type'] ?? 'child') === 'child' ? 'selected' : ''; ?>>Child</option>
                    <option value="parent" <?php echo ($story['source_type'] ?? '') === 'parent' ? 'selected' : ''; ?>>Parent</option>
                    <option value="classic" <?php echo ($story['source_type'] ?? '') === 'classic' ? 'selected' : ''; ?>>Classic</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    Child: Never allows reviews | Parent: Can choose | Classic: Always allows reviews
                </p>
            </div>

            <div id="allow-reviews-container" class="form-group" style="<?php echo ($story['source_type'] ?? 'child') !== 'parent' ? 'display: none;' : ''; ?>">
                <label class="checkbox-label">
                    <input type="checkbox" id="allow_reviews" name="allow_reviews" value="1"
                           <?php echo ($story['allow_reviews'] ?? 0) == 1 ? 'checked' : ''; ?>>
                    Allow Reviews
                </label>
                <p class="text-sm text-gray-500 mt-1">Only parent/family stories can toggle this option</p>
            </div>

            <script>
                // Immediately execute this script
                (function() {
                    function updateAllowReviewsVisibility() {
                        const sourceType = document.getElementById('source_type').value;
                        const allowReviewsContainer = document.getElementById('allow-reviews-container');
                        const allowReviewsCheckbox = document.getElementById('allow_reviews');
                        
                        console.log("Source type changed to:", sourceType);
                        
                        if (sourceType === 'parent') {
                            allowReviewsContainer.style.display = 'block';
                        } else {
                            allowReviewsContainer.style.display = 'none';
                            // Set the appropriate value based on source type
                            if (sourceType === 'child') {
                                allowReviewsCheckbox.checked = false;
                            } else if (sourceType === 'classic') {
                                allowReviewsCheckbox.checked = true;
                            }
                        }
                    }
                    
                    // Function to update source type based on author type
                    window.updateSourceTypeFromAuthor = function() {
                        const authorSelect = document.getElementById('author_id');
                        const sourceTypeSelect = document.getElementById('source_type');
                        
                        if (authorSelect.selectedIndex > 0) {
                            const selectedOption = authorSelect.options[authorSelect.selectedIndex];
                            const authorType = selectedOption.getAttribute('data-author-type');
                            
                            // Map author type to source type
                            let sourceType;
                            switch (authorType) {
                                case 'child':
                                    sourceType = 'child';
                                    break;
                                case 'parent':
                                    sourceType = 'parent';
                                    break;
                                case 'retail':
                                case 'educator':
                                default:
                                    sourceType = 'classic';
                                    break;
                            }
                            
                            // Set the source type and disable the dropdown
                            sourceTypeSelect.value = sourceType;
                            sourceTypeSelect.disabled = true;
                            
                            // Update the allow reviews visibility
                            updateAllowReviewsVisibility();
                        } else {
                            // Enable the dropdown if no author is selected
                            sourceTypeSelect.disabled = false;
                        }
                    }
                    
                    // Function to update age group based on author age
                    window.updateAgeGroupFromAuthor = function() {
                        const authorSelect = document.getElementById('author_id');
                        const ageGroupSelect = document.getElementById('age_group');
                        
                        if (authorSelect.selectedIndex > 0) {
                            const selectedOption = authorSelect.options[authorSelect.selectedIndex];
                            const authorType = selectedOption.getAttribute('data-author-type');
                            
                            // Only set age group for child authors
                            if (authorType === 'child') {
                                // Get author ID
                                const authorId = selectedOption.value;
                                
                                // Make an AJAX request to get author age
                                fetch('get-author-age.php?id=' + authorId)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.age) {
                                            // Set age group based on author age
                                            const age = parseInt(data.age);
                                            let ageGroup = '7-12'; // Default
                                            
                                            if (age <= 3) {
                                                ageGroup = '0-3';
                                            } else if (age <= 6) {
                                                ageGroup = '4-6';
                                            } else if (age <= 12) {
                                                ageGroup = '7-12';
                                            } else {
                                                ageGroup = '13+';
                                            }
                                            
                                            ageGroupSelect.value = ageGroup;
                                            console.log("Set age group to", ageGroup, "based on author age", age);
                                        }
                                    })
                                    .catch(error => {
                                        console.error("Error fetching author age:", error);
                                    });
                            }
                        }
                    }
                    
                    // Run immediately
                    updateAllowReviewsVisibility();
                    updateSourceTypeFromAuthor();
                    updateAgeGroupFromAuthor();
                    
                    // Also run when the dropdown changes
                    document.getElementById('source_type').addEventListener('change', updateAllowReviewsVisibility);
                    document.getElementById('author_id').addEventListener('change', updateAgeGroupFromAuthor);
                })();
            </script>

            <div class="form-group">
                <label class="form-label" for="author_id">Author</label>
                <select id="author_id" name="author_id" class="form-input" required onchange="updateSourceTypeFromAuthor()">
                    <option value="">Select Author</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?php echo $author['id']; ?>"
                                data-author-type="<?php echo htmlspecialchars($author['author_type'] ?? 'retail'); ?>"
                                <?php echo ($story['author_id'] ?? '') == $author['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                            (<?php echo ucfirst(htmlspecialchars($author['author_type'] ?? 'retail')); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    Author type determines the default source type for the story
                </p>
            </div>

            <div class="form-group">
                <label class="form-label" for="content">Content</label>
                <textarea id="content" name="content" class="form-input" rows="10" required><?php 
                    echo htmlspecialchars($story['content'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="estimated_reading_time">Estimated Reading Time (minutes)</label>
                <input type="number" id="estimated_reading_time" name="estimated_reading_time" class="form-input" min="1" max="60" required
                       value="<?php echo htmlspecialchars($story['estimated_reading_time'] ?? '1'); ?>">
                <p class="text-sm text-gray-500 mt-1">
                    For children's stories, this is typically 1-5 minutes
                </p>
            </div>

            <div class="form-group">
                <label class="form-label" for="age_group">Age Group</label>
                <select id="age_group" name="age_group" class="form-input" required>
                    <option value="0-3" <?php echo ($story['age_group'] ?? '') === '0-3' ? 'selected' : ''; ?>>0-3 years</option>
                    <option value="4-6" <?php echo ($story['age_group'] ?? '') === '4-6' ? 'selected' : ''; ?>>4-6 years</option>
                    <option value="7-12" <?php echo ($story['age_group'] ?? '7-12') === '7-12' ? 'selected' : ''; ?>>7-12 years</option>
                    <option value="13+" <?php echo ($story['age_group'] ?? '') === '13+' ? 'selected' : ''; ?>>13+ years</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">
                    Target age group for this story
                </p>
            </div>

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