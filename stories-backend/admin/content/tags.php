<?php
/**
 * Tags Admin Page
 *
 * This page displays a list of all tags and allows for searching, filtering, and bulk actions.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

try {

    // Check if tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tags'");
    if ($stmt->rowCount() === 0) {
        // Create tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        )");
    }

    // Check if story_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'story_tags'");
    if ($stmt->rowCount() === 0) {
        // Create story_tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS story_tags (
            story_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (story_id, tag_id)
        )");
    }

    // Check if post_tags table exists
    $stmt = $db->query("SHOW TABLES LIKE 'post_tags'");
    if ($stmt->rowCount() === 0) {
        // Create post_tags table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS post_tags (
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (post_id, tag_id)
        )");
    }

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;
    $offset = ($page - 1) * $perPage;

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM tags";
    $totalItems = $db->query($countQuery)->fetchColumn();

    // Get all tags with usage counts
    $query = "SELECT t.*,
              (SELECT COUNT(*) FROM story_tags WHERE tag_id = t.id) as story_count,
              (SELECT COUNT(*) FROM post_tags WHERE tag_id = t.id) as post_count
              FROM tags t
              ORDER BY t.name ASC
              LIMIT $offset, $perPage";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tags = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Tags page error: " . $e->getMessage());
    $error = "Error loading tags. Please try again.";
    $tags = [];
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
$pageTitle = 'Tags';
$currentPage = 'tags';
$pageDescription = 'Manage all your tags from here.';

// Add extra head content for premium features
$extraHeadContent = '
<!-- Add Premium Admin CSS -->
<link rel="stylesheet" href="../assets/css/premium-admin.css">
<!-- Add Live Search JS -->
<script src="../assets/js/live-search.js"></script>
<!-- Add Inline Editing JS -->
<script src="../assets/js/inline-editing.js"></script>
';

$pageActions = '
<div class="premium-flex premium-gap-2">
    <a href="tag-form.php" class="premium-btn premium-btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Tag
    </a>
    <button onclick="window.location.reload()" class="premium-btn premium-btn-secondary">
        <i class="fas fa-sync" aria-hidden="true"></i> Refresh
    </button>
</div>
';

// Include header
require_once '../includes/header.php';

// Include live search component
include_once '../includes/live-search-component.php';
if (function_exists('renderLiveSearchComponent')) {
    renderLiveSearchComponent('tags', ['name', 'slug', 'description'], 'tags-table');
} else {
    // Fallback to regular search component
    include_once '../includes/search-component.php';
    if (function_exists('renderSearchComponent')) {
        renderSearchComponent('tags', ['name', 'slug', 'description']);
    }
}

// Include enhanced table component
include_once '../includes/enhanced-table-component.php';
if (function_exists('renderEnhancedTable')) {
    // Define columns
    $columns = [
        'name' => 'Name',
        'slug' => 'Slug',
        'description' => 'Description',
        'story_count' => 'Stories',
        'post_count' => 'Blog Posts'
    ];

    // Define which fields are editable inline
    $editableFields = ['name', 'description'];

    // Render the enhanced table
    renderEnhancedTable(
        $tags,
        $columns,
        'tag', // This must match a key in the $tableMap array in update-field.php
        'tags-table',
        [
            'showCheckboxes' => true,
            'showActions' => true,
            'actions' => ['view', 'edit', 'delete'],
            'editableFields' => $editableFields,
            'bulkActions' => ['delete'],
            'itemsPerPage' => $perPage,
            'currentPage' => $page
        ]
    );
} else {
    // Fallback to the original table component
    include_once '../includes/table-component.php';
    if (function_exists('renderTable')) {
        // Define custom formatters
        $customFormatters = [
            'description' => function($tag, $key) {
                return htmlspecialchars(substr($tag[$key] ?? '', 0, 100) . (strlen($tag[$key] ?? '') > 100 ? '...' : ''));
            }
        ];

        // Render the table
        renderTable($tags, $columns, [
            'content_type' => 'tags',
            'name_field' => 'name',
            'empty_message' => 'No tags found. Add your first tag!',
            'custom_formatters' => $customFormatters,
            'view_url' => 'view-tag.php?id={id}',
            'edit_url' => 'tag-form.php?id={id}',
            'delete_url' => 'delete-tag.php'
        ]);
    }
}

// Include pagination component if needed
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination') && $totalItems > $perPage) {
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
include_once '../includes/footer.php';