<?php
/**
 * Handler for getting author details for preview
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set headers for JSON response
header('Content-Type: application/json');

// Try to include database connection
try {
    require_once '../includes/db-connect.php';

    // Check if $db is set
    if (!isset($db)) {
        throw new Exception('Database connection not established');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage()
    ]);
    exit;
}



// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Author ID is required'
    ]);
    exit;
}

$authorId = intval($_GET['id']);

try {
    // Log the author ID we're trying to fetch
    error_log("Fetching author with ID: " . $authorId);

    // Check if tables exist
    $hasStoriesTable = false;
    $hasPostsTable = false;

    try {
        $stmt = $db->query("SHOW TABLES LIKE 'stories'");
        $hasStoriesTable = $stmt->rowCount() > 0;
        error_log("Stories table exists: " . ($hasStoriesTable ? 'Yes' : 'No'));

        $stmt = $db->query("SHOW TABLES LIKE 'posts'");
        $hasPostsTable = $stmt->rowCount() > 0;
        error_log("Posts table exists: " . ($hasPostsTable ? 'Yes' : 'No'));

        // Check if authors table exists and has records
        $stmt = $db->query("SHOW TABLES LIKE 'authors'");
        $hasAuthorsTable = $stmt->rowCount() > 0;
        error_log("Authors table exists: " . ($hasAuthorsTable ? 'Yes' : 'No'));

        if ($hasAuthorsTable) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM authors");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Number of authors in database: " . $result['count']);
        }
    } catch (Exception $e) {
        error_log("Error checking tables: " . $e->getMessage());
    }

    // Build the query based on available tables
    $query = "SELECT a.*";
    $joins = "";

    if ($hasStoriesTable) {
        $query .= ", COUNT(DISTINCT s.id) as story_count";
        $joins .= " LEFT JOIN stories s ON a.id = s.author_id";
    } else {
        $query .= ", 0 as story_count";
    }

    if ($hasPostsTable) {
        $query .= ", COUNT(DISTINCT p.id) as post_count";
        $joins .= " LEFT JOIN posts p ON a.id = p.author_id";
    } else {
        $query .= ", 0 as post_count";
    }

    $query .= " FROM authors a" . $joins . " WHERE a.id = ? GROUP BY a.id";

    // Log the query for debugging
    error_log("Author query: " . $query . " with ID: " . $authorId);

    // Get author details
    $stmt = $db->prepare($query);
    $stmt->execute([$authorId]);
    $author = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("Author found: " . ($author ? 'Yes' : 'No'));

    if (!$author) {
        echo json_encode([
            'success' => false,
            'message' => 'Author not found with ID: ' . $authorId
        ]);
        exit;
    }

    // Initialize arrays
    $stories = [];
    $posts = [];

    // Get stories by this author if the table exists
    if ($hasStoriesTable) {
        $stmtStories = $db->prepare("
            SELECT id, title, slug, published_at
            FROM stories
            WHERE author_id = ?
            ORDER BY published_at DESC
        ");
        $stmtStories->execute([$authorId]);
        $stories = $stmtStories->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get posts by this author if the table exists
    if ($hasPostsTable) {
        $stmtPosts = $db->prepare("
            SELECT id, title, slug, published_at
            FROM posts
            WHERE author_id = ?
            ORDER BY published_at DESC
        ");
        $stmtPosts->execute([$authorId]);
        $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);
    }

    // Format the avatar URL
    if (!empty($author['avatar_url'])) {
        // If it's a relative URL, make it absolute
        if (strpos($author['avatar_url'], 'http') !== 0) {
            $author['avatar_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($author['avatar_url'], '/');
        }
    } else if (!empty($author['avatar'])) {
        // If avatar_url is empty but avatar is set, use that
        if (strpos($author['avatar'], 'http') !== 0) {
            $author['avatar_url'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . ltrim($author['avatar'], '/');
        } else {
            $author['avatar_url'] = $author['avatar'];
        }
    }

    // Return the author data
    echo json_encode([
        'success' => true,
        'author' => $author,
        'stories' => $stories,
        'posts' => $posts
    ]);

} catch (Exception $e) {
    $errorMessage = 'Error in get-author.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
    error_log($errorMessage);
    error_log('Stack trace: ' . $e->getTraceAsString());

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching author data: ' . $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
