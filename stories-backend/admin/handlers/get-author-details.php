<?php
/**
 * Get Author Details Handler
 *
 * This script fetches author details (name, age, location) from the database
 * and returns them as JSON.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Include auth check
require_once '../includes/auth-check.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if author ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Author ID is required'
    ]);
    exit;
}

$authorId = intval($_GET['id']);

try {
    // Prepare and execute query
    $stmt = $db->prepare("SELECT name, age, location FROM authors WHERE id = ?");
    $stmt->execute([$authorId]);
    $author = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($author) {
        // Return author details
        echo json_encode([
            'success' => true,
            'name' => $author['name'],
            'age' => $author['age'],
            'location' => $author['location']
        ]);
    } else {
        // Author not found
        echo json_encode([
            'success' => false,
            'message' => 'Author not found'
        ]);
    }
} catch (PDOException $e) {
    // Database error
    error_log("Error fetching author details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
