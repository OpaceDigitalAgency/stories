<?php
/**
 * Goodreads Review Fetcher
 *
 * This class fetches reviews from Goodreads by scraping the website.
 * Note: Goodreads API was deprecated, so we need to scrape the website.
 */

namespace Services\ReviewFetcher;

use PDO;

class GoodreadsReviewFetcher extends AbstractReviewFetcher {
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Goodreads');
    }

    /**
     * Check if the fetcher is configured correctly
     *
     * @return bool True if the fetcher is configured correctly, false otherwise
     */
    public function isConfigured(): bool {
        // No configuration needed for scraping
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

        // First, search for the book on Goodreads
        $bookUrl = $this->findBookUrl($isbnToUse);

        if (empty($bookUrl)) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $bookUrl = $this->findBookUrl($isbnToUse);
            }

            if (empty($bookUrl)) {
                $this->lastError = "No book found on Goodreads for ISBN: $isbnToUse";
                return [];
            }
        }

        // Get book details
        $bookDetails = $this->getBookDetails($bookUrl);

        if (empty($bookDetails)) {
            $this->lastError = "Failed to get book details from Goodreads";
            return [];
        }

        // Get reviews URL
        $reviewsUrl = $bookUrl . "/reviews";

        // Fetch reviews
        $reviews = $this->scrapeReviews($reviewsUrl, $limit);

        // If no reviews found but we have average rating, add an aggregate review
        if (empty($reviews) && !empty($bookDetails['average_rating'])) {
            $averageRating = (float)$bookDetails['average_rating'];
            $ratingsCount = $bookDetails['ratings_count'] ?? 0;

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Goodreads Aggregate",
                'reviewer_age' => null,
                'review_date' => date('Y-m-d'),
                'original_rating' => "{$averageRating}/5",
                'rating_value' => $averageRating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($averageRating, 5),
                'review_text' => "This book has an average rating of {$averageRating}/5 based on {$ratingsCount} ratings on Goodreads.",
                'metadata' => json_encode([
                    'book_url' => $bookDetails['url'] ?? '',
                    'is_synthetic' => false,
                    'is_aggregate' => true,
                    'ratings_count' => $ratingsCount
                ])
            ];
        }

        // Add book metadata to each review
        foreach ($reviews as $key => $review) {
            $reviews[$key]['book_metadata'] = $bookDetails;
        }

        return $reviews;
    }

    /**
     * Find the Goodreads book URL by ISBN
     *
     * @param string $isbn The ISBN to search for
     * @return string|null The book URL or null if not found
     */
    private function findBookUrl(string $isbn): ?string {
        // Build the search URL
        $searchUrl = "https://www.goodreads.com/search?q={$isbn}";

        // Make the request
        $response = $this->makeRequest($searchUrl);

        if ($response === false) {
            return null;
        }

        // Extract the book URL from the search results
        if (preg_match('/<a class="bookTitle" href="([^"]+)"/i', $response, $matches)) {
            return 'https://www.goodreads.com' . html_entity_decode($matches[1]);
        }

        return null;
    }

    /**
     * Get book details from Goodreads
     *
     * @param string $bookUrl The book URL
     * @return array|null The book details or null if not found
     */
    private function getBookDetails(string $bookUrl): ?array {
        // Make the request
        $response = $this->makeRequest($bookUrl);

        if ($response === false) {
            return null;
        }

        $details = [];

        // Extract book title
        if (preg_match('/<h1 id="bookTitle"[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        }

        // Extract book author
        if (preg_match('/<a class="authorName"[^>]*><span[^>]*>([^<]+)<\/span><\/a>/i', $response, $matches)) {
            $details['author'] = trim($matches[1]);
        }

        // Extract book cover
        if (preg_match('/<img id="coverImage"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        }

        // Extract book description
        if (preg_match('/<div id="description"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        }

        // Extract average rating
        if (preg_match('/<span itemprop="ratingValue"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
        }

        // Extract ratings count
        if (preg_match('/<meta itemprop="ratingCount" content="([^"]+)"/i', $response, $matches)) {
            $details['ratings_count'] = (int)$matches[1];
        }

        // Extract publication info
        if (preg_match('/Published\s+(.*?)(?:\s+by\s+(.*?))?(?:\s+\(first published\s+(.*?)\))?</is', $response, $matches)) {
            if (!empty($matches[1])) {
                $details['published_date'] = trim($matches[1]);
            }
            if (!empty($matches[2])) {
                $details['publisher'] = trim($matches[2]);
            }
        }

        // Extract ISBN
        if (preg_match('/ISBN\s+(\d+X?)/i', $response, $matches)) {
            $details['isbn'] = $matches[1];
        }

        // Extract ISBN13
        if (preg_match('/ISBN13\s+(\d+)/i', $response, $matches)) {
            $details['isbn13'] = $matches[1];
        }

        // Extract page count
        if (preg_match('/(\d+)\s+pages/i', $response, $matches)) {
            $details['page_count'] = (int)$matches[1];
        }

        // Extract genres/shelves
        if (preg_match_all('/<a class="actionLinkLite bookPageGenreLink"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['genres'] = array_map('trim', $matches[1]);
        }

        $details['url'] = $bookUrl;

        return $details;
    }

    /**
     * Scrape reviews from Goodreads
     *
     * @param string $reviewsUrl The reviews URL
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function scrapeReviews(string $reviewsUrl, int $limit): array {
        // Make the request
        $response = $this->makeRequest($reviewsUrl);

        if ($response === false) {
            return [];
        }

        $reviews = [];

        // Extract review blocks
        if (preg_match_all('/<div class="review"[^>]*id="review_(\d+)".*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/is', $response, $reviewBlocks, PREG_SET_ORDER)) {
            foreach ($reviewBlocks as $index => $block) {
                if ($index >= $limit) {
                    break;
                }

                $reviewId = $block[1];
                $reviewHtml = $block[0];

                // Extract reviewer name
                $reviewerName = 'Goodreads User';
                if (preg_match('/<a class="user"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                    $reviewerName = trim($matches[1]);
                }

                // Extract rating
                $rating = 0;
                if (preg_match('/<span class="static-stars"[^>]*title="([^"]+)"/i', $reviewHtml, $matches)) {
                    if (preg_match('/(\d+)/', $matches[1], $ratingMatch)) {
                        $rating = (int)$ratingMatch[1];
                    }
                }

                // Extract review text
                $reviewText = '';
                if (preg_match('/<div class="reviewText"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                    $reviewText = trim(strip_tags($matches[1]));
                }

                // Extract review date
                $reviewDate = null;
                if (preg_match('/<a class="reviewDate"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                    $reviewDate = $this->formatDate($matches[1]);
                }

                // Skip reviews without text or rating
                if (empty($reviewText) || $rating == 0) {
                    continue;
                }

                $reviews[] = [
                    'source_id' => $this->sourceId,
                    'reviewer_name' => $reviewerName,
                    'reviewer_age' => null,
                    'review_date' => $reviewDate,
                    'original_rating' => "{$rating}/5",
                    'rating_value' => (float)$rating,
                    'rating_scale' => 5,
                    'rating_normalised' => $this->normalizeRating((float)$rating, 5),
                    'review_text' => $this->cleanText($reviewText),
                    'metadata' => json_encode([
                        'review_id' => $reviewId,
                        'review_url' => "{$reviewsUrl}#{$reviewId}",
                        'is_synthetic' => false
                    ])
                ];
            }
        }

        return $reviews;
    }


}
