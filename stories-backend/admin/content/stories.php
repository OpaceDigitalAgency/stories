<?php
/**
 * Stories Admin Page
 *
 * This page displays a list of all stories and allows for searching, filtering, and bulk actions.
 *
 * Updated 2025-05-04: Aligned authentication and database connection pattern with dashboard.php
 * - Removed duplicate database configuration
 * - Using auth-check.php for consistent authentication
 * - Added error reporting for debugging
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

try {

    // Check if stories table exists and has the correct structure
    $requiredTables = ['stories', 'authors', 'story_authors', 'story_tags'];
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("Required table '$table' does not exist");
        }
    }

    // Get stories table structure
    $columns = [];
    $stmt = $db->query("DESCRIBE stories");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }
    // Verify required columns exist
    $requiredColumns = ['id', 'title', 'content', 'created_at', 'updated_at'];
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            throw new Exception("Required column '$column' missing from stories table");
        }
    }

    // Get search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchField = isset($_GET['search_field']) ? $_GET['search_field'] : 'all';

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 25;

    // Get all stories with all available fields
    try {
        // Build the query based on search parameters
        $params = [];
        $whereClause = '';

        if (!empty($search)) {
            if ($searchField === 'all' || $searchField === 'title') {
                // Search in title field
                $whereClause = " WHERE s.title LIKE ?";
                $params[] = "%$search%";
            } else if ($searchField === 'content') {
                // Search in content field
                $whereClause = " WHERE s.content LIKE ?";
                $params[] = "%$search%";
            } else if (in_array($searchField, $columns)) {
                // Search in a specific field
                $whereClause = " WHERE s.$searchField LIKE ?";
                $params[] = "%$search%";
            }
        }

        // Get total count for pagination
        $countQuery = "
            SELECT COUNT(DISTINCT s.id)
            FROM stories s
            LEFT JOIN story_authors sa ON s.id = sa.story_id
            LEFT JOIN authors a ON sa.author_id = a.id
            $whereClause
        ";
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $totalItems = $stmt->fetchColumn();

        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;

        // Get stories with pagination - include author information through story_authors
        $query = "
            SELECT s.*,
                   GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as author_names,
                   GROUP_CONCAT(DISTINCT a.id SEPARATOR ',') as author_ids,
                   GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tag_names
            FROM stories s
            LEFT JOIN story_authors sa ON s.id = sa.story_id
            LEFT JOIN authors a ON sa.author_id = a.id
            LEFT JOIN story_tags st ON s.id = st.story_id
            LEFT JOIN tags t ON st.tag_id = t.id
            $whereClause
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT $offset, $perPage
        ";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $allStories = $stmt->fetchAll();

        // Process the stories and their authors
        $stories = [];
        foreach ($allStories as $story) {
            // Split author names and IDs into arrays
            $authorNames = $story['author_names'] ? explode(',', $story['author_names']) : [];
            $authorIds = $story['author_ids'] ? explode(',', $story['author_ids']) : [];

            // Format author name for display
            $story['author_name'] = $authorNames ? implode(', ', $authorNames) : 'Unknown';
            $story['author_id'] = $authorIds ? $authorIds[0] : null; // Keep first author ID for compatibility

            // Remove the concatenated fields from display
            unset($story['author_names']);
            unset($story['author_ids']);

            $stories[] = $story;
        }

        // Get tags for each story
        foreach ($stories as $index => $storyItem) {

            // Get tags for the story
            try {
                $stmt = $db->prepare("
                    SELECT GROUP_CONCAT(t.name ORDER BY t.name ASC SEPARATOR ', ') as tags
                    FROM story_tags st
                    JOIN tags t ON st.tag_id = t.id
                    WHERE st.story_id = ?
                ");
                $stmt->execute([$storyItem['id']]);
                $tags = $stmt->fetch();

                if ($tags && isset($tags['tags'])) {
                    $stories[$index]['tags'] = $tags['tags'];
                } else {
                    $stories[$index]['tags'] = '';
                }
            } catch (Exception $e) {
                $stories[$index]['tags'] = '';
            }

        }
    } catch (Exception $e) {
        error_log("Error fetching stories: " . $e->getMessage());
        $stories = [];
    }

} catch (PDOException $e) {
    error_log("Stories page error: " . $e->getMessage());
    $error = "Error loading stories. Please try again.";
    $stories = [];
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
$pageTitle = 'Stories';
$currentPage = 'stories';
$pageDescription = 'Manage all your stories from here.';

// Add extra head content for premium features
$extraHeadContent = '
<!-- Add Premium Admin CSS -->
<link rel="stylesheet" href="../assets/css/premium-admin.css">
<!-- Add Live Search JS -->
<script src="../assets/js/live-search.js"></script>
<!-- Add Inline Editing JS -->
<script src="../assets/js/inline-editing.js"></script>
<!-- Add Story Preview CSS and JS -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<script src="../assets/js/story-preview.js"></script>
';

$pageActions = '
<div class="premium-flex premium-gap-2">
    <a href="story-form.php" class="premium-btn premium-btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Story
    </a>
    <button onclick="window.location.reload()" class="premium-btn premium-btn-secondary">
        <i class="fas fa-sync" aria-hidden="true"></i> Refresh
    </button>
    <button onclick="window.location.href=\'?debug=1\'" class="premium-btn premium-btn-info">
        <i class="fas fa-bug" aria-hidden="true"></i> Debug Mode
    </button>
</div>
';

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
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error Loading Stories</h4>';
    echo '<p>' . htmlspecialchars($error) . '</p>';
    echo '<hr>';
    echo '<p class="mb-0">Please check the error logs for more details or contact support.</p>';
    echo '</div>';
}

// Include live search component
include_once '../includes/live-search-component.php';
if (function_exists('renderLiveSearchComponent')) {
    renderLiveSearchComponent('stories', ['title', 'content', 'author', 'tags'], 'stories-table');
} else {
    // Fallback to predictive search component
    include_once '../includes/predictive-search-component.php';
    if (function_exists('renderPredictiveSearchComponent')) {
        renderPredictiveSearchComponent('stories', ['title', 'content', 'author', 'tags']);
    } else {
        // Fallback to regular search component
        include_once '../includes/search-component.php';
        if (function_exists('renderSearchComponent')) {
            renderSearchComponent('stories', ['title', 'content', 'author', 'tags']);
        }
    }
}

// Include status indicator component
include_once '../includes/status-indicator-component.php';

// Include enhanced table component
require_once __DIR__ . '/../includes/enhanced-table-component.php';
if (function_exists('renderEnhancedTable')) {
    // Prepare data for the enhanced table
    $tableData = [];
    foreach ($stories as $story) {
        // Format the status
        $status = isset($story['is_published']) && $story['is_published'] ? 'Published' : 'Draft';
        if (isset($story['needs_moderation']) && $story['needs_moderation']) {
            $status .= ' (Needs Moderation)';
        }

        // Format the created date
        $createdDate = date('M j, Y', strtotime($story['created_at']));

        // Format reviews information
        $reviewsInfo = '';
        if (isset($story['review_count']) && $story['review_count'] > 0) {
            $rating = isset($story['average_rating']) ? round($story['average_rating'] * 5, 1) : 0;
            $reviewsInfo = '<div class="rating-display">';
            $reviewsInfo .= '<span class="rating-value">' . $rating . '/5</span> ';
            $reviewsInfo .= '<span class="review-count">(' . $story['review_count'] . ' reviews)</span>';
            $reviewsInfo .= '</div>';
        } else {
            $reviewsInfo = '<span class="no-reviews">No reviews</span>';
        }

        // Add the item to the table data
        $tableData[] = [
            'id' => $story['id'],
            'image' => $story['cover_url'] ?? '../assets/images/default-cover.jpg',
            'title' => $story['title'],
            'author' => $story['author_name'] ?? 'Unknown',
            'status' => $status,
            'reviews' => $reviewsInfo,
            'tags' => $story['tags'] ?? '',
            'created' => $createdDate
        ];
    }

    // Define columns for the table
    $columns = [
        'title' => 'Title',
        'author' => 'Author',
        'status' => 'Status',
        'reviews' => 'Reviews',
        'tags' => 'Tags',
        'created' => 'Created'
    ];

    // Define which fields are editable inline
    $editableFields = ['title', 'tags'];

    // Custom action renderer for stories
    $customActionRenderer = function($item) {
        $html = '<div class="premium-table-actions">';

        // View button - uses the story preview lightbox
        $html .= '<button type="button" class="premium-btn premium-btn-info premium-btn-sm story-preview-btn" data-story-id="' . htmlspecialchars($item['id']) . '" title="Preview">';
        $html .= '<i class="fas fa-eye"></i>';
        $html .= '</button>';

        // Edit button - goes to story-form.php
        $html .= '<a href="story-form.php?id=' . htmlspecialchars($item['id']) . '" class="premium-btn premium-btn-primary premium-btn-sm">';
        $html .= '<i class="fas fa-edit"></i>';
        $html .= '</a>';

        // Delete button
        $html .= '<button type="button" class="premium-btn premium-btn-danger premium-btn-sm delete-item-btn" data-id="' . htmlspecialchars($item['id']) . '" title="Delete">';
        $html .= '<i class="fas fa-trash"></i>';
        $html .= '</button>';

        $html .= '</div>';
        return $html;
    };

    // Render the enhanced table
    renderEnhancedTable(
        $tableData,
        $columns,
        'story', // This must match a key in the $tableMap array in update-field.php
        'stories-table',
        [
            'showCheckboxes' => true,
            'showActions' => true,
            'actions' => ['view', 'edit', 'delete'],
            'thumbnailField' => 'image',
            'thumbnailAltField' => 'title',
            'editableFields' => $editableFields,
            'htmlFields' => ['reviews', 'status'], // Fields that should render HTML
            'bulkActions' => ['delete', 'publish', 'unpublish', 'feature', 'unfeature'],
            'itemsPerPage' => $perPage,
            'currentPage' => $page,
            'totalItems' => $totalItems, // Pass the total items count from SQL query
            'customActionRenderer' => $customActionRenderer // Add custom action renderer
        ]
    );
} else {
    // Fallback to the original table component
    include_once '../includes/table-component.php';
    if (function_exists('renderTable')) {
        // Define columns
        $columns = [
            'id' => 'ID',
            'title' => 'Title',
            'author_name' => 'Author',
            'status' => 'Status',
            'tags' => 'Tags',
            'created_at' => 'Created'
        ];

        // Define custom formatters
        $customFormatters = [
            'title' => function($story, $key) {
                $output = '<div class="item-title">';
                $output .= htmlspecialchars($story['title']);

                if (isset($story['featured']) && $story['featured']) {
                    $output .= ' <span class="featured-badge" title="Featured story" aria-label="Featured story">';
                    $output .= '<i class="fas fa-star" aria-hidden="true"></i>';
                    $output .= '</span>';
                }

                $output .= '</div>';

                return $output;
            },
            'author_name' => function($story, $key) {
                return htmlspecialchars($story['author_name'] ?? $story['author'] ?? 'Unknown');
            },
            'status' => function($story, $key) {
                $output = '';
                if (function_exists('getPublishedStatusIndicator')) {
                    $output .= getPublishedStatusIndicator(isset($story['is_published']) ? $story['is_published'] : false);
                } else {
                    $output .= isset($story['is_published']) && $story['is_published'] ? 'Published' : 'Draft';
                }

                if (isset($story['needs_moderation']) && $story['needs_moderation'] && function_exists('getModerationStatusIndicator')) {
                    $output .= '<br>' . getModerationStatusIndicator(true);
                }

                return $output;
            },
            'created_at' => function($story, $key) {
                $output = '<div>' . date('M j, Y', strtotime($story['created_at'])) . '</div>';
                $output .= '<div class="text-muted">Updated: ' . date('M j, Y', strtotime($story['updated_at'])) . '</div>';
                return $output;
            }
        ];

        // Render the table
        renderTable($stories, $columns, [
            'content_type' => 'stories',
            'name_field' => 'title',
            'empty_message' => 'No stories found. Add your first story!',
            'custom_formatters' => $customFormatters,
            'view_url' => 'javascript:void(0);',
            'view_onclick' => 'if(window.storyPreview) window.storyPreview.loadStoryPreview("{id}"); return false;',
            'edit_url' => 'story-form.php?id={id}',
            'delete_url' => 'delete-story.php'
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