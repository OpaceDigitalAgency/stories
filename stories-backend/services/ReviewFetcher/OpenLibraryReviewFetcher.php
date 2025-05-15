<?php
/**
 * Open Library Review Fetcher
 *
 * This class fetches book information from Open Library and reviews from Internet Archive.
 */

namespace Services\ReviewFetcher;

use PDO;

class OpenLibraryReviewFetcher extends AbstractReviewFetcher {
    /**
     * @var string Open Library API base URL
     */
    private $apiBaseUrl = 'https://openlibrary.org/api';

    /**
     * @var string Internet Archive API base URL
     */
    private $iaApiBaseUrl = 'https://archive.org/metadata';

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Open Library');
    }

    /**
     * Check if the fetcher is configured correctly
     *
     * @return bool True if the fetcher is configured correctly, false otherwise
     */
    public function isConfigured(): bool {
        // Open Library API doesn't require authentication
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

        // Build the API URL
        $url = "{$this->apiBaseUrl}/books?bibkeys=ISBN:{$isbnToUse}&format=json&jscmd=data";

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            return [];
        }

        // Parse the response
        $data = json_decode($response, true);

        if (empty($data["ISBN:$isbnToUse"])) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $url = "{$this->apiBaseUrl}/books?bibkeys=ISBN:{$isbnToUse}&format=json&jscmd=data";
                $response = $this->makeRequest($url);

                if ($response === false) {
                    return [];
                }

                $data = json_decode($response, true);
            }

            if (empty($data["ISBN:$isbnToUse"])) {
                $this->lastError = "No books found for ISBN: $isbnToUse";
                return [];
            }
        }

        // Get the book data
        $bookData = $data["ISBN:$isbnToUse"];

        // Get the Open Library ID
        $olid = null;
        if (!empty($bookData['identifiers']['openlibrary'])) {
            $olid = $bookData['identifiers']['openlibrary'][0];
        }

        if (empty($olid)) {
            $this->lastError = "No Open Library ID found for ISBN: $isbnToUse";
            return [];
        }

        // Get the Internet Archive ID
        $iaId = $this->getInternetArchiveId($olid);

        if (empty($iaId)) {
            // No Internet Archive ID, return empty array
            $this->lastError = "No Internet Archive ID found for Open Library ID: $olid";
            return [];
        }

        // Fetch reviews from Internet Archive
        $reviews = $this->fetchReviewsFromInternetArchive($iaId, $limit);

        // If no reviews found, add an aggregate rating if available
        if (empty($reviews) && !empty($bookData['ratings_average'])) {
            $averageRating = $bookData['ratings_average'];
            $ratingCount = $bookData['ratings_count'] ?? 0;

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Open Library Aggregate",
                'reviewer_age' => null,
                'review_date' => date('Y-m-d'),
                'original_rating' => "{$averageRating}/5",
                'rating_value' => $averageRating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($averageRating, 5),
                'review_text' => "This book has an average rating of {$averageRating}/5 based on {$ratingCount} ratings on Open Library.",
                'metadata' => json_encode([
                    'ia_id' => $iaId,
                    'book_url' => $bookData['url'] ?? '',
                    'is_synthetic' => false,
                    'is_aggregate' => true,
                    'ratings_count' => $ratingCount
                ])
            ];
        }

        // Add book metadata to each review
        foreach ($reviews as &$review) {
            $review['book_metadata'] = [
                'title' => $bookData['title'] ?? '',
                'authors' => array_map(function($author) {
                    return $author['name'] ?? '';
                }, $bookData['authors'] ?? []),
                'publisher' => $bookData['publishers'][0]['name'] ?? '',
                'published_date' => $bookData['publish_date'] ?? '',
                'number_of_pages' => $bookData['number_of_pages'] ?? null,
                'subjects' => array_map(function($subject) {
                    return $subject['name'] ?? '';
                }, $bookData['subjects'] ?? []),
                'cover_url' => $bookData['cover']['large'] ?? $bookData['cover']['medium'] ?? $bookData['cover']['small'] ?? '',
                'url' => $bookData['url'] ?? '',
            ];
        }

        return $reviews;
    }

    /**
     * Get the Internet Archive ID for an Open Library ID
     *
     * @param string $olid The Open Library ID
     * @return string|null The Internet Archive ID or null if not found
     */
    private function getInternetArchiveId(string $olid): ?string {
        // Build the API URL
        $url = "https://openlibrary.org/works/$olid.json";

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            return null;
        }

        // Parse the response
        $data = json_decode($response, true);

        // Check for Internet Archive ID
        if (!empty($data['ocaid'])) {
            return $data['ocaid'];
        }

        return null;
    }

    /**
     * Fetch reviews from Internet Archive
     *
     * @param string $iaId The Internet Archive ID
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function fetchReviewsFromInternetArchive(string $iaId, int $limit): array {
        // Build the API URL
        $url = "{$this->iaApiBaseUrl}/{$iaId}/reviews";

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            return [];
        }

        // Parse the response
        $data = json_decode($response, true);

        if (empty($data['reviews'])) {
            return [];
        }

        // Process reviews
        $reviews = [];
        $count = 0;

        foreach ($data['reviews'] as $review) {
            if ($count >= $limit) {
                break;
            }

            // Skip reviews without text or stars
            if (empty($review['reviewtext']) || !isset($review['stars'])) {
                continue;
            }

            $reviewDate = null;
            if (!empty($review['createdate'])) {
                $reviewDate = $this->formatDate($review['createdate']);
            }

            $reviewerName = !empty($review['reviewer']) ? $review['reviewer'] : 'Anonymous';

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $reviewerName,
                'reviewer_age' => null,
                'review_date' => $reviewDate,
                'original_rating' => "{$review['stars']}/5",
                'rating_value' => (float)$review['stars'],
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating((float)$review['stars'], 5),
                'review_text' => $this->cleanText($review['reviewtext']),
                'metadata' => json_encode([
                    'ia_id' => $iaId,
                    'review_id' => $review['reviewid'] ?? '',
                    'review_url' => "https://archive.org/details/{$iaId}/reviews",
                    'is_synthetic' => false
                ])
            ];

            $count++;
        }

        return $reviews;
    }


}
