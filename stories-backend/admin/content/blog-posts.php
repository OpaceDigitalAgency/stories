<?php
/**
 * Blog Posts Admin Page
 *
 * This page displays a list of all blog posts and allows for searching, filtering, and bulk actions.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Enable detailed error reporting
    error_log("Starting blog posts page load...");
    
    // Verify database connection
    if (!$db) {
        throw new Exception("Database connection is not available");
    }
    error_log("Database connection verified");

    // Check if blog_posts or blog table exists
    $blogTableName = 'blog_posts';
    $stmt = $db->query("SHOW TABLES LIKE 'blog_posts'");
    if ($stmt->rowCount() === 0) {
        // Check if blog table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog'");
        if ($stmt->rowCount() > 0) {
            $blogTableName = 'blog';
        } else {
            // Create blog_posts table if neither exists
            $db->exec("CREATE TABLE IF NOT EXISTS blog_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                author_id INT NOT NULL,
                content TEXT NOT NULL,
                excerpt TEXT,
                status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )");
        }
    }

    // Check if post_tags table exists
    $postTagsTableName = 'post_tags';
    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
    if ($stmt->rowCount() === 0) {
        // Check if blog_tags table exists instead
        $stmt = $db->query("SHOW TABLES LIKE 'blog_tags'");
        if ($stmt->rowCount() > 0) {
            $postTagsTableName = 'blog_tags';
        } else {
            // Create post_tags table if neither exists
            $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
                post_id INT NOT NULL,
                tag_id INT NOT NULL,
                PRIMARY KEY (post_id, tag_id)
            )");
        }
    }

    // Get all columns from the blog table
    $columns = [];
    $stmt = $db->query("DESCRIBE $blogTableName");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    $offset = ($page - 1) * $perPage;

    // Check if author_id column exists
    $hasAuthorIdColumn = in_array('author_id', $columns);
    $authorJoinCondition = $hasAuthorIdColumn ? "bp.author_id = a.id" : "1=0"; // No join if no author_id

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM $blogTableName";
    $totalItems = $db->query($countQuery)->fetchColumn();
    error_log("Total blog posts found: $totalItems");

    // Get blog posts with pagination
    $query = "
        SELECT bp.id,
               bp.title,
               bp.content,
               bp.excerpt,
               bp.status,
               bp.created_at,
               bp.updated_at,
               a.name as author_name,
               GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags
        FROM $blogTableName bp
        LEFT JOIN authors a ON $authorJoinCondition
        LEFT JOIN $postTagsTableName pt ON bp.id = pt.post_id
        LEFT JOIN tags t ON pt.tag_id = t.id
        GROUP BY bp.id, bp.title, bp.content, bp.excerpt, bp.status, bp.created_at, bp.updated_at, a.name
        ORDER BY bp.created_at DESC
        LIMIT $offset, $perPage
    ";
    error_log("Executing query: " . $query);

    $stmt = $db->prepare($query);
    $stmt->execute();
    $posts = $stmt->fetchAll();
    error_log("Number of posts fetched: " . count($posts));

} catch (PDOException $e) {
    error_log("Blog posts page error: " . $e->getMessage());
    $error = "Error loading blog posts. Please try again.";
    $posts = [];
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

// Set page variables for header
$pageTitle = 'Blog Posts';
$currentPage = 'blog-posts';
$pageDescription = 'Manage all your blog posts from here.';
$pageActions = '
<div class="d-flex gap-2">
    <form method="GET" action="post-form.php">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i> Add New Post
        </button>
    </form>
    <button onclick="window.location.reload()" class="btn btn-secondary">
        <i class="fas fa-sync" aria-hidden="true"></i> Refresh
    </button>
    <button onclick="window.location.href=\'?debug=1\'" class="btn btn-info">
        <i class="fas fa-bug" aria-hidden="true"></i> Debug Mode
    </button>
</div>
';

// Add additional debug logging if debug mode is enabled
if (isset($_GET['debug']) && $_GET['debug'] === '1') {
    error_log("DEBUG MODE ENABLED");
    error_log("PHP Version: " . phpversion());
    error_log("MySQL Version: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION));
    error_log("Current User: " . ($user['name'] ?? 'Unknown'));
    error_log("Session Status: " . session_status());
    error_log("Memory Usage: " . memory_get_usage(true) / 1024 / 1024 . " MB");
}

// Include header
require_once '../includes/header.php';

// Display any database connection errors
if (!$db) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-database"></i> Database Connection Error</h4>';
    echo '<p>Could not connect to the database. Please check your configuration.</p>';
    echo '</div>';
}

// Display any errors prominently
if (isset($error)) {
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error Loading Blog Posts</h4>';
    echo '<p>' . htmlspecialchars($error) . '</p>';
    echo '<hr>';
    echo '<p class="mb-0">Please check the error logs for more details or contact support.</p>';
    echo '</div>';
}

// Display debug information for administrators
if ($user && $user['role'] === 'admin') {
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-info text-white">';
    echo '<i class="fas fa-bug"></i> Debug Information';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<pre class="mb-0">';
    echo "Database Connection: " . ($db ? "Success" : "Failed") . "\n";
    echo "Total Posts Found: " . ($totalItems ?? 0) . "\n";
    echo "Posts Loaded: " . (isset($posts) ? count($posts) : 0) . "\n";
    echo "Current Page: " . $page . "\n";
    echo "Items Per Page: " . $perPage . "\n";
    echo '</pre>';
    echo '</div>';
    echo '</div>';
}

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('posts', ['title', 'content', 'author', 'tags']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('posts', ['delete', 'publish', 'unpublish']);
}

// Include status indicator component
include_once '../includes/status-indicator-component.php';

// Include table component
include_once '../includes/table-component.php';
if (function_exists('renderTable')) {
    // Define columns
    $columns = [
        'title' => 'Title',
        'author_name' => 'Author',
        'tags' => 'Tags',
        'status' => 'Status',
        'created_at' => 'Created',
        'updated_at' => 'Updated'
    ];

    // Define custom formatters
    $customFormatters = [
        'author_name' => function($post, $key) {
            return htmlspecialchars($post[$key] ?? 'Unknown');
        },
        'tags' => function($post, $key) {
            return htmlspecialchars($post[$key] ?? '');
        },
        'status' => function($post, $key) {
            $status = $post[$key] ?? 'draft';
            if (function_exists('getStatusIndicator')) {
                return getStatusIndicator($status);
            }
            return ucfirst(htmlspecialchars($status));
        },
        'created_at' => function($post, $key) {
            return date('M j, Y', strtotime($post[$key]));
        },
        'updated_at' => function($post, $key) {
            return date('M j, Y', strtotime($post[$key]));
        }
    ];

    // Render the table
    renderTable($posts, $columns, [
        'content_type' => 'posts',
        'name_field' => 'title',
        'empty_message' => 'No blog posts found. Add your first post!',
        'custom_formatters' => $customFormatters,
        'view_url' => 'view-post.php?id={id}',
        'edit_url' => 'post-form.php?id={id}',
        'delete_url' => 'delete-post.php'
    ]);
}

// Include pagination component
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination')) {
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
include_once '../includes/footer.php';