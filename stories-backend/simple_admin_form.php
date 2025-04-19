<?php
/**
 * Simple Admin Form
 * 
 * This script provides a simple HTML form for editing stories without any JavaScript.
 * It submits directly to the database using a standard HTML form POST.
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$dbHost = 'localhost';
$dbName = 'stories_db';
$dbUser = 'stories_user';
$dbPass = ''; // Add password if needed

try {
    $db = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get the story ID from the URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Prepare the update query
        $query = "UPDATE stories SET 
                  title = :title,
                  slug = :slug,
                  excerpt = :excerpt,
                  content = :content,
                  published_at = :published_at,
                  featured = :featured,
                  estimated_reading_time = :estimated_reading_time,
                  is_sponsored = :is_sponsored,
                  age_group = :age_group,
                  needs_moderation = :needs_moderation,
                  is_self_published = :is_self_published,
                  is_ai_enhanced = :is_ai_enhanced,
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $_POST['title']);
        $stmt->bindParam(':slug', $_POST['slug']);
        $stmt->bindParam(':excerpt', $_POST['excerpt']);
        $stmt->bindParam(':content', $_POST['content']);
        $stmt->bindParam(':published_at', $_POST['publishedAt']);
        $stmt->bindParam(':featured', isset($_POST['featured']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindParam(':estimated_reading_time', $_POST['estimatedReadingTime']);
        $stmt->bindParam(':is_sponsored', isset($_POST['isSponsored']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindParam(':age_group', $_POST['ageGroup']);
        $stmt->bindParam(':needs_moderation', isset($_POST['needsModeration']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindParam(':is_self_published', isset($_POST['isSelfPublished']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindParam(':is_ai_enhanced', isset($_POST['isAIEnhanced']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        // Execute the query
        $stmt->execute();
        
        // Redirect to the stories list
        header('Location: simple_admin_list.php');
        exit;
    } catch (PDOException $e) {
        $error = "Error updating story: " . $e->getMessage();
    }
}

// Get the story data
if ($id > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM stories WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $story = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$story) {
            die("Story not found");
        }
    } catch (PDOException $e) {
        die("Error fetching story: " . $e->getMessage());
    }
} else {
    die("No story ID provided");
}

// Get authors for dropdown
try {
    $stmt = $db->query("SELECT id, name FROM authors ORDER BY name");
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $authors = [];
}

// Get tags for dropdown
try {
    $stmt = $db->query("SELECT id, name FROM tags ORDER BY name");
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tags = [];
}

// Get story tags
try {
    $stmt = $db->prepare("SELECT tag_id FROM story_tags WHERE story_id = :story_id");
    $stmt->bindParam(':story_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $storyTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $storyTags = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Story - Simple Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="datetime-local"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        textarea {
            height: 200px;
        }
        .checkbox-group {
            margin-top: 5px;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
        .btn-primary {
            background-color: #2980b9;
        }
        .btn-secondary {
            background-color: #7f8c8d;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Story - Simple Admin</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post" action="simple_admin_form.php?id=<?php echo $id; ?>">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($story['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($story['slug']); ?>">
                <small>URL-friendly version of the title. Leave blank to generate automatically.</small>
            </div>
            
            <div class="form-group">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt"><?php echo htmlspecialchars($story['excerpt']); ?></textarea>
                <small>A short summary of the story.</small>
            </div>
            
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" required><?php echo htmlspecialchars($story['content']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="publishedAt">Published Date</label>
                <input type="datetime-local" id="publishedAt" name="publishedAt" value="<?php echo date('Y-m-d\TH:i', strtotime($story['published_at'])); ?>" required>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="featured" name="featured" value="1" <?php echo $story['featured'] ? 'checked' : ''; ?>>
                    <label for="featured" style="display: inline;">Mark as featured</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="estimatedReadingTime">Estimated Reading Time</label>
                <input type="text" id="estimatedReadingTime" name="estimatedReadingTime" value="<?php echo htmlspecialchars($story['estimated_reading_time']); ?>">
                <small>e.g., "5 minutes"</small>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="isSponsored" name="isSponsored" value="1" <?php echo $story['is_sponsored'] ? 'checked' : ''; ?>>
                    <label for="isSponsored" style="display: inline;">Mark as sponsored content</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="ageGroup">Age Group</label>
                <input type="text" id="ageGroup" name="ageGroup" value="<?php echo htmlspecialchars($story['age_group']); ?>">
                <small>e.g., "5-8", "9-12", "13+"</small>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="needsModeration" name="needsModeration" value="1" <?php echo $story['needs_moderation'] ? 'checked' : ''; ?>>
                    <label for="needsModeration" style="display: inline;">Requires moderation</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="isSelfPublished" name="isSelfPublished" value="1" <?php echo $story['is_self_published'] ? 'checked' : ''; ?>>
                    <label for="isSelfPublished" style="display: inline;">Self-published content</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="isAIEnhanced" name="isAIEnhanced" value="1" <?php echo $story['is_ai_enhanced'] ? 'checked' : ''; ?>>
                    <label for="isAIEnhanced" style="display: inline;">Enhanced with AI</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="author">Author</label>
                <select id="author" name="author">
                    <option value="">-- Select Author --</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?php echo $author['id']; ?>" <?php echo ($story['author_id'] == $author['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($author['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tags</label>
                <?php foreach ($tags as $tag): ?>
                    <div class="checkbox-group">
                        <input type="checkbox" id="tag_<?php echo $tag['id']; ?>" name="tags[]" value="<?php echo $tag['id']; ?>" 
                            <?php echo in_array($tag['id'], $storyTags) ? 'checked' : ''; ?>>
                        <label for="tag_<?php echo $tag['id']; ?>" style="display: inline;">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="simple_admin_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>