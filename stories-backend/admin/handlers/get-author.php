<?php
/**
 * Handler for getting author details for preview
 */

// Include database connection
require_once '../includes/db-config.php';

// Set headers for JSON response
header('Content-Type: application/json');

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
    // Get author details
    $stmt = $db->prepare("
        SELECT a.*, 
               COUNT(DISTINCT s.id) as story_count,
               COUNT(DISTINCT p.id) as post_count
        FROM authors a
        LEFT JOIN stories s ON a.id = s.author_id
        LEFT JOIN posts p ON a.id = p.author_id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$authorId]);
    $author = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$author) {
        echo json_encode([
            'success' => false,
            'message' => 'Author not found'
        ]);
        exit;
    }

    // Get stories by this author
    $stmtStories = $db->prepare("
        SELECT id, title, slug, published_at
        FROM stories
        WHERE author_id = ?
        ORDER BY published_at DESC
    ");
    $stmtStories->execute([$authorId]);
    $stories = $stmtStories->fetchAll(PDO::FETCH_ASSOC);

    // Get posts by this author
    $stmtPosts = $db->prepare("
        SELECT id, title, slug, published_at
        FROM posts
        WHERE author_id = ?
        ORDER BY published_at DESC
    ");
    $stmtPosts->execute([$authorId]);
    $posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

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
    error_log('Error in get-author.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching author data'
    ]);
}
