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

    // Get author if editing
    $author = null;
    if (isset($_GET['id'])) {
        $stmt = $db->prepare("SELECT * FROM authors WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $author = $stmt->fetch();
        
        if (!$author) {
            header("Location: authors.php");
            exit;
        }
    }

    // Get all columns from authors table
    $columns = [];
    $stmt = $db->query("DESCRIBE authors");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Check if slug column exists
    $hasSlugColumn = in_array('slug', $columns);
    
    // Check if email column exists
    $hasEmailColumn = in_array('email', $columns);

} catch (PDOException $e) {
    error_log("Author form error: " . $e->getMessage());
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
    <title><?php echo $author ? 'Edit' : 'Add'; ?> Author - Admin</title>
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
            <h1><?php echo $author ? 'Edit' : 'Add'; ?> Author</h1>
            <form method="GET" action="authors.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #6c757d;">Back to Authors</button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="save-author.php" class="content-form">
            <?php if ($author): ?>
                <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="name">Name</label>
                <input type="text" id="name" name="name" class="form-input" required
                       value="<?php echo htmlspecialchars($author['name'] ?? ''); ?>">
            </div>

            <?php if ($hasSlugColumn): ?>
            <div class="form-group">
                <label class="form-label" for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="form-input" required
                       value="<?php echo htmlspecialchars($author['slug'] ?? ''); ?>"
                       placeholder="author-name-in-lowercase">
                <small>Use lowercase letters, numbers, and hyphens only. No spaces.</small>
            </div>
            <?php endif; ?>

            <?php if ($hasEmailColumn): ?>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-input" required
                       value="<?php echo htmlspecialchars($author['email'] ?? ''); ?>">
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="bio">Bio</label>
                <textarea id="bio" name="bio" class="form-input" rows="5"><?php 
                    echo htmlspecialchars($author['bio'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="form-submit">Save Author</button>
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
        small {
            color: #666;
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }
    </style>
    <script>
        // Auto-generate slug from name
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            
            if (nameInput && slugInput) {
                nameInput.addEventListener('input', function() {
                    // Only auto-generate if slug is empty or hasn't been manually edited
                    if (!slugInput.value || slugInput._autoGenerated) {
                        const slug = nameInput.value
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