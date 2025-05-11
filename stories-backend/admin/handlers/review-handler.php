<?php
/**
 * Review Handler
 *
 * Handles AJAX requests for adding, editing, and deleting reviews.
 */

// Include database connection
require_once '../includes/db-connect.php';

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
        // Remove asterisks from reviewer name if present
        $reviewerName = preg_replace('/^\*\*/', '', $reviewerName);
        $reviewerName = preg_replace('/\*\*.*$/', '', $reviewerName); // Also remove any trailing ** and text after it
        $reviewerAge = $_POST['reviewer_age'] ?? null;
        $sourceId = $_POST['source_id'] ?? 1; // Default to Stories from the Web
        $reviewDate = $_POST['review_date'] ?? date('Y-m-d');
        $originalRating = $_POST['original_rating'] ?? '';
        $ratingNormalised = $_POST['rating_normalised'] ?? 0;
        $reviewText = $_POST['review_text'] ?? '';

        // Debug logging
        error_log("Add review function called");
        error_log("Book ID: " . $bookId);
        error_log("Reviewer Name: " . $reviewerName);
        error_log("Rating: " . $ratingNormalised);

        // Validate required fields
        if (empty($bookId)) {
            throw new Exception('Book ID is required.');
        }

        if (empty($ratingNormalised)) {
            throw new Exception('Rating is required.');
        }

        // Check if a review with the same reviewer name already exists for this book
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ? AND LOWER(TRIM(reviewer_name)) = LOWER(TRIM(?))");
        $checkStmt->execute([$bookId, $reviewerName]);
        $exists = $checkStmt->fetchColumn() > 0;

        if ($exists) {
            throw new Exception('A review by this reviewer already exists for this book. Please use the edit function instead.');
        }

        // Ensure rating_normalised is a valid float between 0 and 1
        $ratingNormalised = floatval($ratingNormalised);
        if ($ratingNormalised < 0) $ratingNormalised = 0;
        if ($ratingNormalised > 1) $ratingNormalised = 1;

        // Format review date if empty
        if (empty($reviewDate)) {
            $reviewDate = date('Y-m-d');
        }

        // Calculate rating value and scale
        $ratingValue = $ratingNormalised * 5;
        $ratingScale = 5;

        // Check if the book exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM directory_items WHERE id = ?");
        $checkStmt->execute([$bookId]);
        $bookExists = $checkStmt->fetchColumn() > 0;

        error_log("Book exists: " . ($bookExists ? 'Yes' : 'No'));

        if (!$bookExists) {
            throw new Exception('Book not found.');
        }

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

        $newReviewId = $db->lastInsertId();
        error_log("New review ID: " . $newReviewId);

        if (!$newReviewId) {
            throw new Exception('Failed to add review. No ID returned.');
        }

        // Update the book's average rating and review count
        updateBookRatings($bookId);

        $response['success'] = true;
        $response['message'] = 'Review added successfully.';
        $response['review_id'] = $newReviewId;
    } catch (Exception $e) {
        error_log("Error in addReview: " . $e->getMessage());
        $response['message'] = 'Error adding review: ' . $e->getMessage();
        $response['error'] = $e->getMessage();
    }

    error_log("Add response: " . json_encode($response));
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
        // Remove asterisks from reviewer name if present
        $reviewerName = preg_replace('/^\*\*/', '', $reviewerName);
        $reviewerName = preg_replace('/\*\*.*$/', '', $reviewerName); // Also remove any trailing ** and text after it
        $reviewerAge = $_POST['reviewer_age'] ?? null;
        $sourceId = $_POST['source_id'] ?? 1;
        $reviewDate = $_POST['review_date'] ?? date('Y-m-d');
        $originalRating = $_POST['original_rating'] ?? '';
        $ratingNormalised = $_POST['rating_normalised'] ?? 0;
        $reviewText = $_POST['review_text'] ?? '';

        // Debug logging
        error_log("Update review function called");
        error_log("Review ID: " . $reviewId);
        error_log("Book ID: " . $bookId);
        error_log("Reviewer Name: " . $reviewerName);
        error_log("Rating: " . $ratingNormalised);

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

        // Ensure rating_normalised is a valid float between 0 and 1
        $ratingNormalised = floatval($ratingNormalised);
        if ($ratingNormalised < 0) $ratingNormalised = 0;
        if ($ratingNormalised > 1) $ratingNormalised = 1;

        // Format review date if empty
        if (empty($reviewDate)) {
            $reviewDate = date('Y-m-d');
        }

        // Calculate rating value and scale
        $ratingValue = $ratingNormalised * 5;
        $ratingScale = 5;

        // Check if the review exists
        $checkStmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
        $checkStmt->execute([$reviewId]);
        $reviewData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $reviewExists = $reviewData !== false;

        // Debug the review data
        error_log("Review data: " . print_r($reviewData, true));
        error_log("Review exists: " . ($reviewExists ? 'Yes' : 'No'));

        if (!$reviewExists) {
            throw new Exception('Review not found.');
        }

        // Store the actual book_id from the review data
        $actualBookId = $reviewData['book_id'] ?? $bookId;

        // Update the review in the database - only use the review ID for the WHERE clause
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
            WHERE id = ?
        ");

        $stmt->execute([
            $sourceId, $reviewerName, $reviewerAge, $reviewDate,
            $originalRating, $ratingValue, $ratingScale, $ratingNormalised, $reviewText,
            $reviewId
        ]);

        $rowsAffected = $stmt->rowCount();
        error_log("Rows affected by update: " . $rowsAffected);

        if ($rowsAffected === 0) {
            throw new Exception('Failed to update review. No rows affected.');
        }

        // Use the actual book_id from the review data for updating ratings
        $bookId = $actualBookId;

        // Update the book's average rating and review count
        updateBookRatings($bookId);

        $response['success'] = true;
        $response['message'] = 'Review updated successfully.';
    } catch (Exception $e) {
        error_log("Error in updateReview: " . $e->getMessage());
        $response['message'] = 'Error updating review: ' . $e->getMessage();
        $response['error'] = $e->getMessage();
    }

    error_log("Update response: " . json_encode($response));
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

        // Check if the review exists - use a more lenient check that only looks at the review ID
        $checkStmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
        $checkStmt->execute([$reviewId]);
        $reviewData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $reviewExists = $reviewData !== false;

        // Debug the review data
        error_log("Review data: " . print_r($reviewData, true));
        error_log("Review exists: " . ($reviewExists ? 'Yes' : 'No'));

        // Continue even if review doesn't exist - this allows us to handle cases where the review might have been deleted already

        // Store the book_id from the review data if it exists
        $actualBookId = $reviewData['book_id'] ?? $bookId;

        // Delete the review from the database - only use the review ID for deletion
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$reviewId]);

        $rowsAffected = $stmt->rowCount();
        error_log("Rows affected by delete: " . $rowsAffected);

        // Don't throw an exception if no rows were affected
        // This allows the function to succeed even if the review was already deleted
        if ($rowsAffected === 0) {
            error_log("No rows affected by delete, but continuing anyway");
        }

        // Use the actual book_id from the review data for updating ratings
        $bookId = $actualBookId;

        // Update the book's average rating and review count
        updateBookRatings($bookId);

        $response['success'] = true;
        $response['message'] = 'Review deleted successfully.';
    } catch (Exception $e) {
        error_log("Error in deleteReview: " . $e->getMessage());
        $response['message'] = 'Error deleting review: ' . $e->getMessage();
        $response['error'] = $e->getMessage();
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
        $highestRating = 0;
        $lowestRating = 1; // Start with maximum possible value (normalized ratings are 0-1)

        if ($reviewCount > 0) {
            $ratingSum = 0;
            foreach ($reviews as $review) {
                $rating = (float)$review['rating_normalised'];
                $ratingSum += $rating;

                // Track highest and lowest ratings
                if ($rating > $highestRating) {
                    $highestRating = $rating;
                }
                if ($rating < $lowestRating) {
                    $lowestRating = $rating;
                }
            }
            $averageRating = $ratingSum / $reviewCount;
        } else {
            // If no reviews, set lowest rating to 0
            $lowestRating = 0;
        }

        error_log("Updating book ID $bookId with: count=$reviewCount, avg=$averageRating, high=$highestRating, low=$lowestRating");

        // Update the directory item with the new ratings data
        $stmt = $db->prepare("
            UPDATE directory_items SET
                average_rating = ?,
                review_count = ?,
                highest_rating = ?,
                lowest_rating = ?
            WHERE id = ?
        ");
        $stmt->execute([$averageRating, $reviewCount, $highestRating, $lowestRating, $bookId]);

        $rowsAffected = $stmt->rowCount();
        error_log("Rows affected by updating book ratings: " . $rowsAffected);

        if ($rowsAffected === 0) {
            error_log("Warning: No rows affected when updating book ratings for book ID: $bookId");
        }
    } catch (Exception $e) {
        error_log('Error updating book ratings: ' . $e->getMessage());
    }
}
