<?php
/**
 * School Library Journal Review Fetcher
 *
 * This class fetches reviews from School Library Journal by scraping the website.
 */

namespace Services\ReviewFetcher;

use PDO;

class SLJReviewFetcher extends AbstractReviewFetcher {
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'School Library Journal');
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
     * @param array $options Additional options for the fetcher
     * @return array Array of review data
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10, array $options = []): array {
        // Standardize ISBN format
        $isbnData = $this->standardizeISBN($isbn);

        // Try ISBN-13 first, then ISBN-10
        $isbnToUse = !empty($isbnData['isbn13']) ? $isbnData['isbn13'] : $isbnData['isbn'];

        // First, search for the book on SLJ
        $reviewUrl = $this->findReviewUrl($isbnToUse);

        if (empty($reviewUrl)) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $reviewUrl = $this->findReviewUrl($isbnToUse);
            }

            if (empty($reviewUrl)) {
                $this->lastError = "No review found on School Library Journal for ISBN: $isbnToUse";
                return [];
            }
        }

        // Get review content
        $reviewContent = $this->getReviewContent($reviewUrl);

        if (empty($reviewContent)) {
            $this->lastError = "Failed to get review content from School Library Journal";
            return [];
        }

        // Create a review object
        $reviews = [];
        $reviews[] = [
            'source_id' => $this->sourceId,
            'reviewer_name' => "School Library Journal",
            'reviewer_age' => null,
            'review_date' => $reviewContent['review_date'] ?? date('Y-m-d'),
            'original_rating' => $reviewContent['rating'] ?? "N/A",
            'rating_value' => $reviewContent['rating_value'] ?? null,
            'rating_scale' => $reviewContent['rating_scale'] ?? null,
            'rating_normalised' => $reviewContent['rating_normalised'] ?? null,
            'review_text' => $reviewContent['review_text'] ?? "",
            'metadata' => json_encode([
                'review_url' => $reviewUrl,
                'is_synthetic' => false,
                'book_title' => $reviewContent['book_title'] ?? "",
                'book_author' => $reviewContent['book_author'] ?? ""
            ])
        ];

        return $reviews;
    }

    /**
     * Find the SLJ review URL by ISBN
     *
     * @param string $isbn The ISBN to search for
     * @return string|null The review URL or null if not found
     */
    private function findReviewUrl(string $isbn): ?string {
        // Build the search URL
        $searchUrl = "https://www.slj.com/search?q={$isbn}";

        // Make the request
        $response = $this->makeRequest($searchUrl);

        if ($response === false) {
            return null;
        }

        // Debug: Save the raw HTML to a file for inspection
        // Uncomment this line to debug
        // file_put_contents(__DIR__ . '/slj_search_debug.html', substr($response, 0, 50000));

        // Extract the review URL from the search results
        if (preg_match('/<a[^>]+href="(https:\/\/www\.slj\.com\/review\/[^"]+)"[^>]*>/i', $response, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get review content from SLJ
     *
     * @param string $reviewUrl The review URL
     * @return array|null The review content or null if not found
     */
    private function getReviewContent(string $reviewUrl): ?array {
        // Make the request
        $response = $this->makeRequest($reviewUrl);

        if ($response === false) {
            return null;
        }

        // Debug: Save the raw HTML to a file for inspection
        // Uncomment this line to debug
        // file_put_contents(__DIR__ . '/slj_review_debug.html', substr($response, 0, 50000));

        $content = [];

        // Extract book title
        if (preg_match('/<h1[^>]*class="[^"]*article-title[^"]*"[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $content['book_title'] = trim($matches[1]);
        }

        // Extract book author
        if (preg_match('/By\s+([^<]+)<\/p>/i', $response, $matches)) {
            $content['book_author'] = trim($matches[1]);
        }

        // Extract review text
        if (preg_match('/<div[^>]*class="[^"]*article-content[^"]*"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $content['review_text'] = trim(strip_tags($matches[1]));
        }

        // Extract review date
        if (preg_match('/<time[^>]*datetime="([^"]+)"[^>]*>/i', $response, $matches)) {
            $content['review_date'] = substr($matches[1], 0, 10); // Extract YYYY-MM-DD
        } else if (preg_match('/<span[^>]*class="[^"]*date[^"]*"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $dateStr = trim($matches[1]);
            $timestamp = strtotime($dateStr);
            if ($timestamp) {
                $content['review_date'] = date('Y-m-d', $timestamp);
            }
        }

        // SLJ doesn't typically provide star ratings, so we'll leave those fields null

        // If we have a review text, we consider it a success
        if (!empty($content['review_text'])) {
            return $content;
        }

        return null;
    }
}
