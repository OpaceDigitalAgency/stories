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
    $authors = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

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
    <a href="author-form.php" class="premium-btn premium-btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Author
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
    renderLiveSearchComponent('authors', ['name', 'email', 'bio'], 'authors-table');
} else {
    // Fallback to regular search component
    include_once '../includes/search-component.php';
    if (function_exists('renderSearchComponent')) {
        renderSearchComponent('authors', ['name', 'email', 'bio']);
    }
}

// Include enhanced table component
include_once '../includes/enhanced-table-component.php';
if (function_exists('renderEnhancedTable')) {
    // Prepare data for the enhanced table
    $tableData = [];
    foreach ($authors as $author) {
        // Get avatar image if available
        $avatarImage = isset($author['avatar_url']) && !empty($author['avatar_url']) ? $author['avatar_url'] :
                     (isset($author['avatar']) && !empty($author['avatar']) ? $author['avatar'] : '../assets/images/default-avatar.svg');

        // Format the bio
        $bio = isset($author['bio']) ? substr($author['bio'], 0, 100) . (strlen($author['bio']) > 100 ? '...' : '') : '';

        // Add the item to the table data
        $tableData[] = [
            'id' => $author['id'],
            'image' => $avatarImage,
            'name' => $author['name'],
            'email' => $author['email'] ?? '',
            'type' => ucfirst($author['author_type'] ?? 'retail'),
            'bio' => $bio,
            'stories' => $author['story_count'] ?? 0,
            'posts' => $author['post_count'] ?? 0
        ];
    }

    // Define columns for the table
    $columns = [
        'name' => 'Name',
        'email' => 'Email',
        'type' => 'Type',
        'bio' => 'Bio',
        'stories' => 'Stories',
        'posts' => 'Blog Posts'
    ];

    // Define which fields are editable inline
    $editableFields = ['name', 'email', 'bio'];

    // Render the enhanced table
    renderEnhancedTable(
        $tableData,
        $columns,
        'author', // This must match a key in the $tableMap array in update-field.php
        'authors-table',
        [
            'showCheckboxes' => true,
            'showActions' => true,
            'actions' => ['view', 'edit', 'delete'],
            'thumbnailField' => 'image',
            'thumbnailAltField' => 'name',
            'editableFields' => $editableFields,
            'bulkActions' => ['delete'],
            'itemsPerPage' => 10,
            'currentPage' => 1
        ]
    );
} else {
    // Fallback to the original table component
    include_once '../includes/enhanced-table-component.php';
    if (function_exists('renderEnhancedTable')) {
        // Define columns
        $columns = [
            'name' => 'Name',
            'email' => 'Email',
            'author_type' => 'Type',
            'bio' => 'Bio',
            'story_count' => 'Stories',
            'post_count' => 'Blog Posts'
        ];

        // Prepare table data
        $tableData = [];
        foreach ($authors as $author) {
            // Format the bio
            $bio = isset($author['bio']) ? substr($author['bio'], 0, 100) . (strlen($author['bio']) > 100 ? '...' : '') : '';

            // Format the author type
            $authorType = isset($author['author_type']) ? ucfirst($author['author_type']) : 'Retail';

            // Get avatar image
            $avatarImage = isset($author['avatar_url']) && !empty($author['avatar_url']) ? $author['avatar_url'] :
                         (isset($author['avatar']) && !empty($author['avatar']) ? $author['avatar'] : '');

            // Add to table data
            $tableData[] = [
                'id' => $author['id'],
                'name' => $author['name'] ?? '',
                'email' => $author['email'] ?? '',
                'author_type' => $authorType,
                'bio' => $bio,
                'story_count' => $author['story_count'] ?? 0,
                'post_count' => $author['post_count'] ?? 0,
                'avatar' => $avatarImage
            ];
        }

        // Define editable fields
        $editableFields = ['name', 'email'];

        // Render the enhanced table
        renderEnhancedTable(
            $tableData,
            $columns,
            'author', // This must match a key in the $tableMap array in update-field.php
            'authors-table',
            [
                'showCheckboxes' => true,
                'showActions' => true,
                'actions' => ['view', 'edit', 'delete'],
                'thumbnailField' => 'avatar',
                'thumbnailAltField' => 'name',
                'editableFields' => $editableFields,
                'bulkActions' => ['delete', 'notify'],
                'itemsPerPage' => 10,
                'currentPage' => 1
            ]
        );
    } else {
        // Manual fallback if no table component is available
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
                echo '<a href="author-delete-process.php?id=' . $author['id'] . '" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</a>';
                echo '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}

// No need for JavaScript includes

// Include footer
require_once '../includes/footer.php';