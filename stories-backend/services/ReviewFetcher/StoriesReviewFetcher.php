<?php
/**
 * Stories From The Web Review Fetcher
 *
 * This class fetches reviews from the Stories From The Web database.
 */

namespace Services\ReviewFetcher;

use PDO;

class StoriesReviewFetcher extends AbstractReviewFetcher {
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Stories From The Web');
    }

    /**
     * Check if the fetcher is configured correctly
     *
     * @return bool True if the fetcher is configured correctly, false otherwise
     */
    public function isConfigured(): bool {
        // No external configuration needed for internal database
        return true;
    }

    /**
     * Fetch reviews for a book by ISBN
     *
     * @param string $isbn The ISBN of the book (can be ISBN-10 or ISBN-13)
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array {
        // Standardize ISBN format
        $isbnData = $this->standardizeISBN($isbn);

        // Try ISBN-13 first, then ISBN-10
        $isbnToUse = !empty($isbnData['isbn13']) ? $isbnData['isbn13'] : $isbnData['isbn'];

        // Find the book in our database
        $bookId = $this->findBookIdByISBN($isbnToUse);

        if (empty($bookId)) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $bookId = $this->findBookIdByISBN($isbnToUse);
            }

            if (empty($bookId)) {
                $this->lastError = "No book found in Stories From The Web database for ISBN: $isbnToUse";
                return [];
            }
        }

        // Get reviews from our database
        $reviews = $this->getReviewsForBook($bookId, $limit);

        if (empty($reviews)) {
            $this->lastError = "No reviews found in Stories From The Web database for this book";
            return [];
        }

        return $reviews;
    }

    /**
     * Find a book ID by ISBN
     *
     * @param string $isbn The ISBN to search for
     * @return int|null The book ID or null if not found
     */
    private function findBookIdByISBN(string $isbn): ?int {
        // Try to find the book by ISBN-13 or ISBN-10
        $stmt = $this->db->prepare("
            SELECT di.id
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE b.isbn = ? OR b.isbn13 = ?
            LIMIT 1
        ");
        $stmt->execute([$isbn, $isbn]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['id'] : null;
    }

    /**
     * Get reviews for a book from our database
     *
     * @param int $bookId The book ID
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function getReviewsForBook(int $bookId, int $limit): array {
        // Get reviews from our database
        $stmt = $this->db->prepare("
            SELECT r.*, di.title as book_title
            FROM reviews r
            JOIN directory_items di ON r.book_id = di.id
            WHERE r.book_id = ? AND r.source_id = ?
            ORDER BY r.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$bookId, $this->sourceId, $limit]);
        $reviewsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reviews = [];
        foreach ($reviewsData as $review) {
            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $review['reviewer_name'],
                'reviewer_age' => $review['reviewer_age'],
                'review_date' => $review['review_date'],
                'original_rating' => $review['original_rating'],
                'rating_value' => (float)$review['rating_value'],
                'rating_scale' => (float)$review['rating_scale'],
                'rating_normalised' => (float)$review['rating_normalised'],
                'review_text' => $review['review_text'],
                'metadata' => json_encode([
                    'review_id' => $review['id'],
                    'book_title' => $review['book_title'],
                    'is_synthetic' => false
                ])
            ];
        }

        return $reviews;
    }
}
