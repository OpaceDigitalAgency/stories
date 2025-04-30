<?php
/**
 * Dashboard Page
 *
 * This is the main dashboard page for the admin panel.
 */

// Include auth check
include_once 'includes/auth-check.php';

// Include database connection
include_once 'includes/db-connect.php';

try {
    // Get content statistics

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

// Set page variables for header
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$pageDescription = 'Welcome to the Stories Admin Dashboard. Manage all your content from here.';
$pageActions = '
<div class="d-flex gap-2">
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
';

// Include header
include_once 'includes/header.php';

// Display error message if any
if (isset($error)): ?>
    <div class="error" role="alert">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<h2 class="section-title"><i class="fas fa-chart-pie"></i> Content Overview</h2>

        <div class="dashboard-cards">
            <div class="dashboard-card content-card" aria-labelledby="stories-card-title">
                <h3 id="stories-card-title"><i class="fas fa-book" aria-hidden="true"></i> Stories</h3>
                <div class="stat-number"><?php echo $stats['stories']; ?></div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    <span class="visually-hidden">Increased by</span> 5% from last month
                </div>
                <div class="stat-actions">
                    <a href="content/stories.php" class="btn btn-primary btn-sm" aria-label="Manage Stories">
                        <i class="fas fa-list" aria-hidden="true"></i> Manage
                    </a>
                    <a href="content/story-form.php" class="btn btn-success btn-sm" aria-label="Add New Story">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card content-card" aria-labelledby="blog-posts-card-title">
                <h3 id="blog-posts-card-title"><i class="fas fa-newspaper" aria-hidden="true"></i> Blog Posts</h3>
                <div class="stat-number"><?php echo $stats['blog_posts']; ?></div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    <span class="visually-hidden">Increased by</span> 3% from last month
                </div>
                <div class="stat-actions">
                    <a href="content/blog-posts.php" class="btn btn-primary btn-sm" aria-label="Manage Blog Posts">
                        <i class="fas fa-list" aria-hidden="true"></i> Manage
                    </a>
                    <a href="content/post-form.php" class="btn btn-success btn-sm" aria-label="Add New Blog Post">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card user-card" aria-labelledby="authors-card-title">
                <h3 id="authors-card-title"><i class="fas fa-user-edit" aria-hidden="true"></i> Authors</h3>
                <div class="stat-number"><?php echo $stats['authors']; ?></div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    <span class="visually-hidden">Increased by</span> 2% from last month
                </div>
                <div class="stat-actions">
                    <a href="content/authors.php" class="btn btn-primary btn-sm" aria-label="Manage Authors">
                        <i class="fas fa-list" aria-hidden="true"></i> Manage
                    </a>
                    <a href="content/author-form.php" class="btn btn-success btn-sm" aria-label="Add New Author">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card content-card" aria-labelledby="games-card-title">
                <h3 id="games-card-title"><i class="fas fa-gamepad" aria-hidden="true"></i> Games</h3>
                <div class="stat-number"><?php echo $stats['games']; ?></div>
                <div class="stat-trend trend-down">
                    <i class="fas fa-arrow-down" aria-hidden="true"></i>
                    <span class="visually-hidden">Decreased by</span> 1% from last month
                </div>
                <div class="stat-actions">
                    <a href="content/games.php" class="btn btn-primary btn-sm" aria-label="Manage Games">
                        <i class="fas fa-list" aria-hidden="true"></i> Manage
                    </a>
                    <a href="content/game-form.php" class="btn btn-success btn-sm" aria-label="Add New Game">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card content-card" aria-labelledby="directory-card-title">
                <h3 id="directory-card-title"><i class="fas fa-folder" aria-hidden="true"></i> Directory Items</h3>
                <div class="stat-number"><?php echo $stats['directory_items']; ?></div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    <span class="visually-hidden">Increased by</span> 4% from last month
                </div>
                <div class="stat-actions">
                    <a href="content/directory-items.php" class="btn btn-primary btn-sm" aria-label="Manage Directory Items">
                        <i class="fas fa-list" aria-hidden="true"></i> Manage
                    </a>
                    <a href="content/directory-item-form.php" class="btn btn-success btn-sm" aria-label="Add New Directory Item">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card content-card" aria-labelledby="ai-tools-card-title">
                <h3 id="ai-tools-card-title"><i class="fas fa-robot" aria-hidden="true"></i> AI Tools</h3>
                <div class="stat-number"><?php echo $stats['ai_tools']; ?></div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    <span class="visually-hidden">Increased by</span> 8% from last month
                </div>
                <div class="stat-actions">
                    <a href="content/ai-tools.php" class="btn btn-primary btn-sm" aria-label="Manage AI Tools">
                        <i class="fas fa-list" aria-hidden="true"></i> Manage
                    </a>
                    <a href="content/ai-tool-form.php" class="btn btn-success btn-sm" aria-label="Add New AI Tool">
                        <i class="fas fa-plus" aria-hidden="true"></i> Add New
                    </a>
                </div>
            </div>

            <div class="dashboard-card media-card" aria-labelledby="media-card-title">
                <h3 id="media-card-title"><i class="fas fa-images" aria-hidden="true"></i> Media</h3>
                <div class="stat-number"><?php echo isset($stats['media']) ? $stats['media'] : 0; ?></div>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up" aria-hidden="true"></i>
                    <span class="visually-hidden">Increased by</span> 6% from last month
                </div>
                <div class="stat-actions">
                    <a href="content/media.php" class="btn btn-primary btn-sm" aria-label="Manage Media">
                        <i class="fas fa-list" aria-hidden="true"></i> Manage
                    </a>
                    <a href="content/media.php?action=upload" class="btn btn-success btn-sm" aria-label="Upload Media">
                        <i class="fas fa-upload" aria-hidden="true"></i> Upload
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Content Sections with CSS-only Expand/Collapse -->
        <div class="content-section mb-4">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-clock" aria-hidden="true"></i> Recent Activity</h2>
                <p class="section-description">Recently added or updated content across all sections</p>
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

<?php
// Include footer
include_once 'includes/footer.php';
?>