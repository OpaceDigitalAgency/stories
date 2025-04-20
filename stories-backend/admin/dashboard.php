<?php
require_once 'includes/config.php';
session_start();

// Debug session
error_log("Session data: " . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    // Get user info
    $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // User not found or inactive, destroy session and redirect
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // Get content statistics
    $stats = [
        'stories' => $db->query("SELECT COUNT(*) FROM stories")->fetchColumn(),
        'authors' => $db->query("SELECT COUNT(*) FROM authors")->fetchColumn(),
        'blog_posts' => $db->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn(),
        'games' => $db->query("SELECT COUNT(*) FROM games")->fetchColumn(),
        'directory_items' => $db->query("SELECT COUNT(*) FROM directory_items")->fetchColumn(),
        'ai_tools' => $db->query("SELECT COUNT(*) FROM ai_tools")->fetchColumn()
    ];

    // Get recent content
    $recentStories = $db->query("SELECT title, created_at FROM stories ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recentPosts = $db->query("SELECT title, created_at FROM blog_posts ORDER BY created_at DESC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error loading dashboard data. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stories Admin</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #4a6cf7;
            margin: 10px 0;
        }
        .nav-menu {
            background: white;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-menu a {
            display: inline-block;
            padding: 8px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav-menu a:hover {
            background: #f5f5f5;
        }
        .recent-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .recent-list {
            list-style: none;
            padding: 0;
        }
        .recent-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .recent-list li:last-child {
            border: none;
        }
        .user-info {
            text-align: right;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['name']); ?> |
            <a href="logout.php" class="form-submit" style="background: #dc3545;">Logout</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <nav class="nav-menu">
            <a href="content/stories.php">Stories</a>
            <a href="content/blog-posts.php">Blog Posts</a>
            <a href="content/authors.php">Authors</a>
            <a href="content/tags.php">Tags</a>
            <a href="content/games.php">Games</a>
            <a href="content/directory-items.php">Directory</a>
            <a href="content/ai-tools.php">AI Tools</a>
            <a href="content/media.php">Media</a>
        </nav>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Stories</h3>
                <div class="stat-number"><?php echo $stats['stories'] ?? 0; ?></div>
                <a href="content/stories.php" class="form-submit">Manage Stories</a>
            </div>
            
            <div class="stat-card">
                <h3>Blog Posts</h3>
                <div class="stat-number"><?php echo $stats['blog_posts'] ?? 0; ?></div>
                <a href="content/blog-posts.php" class="form-submit">Manage Posts</a>
            </div>
            
            <div class="stat-card">
                <h3>Authors</h3>
                <div class="stat-number"><?php echo $stats['authors'] ?? 0; ?></div>
                <a href="content/authors.php" class="form-submit">Manage Authors</a>
            </div>
            
            <div class="stat-card">
                <h3>Games</h3>
                <div class="stat-number"><?php echo $stats['games'] ?? 0; ?></div>
                <a href="content/games.php" class="form-submit">Manage Games</a>
            </div>
            
            <div class="stat-card">
                <h3>Directory Items</h3>
                <div class="stat-number"><?php echo $stats['directory_items'] ?? 0; ?></div>
                <a href="content/directory-items.php" class="form-submit">Manage Directory</a>
            </div>
            
            <div class="stat-card">
                <h3>AI Tools</h3>
                <div class="stat-number"><?php echo $stats['ai_tools'] ?? 0; ?></div>
                <a href="content/ai-tools.php" class="form-submit">Manage AI Tools</a>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="recent-content">
                <h3>Recent Stories</h3>
                <ul class="recent-list">
                    <?php foreach ($recentStories as $story): ?>
                        <li>
                            <?php echo htmlspecialchars($story['title']); ?>
                            <small style="color: #666;">
                                (<?php echo date('M j, Y', strtotime($story['created_at'])); ?>)
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="recent-content">
                <h3>Recent Blog Posts</h3>
                <ul class="recent-list">
                    <?php foreach ($recentPosts as $post): ?>
                        <li>
                            <?php echo htmlspecialchars($post['title']); ?>
                            <small style="color: #666;">
                                (<?php echo date('M j, Y', strtotime($post['created_at'])); ?>)
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>