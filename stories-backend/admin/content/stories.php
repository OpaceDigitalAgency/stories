<?php
/**
 * Stories Admin Page
 *
 * This page displays a list of all stories and allows for searching, filtering, and bulk actions.
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include simple_auth.php directly
require_once '../../simple_auth.php';

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
$user = SimpleAuth::check();
if (!$user) {
    // Redirect to login
    header("Location: ../login.php");
    exit;
}

// Include database connection
include_once '../includes/db-connect.php';

try {

    // Check if stories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'stories'");
    if ($stmt->rowCount() === 0) {
        // Create stories table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS stories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
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

    // Get all columns from stories table
    $columns = [];
    $stmt = $db->query("DESCRIBE stories");
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }

    // Determine the join condition based on available columns
    $joinCondition = "1=0"; // Default to no join if neither column exists
    if (in_array('author_id', $columns)) {
        $joinCondition = "s.author_id = a.id";
    } elseif (in_array('author', $columns)) {
        $joinCondition = "s.author = a.name";
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
            if ($searchField === 'all') {
                // Search in all searchable fields
                $whereClause = " WHERE (title LIKE ? OR content LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";

                // Add author search if the column exists
                if (in_array('author', $columns)) {
                    $whereClause .= " OR author LIKE ?";
                    $params[] = "%$search%";
                }

                // Close the where clause
                $whereClause .= ")";
            } else {
                // Search in a specific field
                if (in_array($searchField, $columns)) {
                    $whereClause = " WHERE $searchField LIKE ?";
                    $params[] = "%$search%";
                }
            }
        }

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) FROM stories" . $whereClause;
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $totalItems = $stmt->fetchColumn();

        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;

        // Get stories with pagination
        $query = "SELECT * FROM stories" . $whereClause . " ORDER BY created_at DESC LIMIT $offset, $perPage";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $allStories = $stmt->fetchAll();

        error_log("Number of stories fetched: " . count($allStories));

        // Create a new array to store unique stories by ID
        $stories = [];
        $seenIds = [];

        // Only add each story ID once
        foreach ($allStories as $story) {
            $id = $story['id'];
            if (!in_array($id, $seenIds)) {
                $stories[] = $story;
                $seenIds[] = $id;
                error_log("Adding story ID: " . $id . ", Title: " . $story['title']);
            } else {
                error_log("Skipping duplicate story ID: " . $id . ", Title: " . $story['title']);
            }
        }

        error_log("Number of unique stories: " . count($stories));

        // Then for each story, try to get the author information from story_authors table
        foreach ($stories as $index => $storyItem) {
            try {
                // Get author from story_authors table
                $stmt = $db->prepare("
                    SELECT a.id, a.name
                    FROM story_authors sa
                    JOIN authors a ON sa.author_id = a.id
                    WHERE sa.story_id = ?
                ");
                $stmt->execute([$storyItem['id']]);
                $author = $stmt->fetch();

                if ($author) {
                    $stories[$index]['author_id'] = $author['id'];
                    $stories[$index]['author_name'] = $author['name'];
                } else {
                    $stories[$index]['author_name'] = 'Unknown';
                }
            } catch (Exception $e) {
                error_log("Error fetching author for story ID " . $storyItem['id'] . ": " . $e->getMessage());
                $stories[$index]['author_name'] = 'Unknown';
            }

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
<form method="GET" action="story-form.php">
    <button type="submit" class="btn btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Story
    </button>
</form>
';

// Include header
include_once '../includes/header.php';

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