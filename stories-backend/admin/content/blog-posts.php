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

try {

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

    // Check if author_id column exists
    $hasAuthorIdColumn = in_array('author_id', $columns);
    $authorJoinCondition = $hasAuthorIdColumn ? "bp.author_id = a.id" : "1=0"; // No join if no author_id

    // Get all blog posts with author names and tags
    $query = "SELECT bp.*, a.name as author_name";

    // Add tags subquery if the post_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE '$postTagsTableName'");
    if ($stmt->rowCount() > 0) {
        $query .= ", (SELECT GROUP_CONCAT(t.name ORDER BY t.name ASC SEPARATOR ', ')
                   FROM $postTagsTableName pt
                   JOIN tags t ON pt.tag_id = t.id
                   WHERE pt.post_id = bp.id) as tags";
    } else {
        $query .= ", '' as tags";
    }

    $query .= " FROM $blogTableName bp
               LEFT JOIN authors a ON $authorJoinCondition
               ORDER BY bp.created_at DESC";

    $posts = $db->query($query)->fetchAll();

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
<form method="GET" action="post-form.php">
    <button type="submit" class="btn btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Post
    </button>
</form>
';

// Include header
require_once '../includes/header.php';

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

// Include footer
include_once '../includes/footer.php';