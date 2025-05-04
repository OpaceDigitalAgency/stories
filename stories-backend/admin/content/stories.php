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

        // Log this important information
        error_log("Stories table did not exist and was created");
    }

    // Check if stories table has any data
    $countStories = $db->query("SELECT COUNT(*) FROM stories")->fetchColumn();
    error_log("Number of stories in database: " . $countStories);

    // If no stories, create a sample story for testing
    if ($countStories == 0) {
        error_log("No stories found in database, creating a sample story");

        // First check if we have any authors
        $authorCount = $db->query("SELECT COUNT(*) FROM authors")->fetchColumn();
        $authorId = null;

        if ($authorCount > 0) {
            // Get the first author
            $authorId = $db->query("SELECT id FROM authors LIMIT 1")->fetchColumn();
        } else {
            // Create a sample author
            $db->exec("INSERT INTO authors (name, slug, bio, created_at, updated_at)
                      VALUES ('Sample Author', 'sample-author', 'This is a sample author', NOW(), NOW())");
            $authorId = $db->lastInsertId();
        }

        // Create a sample story
        $db->exec("INSERT INTO stories (title, content, created_at, updated_at)
                  VALUES ('Sample Story', 'This is a sample story content.', NOW(), NOW())");
        $storyId = $db->lastInsertId();

        // Link the story to the author
        if ($authorId) {
            $db->exec("INSERT INTO story_authors (story_id, author_id) VALUES ($storyId, $authorId)");
        }
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
        error_log("story_tags table did not exist and was created");
    }

    // Check if story_authors table exists
    $stmt = $db->query("SHOW TABLES LIKE 'story_authors'");
    if ($stmt->rowCount() === 0) {
        // Create story_authors table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS story_authors (
            story_id INT NOT NULL,
            author_id INT NOT NULL,
            PRIMARY KEY (story_id, author_id)
        )");
        error_log("story_authors table did not exist and was created");

        // Check if we have stories with author_id column
        if (in_array('author_id', $columns)) {
            // Migrate data from author_id column to junction table
            $stories = $db->query("SELECT id, author_id FROM stories WHERE author_id IS NOT NULL")->fetchAll();
            foreach ($stories as $story) {
                $db->exec("INSERT IGNORE INTO story_authors (story_id, author_id) VALUES ({$story['id']}, {$story['author_id']})");
            }
            error_log("Migrated " . count($stories) . " stories with author_id to story_authors junction table");
        }
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
            SELECT s.*,
                   GROUP_CONCAT(DISTINCT a.name) as author_names,
                   GROUP_CONCAT(DISTINCT a.id) as author_ids
            FROM stories s
            LEFT JOIN story_authors sa ON s.id = sa.story_id
            LEFT JOIN authors a ON sa.author_id = a.id
            $whereClause
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT $offset, $perPage
        ";
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
<form method="GET" action="story-form.php">
    <button type="submit" class="btn btn-success">
        <i class="fas fa-plus" aria-hidden="true"></i> Add New Story
    </button>
</form>
';

// Include header
require_once '../includes/header.php';

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