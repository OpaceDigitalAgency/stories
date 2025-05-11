<?php
/**
 * Review Handler
 *
 * Handles AJAX requests for adding, editing, and deleting reviews.
 */

// Include database connection
require_once '../../includes/db.php';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Get the action from the request
$action = $_POST['action'] ?? '';

// Debug logging
error_log("Review handler called with action: " . $action);
error_log("POST data: " . print_r($_POST, true));

// Handle different actions
switch ($action) {
    case 'add_review':
        addReview();
        break;
    case 'update_review':
        updateReview();
        break;
    case 'delete_review':
        deleteReview();
        break;
    default:
        $response['message'] = 'Invalid action.';
        echo json_encode($response);
        exit;
}

/**
 * Add a new review
 */
function addReview() {
    global $db, $response;

    try {
        // Get review data from the request
        $bookId = $_POST['book_id'] ?? null;
        $reviewerName = $_POST['reviewer_name'] ?? '';
        $reviewerAge = $_POST['reviewer_age'] ?? null;
        $sourceId = $_POST['source_id'] ?? 1; // Default to Stories from the Web
        $reviewDate = $_POST['review_date'] ?? date('Y-m-d');
        $originalRating = $_POST['original_rating'] ?? '';
        $ratingNormalised = $_POST['rating_normalised'] ?? 0;
        $reviewText = $_POST['review_text'] ?? '';

        // Validate required fields
        if (empty($bookId)) {
            throw new Exception('Book ID is required.');
        }

        if (empty($ratingNormalised)) {
            throw new Exception('Rating is required.');
        }

        // Calculate rating value and scale
        $ratingValue = $ratingNormalised * 5;
        $ratingScale = 5;

        // Insert the review into the database
        $stmt = $db->prepare("
            INSERT INTO reviews (
                book_id, source_id, reviewer_name, reviewer_age, review_date,
                original_rating, rating_value, rating_scale, rating_normalised, review_text
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $bookId, $sourceId, $reviewerName, $reviewerAge, $reviewDate,
            $originalRating, $ratingValue, $ratingScale, $ratingNormalised, $reviewText
        ]);

        // Update the book's average rating and review count
        updateBookRatings($bookId);

        $response['success'] = true;
        $response['message'] = 'Review added successfully.';
    } catch (Exception $e) {
        $response['message'] = 'Error adding review: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

/**
 * Update an existing review
 */
function updateReview() {
    global $db, $response;

    try {
        // Get review data from the request
        $reviewId = $_POST['review_id'] ?? null;
        $bookId = $_POST['book_id'] ?? null;
        $reviewerName = $_POST['reviewer_name'] ?? '';
        $reviewerAge = $_POST['reviewer_age'] ?? null;
        $sourceId = $_POST['source_id'] ?? 1;
        $reviewDate = $_POST['review_date'] ?? date('Y-m-d');
        $originalRating = $_POST['original_rating'] ?? '';
        $ratingNormalised = $_POST['rating_normalised'] ?? 0;
        $reviewText = $_POST['review_text'] ?? '';

        // Validate required fields
        if (empty($reviewId)) {
            throw new Exception('Review ID is required.');
        }

        if (empty($bookId)) {
            throw new Exception('Book ID is required.');
        }

        if (empty($ratingNormalised)) {
            throw new Exception('Rating is required.');
        }

        // Calculate rating value and scale
        $ratingValue = $ratingNormalised * 5;
        $ratingScale = 5;

        // Update the review in the database
        $stmt = $db->prepare("
            UPDATE reviews SET
                source_id = ?,
                reviewer_name = ?,
                reviewer_age = ?,
                review_date = ?,
                original_rating = ?,
                rating_value = ?,
                rating_scale = ?,
                rating_normalised = ?,
                review_text = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND book_id = ?
        ");

        $stmt->execute([
            $sourceId, $reviewerName, $reviewerAge, $reviewDate,
            $originalRating, $ratingValue, $ratingScale, $ratingNormalised, $reviewText,
            $reviewId, $bookId
        ]);

        // Update the book's average rating and review count
        updateBookRatings($bookId);

        $response['success'] = true;
        $response['message'] = 'Review updated successfully.';
    } catch (Exception $e) {
        $response['message'] = 'Error updating review: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

/**
 * Delete a review
 */
function deleteReview() {
    global $db, $response;

    try {
        // Get review data from the request
        $reviewId = $_POST['review_id'] ?? null;
        $bookId = $_POST['book_id'] ?? null;

        // Debug logging
        error_log("Delete review function called");
        error_log("Review ID: " . $reviewId);
        error_log("Book ID: " . $bookId);

        // Validate required fields
        if (empty($reviewId)) {
            throw new Exception('Review ID is required.');
        }

        if (empty($bookId)) {
            throw new Exception('Book ID is required.');
        }

        // Check if the review exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE id = ? AND book_id = ?");
        $checkStmt->execute([$reviewId, $bookId]);
        $reviewExists = $checkStmt->fetchColumn() > 0;

        error_log("Review exists: " . ($reviewExists ? 'Yes' : 'No'));

        if (!$reviewExists) {
            throw new Exception('Review not found.');
        }

        // Delete the review from the database
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ? AND book_id = ?");
        $stmt->execute([$reviewId, $bookId]);

        $rowsAffected = $stmt->rowCount();
        error_log("Rows affected by delete: " . $rowsAffected);

        // Update the book's average rating and review count
        updateBookRatings($bookId);

        $response['success'] = true;
        $response['message'] = 'Review deleted successfully.';
    } catch (Exception $e) {
        error_log("Error in deleteReview: " . $e->getMessage());
        $response['message'] = 'Error deleting review: ' . $e->getMessage();
    }

    error_log("Delete response: " . json_encode($response));
    echo json_encode($response);
    exit;
}

/**
 * Update a book's average rating and review count
 */
function updateBookRatings($bookId) {
    global $db;

    try {
        // Get all reviews for the book
        $stmt = $db->prepare("SELECT rating_normalised FROM reviews WHERE book_id = ?");
        $stmt->execute([$bookId]);
        $reviews = $stmt->fetchAll();

        // Calculate average rating and review count
        $reviewCount = count($reviews);
        $averageRating = 0;

        if ($reviewCount > 0) {
            $ratingSum = 0;
            foreach ($reviews as $review) {
                $ratingSum += $review['rating_normalised'];
            }
            $averageRating = $ratingSum / $reviewCount;
        }

        // Update the directory item with the new average rating and review count
        $stmt = $db->prepare("
            UPDATE directory_items SET
                average_rating = ?,
                review_count = ?
            WHERE id = ?
        ");
        $stmt->execute([$averageRating, $reviewCount, $bookId]);
    } catch (Exception $e) {
        error_log('Error updating book ratings: ' . $e->getMessage());
    }
}
