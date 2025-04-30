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

    // Check if stories table has author_id column
    $hasStoriesAuthorId = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM stories LIKE 'author_id'");
        $hasStoriesAuthorId = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Check if blog_posts table has author_id column
    $hasBlogPostsAuthorId = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM blog_posts LIKE 'author_id'");
        $hasBlogPostsAuthorId = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Check if story_authors junction table exists
    $hasStoryAuthorsTable = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
        $hasStoryAuthorsTable = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Build the query based on available tables and columns
    $storyCountQuery = "0"; // Default to 0

    if ($hasStoryAuthorsTable) {
        // Use the junction table if it exists
        $storyCountQuery = "(SELECT COUNT(*) FROM story_authors sa WHERE sa.author_id = a.id)";
    } else if ($hasStoriesAuthorId) {
        // Fall back to direct column if junction table doesn't exist
        $storyCountQuery = "(SELECT COUNT(*) FROM stories WHERE author_id = a.id)";
    }

    $postCountQuery = $hasBlogPostsAuthorId
        ? "(SELECT COUNT(*) FROM blog_posts WHERE author_id = a.id)"
        : "0";

    // Get all authors with content counts
    $query = "SELECT a.*,
              $storyCountQuery as story_count,
              $postCountQuery as post_count
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
                <button type="submit" formaction="stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="authors.php" class="nav-link active">Authors</button>
                <button type="submit" formaction="tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="games.php" class="nav-link">Games</button>
                <button type="submit" formaction="directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="page-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Authors</h1>
                <p class="page-description">Manage all your authors from here.</p>
            </div>
            <form method="GET" action="author-form.php">
                <button type="submit" class="btn btn-success">
                    <span class="icon-edit"></span> Add New Author
                </button>
            </form>
        </div>

        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Bio</th>
                        <th>Stories</th>
                        <th>Blog Posts</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($authors)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No authors found. Add your first author!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($authors as $author): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($author['name']); ?></td>
                                <td><?php echo htmlspecialchars($author['email']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($author['author_type'] ?? 'retail')); ?></td>
                                <td><?php echo htmlspecialchars(substr($author['bio'] ?? '', 0, 100) . (strlen($author['bio'] ?? '') > 100 ? '...' : '')); ?></td>
                                <td><?php echo $author['story_count']; ?></td>
                                <td><?php echo $author['post_count']; ?></td>
                                <td>
                                    <div class="table-actions">
                                        <form method="GET" action="view-author.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                                            <button type="submit" class="btn btn-info btn-sm">
                                                <span class="icon-view"></span> View
                                            </button>
                                        </form>
                                        <form method="GET" action="author-form.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <span class="icon-edit"></span> Edit
                                            </button>
                                        </form>
                                        <form method="GET" action="author-delete.php" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <span class="icon-delete"></span> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>