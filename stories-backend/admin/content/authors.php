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

    // Check if authors table exists
    $stmt = $db->query("SHOW TABLES LIKE 'authors'");
    if ($stmt->rowCount() === 0) {
        // Create authors table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            bio TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Get all authors with content counts
    $query = "SELECT a.*, 
              (SELECT COUNT(*) FROM stories WHERE author_id = a.id) as story_count,
              (SELECT COUNT(*) FROM blog_posts WHERE author_id = a.id) as post_count
              FROM authors a 
              ORDER BY a.name ASC";
    $authors = $db->query($query)->fetchAll();

} catch (PDOException $e) {
    error_log("Authors page error: " . $e->getMessage());
    $error = "Error loading authors. Please try again.";
    $authors = [];
}

// Check for success/error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
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
    <title>Authors - Admin</title>
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
            <h1>Authors</h1>
            <form method="GET" action="author-form.php" style="display: inline;">
                <button type="submit" class="form-submit">Add New Author</button>
            </form>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Bio</th>
                    <th>Stories</th>
                    <th>Blog Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($authors)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No authors found. Add your first author!</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($authors as $author): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($author['name']); ?></td>
                            <td><?php echo htmlspecialchars($author['email']); ?></td>
                            <td><?php echo htmlspecialchars(substr($author['bio'] ?? '', 0, 100) . (strlen($author['bio'] ?? '') > 100 ? '...' : '')); ?></td>
                            <td><?php echo $author['story_count']; ?></td>
                            <td><?php echo $author['post_count']; ?></td>
                            <td>
                                <form method="GET" action="author-form.php" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                                    <button type="submit" class="form-submit">Edit</button>
                                </form>
                                <?php if ($author['story_count'] == 0 && $author['post_count'] == 0): ?>
                                    <form method="POST" action="delete-author.php" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                                        <button type="submit" class="form-submit" style="background: #dc3545;"
                                                onclick="return confirm('Are you sure you want to delete this author?')">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
        .text-center {
            text-align: center;
            padding: 20px;
        }
    </style>
</body>
</html>