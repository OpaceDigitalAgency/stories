<?php
/**
 * Authors Admin Page
 *
 * This page displays a list of all authors and allows for searching, filtering, and bulk actions.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

try {

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

    // Default story count query
    $storyCountQuery = "0";
    
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

// Set page variables for header
$pageTitle = 'Authors';
$currentPage = 'authors';
$pageDescription = 'Manage all your authors from here.';
$pageActions = '
<form method="GET" action="author-form.php">
    <button type="submit" class="btn btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Author
    </button>
</form>
';

// Include header
require_once '../includes/header.php';

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('authors', ['name', 'email', 'bio']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('authors', ['delete']);
}

// Include table component
include_once '../includes/table-component.php';

// Define columns
$columns = [
    'name' => 'Name',
    'email' => 'Email',
    'author_type' => 'Type',
    'bio' => 'Bio',
    'story_count' => 'Stories',
    'post_count' => 'Blog Posts'
];

// Define custom formatters
$customFormatters = [
    'author_type' => function($author, $key) {
        return ucfirst(htmlspecialchars($author[$key] ?? 'retail'));
    },
    'bio' => function($author, $key) {
        return htmlspecialchars(substr($author[$key] ?? '', 0, 100) . (strlen($author[$key] ?? '') > 100 ? '...' : ''));
    }
];

// If the table component is not available, render the table manually
if (!function_exists('renderEnhancedTable')) {
    // Render table
    echo '<div class="table-container">';
    echo '<table id="data-table" class="table">';
    echo '<thead>';
    echo '<tr>';
    foreach ($columns as $label) {
        echo '<th>' . htmlspecialchars($label) . '</th>';
    }
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if ($authors) {
        foreach ($authors as $author) {
            echo '<tr>';
            foreach ($columns as $key => $label) {
                echo '<td>';
                if (isset($customFormatters[$key])) {
                    echo $customFormatters[$key]($author, $key);
                } else {
                    echo isset($author[$key]) ? htmlspecialchars($author[$key]) : '';
                }
                echo '</td>';
            }
            echo '<td>';
            echo '<a href="view-author.php?id=' . $author['id'] . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a> ';
            echo '<a href="author-form.php?id=' . $author['id'] . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a> ';
            echo '<a href="author-delete.php?id=' . $author['id'] . '" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
} else {
    // Use the table component
    renderEnhancedTable($authors, $columns, [
        'content_type' => 'authors',
        'name_field' => 'name',
        'empty_message' => 'No authors found. Add your first author!',
        'custom_formatters' => $customFormatters,
        'view_url' => 'view-author.php?id={id}',
        'edit_url' => 'author-form.php?id={id}',
        'delete_url' => 'delete-author.php'
    ]);
}

// No need for JavaScript includes

// Include footer
require_once '../includes/footer.php';