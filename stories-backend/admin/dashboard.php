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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stories Admin</title>
    <link rel="stylesheet" href="assets/css/modern-admin.css">
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
            </form>
        </nav>

        <div class="page-header mb-4">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-description">Welcome to the Stories Admin Dashboard. Manage all your content from here.</p>
            <div class="mt-3">
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
                
                <!-- AI Tools Section -->
                <input type="checkbox" id="toggle-ai-tools" class="collapsible-toggle">
                <div class="collapsible">
                    <label for="toggle-ai-tools" class="collapsible-header">
                        AI Tools
                    </label>
                    <div class="collapsible-content">
                        <?php if (empty($recentContent['ai_tools'])): ?>
                            <p class="p-3">No AI tools found.</p>
                        <?php else: ?>
                            <ul class="content-list">
                                <?php foreach ($recentContent['ai_tools'] as $item): ?>
                                    <li class="content-item">
                                        <div>
                                            <div class="content-item-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                            <div class="content-item-meta">
                                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <form method="GET" action="content/view-ai-tool.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="GET" action="content/ai-tool-form.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <span class="icon-edit"></span> Edit
                                                </button>
                                            </form>
                                            <form method="POST" action="content/delete-ai-tool.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Are you sure you want to delete this AI tool?')">
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
                
                <!-- Media Section -->
                <input type="checkbox" id="toggle-media" class="collapsible-toggle">
                <div class="collapsible">
                    <label for="toggle-media" class="collapsible-header">
                        Media
                    </label>
                    <div class="collapsible-content">
                        <?php if (empty($recentContent['media'])): ?>
                            <p class="p-3">No media files found.</p>
                        <?php else: ?>
                            <ul class="content-list">
                                <?php foreach ($recentContent['media'] as $item): ?>
                                    <li class="content-item">
                                        <div>
                                            <div class="content-item-title"><?php echo htmlspecialchars($item['title'] ?? $item['filename']); ?></div>
                                            <div class="content-item-meta">
                                                <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="content-item-actions">
                                            <form method="GET" action="content/view-media.php" style="display: inline;">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm">
                                                    <span class="icon-view"></span> View
                                                </button>
                                            </form>
                                            <form method="POST" action="content/media.php" style="display: inline;">
                                                <input type="hidden" name="delete" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Are you sure you want to delete this media file?')">
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
    
    <style>
        /* Dashboard specific styles */
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0.5rem 0;
        }
        
        .stat-actions {
            display: flex;
            margin-top: 1rem;
        }
        
        /* Collapsible sections */
        .collapsible {
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        
        .collapsible-toggle {
            display: none;
        }
        
        .collapsible-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background-color: var(--gray-50);
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-800);
            position: relative;
        }
        
        .collapsible-header::after {
            content: "▼";
            font-size: 0.8rem;
            transition: transform 0.2s ease;
        }
        
        .collapsible-toggle:checked + .collapsible .collapsible-header::after {
            transform: rotate(180deg);
        }
        
        .collapsible-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .collapsible-toggle:checked + .collapsible .collapsible-content {
            max-height: 1000px;
        }
        
        /* Content list */
        .content-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .content-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .content-item:last-child {
            border-bottom: none;
        }
        
        .content-item-title {
            font-weight: 500;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }
        
        .content-item-meta {
            font-size: 0.85rem;
            color: var(--gray-600);
        }
        
        .content-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .p-3 {
            padding: 1rem;
        }
        
        @media (max-width: 768px) {
            .content-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .content-item-actions {
                margin-top: 1rem;
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</body>
</html>