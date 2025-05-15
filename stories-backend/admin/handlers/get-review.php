<?php
/**
 * Get Review Handler
 *
 * Handles AJAX requests for getting review details.
 */

// Include database connection
require_once '../includes/db-connect.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'review' => null
];

// Check if the request has an ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Invalid review ID.';
    echo json_encode($response);
    exit;
}

$reviewId = (int)$_GET['id'];

try {
    // Get review details
    $stmt = $db->prepare("
        SELECT r.*, d.title as book_title, s.name as source_name, s.is_third_party
        FROM reviews r
        LEFT JOIN directory_items d ON r.book_id = d.id
        LEFT JOIN review_sources s ON r.source_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$review) {
        $response['message'] = 'Review not found.';
        echo json_encode($response);
        exit;
    }
    
    // Format review data
    if ($review['metadata']) {
        $review['metadata'] = json_decode($review['metadata'], true);
    }
    
    if ($review['content_flags']) {
        $review['content_flags'] = $review['content_flags'];
    }
    
    $response['success'] = true;
    $response['review'] = $review;
} catch (Exception $e) {
    $response['message'] = 'Error retrieving review: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
