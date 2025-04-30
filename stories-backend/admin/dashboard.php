<?php
require_once '../simple_auth.php';
// Load admin configuration
$config = require __DIR__ . '/includes/config.php';
// Initialize database connection for SimpleAuth
SimpleAuth::initDB($config['db']);
 // Initialize database connection for SimpleAuth
 SimpleAuth::initDB($config);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!SimpleAuth::check()) {
    header("Location: login.php");
    exit;
}

try {
    // Get content statistics
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']};port={$config['db']['port']}",
        $config['db']['user'],
        $config['db']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Initialize stats array
    $stats = [
        'stories' => 0,
        'authors' => 0,
        'blog_posts' => 0,
        'games' => 0,
        'directory_items' => 0,
        'ai_tools' => 0,
        'media' => 0
    ];

    // Check if tables exist before querying
    $tables = [
        'stories',
        'authors',
        'blog_posts',
        'games',
        'directory_items',
        'ai_tools',
        'media'
    ];

    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $stats[$table] = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            }
        } catch (PDOException $e) {
            // Ignore errors for tables that don't exist
            error_log("Dashboard error checking table $table: " . $e->getMessage());
        }
    }

    // Get recent content for each content type
    $contentTypes = [
        'stories' => ['title', 'created_at', 'id'],
        'blog_posts' => ['title', 'created_at', 'id'],
        'authors' => ['name', 'created_at', 'id'],
        'games' => ['title', 'created_at', 'id'],
        'directory_items' => ['title', 'created_at', 'id'],
        'ai_tools' => ['title', 'created_at', 'id'],
        'media' => ['filename', 'created_at', 'id']
    ];

    $recentContent = [];

    foreach ($contentTypes as $table => $fields) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                // Check if the table has these columns
                $columnsExist = true;
                foreach ($fields as $field) {
                    if ($field !== 'id') { // ID is always expected
                        $checkColumn = $db->query("SHOW COLUMNS FROM `$table` LIKE '$field'");
                        if ($checkColumn->rowCount() === 0) {
                            $columnsExist = false;
                            break;
                        }
                    }
                }

                if ($columnsExist) {
                    $titleField = $fields[0]; // First field is the title/name
                    $dateField = $fields[1];  // Second field is the date
                    $idField = $fields[2];    // Third field is the id

                    $query = "SELECT `$idField` as id, `$titleField` as title, `$dateField` as created_at FROM `$table` ORDER BY `$dateField` DESC LIMIT 5";
                    $recentContent[$table] = $db->query($query)->fetchAll();
                }
            }
        } catch (PDOException $e) {
            error_log("Error getting recent $table: " . $e->getMessage());
            $recentContent[$table] = [];
        }
    }

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Error loading dashboard data. Please try again.";
}

// Get user information
$user = SimpleAuth::check();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stories Admin</title>
    <!-- Fix CSS path to ensure it loads correctly -->
    <link rel="stylesheet" href="/admin/assets/css/modern-admin-new.css">
    <!-- Add Font Awesome for better icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- No inline styles needed as we have a comprehensive CSS file -->
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
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <nav class="nav-menu">
            <form method="GET">
                <button type="submit" formaction="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </button>
                <button type="submit" formaction="content/stories.php" class="nav-link">
                    <i class="fas fa-book"></i> Stories
                </button>
                <button type="submit" formaction="content/blog-posts.php" class="nav-link">
                    <i class="fas fa-newspaper"></i> Blog Posts
                </button>
                <button type="submit" formaction="content/authors.php" class="nav-link">
                    <i class="fas fa-user-edit"></i> Authors
                </button>
                <button type="submit" formaction="content/tags.php" class="nav-link">
                    <i class="fas fa-tags"></i> Tags
                </button>
                <button type="submit" formaction="content/games.php" class="nav-link">
                    <i class="fas fa-gamepad"></i> Games
                </button>
                <button type="submit" formaction="content/directory-items.php" class="nav-link">
                    <i class="fas fa-folder"></i> Directory
                </button>
                <button type="submit" formaction="content/ai-tools.php" class="nav-link">
                    <i class="fas fa-robot"></i> AI Tools
                </button>
                <button type="submit" formaction="content/media.php" class="nav-link">
                    <i class="fas fa-images"></i> Media
                </button>
                <button type="submit" formaction="test_tools.php" class="nav-link">
                    <i class="fas fa-tools"></i> Test Tools
                </button>
            </form>
        </nav>

        <div class="page-header mb-4">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-description">Welcome to the Stories Admin Dashboard. Manage all your content from here.</p>
            <div class="mt-3 d-flex gap-2">
                <!-- Fix the diagnostic dashboard link -->
                <a href="https://api.storiesfromtheweb.org/diagnostic-dashboard.php" target="_blank" class="btn btn-info">
                    <i class="fas fa-chart-line"></i> Diagnostic Dashboard
                </a>
                <a href="https://api.storiesfromtheweb.org/docs/comprehensive-system-architecture-new.php" target="_blank" class="btn btn-info">
                    <i class="fas fa-book-open"></i> View System Documentation
                </a>
                <a href="https://api.storiesfromtheweb.org/public/optimize_image.php" target="_blank" class="btn btn-success">
                    <i class="fas fa-image"></i> Image Optimization Tool
                </a>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h3><i class="fas fa-book"></i> Stories</h3>
                <div class="stat-number"><?php echo $stats['stories']; ?></div>
                <div class="stat-actions">
                    <a href="content/stories.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Manage
                    </a>
                    <a href="content/story-form.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-newspaper"></i> Blog Posts</h3>
                <div class="stat-number"><?php echo $stats['blog_posts']; ?></div>
                <div class="stat-actions">
                    <a href="content/blog-posts.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Manage
                    </a>
                    <a href="content/post-form.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-user-edit"></i> Authors</h3>
                <div class="stat-number"><?php echo $stats['authors']; ?></div>
                <div class="stat-actions">
                    <a href="content/authors.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Manage
                    </a>
                    <a href="content/author-form.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-gamepad"></i> Games</h3>
                <div class="stat-number"><?php echo $stats['games']; ?></div>
                <div class="stat-actions">
                    <a href="content/games.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Manage
                    </a>
                    <a href="content/game-form.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-folder"></i> Directory Items</h3>
                <div class="stat-number"><?php echo $stats['directory_items']; ?></div>
                <div class="stat-actions">
                    <a href="content/directory-items.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Manage
                    </a>
                    <a href="content/directory-item-form.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-robot"></i> AI Tools</h3>
                <div class="stat-number"><?php echo $stats['ai_tools']; ?></div>
                <div class="stat-actions">
                    <a href="content/ai-tools.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Manage
                    </a>
                    <a href="content/ai-tool-form.php" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card">
                <h3><i class="fas fa-images"></i> Media</h3>
                <div class="stat-number"><?php echo isset($stats['media']) ? $stats['media'] : 0; ?></div>
                <div class="stat-actions">
                    <a href="content/media.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-list"></i> Manage
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Content Sections with CSS-only Expand/Collapse -->
        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-clock"></i> Recent Content</h2>
            </div>

            <div class="section-body">
                <!-- Stories Section -->
                <input type="checkbox" id="toggle-stories" class="collapsible-toggle" checked>
                <div class="collapsible">
                    <label for="toggle-stories" class="collapsible-header">
                        <i class="fas fa-book"></i> Stories
                    </label>
                    <div class="collapsible-content">
                        <?php if (empty($recentContent['stories'])): ?>
                            <p class="p-3">No stories found.</p>
                        <?php else: ?>
                            <ul class="content-list">
                                <?php foreach ($recentContent['stories'] as $item): ?>
                                    <li class="content-item">
                                        <div>
                                            <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="content-item-meta">
                                                <i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <a href="content/view-story.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="content/story-form.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="content/delete-story.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this story?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Blog Posts Section -->
                <input type="checkbox" id="toggle-blog-posts" class="collapsible-toggle" checked>
                <div class="collapsible">
                    <label for="toggle-blog-posts" class="collapsible-header">
                        <i class="fas fa-newspaper"></i> Blog Posts
                    </label>
                    <div class="collapsible-content">
                        <?php if (empty($recentContent['blog_posts'])): ?>
                            <p class="p-3">No blog posts found.</p>
                        <?php else: ?>
                            <ul class="content-list">
                                <?php foreach ($recentContent['blog_posts'] as $item): ?>
                                    <li class="content-item">
                                        <div>
                                            <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="content-item-meta">
                                                <i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <a href="content/view-post.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="content/post-form.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="content/delete-post.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this post?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Authors Section -->
                <input type="checkbox" id="toggle-authors" class="collapsible-toggle">
                <div class="collapsible">
                    <label for="toggle-authors" class="collapsible-header">
                        <i class="fas fa-user-edit"></i> Authors
                    </label>
                    <div class="collapsible-content">
                        <?php if (empty($recentContent['authors'])): ?>
                            <p class="p-3">No authors found.</p>
                        <?php else: ?>
                            <ul class="content-list">
                                <?php foreach ($recentContent['authors'] as $item): ?>
                                    <li class="content-item">
                                        <div>
                                            <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="content-item-meta">
                                                <i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <a href="content/view-author.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="content/author-form.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="content/delete-author.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this author?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Games Section -->
                <input type="checkbox" id="toggle-games" class="collapsible-toggle">
                <div class="collapsible">
                    <label for="toggle-games" class="collapsible-header">
                        <i class="fas fa-gamepad"></i> Games
                    </label>
                    <div class="collapsible-content">
                        <?php if (empty($recentContent['games'])): ?>
                            <p class="p-3">No games found.</p>
                        <?php else: ?>
                            <ul class="content-list">
                                <?php foreach ($recentContent['games'] as $item): ?>
                                    <li class="content-item">
                                        <div>
                                            <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="content-item-meta">
                                                <i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <a href="content/view-game.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="content/game-form.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="content/delete-game.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this game?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Directory Items Section -->
                <input type="checkbox" id="toggle-directory" class="collapsible-toggle">
                <div class="collapsible">
                    <label for="toggle-directory" class="collapsible-header">
                        <i class="fas fa-folder"></i> Directory Items
                    </label>
                    <div class="collapsible-content">
                        <?php if (empty($recentContent['directory_items'])): ?>
                            <p class="p-3">No directory items found.</p>
                        <?php else: ?>
                            <ul class="content-list">
                                <?php foreach ($recentContent['directory_items'] as $item): ?>
                                    <li class="content-item">
                                        <div>
                                            <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="content-item-meta">
                                                <i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <a href="content/view-directory-item.php?id=<?php echo $item['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="content/directory-item-form.php?id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="content/delete-directory-item.php?id=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this directory item?')">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="admin-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Stories from the Web. All rights reserved.</p>
            <p class="text-muted">Version 2.0 - Professional Admin Dashboard</p>
        </div>
    </footer>
</body>
</html>