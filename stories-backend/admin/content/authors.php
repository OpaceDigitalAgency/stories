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

    // Check if book_authors table exists
    $hasBookAuthorsTable = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'book_authors'");
        $hasBookAuthorsTable = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }

    // Default book count query
    $bookCountQuery = "0";

    if ($hasBookAuthorsTable) {
        // Use the book_authors table if it exists
        $bookCountQuery = "(SELECT COUNT(*) FROM book_authors ba WHERE ba.author_id = a.id)";
    }

    // Get filter parameters
    $authorTypeFilter = isset($_GET['author_type']) ? $_GET['author_type'] : '';

    // Get page and per_page parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 10;
    $offset = ($page - 1) * $perPage;

    // Build WHERE clause for filtering
    $whereClause = "1=1"; // Always true condition to start
    $params = [];

    if (!empty($authorTypeFilter)) {
        $whereClause .= " AND a.author_type = ?";
        $params[] = $authorTypeFilter;
    }

    // Get total count with filters
    $countQuery = "SELECT COUNT(*) FROM authors a WHERE $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();

    // Get all authors with content counts and pagination
    $query = "SELECT a.*,
              $storyCountQuery as story_count,
              $postCountQuery as post_count,
              $bookCountQuery as book_count
              FROM authors a
              WHERE $whereClause
              ORDER BY a.name ASC
              LIMIT $offset, $perPage";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Add preview modal CSS and author preview script
$extraHeadContent .= '
<!-- Add Preview Modal CSS -->
<link rel="stylesheet" href="../assets/css/preview-modal.css">
<!-- Add Author Preview JS -->
<script src="../assets/js/author-preview.js"></script>
';

// Include header
require_once '../includes/header.php';

// Add author type filter tabs
?>
<div class="premium-tabs mb-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?php echo empty($authorTypeFilter) ? 'active' : ''; ?>" href="authors.php">
                All Authors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $authorTypeFilter === 'retail' ? 'active' : ''; ?>" href="authors.php?author_type=retail">
                Retail/Book Authors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $authorTypeFilter === 'child' ? 'active' : ''; ?>" href="authors.php?author_type=child">
                Child Authors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $authorTypeFilter === 'parent' ? 'active' : ''; ?>" href="authors.php?author_type=parent">
                Parent Authors
            </a>
        </li>
    </ul>
</div>
<?php

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

        // Make sure avatar URL is absolute for proper display
        if (!empty($avatarImage) && strpos($avatarImage, 'http') !== 0) {
            // Check if the file exists on the server
            $localPath = $avatarImage;

            // If it starts with ../, convert to server path for file_exists check
            if (strpos($localPath, '../') === 0) {
                $localPath = str_replace('../', $_SERVER['DOCUMENT_ROOT'] . '/', $localPath);
            } else if (strpos($localPath, '/') === 0) {
                $localPath = $_SERVER['DOCUMENT_ROOT'] . $localPath;
            } else {
                $localPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $localPath;
            }

            // If file doesn't exist, use default avatar
            if (!file_exists($localPath)) {
                $avatarImage = '../assets/images/default-avatar.svg';
                error_log("Avatar file not found at: " . $localPath . " - using default");
            }

            // Now create the absolute URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'api.storiesfromtheweb.org';

            // Handle relative paths that start with ../
            if (strpos($avatarImage, '../') === 0) {
                $avatarImage = str_replace('../', '/', $avatarImage);
            }

            // Normalize path to always start with /
            if (strpos($avatarImage, '/') !== 0) {
                $avatarImage = '/' . ltrim($avatarImage, '/');
            }

            $avatarImage = $protocol . $serverHost . $avatarImage;
        }

        // Log the avatar URL for debugging
        error_log("Author ID: " . $author['id'] . " - Final Avatar URL: " . $avatarImage);



        // Format the bio
        $bio = isset($author['bio']) ? substr($author['bio'], 0, 100) . (strlen($author['bio']) > 100 ? '...' : '') : '';

        // Clean up author name - remove ** prefix if it exists
        $authorName = $author['name'];
        if (strpos($authorName, '**') === 0) {
            $authorName = trim(str_replace('**', '', $authorName));
        }

        // Add the item to the table data
        $tableData[] = [
            'id' => $author['id'],
            'image' => $avatarImage,
            'name' => $authorName,
            'email' => $author['email'] ?? '',
            'type' => ucfirst($author['author_type'] ?? 'retail'),
            'bio' => $bio,
            'stories' => $author['story_count'] ?? 0,
            'books' => $author['book_count'] ?? 0,
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
        'books' => 'Books',
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
            'itemsPerPage' => $perPage,
            'currentPage' => $page
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
            'book_count' => 'Books',
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
                         (isset($author['avatar']) && !empty($author['avatar']) ? $author['avatar'] : '../assets/images/default-avatar.svg');

            // Make sure avatar URL is absolute for proper display
            if (!empty($avatarImage) && strpos($avatarImage, 'http') !== 0) {
                // Check if the file exists on the server
                $localPath = $avatarImage;

                // If it starts with ../, convert to server path for file_exists check
                if (strpos($localPath, '../') === 0) {
                    $localPath = str_replace('../', $_SERVER['DOCUMENT_ROOT'] . '/', $localPath);
                } else if (strpos($localPath, '/') === 0) {
                    $localPath = $_SERVER['DOCUMENT_ROOT'] . $localPath;
                } else {
                    $localPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $localPath;
                }

                // If file doesn't exist, use default avatar
                if (!file_exists($localPath)) {
                    $avatarImage = '../assets/images/default-avatar.svg';
                    error_log("Fallback - Avatar file not found at: " . $localPath . " - using default");
                }

                // Now create the absolute URL
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'api.storiesfromtheweb.org';

                // Handle relative paths that start with ../
                if (strpos($avatarImage, '../') === 0) {
                    $avatarImage = str_replace('../', '/', $avatarImage);
                }

                // Normalize path to always start with /
                if (strpos($avatarImage, '/') !== 0) {
                    $avatarImage = '/' . ltrim($avatarImage, '/');
                }

                $avatarImage = $protocol . $serverHost . $avatarImage;
                error_log("Fallback - Author ID: " . $author['id'] . " - Fixed avatar URL: " . $avatarImage);
            }

            // Clean up author name - remove ** prefix if it exists
            $authorName = $author['name'];
            if (strpos($authorName, '**') === 0) {
                $authorName = trim(str_replace('**', '', $authorName));
            }

            // Add to table data
            $tableData[] = [
                'id' => $author['id'],
                'name' => $authorName,
                'email' => $author['email'] ?? '',
                'author_type' => $authorType,
                'bio' => $bio,
                'story_count' => $author['story_count'] ?? 0,
                'book_count' => $author['book_count'] ?? 0,
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
                'itemsPerPage' => $perPage,
                'currentPage' => $page
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

// Include author preview script
echo '<link rel="stylesheet" href="../assets/css/preview-modal.css">';
echo '<script src="../assets/js/author-preview.js"></script>';

// Add debugging script to help identify any issues
echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        console.log("Authors page loaded - author preview should be initialized by author-preview.js");

        // Check if the AuthorPreview class is available
        if (window.authorPreview) {
            console.log("AuthorPreview instance is available");
        } else {
            console.error("AuthorPreview instance is NOT available - check if author-preview.js loaded correctly");
        }

        // Log the number of preview buttons found
        const previewButtons = document.querySelectorAll(".author-preview-btn, [data-action=\"preview-author\"]");
        console.log("Found " + previewButtons.length + " author preview buttons");
    });
</script>';

// Include pagination component
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination') && $totalItems > $perPage) {
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
require_once '../includes/footer.php';