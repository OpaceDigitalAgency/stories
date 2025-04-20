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
$item = null;
$categories = [];
$error = null;

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

    // Get all categories
    $stmt = $db->query("SHOW TABLES LIKE 'directory_categories'");
    if ($stmt->rowCount() > 0) {
        $categories = $db->query("SELECT * FROM directory_categories ORDER BY name")->fetchAll();
    }

    // Get directory item if editing
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM directory_items WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $item = $stmt->fetch();
        
        if (!$item) {
            header("Location: directory-items.php");
            exit;
        }
    }

} catch (PDOException $e) {
    error_log("Directory item form error: " . $e->getMessage());
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
    <title><?php echo $item ? 'Edit' : 'Add'; ?> Directory Item - Admin</title>
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
            <h1><?php echo $item ? 'Edit' : 'Add'; ?> Directory Item</h1>
            <form method="GET" action="directory-items.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Directory Items</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-info">
            <p><strong>Required fields:</strong> Title</p>
        </div>

        <form method="POST" action="save-directory-item.php" class="content-form">
            <?php if ($item): ?>
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-input" required
                       value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="form-input"
                       value="<?php echo htmlspecialchars($item['slug'] ?? ''); ?>">
                <small>URL-friendly version of the title. Will be auto-generated if left empty.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="category_id">Category</label>
                <select id="category_id" name="category_id" class="form-input">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($item['category_id']) && $item['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-input" rows="5"><?php 
                    echo htmlspecialchars($item['description'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="website_url">Website URL</label>
                <input type="url" id="website_url" name="website_url" class="form-input"
                       value="<?php echo htmlspecialchars($item['website_url'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-input"
                       value="<?php echo htmlspecialchars($item['contact_email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="contact_phone">Contact Phone</label>
                <input type="tel" id="contact_phone" name="contact_phone" class="form-input"
                       value="<?php echo htmlspecialchars($item['contact_phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Address</label>
                <textarea id="address" name="address" class="form-input" rows="3"><?php 
                    echo htmlspecialchars($item['address'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group checkbox-field">
                <label class="checkbox-label">
                    <input type="checkbox" name="featured" value="1"
                           <?php echo (isset($item['featured']) && $item['featured'] == 1) ? 'checked' : ''; ?>>
                    Featured
                </label>
            </div>

            <div class="form-group checkbox-field">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_published" value="1"
                           <?php echo (isset($item['is_published']) && $item['is_published'] == 1) ? 'checked' : ''; ?>>
                    Published
                </label>
            </div>

            <div class="form-group">
                <label class="form-label" for="published_at">Published at</label>
                <input type="datetime-local" id="published_at" name="published_at" class="form-input"
                       value="<?php echo isset($item['published_at']) ? date('Y-m-d\TH:i', strtotime($item['published_at'])) : date('Y-m-d\TH:i'); ?>">
                <small>Format: YYYY-MM-DD HH:MM (pre-filled with current date/time)</small>
            </div>

            <div class="form-group">
                <button type="submit" class="form-submit"><?php echo $item ? 'Update' : 'Add'; ?> Directory Item</button>
            </div>
        </form>

        <?php if ($item): ?>
            <div class="form-metadata">
                <p>Created: <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></p>
                <p>Last Updated: <?php echo date('M j, Y g:i A', strtotime($item['updated_at'])); ?></p>
                <p>ID: <?php echo $item['id']; ?></p>
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .checkbox-field {
            margin-bottom: 15px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
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