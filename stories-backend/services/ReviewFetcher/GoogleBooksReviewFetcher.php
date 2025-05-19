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
     * @param array $options Additional options for the fetcher
     * @return array Array of review data
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10, array $options = []): array {
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
        // Get the volume details from the API
        $url = "{$this->apiBaseUrl}/volumes/{$volumeId}";

        // Add API key if available
        if (!empty($this->apiKey)) {
            $url .= "?key={$this->apiKey}";
        }

        $response = $this->makeRequest($url);

        if ($response === false) {
            return [];
        }

        // Parse the response
        $volumeData = json_decode($response, true);

        if (empty($volumeData) || !isset($volumeData['volumeInfo'])) {
            return [];
        }

        $reviews = [];

        // Check if we have ratings
        if (isset($volumeData['volumeInfo']['averageRating']) && isset($volumeData['volumeInfo']['ratingsCount'])) {
            $averageRating = (float)$volumeData['volumeInfo']['averageRating'];
            $ratingCount = (int)$volumeData['volumeInfo']['ratingsCount'];

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
                    'review_text' => $this->generateReviewText($volumeData, $averageRating, $ratingCount),
                    'metadata' => json_encode([
                        'volume_id' => $volumeId,
                        'review_url' => $volumeData['volumeInfo']['infoLink'] ?? "https://books.google.com/books?id={$volumeId}",
                        'is_synthetic' => false,
                        'is_aggregate' => true,
                        'ratings_count' => $ratingCount,
                        'volume_info' => $this->extractVolumeInfo($volumeData)
                    ])
                ];
            }
        }

        return $reviews;
    }

    /**
     * Generate review text based on volume data
     *
     * @param array $volumeData The volume data from Google Books API
     * @param float $averageRating The average rating
     * @param int $ratingCount The number of ratings
     * @return string The generated review text
     */
    private function generateReviewText(array $volumeData, float $averageRating, int $ratingCount): string {
        $volumeInfo = $volumeData['volumeInfo'] ?? [];
        $title = $volumeInfo['title'] ?? 'This book';
        $authors = isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : 'Unknown author';

        $text = "{$title} by {$authors} has an average rating of {$averageRating}/5 based on {$ratingCount} ratings on Google Books.";

        // Add description if available
        if (!empty($volumeInfo['description'])) {
            $description = $volumeInfo['description'];
            // Truncate description if too long
            if (strlen($description) > 500) {
                $description = substr($description, 0, 500) . '...';
            }
            $text .= "\n\nDescription: {$description}";
        }

        // Add categories if available
        if (!empty($volumeInfo['categories'])) {
            $categories = implode(', ', $volumeInfo['categories']);
            $text .= "\n\nCategories: {$categories}";
        }

        return $text;
    }

    /**
     * Extract relevant volume information
     *
     * @param array $volumeData The volume data from Google Books API
     * @return array The extracted volume information
     */
    private function extractVolumeInfo(array $volumeData): array {
        $volumeInfo = $volumeData['volumeInfo'] ?? [];

        return [
            'title' => $volumeInfo['title'] ?? '',
            'subtitle' => $volumeInfo['subtitle'] ?? '',
            'authors' => $volumeInfo['authors'] ?? [],
            'publisher' => $volumeInfo['publisher'] ?? '',
            'publishedDate' => $volumeInfo['publishedDate'] ?? '',
            'description' => $volumeInfo['description'] ?? '',
            'pageCount' => $volumeInfo['pageCount'] ?? null,
            'categories' => $volumeInfo['categories'] ?? [],
            'averageRating' => $volumeInfo['averageRating'] ?? null,
            'ratingsCount' => $volumeInfo['ratingsCount'] ?? 0,
            'maturityRating' => $volumeInfo['maturityRating'] ?? '',
            'language' => $volumeInfo['language'] ?? '',
            'imageLinks' => $volumeInfo['imageLinks'] ?? [],
            'previewLink' => $volumeInfo['previewLink'] ?? '',
            'infoLink' => $volumeInfo['infoLink'] ?? '',
            'canonicalVolumeLink' => $volumeInfo['canonicalVolumeLink'] ?? ''
        ];
    }


}
