<?php
/**
 * Amazon Review Fetcher
 *
 * This class fetches reviews from Amazon by scraping the website.
 * Note: Amazon doesn't provide a public API for reviews, so we need to scrape the website.
 */

namespace Services\ReviewFetcher;

use PDO;

// Set up error logging
ini_set('error_log', __DIR__ . '/debug/amazon-debug.log');
ini_set('log_errors', 'On');

class AmazonReviewFetcher extends AbstractReviewFetcher {
    /**
     * @var string Amazon Associate Tag for affiliate links
     */
    private $affiliateTag;

    /**
     * @var string Amazon domain to use (default: amazon.co.uk)
     */
    private $domain = 'amazon.co.uk';

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Amazon');

        // Get affiliate tag from environment or settings
        $this->affiliateTag = getenv('AMAZON_ASSOCIATE_TAG') ?: 'storiesfro0f0-20';

        // Try to get domain from settings
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = 'amazon_domain'");
        if ($stmt && $stmt->execute() && ($domain = $stmt->fetchColumn())) {
            $this->domain = $domain;
        }
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
     * Find the Amazon ASIN by ISBN
     *
     * @param string $isbn The ISBN to search for
     * @return string|null The ASIN or null if not found
     */
    private function findAsinByISBN(string $isbn): ?string {
        // First, try direct conversion from ISBN to ASIN
        $asin = $this->convertISBNtoASIN($isbn);

        // If we have an ASIN, verify it exists on Amazon
        if (!empty($asin)) {
            $productUrl = "https://{$this->domain}/dp/{$asin}";
            $response = $this->makeRequest($productUrl);

            // If we get a valid response and it's not a "product not found" page
            if ($response !== false &&
                strpos($response, 'Page Not Found') === false &&
                strpos($response, 'We couldn\'t find that page') === false) {
                return $asin;
            }
        }

        // If direct conversion failed, try searching Amazon
        $searchUrl = "https://{$this->domain}/s?k={$isbn}&i=stripbooks";
        $response = $this->makeRequest($searchUrl);

        if ($response === false) {
            return null;
        }

        // Save the search results for debugging
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        file_put_contents("{$debugDir}/amazon-search-{$isbn}.html", $response);

        // Try to extract ASIN from search results
        if (preg_match('/\/dp\/([A-Z0-9]{10})(?:[\/\?]|$)/i', $response, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Convert ISBN to ASIN
     *
     * @param string $isbn The ISBN to convert
     * @return string|null The ASIN or null if conversion failed
     */
    private function convertISBNtoASIN(string $isbn): ?string {
        // Remove hyphens and spaces
        $isbn = preg_replace('/[^0-9X]/i', '', $isbn);

        // If it's already a 10-digit ISBN, it's likely the ASIN
        if (strlen($isbn) == 10) {
            return $isbn;
        }

        // If it's a 13-digit ISBN starting with 978, convert to ISBN-10/ASIN
        if (strlen($isbn) == 13 && substr($isbn, 0, 3) == '978') {
            // Extract the middle 9 digits
            $digits = substr($isbn, 3, 9);

            // Calculate the check digit
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int)$digits[$i] * (10 - $i);
            }
            $checkDigit = 11 - ($sum % 11);

            if ($checkDigit == 10) {
                $checkDigit = 'X';
            } elseif ($checkDigit == 11) {
                $checkDigit = '0';
            }

            return $digits . $checkDigit;
        }

        return null;
    }

    /**
     * Find the Amazon product URL by ISBN
     *
     * @param string $isbn The ISBN to search for
     * @return string|null The product URL or null if not found
     */
    private function findProductUrl(string $isbn): ?string {
        $asin = $this->findAsinByISBN($isbn);

        if (empty($asin)) {
            return null;
        }

        return "https://{$this->domain}/dp/{$asin}";
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

        return "https://{$this->domain}/product-reviews/{$asin}";
    }

    /**
     * Parse reviews from HTML
     *
     * @param string $html The HTML content
     * @param string $asin The Amazon ASIN
     * @return array Array of review data
     */
    private function parseReviewsFromHTML(string $html, string $asin): array {
        $reviews = [];

        // Extract review blocks with more flexible regex
        if (preg_match_all('/<div[^>]+data-hook="review"[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/i', $html, $reviewBlocks, PREG_SET_ORDER)) {
            foreach ($reviewBlocks as $block) {
                $reviewHtml = $block[0];

                // Extract reviewer name
                $reviewerName = 'Amazon Customer';
                if (preg_match('/<span[^>]*class="a-profile-name"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                    $reviewerName = trim($matches[1]);
                }

                // Extract rating with multiple patterns
                $rating = 0;
                // Pattern 1: data-hook="review-star-rating"
                if (preg_match('/data-hook="review-star-rating"[^>]*>([0-9.]+) out of 5 stars<\/span>/i', $reviewHtml, $matches)) {
                    $rating = (float)$matches[1];
                }
                // Pattern 2: a-icon-star with span
                else if (preg_match('/<i[^>]*class="[^"]*a-icon-star[^"]*"[^>]*><span[^>]*>([0-9.]+) out of 5 stars<\/span><\/i>/i', $reviewHtml, $matches)) {
                    $rating = (float)$matches[1];
                }
                // Pattern 3: a-icon-alt
                else if (preg_match('/class="a-icon-alt">([0-9.]+) out of 5 stars<\/span>/i', $reviewHtml, $matches)) {
                    $rating = (float)$matches[1];
                }
                // Pattern 4: data-hook="cmps-review-star-rating"
                else if (preg_match('/data-hook="cmps-review-star-rating"[^>]*>([0-9.]+) out of 5 stars<\/span>/i', $reviewHtml, $matches)) {
                    $rating = (float)$matches[1];
                }

                // Extract review title
                $reviewTitle = '';
                // Pattern 1: data-hook="review-title" in a tag
                if (preg_match('/<a[^>]*data-hook="review-title"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                    $reviewTitle = trim($matches[1]);
                }
                // Pattern 2: data-hook="review-title" in span tag
                else if (preg_match('/<span[^>]*data-hook="review-title"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                    $reviewTitle = trim($matches[1]);
                }
                // Pattern 3: a-size-base review-title
                else if (preg_match('/<span[^>]*class="a-size-base review-title"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                    $reviewTitle = trim($matches[1]);
                }

                // Extract review text
                $reviewText = '';
                // Pattern 1: data-hook="review-body" in span
                if (preg_match('/<span[^>]*data-hook="review-body"[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                    $reviewText = trim(strip_tags($matches[1]));
                }
                // Pattern 2: data-hook="review-body" in div
                else if (preg_match('/<div[^>]*data-hook="review-body"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                    $reviewText = trim(strip_tags($matches[1]));
                }
                // Pattern 3: review-data in div
                else if (preg_match('/<div[^>]*class="[^"]*review-data[^"]*"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                    $reviewText = trim(strip_tags($matches[1]));
                }

                // Extract review date
                $reviewDate = null;
                // Pattern 1: data-hook="review-date"
                if (preg_match('/<span[^>]*data-hook="review-date"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
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

                // Create the affiliate URL
                $affiliateUrl = "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}";

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
                        'asin' => $asin,
                        'review_url' => "https://{$this->domain}/product-reviews/{$asin}",
                        'affiliate_url' => $affiliateUrl,
                        'is_synthetic' => false
                    ])
                ];
            }
        }

        // If no reviews found but we can see we're on a CAPTCHA page, set a specific error
        if (empty($reviews) && (strpos($html, 'captcha') !== false || strpos($html, 'robot check') !== false)) {
            $this->lastError = "Amazon is showing a CAPTCHA or robot check page. Try again later.";
        }

        return $reviews;
    }

    /**
     * Get aggregate rating for a product
     *
     * @param string $asin The Amazon ASIN
     * @return array|null The aggregate review data or null if not found
     */
    private function getAggregateRating(string $asin): ?array {
        $productUrl = "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}";

        // Make the request
        $response = $this->makeRequest($productUrl);

        if ($response === false) {
            return null;
        }

        // Save the HTML for debugging
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        file_put_contents("{$debugDir}/amazon-product-{$asin}.html", $response);

        // Extract product title
        $title = "Book with ASIN {$asin}";
        if (preg_match('/<span id="productTitle"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $title = trim($matches[1]);
        }

        // Extract author
        $author = "Unknown Author";
        if (preg_match('/<a[^>]*id="bylineInfo"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $author = trim($matches[1]);
        } else if (preg_match('/<span[^>]*class="author[^"]*"[^>]*>.*?<a[^>]*>([^<]+)<\/a>/is', $response, $matches)) {
            $author = trim($matches[1]);
        }

        // Extract average rating
        $averageRating = 0;
        if (preg_match('/class="a-icon-alt">([0-9.]+) out of 5 stars<\/span>/i', $response, $matches)) {
            $averageRating = (float)$matches[1];
        }

        // Extract ratings count
        $ratingsCount = 0;
        if (preg_match('/class="a-size-base"[^>]*>([0-9,]+) ratings<\/span>/i', $response, $matches)) {
            $ratingsCount = (int)str_replace(',', '', $matches[1]);
        }

        // If we don't have a rating, return null
        if ($averageRating == 0) {
            return null;
        }

        // Create the review text
        $reviewText = "{$title} by {$author} has an average rating of {$averageRating}/5 based on {$ratingsCount} ratings on Amazon.";

        // Add publisher info if available
        if (preg_match('/Publisher\s*:\s*([^;]+);\s*([^(]+)\s*\(([^)]+)\)/i', $response, $matches)) {
            $publisher = trim($matches[1]);
            $publicationDate = trim($matches[3]);
            $reviewText .= "\n\nPublisher: {$publisher} ({$publicationDate})";
        }

        return [
            'source_id' => $this->sourceId,
            'reviewer_name' => "Amazon Aggregate",
            'reviewer_age' => null,
            'review_date' => date('Y-m-d'),
            'original_rating' => "{$averageRating}/5",
            'rating_value' => $averageRating,
            'rating_scale' => 5,
            'rating_normalised' => $this->normalizeRating($averageRating, 5),
            'review_text' => $reviewText,
            'metadata' => json_encode([
                'asin' => $asin,
                'product_url' => $productUrl,
                'affiliate_url' => $productUrl,
                'is_synthetic' => false,
                'is_aggregate' => true,
                'ratings_count' => $ratingsCount
            ])
        ];
    }

    /**
     * Scrape reviews from Amazon
     *
     * @param string $reviewsUrl The reviews URL
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function scrapeReviews(string $reviewsUrl, int $limit): array {
        error_log("▶️ ENTER scrapeReviews(reviewsUrl={$reviewsUrl}, limit={$limit})");

        // Extract ASIN from URL
        $asin = '';
        if (preg_match('/\/product-reviews\/([A-Z0-9]{10})/', $reviewsUrl, $matches)) {
            $asin = $matches[1];
        }

        if (empty($asin)) {
            $this->lastError = "Invalid reviews URL: {$reviewsUrl}";
            error_log("❌ ERROR: {$this->lastError}");
            return [];
        }

        error_log("Using ASIN: {$asin} for reviews");

        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        // Fetch reviews page by page
        $reviews = [];
        $page = 1;
        $maxPages = 3; // Limit to 3 pages to avoid being blocked

        while ($page <= $maxPages && count($reviews) < $limit) {
            $pageUrl = $reviewsUrl . "?pageNumber={$page}";

            // Log the request
            error_log("🔍 REQ AMZ [{$asin}][p{$page}]: {$pageUrl}");

            // Add a random delay to avoid being blocked
            $delay = rand(1000000, 3000000); // 1-3 seconds
            $delaySeconds = $delay / 1000000;
            error_log("Waiting {$delaySeconds} seconds before request");
            usleep($delay);

            // Make the request with specific headers for Amazon
            $options = [
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Referer: https://' . $this->domain,
                    'DNT: 1',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1'
                ]
            ];

            $response = $this->makeRequest($pageUrl, $options);

            if ($response === false) {
                error_log("❌ Failed to fetch page {$page}: {$this->lastError}");
                // If we already have some reviews, return them
                if (!empty($reviews)) {
                    error_log("Returning " . count($reviews) . " reviews collected so far");
                    break;
                }

                $this->lastError = "Failed to fetch reviews from Amazon (page {$page})";
                error_log("❌ ERROR: {$this->lastError}");
                return [];
            }

            // Save the HTML for debugging
            $htmlFile = "{$debugDir}/amazon-{$asin}-page{$page}.html";
            file_put_contents($htmlFile, $response);
            error_log("📄 Saved HTML to {$htmlFile} (size: " . strlen($response) . " bytes)");

            // Check for CAPTCHA or robot check
            if (strpos($response, 'captcha') !== false || strpos($response, 'robot check') !== false) {
                error_log("⚠️ CAPTCHA or robot check detected on page {$page}");
                file_put_contents("{$debugDir}/amazon-CAPTCHA-{$asin}-page{$page}.html", $response);
            }

            // Parse the reviews from this page
            error_log("▶️ ENTER parseReviewsFromHTML, HTML length=" . strlen($response));
            $pageReviews = $this->parseReviewsFromHTML($response, $asin);
            error_log("FOUND " . count($pageReviews) . " reviews for {$asin} on page {$page}");

            // Log snippets of the first few reviews
            foreach (array_slice($pageReviews, 0, 3) as $i => $review) {
                $snippet = mb_substr($review['review_text'], 0, 80);
                error_log("SNIPPET {$i}: {$snippet}…");
            }

            if (empty($pageReviews)) {
                error_log("No reviews found on page {$page}, stopping pagination");
                // No more reviews on this page
                break;
            }

            // Add the reviews from this page
            $reviews = array_merge($reviews, $pageReviews);
            error_log("Total reviews collected so far: " . count($reviews));

            // Move to the next page
            $page++;
        }

        // Limit the number of reviews
        if (count($reviews) > $limit) {
            error_log("Limiting reviews from " . count($reviews) . " to {$limit}");
            $reviews = array_slice($reviews, 0, $limit);
        }

        error_log("Returning " . count($reviews) . " reviews total");
        return $reviews;
    }


}
