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
    // Get content statistics
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
            <form method="POST" action="logout.php" style="display: inline;">
                <button type="submit" class="form-submit" style="background: #dc3545;">Logout</button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <nav class="nav-menu">
            <form method="GET" style="display: inline;">
                <button type="submit" formaction="content/stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="content/blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="content/authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="content/tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="content/games.php" class="nav-link">Games</button>
                <button type="submit" formaction="content/directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="content/ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="content/media.php" class="nav-link">Media</button>
            </form>
        </nav>

        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Stories</h3>
                <div class="stat-number"><?php echo $stats['stories'] ?? 0; ?></div>
                <form method="GET" action="content/stories.php">
                    <button type="submit" class="form-submit">Manage Stories</button>
                </form>
            </div>
            
            <div class="stat-card">
                <h3>Blog Posts</h3>
                <div class="stat-number"><?php echo $stats['blog_posts'] ?? 0; ?></div>
                <form method="GET" action="content/blog-posts.php">
                    <button type="submit" class="form-submit">Manage Posts</button>
                </form>
            </div>
            
            <div class="stat-card">
                <h3>Authors</h3>
                <div class="stat-number"><?php echo $stats['authors'] ?? 0; ?></div>
                <form method="GET" action="content/authors.php">
                    <button type="submit" class="form-submit">Manage Authors</button>
                </form>
            </div>
            
            <div class="stat-card">
                <h3>Games</h3>
                <div class="stat-number"><?php echo $stats['games'] ?? 0; ?></div>
                <form method="GET" action="content/games.php">
                    <button type="submit" class="form-submit">Manage Games</button>
                </form>
            </div>
            
            <div class="stat-card">
                <h3>Directory Items</h3>
                <div class="stat-number"><?php echo $stats['directory_items'] ?? 0; ?></div>
                <form method="GET" action="content/directory-items.php">
                    <button type="submit" class="form-submit">Manage Directory</button>
                </form>
            </div>
            
            <div class="stat-card">
                <h3>AI Tools</h3>
                <div class="stat-number"><?php echo $stats['ai_tools'] ?? 0; ?></div>
                <form method="GET" action="content/ai-tools.php">
                    <button type="submit" class="form-submit">Manage AI Tools</button>
                </form>
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
    </style>
</body>
</html>