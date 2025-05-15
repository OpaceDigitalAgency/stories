<?php
/**
 * Amazon Review Fetcher
 *
 * This class fetches reviews from Amazon by scraping the website.
 * Note: Amazon doesn't provide a public API for reviews, so we need to scrape the website.
 */

namespace Services\ReviewFetcher;

use PDO;

class AmazonReviewFetcher extends AbstractReviewFetcher {
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Amazon');
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

        // First, search for the book on Amazon
        $productUrl = $this->findProductUrl($isbnToUse);

        if (empty($productUrl)) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $productUrl = $this->findProductUrl($isbnToUse);
            }

            if (empty($productUrl)) {
                $this->lastError = "No book found on Amazon for ISBN: $isbnToUse";
                return [];
            }
        }

        // Get product details
        $productDetails = $this->getProductDetails($productUrl);

        if (empty($productDetails)) {
            $this->lastError = "Failed to get product details from Amazon";
            return [];
        }

        // Get reviews URL
        $reviewsUrl = $this->getReviewsUrl($productUrl);

        // Fetch reviews
        $reviews = $this->scrapeReviews($reviewsUrl, $limit);

        // If no reviews found but we have average rating, add an aggregate review
        if (empty($reviews) && !empty($productDetails['average_rating'])) {
            $averageRating = (float)$productDetails['average_rating'];
            $ratingsCount = $productDetails['ratings_count'] ?? 0;

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Amazon Aggregate",
                'reviewer_age' => null,
                'review_date' => date('Y-m-d'),
                'original_rating' => "{$averageRating}/5",
                'rating_value' => $averageRating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($averageRating, 5),
                'review_text' => "This book has an average rating of {$averageRating}/5 based on {$ratingsCount} ratings on Amazon.",
                'metadata' => json_encode([
                    'product_url' => $productDetails['url'] ?? '',
                    'is_synthetic' => false,
                    'is_aggregate' => true,
                    'ratings_count' => $ratingsCount
                ])
            ];
        }

        // Add product metadata to each review
        foreach ($reviews as $key => $review) {
            $reviews[$key]['book_metadata'] = $productDetails;
        }

        return $reviews;
    }

    /**
     * Find the Amazon product URL by ISBN
     *
     * @param string $isbn The ISBN to search for
     * @return string|null The product URL or null if not found
     */
    private function findProductUrl(string $isbn): ?string {
        // Note: Direct Amazon scraping is challenging due to anti-scraping measures
        // For a real implementation, consider using a third-party API or service

        // For now, we'll use a direct URL format that sometimes works
        $asin = $isbn;
        if (strlen($isbn) == 13 && substr($isbn, 0, 3) == '978') {
            // Convert ISBN-13 to ASIN (which is essentially ISBN-10)
            $asin = substr($isbn, 3, 9);

            // Calculate the check digit
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int)$asin[$i] * (10 - $i);
            }
            $checkDigit = 11 - ($sum % 11);
            if ($checkDigit == 10) {
                $checkDigit = 'X';
            } elseif ($checkDigit == 11) {
                $checkDigit = '0';
            }
            $asin .= $checkDigit;
        }

        // Return the direct product URL
        return "https://www.amazon.com/dp/{$asin}";
    }

    /**
     * Get product details from Amazon
     *
     * @param string $productUrl The product URL
     * @return array|null The product details or null if not found
     */
    private function getProductDetails(string $productUrl): ?array {
        // Note: In a real implementation, this would make an HTTP request and parse the response
        // However, Amazon has strong anti-scraping measures

        // Extract ASIN from URL
        $asin = '';
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $productUrl, $matches)) {
            $asin = $matches[1];
        }

        if (empty($asin)) {
            return null;
        }

        // Make a request to the product page
        $response = $this->makeRequest($productUrl);

        if ($response === false) {
            return null;
        }

        $details = [];

        // Extract book title
        if (preg_match('/<span id="productTitle"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        } else {
            $details['title'] = "Book with ASIN {$asin}";
        }

        // Extract book author
        if (preg_match('/<a class="a-link-normal contributorNameID"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['author'] = trim($matches[1]);
        } else {
            $details['author'] = "Unknown Author";
        }

        // Extract average rating
        if (preg_match('/class="a-icon-alt">([0-9.]+) out of 5 stars<\/span>/i', $response, $matches)) {
            $details['average_rating'] = (float)$matches[1];
        } else {
            $details['average_rating'] = null;
        }

        // Extract ratings count
        if (preg_match('/class="a-size-base"[^>]*>([0-9,]+) ratings<\/span>/i', $response, $matches)) {
            $details['ratings_count'] = (int)str_replace(',', '', $matches[1]);
        } else {
            $details['ratings_count'] = 0;
        }

        // Extract publisher and publication date
        if (preg_match('/Publisher\s*:\s*([^;]+);\s*([^(]+)\s*\(([^)]+)\)/i', $response, $matches)) {
            $details['publisher'] = trim($matches[1]);
            $details['publication_date'] = trim($matches[3]);
        }

        $details['asin'] = $asin;
        $details['url'] = $productUrl;

        return $details;
    }

    /**
     * Get the reviews URL for a product
     *
     * @param string $productUrl The product URL
     * @return string The reviews URL
     */
    private function getReviewsUrl(string $productUrl): string {
        // Extract ASIN from URL
        $asin = '';
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $productUrl, $matches)) {
            $asin = $matches[1];
        }

        return "https://www.amazon.com/product-reviews/{$asin}";
    }

    /**
     * Scrape reviews from Amazon
     *
     * @param string $reviewsUrl The reviews URL
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function scrapeReviews(string $reviewsUrl, int $limit): array {
        // Make a request to the reviews page
        $response = $this->makeRequest($reviewsUrl);

        if ($response === false) {
            return [];
        }

        $reviews = [];

        // Extract review blocks
        if (preg_match_all('/<div data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is', $response, $reviewBlocks, PREG_SET_ORDER)) {
            foreach ($reviewBlocks as $index => $block) {
                if ($index >= $limit) {
                    break;
                }

                $reviewHtml = $block[0];

                // Extract reviewer name
                $reviewerName = 'Amazon Customer';
                if (preg_match('/<span class="a-profile-name">([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                    $reviewerName = trim($matches[1]);
                }

                // Extract rating
                $rating = 0;
                if (preg_match('/data-hook="review-star-rating"[^>]*>([0-9.]+) out of 5 stars<\/span>/i', $reviewHtml, $matches)) {
                    $rating = (float)$matches[1];
                }

                // Extract review title
                $reviewTitle = '';
                if (preg_match('/data-hook="review-title"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                    $reviewTitle = trim($matches[1]);
                }

                // Extract review text
                $reviewText = '';
                if (preg_match('/data-hook="review-body"[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                    $reviewText = trim(strip_tags($matches[1]));
                }

                // Extract review date
                $reviewDate = null;
                if (preg_match('/data-hook="review-date"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                    $dateStr = trim($matches[1]);
                    if (preg_match('/on\s+([A-Za-z]+\s+\d+,\s+\d{4})/i', $dateStr, $dateMatches)) {
                        $timestamp = strtotime($dateMatches[1]);
                        if ($timestamp) {
                            $reviewDate = date('Y-m-d', $timestamp);
                        }
                    }
                }

                // Skip reviews without text or rating
                if (empty($reviewText) || $rating == 0) {
                    continue;
                }

                // Combine title and text if both exist
                if (!empty($reviewTitle)) {
                    $reviewText = $reviewTitle . ": " . $reviewText;
                }

                $reviews[] = [
                    'source_id' => $this->sourceId,
                    'reviewer_name' => $reviewerName,
                    'reviewer_age' => null,
                    'review_date' => $reviewDate ?: date('Y-m-d'),
                    'original_rating' => "{$rating}/5",
                    'rating_value' => $rating,
                    'rating_scale' => 5,
                    'rating_normalised' => $this->normalizeRating($rating, 5),
                    'review_text' => $this->cleanText($reviewText),
                    'metadata' => json_encode([
                        'review_url' => $reviewsUrl,
                        'is_synthetic' => false
                    ])
                ];
            }
        }

        return $reviews;
    }


}
