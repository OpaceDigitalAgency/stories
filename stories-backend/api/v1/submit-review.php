<?php
/**
 * Review submission endpoint
 * This is a simple placeholder that updates the story's review count and average rating
 */

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// If form data was submitted instead of JSON
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// Validate required fields
$requiredFields = ['story', 'rating', 'review_title', 'review_content', 'age_group'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required fields',
        'missing_fields' => $missingFields
    ]);
    exit;
}

try {
    // Connect to database
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Get the story ID from the slug
    $storySlug = $data['story'];
    $stmt = $db->prepare("SELECT id, average_rating, review_count FROM stories WHERE slug = ?");
    $stmt->execute([$storySlug]);
    $story = $stmt->fetch();

    if (!$story) {
        http_response_code(404);
        echo json_encode(['error' => 'Story not found']);
        exit;
    }

    // Calculate new average rating
    $currentRating = (float)$story['average_rating'];
    $currentCount = (int)$story['review_count'];
    $newRating = (float)$data['rating'];
    
    // If this is the first review, just use the new rating
    if ($currentCount === 0) {
        $newAverageRating = $newRating;
        $newReviewCount = 1;
    } else {
        // Calculate weighted average
        $totalRating = $currentRating * $currentCount + $newRating;
        $newReviewCount = $currentCount + 1;
        $newAverageRating = $totalRating / $newReviewCount;
    }

    // Update the story's average rating and review count
    $updateStmt = $db->prepare("
        UPDATE stories 
        SET average_rating = ?, review_count = ? 
        WHERE id = ?
    ");
    $updateStmt->execute([$newAverageRating, $newReviewCount, $story['id']]);

    // In a real implementation, we would also store the individual review in a reviews table
    // For now, we'll just return success

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully',
        'story' => [
            'id' => $story['id'],
            'slug' => $storySlug,
            'average_rating' => $newAverageRating,
            'review_count' => $newReviewCount
        ]
    ]);

} catch (PDOException $e) {
    error_log("Review submission error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
} catch (Exception $e) {
    error_log("Review submission error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}