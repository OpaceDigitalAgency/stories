<?php
/**
 * Google Books Review Fetcher
 *
 * This class fetches reviews from Google Books API.
 */

namespace Services\ReviewFetcher;

use PDO;

class GoogleBooksReviewFetcher extends AbstractReviewFetcher {
    /**
     * @var string Google Books API key
     */
    private $apiKey;

    /**
     * @var string Google Books API base URL
     */
    private $apiBaseUrl = 'https://www.googleapis.com/books/v1';

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     * @param string $apiKey Google Books API key (optional)
     */
    public function __construct(PDO $db, int $sourceId, string $apiKey = '') {
        parent::__construct($db, $sourceId, 'Google Books');
        $this->apiKey = $apiKey;
    }

    /**
     * Check if the fetcher is configured correctly
     *
     * @return bool True if the fetcher is configured correctly, false otherwise
     */
    public function isConfigured(): bool {
        // Google Books API can be used without an API key, but with rate limits
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
        $isbn = !empty($isbnData['isbn13']) ? $isbnData['isbn13'] : $isbnData['isbn'];

        // Build the API URL
        $url = "{$this->apiBaseUrl}/volumes?q=isbn:{$isbn}";

        // Add API key if available
        if (!empty($this->apiKey)) {
            $url .= "&key={$this->apiKey}";
        }

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            return [];
        }

        // Parse the response
        $data = json_decode($response, true);

        if (empty($data['items'])) {
            $this->lastError = "No books found for ISBN: $isbn";
            return [];
        }

        // Get the first book (should be the only one for a specific ISBN)
        $book = $data['items'][0];

        // Get the volume ID
        $volumeId = $book['id'];

        // Fetch reviews for this volume
        $reviews = $this->fetchReviewsForVolume($volumeId, $limit);

        // Add book metadata to each review
        foreach ($reviews as &$review) {
            $review['book_metadata'] = [
                'title' => $book['volumeInfo']['title'] ?? '',
                'authors' => $book['volumeInfo']['authors'] ?? [],
                'publisher' => $book['volumeInfo']['publisher'] ?? '',
                'published_date' => $book['volumeInfo']['publishedDate'] ?? '',
                'description' => $book['volumeInfo']['description'] ?? '',
                'page_count' => $book['volumeInfo']['pageCount'] ?? null,
                'categories' => $book['volumeInfo']['categories'] ?? [],
                'average_rating' => $book['volumeInfo']['averageRating'] ?? null,
                'ratings_count' => $book['volumeInfo']['ratingsCount'] ?? 0,
                'thumbnail' => $book['volumeInfo']['imageLinks']['thumbnail'] ?? '',
                'info_link' => $book['volumeInfo']['infoLink'] ?? '',
            ];
        }

        return $reviews;
    }

    /**
     * Fetch reviews for a specific volume
     *
     * @param string $volumeId The Google Books volume ID
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function fetchReviewsForVolume(string $volumeId, int $limit): array {
        // Note: Google Books API doesn't provide a direct way to fetch user reviews
        // We'll use a workaround by scraping the Google Books web page

        $url = "https://books.google.com/books?id={$volumeId}&printsec=frontcover&source=gbs_ge_summary_r&cad=0";

        $response = $this->makeRequest($url);

        if ($response === false) {
            return [];
        }

        // Extract reviews from the HTML
        $reviews = $this->extractReviewsFromHTML($response, $volumeId, $limit);

        return $reviews;
    }

    /**
     * Extract reviews from HTML
     *
     * @param string $html The HTML content
     * @param string $volumeId The Google Books volume ID
     * @param int $limit Maximum number of reviews to extract
     * @return array Array of review data
     */
    private function extractReviewsFromHTML(string $html, string $volumeId, int $limit): array {
        $reviews = [];

        // Extract aggregate rating
        if (preg_match('/"aggregateRating":\s*{[^}]*"ratingValue":\s*([0-9.]+)[^}]*"ratingCount":\s*([0-9]+)/s', $html, $matches)) {
            $averageRating = (float)$matches[1];
            $ratingCount = (int)$matches[2];

            // Only proceed if we have ratings
            if ($ratingCount > 0) {
                // Create a single review with the aggregate rating
                $reviews[] = [
                    'source_id' => $this->sourceId,
                    'reviewer_name' => "Google Books Aggregate",
                    'reviewer_age' => null,
                    'review_date' => date('Y-m-d'),
                    'original_rating' => "{$averageRating}/5",
                    'rating_value' => $averageRating,
                    'rating_scale' => 5,
                    'rating_normalised' => $this->normalizeRating($averageRating, 5),
                    'review_text' => "This book has an average rating of {$averageRating}/5 based on {$ratingCount} ratings on Google Books.",
                    'metadata' => json_encode([
                        'volume_id' => $volumeId,
                        'review_url' => "https://books.google.com/books?id={$volumeId}",
                        'is_synthetic' => false,
                        'is_aggregate' => true,
                        'ratings_count' => $ratingCount
                    ])
                ];
            }
        }

        // Try to extract actual reviews if available
        if (preg_match_all('/<div class="review">(.*?)<\/div>/s', $html, $reviewMatches)) {
            foreach ($reviewMatches[1] as $reviewHtml) {
                // Extract reviewer name
                $reviewerName = "Google Books User";
                if (preg_match('/<span class="reviewer">(.*?)<\/span>/s', $reviewHtml, $nameMatch)) {
                    $reviewerName = strip_tags($nameMatch[1]);
                }

                // Extract rating
                $rating = 0;
                if (preg_match('/(\d) stars/i', $reviewHtml, $ratingMatch)) {
                    $rating = (int)$ratingMatch[1];
                } elseif (preg_match('/(\d)\/5/i', $reviewHtml, $ratingMatch)) {
                    $rating = (int)$ratingMatch[1];
                }

                // Extract review text
                $reviewText = "";
                if (preg_match('/<span class="reviewText">(.*?)<\/span>/s', $reviewHtml, $textMatch)) {
                    $reviewText = strip_tags($textMatch[1]);
                }

                // Extract date
                $reviewDate = date('Y-m-d');
                if (preg_match('/<span class="reviewDate">(.*?)<\/span>/s', $reviewHtml, $dateMatch)) {
                    $dateStr = strip_tags($dateMatch[1]);
                    $timestamp = strtotime($dateStr);
                    if ($timestamp) {
                        $reviewDate = date('Y-m-d', $timestamp);
                    }
                }

                // Only add if we have a rating
                if ($rating > 0) {
                    $reviews[] = [
                        'source_id' => $this->sourceId,
                        'reviewer_name' => $reviewerName,
                        'reviewer_age' => null,
                        'review_date' => $reviewDate,
                        'original_rating' => "{$rating}/5",
                        'rating_value' => $rating,
                        'rating_scale' => 5,
                        'rating_normalised' => $this->normalizeRating($rating, 5),
                        'review_text' => $reviewText,
                        'metadata' => json_encode([
                            'volume_id' => $volumeId,
                            'review_url' => "https://books.google.com/books?id={$volumeId}",
                            'is_synthetic' => false
                        ])
                    ];

                    // Limit the number of reviews
                    if (count($reviews) >= $limit) {
                        break;
                    }
                }
            }
        }

        return $reviews;
    }


}
