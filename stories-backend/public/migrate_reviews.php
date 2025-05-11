<?php
/**
 * Review Migration Script
 * This script migrates legacy reviews to the new review system
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer to ensure real-time progress display
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

/**
 * Migrate reviews from book descriptions to the new review system
 * 
 * @param PDO $db Database connection
 * @return array Status information about the migration
 */
function migrateReviews($db) {
    $stats = [
        'total_books_processed' => 0,
        'total_reviews_migrated' => 0,
        'books_with_reviews' => 0,
        'errors' => []
    ];
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        echo "<h3>Starting Review Migration</h3>";
        flushOutput();
        
        // Get all books with descriptions that might contain reviews
        $stmt = $db->prepare("
            SELECT id, title, description 
            FROM directory_items 
            WHERE type = 'book' AND description IS NOT NULL
        ");
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['total_books_processed'] = count($books);
        echo "<p class='info'>Found {$stats['total_books_processed']} books to process</p>";
        flushOutput();
        
        // Process each book
        foreach ($books as $book) {
            echo "<h4>Processing book: {$book['title']} (ID: {$book['id']})</h4>";
            flushOutput();
            
            $reviews = extractReviewsFromDescription($book['description']);
            
            if (!empty($reviews)) {
                $stats['books_with_reviews']++;
                echo "<p class='success'>Found " . count($reviews) . " reviews in description</p>";
                flushOutput();
                
                foreach ($reviews as $review) {
                    try {
                        // Insert the review into the new system
                        $insertStmt = $db->prepare("
                            INSERT INTO reviews (
                                book_id, 
                                source_id, 
                                reviewer_name, 
                                reviewer_age, 
                                review_date, 
                                original_rating, 
                                rating_value, 
                                rating_scale, 
                                rating_normalised, 
                                review_text
                            ) VALUES (
                                :book_id,
                                :source_id,
                                :reviewer_name,
                                :reviewer_age,
                                CURRENT_DATE,
                                :original_rating,
                                :rating_value,
                                :rating_scale,
                                :rating_normalised,
                                :review_text
                            )
                        ");
                        
                        $insertStmt->execute([
                            ':book_id' => $book['id'],
                            ':source_id' => 1, // Stories from the Web source
                            ':reviewer_name' => $review['reviewer_name'],
                            ':reviewer_age' => $review['reviewer_age'],
                            ':original_rating' => $review['original_rating'],
                            ':rating_value' => $review['rating_value'],
                            ':rating_scale' => $review['rating_scale'],
                            ':rating_normalised' => $review['rating_normalised'],
                            ':review_text' => $review['review_text']
                        ]);
                        
                        $stats['total_reviews_migrated']++;
                        echo "<p class='info'>Migrated review by {$review['reviewer_name']}, rating: {$review['original_rating']}</p>";
                        flushOutput();
                    } catch (Exception $e) {
                        $stats['errors'][] = "Error inserting review for book {$book['id']}: " . $e->getMessage();
                        echo "<p class='error'>Error inserting review: " . $e->getMessage() . "</p>";
                        flushOutput();
                    }
                }
                
                // Update aggregate values for the book
                updateBookAggregateValues($db, $book['id']);
            } else {
                echo "<p class='info'>No reviews found in description</p>";
                flushOutput();
            }
        }
        
        // Commit transaction
        $db->commit();
        
        echo "<h3>Review Migration Complete</h3>";
        echo "<p class='success'>Processed {$stats['total_books_processed']} books</p>";
        echo "<p class='success'>Found reviews in {$stats['books_with_reviews']} books</p>";
        echo "<p class='success'>Migrated {$stats['total_reviews_migrated']} reviews</p>";
        
        if (!empty($stats['errors'])) {
            echo "<h4>Errors:</h4>";
            echo "<ul class='error'>";
            foreach ($stats['errors'] as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }
        
        flushOutput();
        
        return $stats;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = "Migration failed: " . $e->getMessage();
        echo "<p class='error'>Migration failed: " . $e->getMessage() . "</p>";
        flushOutput();
        return $stats;
    }
}

/**
 * Extract reviews from a book description
 * 
 * @param string $description The book description
 * @return array Array of extracted reviews
 */
function extractReviewsFromDescription($description) {
    $reviews = [];
    
    // Pattern 1: Look for "Reviewer Name: Reviewer Age: Review: Rating:" format
    if (preg_match_all('/\*\*Reviewer(?:\s*Name)?:\*\*\s*([^\*]+)\s*\*\*(?:Reviewer\s*)?Age:\*\*\s*(\d+)\s*\*\*Review:\*\*\s*([^\*]+)\s*\*\*Indicative Rating:\*\*\s*(\d+)\/(\d+)/i', $description, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];
            
            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }
    
    // Pattern 2: Look for "Reviewer: Name Age: X Review: ... Rating: X/Y" format
    if (preg_match_all('/\*\*Reviewer:\s*([^\*]+)\*\*\s*Age:\s*(\d+)\s*Review:\s*([^\*]+)(?:Indicative\s*)?Rating:\s*(\d+)\/(\d+)/i', $description, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];
            
            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }
    
    // Pattern 3: Look for "Children's Reviews" section with reviewer and age
    if (preg_match_all('/\*\*Reviewer:\s*([^\*]+)\*\*\s*Age:\s*(\d+)\s*Review:\s*([^\.]+(?:\.[^\.]+)*)\.\s*Indicative Rating:\s*(\d+)\/(\d+)/i', $description, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]) . '.';
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];
            
            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }
    
    return $reviews;
}

/**
 * Update aggregate values for a book
 * 
 * @param PDO $db Database connection
 * @param int $bookId The book ID
 * @return bool Success status
 */
function updateBookAggregateValues($db, $bookId) {
    try {
        $stmt = $db->prepare("
            UPDATE directory_items 
            SET 
                review_count = (SELECT COUNT(*) FROM reviews WHERE book_id = :book_id),
                average_rating = (SELECT AVG(rating_normalised) FROM reviews WHERE book_id = :book_id),
                highest_rating = (SELECT MAX(rating_normalised) FROM reviews WHERE book_id = :book_id),
                lowest_rating = (SELECT MIN(rating_normalised) FROM reviews WHERE book_id = :book_id)
            WHERE id = :book_id
        ");
        
        $stmt->execute([':book_id' => $bookId]);
        echo "<p class='success'>Updated aggregate values for book ID: $bookId</p>";
        flushOutput();
        return true;
    } catch (Exception $e) {
        echo "<p class='error'>Error updating aggregate values: " . $e->getMessage() . "</p>";
        flushOutput();
        return false;
    }
}

// If this script is called directly, run the migration
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // Set page variables for header
    $pageTitle = 'Review Migration Tool';
    $currentPage = 'import';
    $pageDescription = 'Migrate legacy reviews to the new review system';

    // Include header
    require_once '../admin/includes/header.php';
    
    echo '<div class="container-fluid">';
    echo '<div class="row">';
    echo '<div class="col-md-12">';
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h2>Review Migration Tool</h2>';
    echo '</div>';
    echo '<div class="card-body">';
    
    // Run migration
    $stats = migrateReviews($db);
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Include footer
    require_once '../admin/includes/footer.php';
}
?>
