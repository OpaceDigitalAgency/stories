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

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Enable detailed error reporting
    error_log("Starting stories page load...");
    
    // Verify database connection
    if (!$db) {
        throw new Exception("Database connection is not available");
    }
    error_log("Database connection verified");

    // Check if stories table exists and has the correct structure
    $requiredTables = ['stories', 'authors', 'story_authors', 'story_tags'];
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("Required table '$table' does not exist");
        }
        error_log("Table '$table' exists");
    }

    // Get stories table structure
    $columns = [];
    $stmt = $db->query("DESCRIBE stories");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }
    error_log("Stories table columns: " . implode(", ", $columns));

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

        // Log the query for debugging
        error_log("Search parameters: " . json_encode(['search' => $search, 'searchField' => $searchField]));

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

        error_log("Total stories found: $totalItems");

        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;

        // Get stories with pagination - include author information through story_authors
        $query = "
            SELECT s.id,
                   s.title,
                   s.content,
                   s.excerpt,
                   s.slug,
                   s.is_published,
                   s.featured,
                   s.average_rating,
                   s.allow_reviews,
                   s.review_count,
                   s.estimated_reading_time,
                   s.is_sponsored,
                   s.age_group,
                   s.needs_moderation,
                   s.is_self_published,
                   s.is_ai_enhanced,
                   s.cover_url,
                   s.created_at,
                   s.updated_at,
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
        error_log("Executing query: " . $query);
        error_log("Stories query: $query");
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $allStories = $stmt->fetchAll();

        error_log("Number of stories fetched: " . count($allStories));

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
            error_log("Processed story ID: " . $story['id'] . ", Authors: " . $story['author_name']);
        }

        error_log("Number of stories processed: " . count($stories));

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
                error_log("Error fetching tags for story ID " . $storyItem['id'] . ": " . $e->getMessage());
                $stories[$index]['tags'] = '';
            }

            // Debug log for author information
            error_log("Story ID: " . $storyItem['id'] . ", Author ID: " . ($stories[$index]['author_id'] ?? 'null') . ", Author Name: " . ($stories[$index]['author_name'] ?? 'null'));
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
$pageActions = '
<div class="d-flex gap-2">
    <form method="GET" action="story-form.php">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-plus" aria-hidden="true"></i> Add New Story
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
    echo '<h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Error Loading Stories</h4>';
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
    echo "Total Stories Found: " . ($totalItems ?? 0) . "\n";
    echo "Stories Loaded: " . (isset($stories) ? count($stories) : 0) . "\n";
    echo "Current Page: " . $page . "\n";
    echo "Items Per Page: " . $perPage . "\n";
    echo '</pre>';
    echo '</div>';
    echo '</div>';
}

// Include search component
include_once '../includes/search-component.php';
if (function_exists('renderSearchComponent')) {
    renderSearchComponent('stories', ['title', 'content', 'author', 'tags']);
}

// Include bulk actions component
include_once '../includes/bulk-actions-component.php';
if (function_exists('renderBulkActionsComponent')) {
    renderBulkActionsComponent('stories', ['delete', 'publish', 'unpublish', 'feature', 'unfeature']);
}

// Include status indicator component
include_once '../includes/status-indicator-component.php';

// Include table component
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
            $output .= '<div class="item-excerpt">';
            $output .= htmlspecialchars(substr($story['content'], 0, 100) . '...');
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
        'custom_formatters' => $customFormatters
    ]);
}

// Include pagination component
include_once '../includes/pagination-component.php';
if (function_exists('renderPagination')) {
    // Use the total items from the query for accurate pagination
    // $totalItems is already set from the count query
    renderPagination($totalItems, $perPage, $page);
}

// Include footer
include_once '../includes/footer.php';