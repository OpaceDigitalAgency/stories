<?php
/**
 * Get Story Slug Handler
 *
 * This script fetches a story's slug from the database based on its ID.
 * Used by the story preview functionality to construct the frontend URL.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Include auth check
require_once '../includes/auth-check.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if story ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Story ID is required'
    ]);
    exit;
}

$storyId = intval($_GET['id']);

try {
    // Prepare and execute query
    $stmt = $db->prepare("SELECT slug FROM stories WHERE id = ?");
    $stmt->execute([$storyId]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($story && !empty($story['slug'])) {
        // Return story slug
        echo json_encode([
            'success' => true,
            'slug' => $story['slug']
        ]);
    } else {
        // Story not found or slug is empty
        echo json_encode([
            'success' => false,
            'message' => 'Story not found or slug is empty'
        ]);
    }
} catch (PDOException $e) {
    // Database error
    error_log("Error fetching story slug: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
