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
    <link rel="stylesheet" href="/admin/assets/css/modern-admin.css">
    <!-- Add Bootstrap CSS as a fallback -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Basic styling in case the CSS file doesn't load */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .admin-header {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-container {
            display: flex;
            align-items: center;
        }
        .logo {
            background-color: #007bff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .nav-menu {
            margin-bottom: 20px;
        }
        .nav-link {
            margin-right: 5px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        .dashboard-card {
            flex: 1 1 200px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: #fff;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 6px 12px;
            margin-bottom: 0;
            font-size: 14px;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-success {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-info {
            color: #fff;
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .content-list {
            list-style: none;
            padding: 0;
        }
        .content-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .admin-footer {
            margin-top: 30px;
            padding: 20px 0;
            border-top: 1px solid #ddd;
            text-align: center;
        }
    </style>
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
            <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                <button type="submit" formaction="dashboard.php" class="nav-link active">Dashboard</button>
                <button type="submit" formaction="content/stories.php" class="nav-link">Stories</button>
                <button type="submit" formaction="content/blog-posts.php" class="nav-link">Blog Posts</button>
                <button type="submit" formaction="content/authors.php" class="nav-link">Authors</button>
                <button type="submit" formaction="content/tags.php" class="nav-link">Tags</button>
                <button type="submit" formaction="content/games.php" class="nav-link">Games</button>
                <button type="submit" formaction="content/directory-items.php" class="nav-link">Directory</button>
                <button type="submit" formaction="content/ai-tools.php" class="nav-link">AI Tools</button>
                <button type="submit" formaction="content/media.php" class="nav-link">Media</button>
                <button type="submit" formaction="test_tools.php" class="nav-link">Test Tools</button>
            </form>
        </nav>

        <div class="page-header mb-4">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-description">Welcome to the Stories Admin Dashboard. Manage all your content from here.</p>
            <div class="mt-3">
                <!-- Fix the diagnostic dashboard link -->
                <a href="https://api.storiesfromtheweb.org/diagnostic-dashboard.php" target="_blank" class="btn btn-info">
                    <span class="icon-view"></span> Diagnostic Dashboard
                </a>
                <a href="https://api.storiesfromtheweb.org/docs/comprehensive-system-architecture-new.php" target="_blank" class="btn btn-info">
                    <span class="icon-view"></span> View System Documentation
                </a>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h3>Stories</h3>
                <div class="stat-number"><?php echo $stats['stories']; ?></div>
                <div class="stat-actions">
                    <form method="GET" action="content/stories.php" style="margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Manage</button>
                    </form>
                    <form method="GET" action="content/story-form.php">
                        <button type="submit" class="btn btn-success btn-sm">Add New</button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3>Blog Posts</h3>
                <div class="stat-number"><?php echo $stats['blog_posts']; ?></div>
                <div class="stat-actions">
                    <form method="GET" action="content/blog-posts.php" style="margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Manage</button>
                    </form>
                    <form method="GET" action="content/post-form.php">
                        <button type="submit" class="btn btn-success btn-sm">Add New</button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3>Authors</h3>
                <div class="stat-number"><?php echo $stats['authors']; ?></div>
                <div class="stat-actions">
                    <form method="GET" action="content/authors.php" style="margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Manage</button>
                    </form>
                    <form method="GET" action="content/author-form.php">
                        <button type="submit" class="btn btn-success btn-sm">Add New</button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3>Games</h3>
                <div class="stat-number"><?php echo $stats['games']; ?></div>
                <div class="stat-actions">
                    <form method="GET" action="content/games.php" style="margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Manage</button>
                    </form>
                    <form method="GET" action="content/game-form.php">
                        <button type="submit" class="btn btn-success btn-sm">Add New</button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3>Directory Items</h3>
                <div class="stat-number"><?php echo $stats['directory_items']; ?></div>
                <div class="stat-actions">
                    <form method="GET" action="content/directory-items.php" style="margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Manage</button>
                    </form>
                    <form method="GET" action="content/directory-item-form.php">
                        <button type="submit" class="btn btn-success btn-sm">Add New</button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3>AI Tools</h3>
                <div class="stat-number"><?php echo $stats['ai_tools']; ?></div>
                <div class="stat-actions">
                    <form method="GET" action="content/ai-tools.php" style="margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Manage</button>
                    </form>
                    <form method="GET" action="content/ai-tool-form.php">
                        <button type="submit" class="btn btn-success btn-sm">Add New</button>
                    </form>
                </div>
            </div>
            
            <div class="dashboard-card">
                <h3>Media</h3>
                <div class="stat-number"><?php echo isset($stats['media']) ? $stats['media'] : 0; ?></div>
                <div class="stat-actions">
                    <form method="GET" action="content/media.php" style="margin-right: 0.5rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Manage</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Content Sections with CSS-only Expand/Collapse -->
        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title">Recent Content</h2>
            </div>
            
            <!-- Stories Section -->
            <div class="section-body">
                <input type="checkbox" id="toggle-stories" class="collapsible-toggle" checked>
                <div class="collapsible">
                    <label for="toggle-stories" class="collapsible-header">
                        Stories
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
                                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <form method="GET" action="content/view-story.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="content/story-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                            <form method="POST" action="content/delete-story.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this story?')">
                                                    <span class="icon-delete"></span> Delete
                                                </button>
                                            </form>
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
                        Blog Posts
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
                                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <form method="GET" action="content/view-post.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="content/post-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                            <form method="POST" action="content/delete-post.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this post?')">
                                                    <span class="icon-delete"></span> Delete
                                                </button>
                                            </form>
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
                        Authors
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
                                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <form method="GET" action="content/view-author.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="content/author-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                            <form method="POST" action="content/delete-author.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this author?')">
                                                    <span class="icon-delete"></span> Delete
                                                </button>
                                            </form>
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
                        Games
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
                                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <form method="GET" action="content/view-game.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="content/game-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                            <form method="POST" action="content/delete-game.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this game?')">
                                                    <span class="icon-delete"></span> Delete
                                                </button>
                                            </form>
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
                        Directory Items
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
                                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <form method="GET" action="content/view-directory-item.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="content/directory-item-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                            <form method="POST" action="content/delete-directory-item.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this directory item?')">
                                                    <span class="icon-delete"></span> Delete
                                                </button>
                                            </form>
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
        </div>
    </footer>
    
    <!-- Add Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>