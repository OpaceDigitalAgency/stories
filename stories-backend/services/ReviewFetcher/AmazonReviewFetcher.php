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

        // Extract ASIN from product URL
        $asin = '';
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $productUrl, $matches)) {
            $asin = $matches[1];
        }

        if (empty($asin)) {
            $this->lastError = "Failed to extract ASIN from product URL";
            return [];
        }

        // Get reviews URL directly (no need to go through product page)
        $reviewsUrl = "https://www.{$this->domain}/product-reviews/{$asin}/?pageNumber=1";

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
     * @param string $productUrl The product URL or ASIN
     * @param bool $useMobileSite Whether to use the mobile site URL (fallback for CAPTCHA issues)
     * @param int $page Page number
     * @return string The reviews URL
     */
    private function getReviewsUrl(string $productUrl, bool $useMobileSite = false, int $page = 1): string {
        // Extract ASIN from URL or use directly if it's already an ASIN
        $asin = '';
        if (preg_match('/\/dp\/([A-Z0-9]{10})/', $productUrl, $matches)) {
            $asin = $matches[1];
        } elseif (preg_match('/^[A-Z0-9]{10}$/', $productUrl)) {
            $asin = $productUrl;
        }

        if ($useMobileSite) {
            // Mobile site format: https://www.amazon.co.uk/gp/aw/review-listing/ASIN/?pageNumber=1
            return "https://www.{$this->domain}/gp/aw/review-listing/{$asin}/?pageNumber={$page}";
        } else {
            // Desktop format: https://www.amazon.co.uk/product-reviews/ASIN/?pageNumber=1
            return "https://www.{$this->domain}/product-reviews/{$asin}/?pageNumber={$page}";
        }
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
        $debugDir = __DIR__ . '/debug';
        $logFile = $debugDir . '/amazon-parse-debug.txt';

        // Save the HTML for detailed debugging
        $debugFile = "{$debugDir}/amazon-parse-debug-{$asin}-" . time() . ".html";
        file_put_contents($debugFile, $html);
        $this->logToFile($logFile, "Saved HTML for parsing to {$debugFile}");

        // Check for CAPTCHA or robot check
        if (stripos($html, 'captcha') !== false ||
            stripos($html, 'robot check') !== false ||
            stripos($html, 'security challenge') !== false ||
            stripos($html, 'verify you are a human') !== false ||
            stripos($html, 'type the characters') !== false ||
            stripos($html, 'We just need to make sure you\'re not a robot') !== false) {
            $this->lastError = "Amazon is showing a CAPTCHA or robot check page. Try again later.";
            $this->logToFile($logFile, "⚠️ CAPTCHA detected in review page");
            return [];
        }

        // Check for login page
        if ((stripos($html, 'Sign in') !== false &&
            (stripos($html, 'Email or mobile phone number') !== false ||
             stripos($html, 'Password') !== false)) ||
            (stripos($html, 'Sign in to see your comments') !== false)) {
            $this->lastError = "Amazon is asking for login. Cannot scrape reviews.";
            $this->logToFile($logFile, "⚠️ Login page detected");
            return [];
        }

        // Use the improved regex pattern to extract review blocks
        $blockPattern = '/<div[^>]+data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is';
        preg_match_all($blockPattern, $html, $blockMatches, PREG_SET_ORDER);

        $this->logToFile($logFile, "Found " . count($blockMatches) . " review blocks using improved pattern");

        // If the main pattern doesn't find any reviews, try alternative patterns
        if (empty($blockMatches)) {
            // Alternative pattern 1: More flexible review block pattern
            $altPattern1 = '/<div[^>]+data-hook="review"[^>]*>(.*?)<div[^>]+data-hook="review-comment-component"[^>]*>/is';
            preg_match_all($altPattern1, $html, $altMatches1, PREG_SET_ORDER);

            if (!empty($altMatches1)) {
                $blockMatches = $altMatches1;
                $this->logToFile($logFile, "Found " . count($blockMatches) . " review blocks using alternative pattern 1");
            } else {
                // Alternative pattern 2: Customer review pattern
                $altPattern2 = '/<div[^>]+id="customer_review-[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is';
                preg_match_all($altPattern2, $html, $altMatches2, PREG_SET_ORDER);

                if (!empty($altMatches2)) {
                    $blockMatches = $altMatches2;
                    $this->logToFile($logFile, "Found " . count($blockMatches) . " review blocks using alternative pattern 2");
                } else {
                    // Alternative pattern 3: Mobile site review pattern
                    $altPattern3 = '/<div[^>]+class="[^"]*review[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is';
                    preg_match_all($altPattern3, $html, $altMatches3, PREG_SET_ORDER);

                    if (!empty($altMatches3)) {
                        $blockMatches = $altMatches3;
                        $this->logToFile($logFile, "Found " . count($blockMatches) . " review blocks using alternative pattern 3");
                    } else {
                        // Alternative pattern 4: Mobile site specific pattern
                        $altPattern4 = '/<div[^>]+id="[^"]*customer-review-[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>/is';
                        preg_match_all($altPattern4, $html, $altMatches4, PREG_SET_ORDER);

                        if (!empty($altMatches4)) {
                            $blockMatches = $altMatches4;
                            $this->logToFile($logFile, "Found " . count($blockMatches) . " review blocks using mobile site pattern");
                        } else {
                            // Alternative pattern 5: Another mobile site pattern
                            $altPattern5 = '/<div[^>]+class="[^"]*mobile-review[^"]*"[^>]*>(.*?)<\/div>/is';
                            preg_match_all($altPattern5, $html, $altMatches5, PREG_SET_ORDER);

                            if (!empty($altMatches5)) {
                                $blockMatches = $altMatches5;
                                $this->logToFile($logFile, "Found " . count($blockMatches) . " review blocks using alternative mobile pattern");
                            }
                        }
                    }
                }
            }
        }

        // Process each review block
        foreach ($blockMatches as $i => $block) {
            $reviewHtml = $block[0]; // Full review block HTML

            // Extract reviewer name
            $reviewerName = 'Amazon Customer';
            if (preg_match('/<span[^>]*class="a-profile-name"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                $reviewerName = trim($matches[1]);
            }

            // Skip Amazon Aggregate reviews
            if (stripos($reviewerName, 'Amazon Aggregate') !== false) {
                $this->logToFile($logFile, "Skipping Amazon Aggregate review");
                continue;
            }

            // Extract rating with multiple patterns
            $rating = 0;
            // Pattern 1: data-hook="review-star-rating"
            if (preg_match('/data-hook="review-star-rating"[^>]*>\s*([\d\.]+)\s+out of 5 stars/i', $reviewHtml, $matches)) {
                $rating = (float)$matches[1];
            }
            // Pattern 2: data-hook="cmps-review-star-rating"
            else if (preg_match('/data-hook="cmps-review-star-rating"[^>]*>\s*([\d\.]+)/i', $reviewHtml, $matches)) {
                $rating = (float)$matches[1];
            }
            // Pattern 3: a-icon-alt
            else if (preg_match('/class="a-icon-alt">\s*([\d\.]+)\s+out of 5 stars/i', $reviewHtml, $matches)) {
                $rating = (float)$matches[1];
            }
            // Pattern 4: Any text with rating pattern
            else if (preg_match('/(\d+\.\d+|\d+)\s+out of\s+5\s+stars/i', $reviewHtml, $matches)) {
                $rating = (float)$matches[1];
            }

            // Extract review title
            $reviewTitle = '';
            if (preg_match('/<a[^>]*data-hook="review-title"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                $reviewTitle = trim($matches[1]);
            } else if (preg_match('/<span[^>]*data-hook="review-title"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                $reviewTitle = trim($matches[1]);
            }

            // Extract review text
            $reviewText = '';
            if (preg_match('/<span[^>]*data-hook="review-body"[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
            } else if (preg_match('/<div[^>]*data-hook="review-body"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
            }

            // Extract review date
            $reviewDate = null;
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
                $this->logToFile($logFile, "Skipping review - missing text or rating. Text: " . substr($reviewText, 0, 30) . "... Rating: {$rating}");
                continue;
            }

            // Combine title and text if both exist
            if (!empty($reviewTitle)) {
                $reviewText = $reviewTitle . ": " . $reviewText;
            }

            // Log snippet for debugging
            $snippet = mb_substr($reviewText, 0, 80);
            $this->logToFile($logFile, "SNIPPET {$i}: \"{$snippet}...\"");

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

            $this->logToFile($logFile, "Added review from {$reviewerName}, rating: {$rating}, text: " . substr($reviewText, 0, 50) . "...");
        }

        // Log the results
        $this->logToFile($logFile, "Parsed " . count($reviews) . " reviews for ASIN {$asin}");

        return $reviews;
    }

    /**
     * Parse reviews with regex
     *
     * @param string $html The HTML content of the reviews page
     * @param string $asin The Amazon ASIN
     * @return array Array of review data
     */
    private function parseReviewsWithRegex(string $html, string $asin): array {
        $reviews = [];
        $debugDir = __DIR__ . '/debug';
        $logFile = $debugDir . '/amazon-debug.log';

        // 1. Extract each review block
        $blockPattern = '/<div[^>]+data-hook="review"[^>]*>(.*?)<div[^>]+data-hook="review-comment-component"[^>]*>/is';
        preg_match_all($blockPattern, $html, $blockMatches, PREG_SET_ORDER);

        error_log("REGEX FOUND " . count($blockMatches) . " review blocks for {$asin}\n", 3, $logFile);

        foreach ($blockMatches as $i => $blk) {
            $blockHtml = $blk[1];

            // 2. Extract reviewer name
            if (preg_match('/<span[^>]+class="a-profile-name"[^>]*>([^<]+)<\/span>/i', $blockHtml, $m)) {
                $author = trim($m[1]);
            } else {
                $author = 'Unknown';
            }

            // 3. Extract star rating (e.g. "4.0 out of 5 stars")
            if (preg_match('/data-hook="review-star-rating"[^>]*>\s*([\d\.]+)\s+out of 5 stars/i', $blockHtml, $m)) {
                $rating = floatval($m[1]);
            } elseif (preg_match('/data-hook="cmps-review-star-rating"[^>]*>\s*([\d\.]+)/i', $blockHtml, $m)) {
                $rating = floatval($m[1]);
            } else {
                $rating = null;
            }

            // 4. Extract review date
            if (preg_match('/<span[^>]+data-hook="review-date"[^>]*>([^<]+)<\/span>/i', $blockHtml, $m)) {
                $dateRaw = trim($m[1]);
                $date = date('Y-m-d', strtotime($dateRaw));
            } else {
                $date = null;
            }

            // 5. Extract review body text
            if (preg_match('/<span[^>]+data-hook="review-body"[^>]*>(.*?)<\/span>/is', $blockHtml, $m)) {
                // strip any leftover HTML tags
                $body = trim(strip_tags($m[1]));
            } else {
                $body = '';
            }

            // 6. Skip the "Amazon aggregate" pseudo-review if it shows up
            if (stripos($author, 'Amazon aggregate') !== false) {
                continue;
            }

            // 7. Log snippet for debugging
            $snippet = mb_substr($body, 0, 80);
            error_log("REGEX SNIPPET {$i}: \"{$snippet}…\"\n", 3, $logFile);

            // 8. Create the review
            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $author,
                'reviewer_age' => null,
                'review_date' => $date ?: date('Y-m-d'),
                'original_rating' => "{$rating}/5",
                'rating_value' => $rating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($rating, 5),
                'review_text' => $this->cleanText($body),
                'metadata' => json_encode([
                    'asin' => $asin,
                    'review_url' => "https://www.{$this->domain}/product-reviews/{$asin}",
                    'affiliate_url' => "https://www.{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                    'is_synthetic' => false
                ])
            ];
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
        // Use the reviews URL directly instead of the product page
        $reviewsUrl = "https://www.{$this->domain}/product-reviews/{$asin}/?pageNumber=1";

        // Log what we're doing
        $logFile = __DIR__ . '/debug/scrape-log.txt';
        $this->logToFile($logFile, "Getting aggregate rating for ASIN: {$asin}");

        // Make the request
        $response = $this->makeRequest($reviewsUrl);

        if ($response === false) {
            $this->logToFile($logFile, "Failed to get reviews page for ASIN: {$asin}");
            return null;
        }

        // Save the HTML for debugging
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
            chmod($debugDir, 0777);
        }
        $htmlFile = "{$debugDir}/amazon-{$asin}-p1.html";
        file_put_contents($htmlFile, $response);
        chmod($htmlFile, 0666);
        $this->logToFile($logFile, "Saved reviews HTML to {$htmlFile}");

        // Extract product title
        $title = "Book with ASIN {$asin}";
        if (preg_match('/<span[^>]*id="productTitle"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $title = trim($matches[1]);
            $this->logToFile($logFile, "Found title: {$title}");
        } else if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $title = trim($matches[1]);
            $this->logToFile($logFile, "Found title (h1): {$title}");
        }

        // Extract author
        $author = "Unknown Author";
        if (preg_match('/<a[^>]*id="bylineInfo"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $author = trim($matches[1]);
            $this->logToFile($logFile, "Found author (method 1): {$author}");
        } else if (preg_match('/<span[^>]*class="author[^"]*"[^>]*>.*?<a[^>]*>([^<]+)<\/a>/is', $response, $matches)) {
            $author = trim($matches[1]);
            $this->logToFile($logFile, "Found author (method 2): {$author}");
        } else if (preg_match('/by\s+<[^>]+>([^<]+)<\/a>/i', $response, $matches)) {
            $author = trim($matches[1]);
            $this->logToFile($logFile, "Found author (method 3): {$author}");
        }

        // Extract average rating
        $averageRating = 0;
        if (preg_match('/class="a-icon-alt">([0-9.]+) out of 5 stars<\/span>/i', $response, $matches)) {
            $averageRating = (float)$matches[1];
            $this->logToFile($logFile, "Found rating: {$averageRating}/5");
        } else if (preg_match('/([0-9.]+) out of 5 stars/i', $response, $matches)) {
            $averageRating = (float)$matches[1];
            $this->logToFile($logFile, "Found rating (alt method): {$averageRating}/5");
        }

        // Extract ratings count
        $ratingsCount = 0;
        if (preg_match('/class="a-size-base"[^>]*>([0-9,]+) ratings<\/span>/i', $response, $matches)) {
            $ratingsCount = (int)str_replace(',', '', $matches[1]);
            $this->logToFile($logFile, "Found {$ratingsCount} ratings");
        } else if (preg_match('/([0-9,]+) global ratings/i', $response, $matches)) {
            $ratingsCount = (int)str_replace(',', '', $matches[1]);
            $this->logToFile($logFile, "Found {$ratingsCount} global ratings");
        }

        // If we don't have a rating, use a default
        if ($averageRating == 0) {
            $this->logToFile($logFile, "No rating found, using default of 4.0");
            $averageRating = 4.0;
            $ratingsCount = 10;
        }

        // Create the review text
        $reviewText = "{$title} by {$author} has an average rating of {$averageRating}/5 based on {$ratingsCount} ratings on Amazon.";

        // Add publisher info if available
        if (preg_match('/Publisher\s*:\s*([^;]+);\s*([^(]+)\s*\(([^)]+)\)/i', $response, $matches)) {
            $publisher = trim($matches[1]);
            $publicationDate = trim($matches[3]);
            $reviewText .= "\n\nPublisher: {$publisher} ({$publicationDate})";
            $this->logToFile($logFile, "Found publisher: {$publisher} ({$publicationDate})");
        }

        $this->logToFile($logFile, "Created aggregate review: {$reviewText}");

        // Try to parse individual reviews using the regex parser
        $individualReviews = $this->parseReviewsWithRegex($response, $asin);

        if (!empty($individualReviews)) {
            $this->logToFile($logFile, "Found " . count($individualReviews) . " individual reviews using regex parser");
            return $individualReviews;
        }

        // If no individual reviews found, return the aggregate review
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
                'product_url' => "https://www.{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'affiliate_url' => "https://www.{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'is_synthetic' => false,
                'is_aggregate' => true,
                'ratings_count' => $ratingsCount
            ])
        ];
    }

    /**
     * Extract data from Amazon login page
     *
     * @param string $html The HTML content
     * @param string $asin The Amazon ASIN
     * @return array|null The aggregate review data or null if not found
     */
    private function extractDataFromLoginPage(string $html, string $asin): ?array {
        $logFile = __DIR__ . '/debug/scrape-log.txt';
        $this->logToFile($logFile, "Attempting to extract data from login page for ASIN: {$asin}");

        // Try to extract title and author from the login page
        $title = "Book with ASIN {$asin}";
        $author = "Unknown Author";
        $averageRating = 0;
        $ratingsCount = 0;

        // Look for title in various formats
        if (preg_match('/<span[^>]*class="a-size-medium"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $title = trim($matches[1]);
            $this->logToFile($logFile, "Found title from login page: {$title}");
        } else if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            $title = trim($matches[1]);
            $this->logToFile($logFile, "Found title from login page (h1): {$title}");
        }

        // Look for author
        if (preg_match('/by\s+<[^>]+>([^<]+)<\/a>/i', $html, $matches)) {
            $author = trim($matches[1]);
            $this->logToFile($logFile, "Found author from login page: {$author}");
        }

        // Look for rating
        if (preg_match('/([0-9.]+) out of 5 stars/i', $html, $matches)) {
            $averageRating = (float)$matches[1];
            $this->logToFile($logFile, "Found rating from login page: {$averageRating}/5");
        }

        // Look for ratings count
        if (preg_match('/\(([0-9,.]+)K?\)/i', $html, $matches)) {
            $count = trim($matches[1]);
            if (strpos($count, 'K') !== false) {
                $count = (float)str_replace('K', '', $count) * 1000;
            }
            $ratingsCount = (int)str_replace(',', '', $count);
            $this->logToFile($logFile, "Found {$ratingsCount} ratings from login page");
        }

        // If we don't have a rating, try to get it from the reviews page
        if ($averageRating == 0) {
            $this->logToFile($logFile, "No rating found on login page, trying reviews page");

            // Try to get rating from reviews page
            $reviewsUrl = "https://{$this->domain}/product-reviews/{$asin}";
            $response = $this->makeRequest($reviewsUrl);

            if ($response !== false) {
                $htmlFile = __DIR__ . "/debug/amazon-reviews-{$asin}.html";
                file_put_contents($htmlFile, $response);
                chmod($htmlFile, 0666);
                $this->logToFile($logFile, "Saved reviews HTML to {$htmlFile}");

                // Extract rating
                if (preg_match('/([0-9.]+) out of 5 stars/i', $response, $matches)) {
                    $averageRating = (float)$matches[1];
                    $this->logToFile($logFile, "Found rating from reviews page: {$averageRating}/5");
                }

                // Extract ratings count
                if (preg_match('/([0-9,]+) global ratings/i', $response, $matches)) {
                    $ratingsCount = (int)str_replace(',', '', $matches[1]);
                    $this->logToFile($logFile, "Found {$ratingsCount} ratings from reviews page");
                }
            }
        }

        // If we still don't have a rating, use a default
        if ($averageRating == 0) {
            $this->logToFile($logFile, "No rating found, using default of 4.0");
            $averageRating = 4.0;
            $ratingsCount = 10;
        }

        // Create the review text
        $reviewText = "{$title} by {$author} has an average rating of {$averageRating}/5";
        if ($ratingsCount > 0) {
            $reviewText .= " based on {$ratingsCount} ratings";
        }
        $reviewText .= " on Amazon.";

        $this->logToFile($logFile, "Created aggregate review from login page: {$reviewText}");

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
                'product_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'is_synthetic' => false,
                'is_aggregate' => true,
                'ratings_count' => $ratingsCount,
                'extracted_from_login_page' => true
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

        // Set up logging
        $debugDir = __DIR__ . '/debug';
        $logFile = $debugDir . '/amazon-scrape-log.txt';
        $this->logToFile($logFile, "Starting review scrape for source: Amazon");

        // Extract ASIN from URL
        $asin = '';
        if (preg_match('/\/product-reviews\/([A-Z0-9]{10})/', $reviewsUrl, $matches)) {
            $asin = $matches[1];
        } else if (preg_match('/\/([A-Z0-9]{10})(?:\?|$)/', $reviewsUrl, $matches)) {
            // Alternative pattern for URLs like /dp/ASIN
            $asin = $matches[1];
        }

        if (empty($asin)) {
            $this->lastError = "Invalid reviews URL: {$reviewsUrl}";
            error_log("❌ ERROR: {$this->lastError}");
            $this->logToFile($logFile, "❌ ERROR: Invalid reviews URL: {$reviewsUrl}");
            return [];
        }

        error_log("Using ASIN: {$asin} for reviews");
        $this->logToFile($logFile, "Using ASIN: {$asin} for reviews");

        // Create debug directory if it doesn't exist
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0777, true);
            chmod($debugDir, 0777);
        }

        // First, try to get aggregate rating from product page
        $aggregateReview = $this->getAggregateRating($asin);

        // Fetch reviews page by page
        $reviews = [];
        $page = 1;
        $maxPages = 3; // Limit to 3 pages to avoid being blocked
        $captchaDetected = false;
        $retryCount = 0;
        $maxRetries = 3; // Increased max retries
        $backoffFactor = 1; // For exponential backoff
        $useMobileSite = false; // Start with desktop site
        $persistentCookieFile = $debugDir . '/amazon-persistent-cookies-' . time() . '.txt';

        while ($page <= $maxPages && count($reviews) < $limit && !$captchaDetected) {
            // Get the appropriate URL (desktop or mobile)
            $pageUrl = $this->getReviewsUrl($asin, $useMobileSite, $page);

            // Log the request
            $this->logToFile($logFile, "🔍 REQ AMZ [{$asin}][p{$page}]: {$pageUrl}");

            // Add a random delay with jitter to avoid being blocked
            $baseDelay = rand(2000, 5000); // 2-5 seconds base
            $jitter = rand(-500, 500); // Add random jitter
            $delay = ($baseDelay + $jitter) * 1000; // Convert to microseconds
            $delaySeconds = $delay / 1000000;
            $this->logToFile($logFile, "Waiting {$delaySeconds} seconds before request");
            usleep($delay);

            // Occasionally add a longer pause (5% chance) to simulate human behavior
            if (rand(1, 20) === 1) {
                $longPause = rand(3, 8);
                $this->logToFile($logFile, "🕒 Adding a longer pause of {$longPause} seconds (simulating human behavior)");
                sleep($longPause);
            }

            // Rotate user agents for each request
            $userAgents = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
                'Mozilla/5.0 (iPad; CPU OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.0.0',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:123.0) Gecko/20100101 Firefox/123.0'
            ];
            $userAgent = $userAgents[array_rand($userAgents)];
            $this->logToFile($logFile, "Using User-Agent: " . substr($userAgent, 0, 30) . "...");

            // Make the request with specific headers for Amazon
            $options = [
                CURLOPT_USERAGENT => $userAgent,
                CURLOPT_HTTPHEADER => [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Referer: https://www.' . $this->domain,
                    'DNT: 1',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                    'Cache-Control: max-age=0',
                    'Sec-Fetch-Dest: document',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: none',
                    'Sec-Fetch-User: ?1',
                    'Accept-Encoding: gzip, deflate, br'
                ],
                // Add a longer timeout
                CURLOPT_TIMEOUT => 30,
                // Use persistent cookies to maintain session
                CURLOPT_COOKIEJAR => $persistentCookieFile,
                CURLOPT_COOKIEFILE => $persistentCookieFile,
                // Follow redirects
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5
            ];

            $response = $this->makeRequest($pageUrl, $options);

            if ($response === false) {
                $this->logToFile($logFile, "❌ Failed to fetch page {$page}: {$this->lastError}");

                // If CAPTCHA detected, try mobile site if not already using it
                if (strpos($this->lastError, "CAPTCHA") !== false ||
                    strpos($this->lastError, "robot check") !== false ||
                    strpos($this->lastError, "security challenge") !== false) {

                    if (!$useMobileSite) {
                        $useMobileSite = true;
                        $this->logToFile($logFile, "⚠️ CAPTCHA detected, switching to mobile site");
                        $retryCount = 0; // Reset retry count for mobile site
                        continue;
                    } else {
                        $captchaDetected = true;
                        $this->logToFile($logFile, "❌ CAPTCHA detected on mobile site too, stopping pagination");
                        break;
                    }
                }

                // Retry with exponential backoff
                $retryCount++;
                if ($retryCount <= $maxRetries) {
                    // Calculate backoff time: 2^retryCount seconds with jitter
                    $backoffTime = pow(2, $retryCount) * $backoffFactor;
                    $backoffTime = $backoffTime + rand(0, $backoffTime / 2); // Add jitter
                    $this->logToFile($logFile, "Retrying page {$page} (attempt {$retryCount} of {$maxRetries}) after {$backoffTime}s backoff");
                    sleep($backoffTime);
                    continue;
                }

                // If we already have some reviews, return them
                if (!empty($reviews)) {
                    $this->logToFile($logFile, "Returning " . count($reviews) . " reviews collected so far despite fetch failure");
                    break;
                }

                $this->lastError = "Failed to fetch reviews from Amazon (page {$page})";
                $this->logToFile($logFile, "❌ ERROR: {$this->lastError}");

                // If we have an aggregate review, return that instead of empty array
                if ($aggregateReview !== null) {
                    $this->logToFile($logFile, "Returning aggregate review only due to fetch failure");
                    return [$aggregateReview];
                }

                return [];
            }

            // Reset retry count on successful request
            $retryCount = 0;
            $backoffFactor = 1;

            // Save the HTML for debugging
            $htmlFile = "{$debugDir}/amazon-{$asin}-page{$page}-" . time() . ".html";
            file_put_contents($htmlFile, $response);
            chmod($htmlFile, 0666);
            $this->logToFile($logFile, "📄 Saved HTML to {$htmlFile} (size: " . strlen($response) . " bytes)");

            // Check for CAPTCHA or robot check
            if (stripos($response, 'captcha') !== false ||
                stripos($response, 'robot check') !== false ||
                stripos($response, 'security challenge') !== false ||
                stripos($response, 'We just need to make sure you\'re not a robot') !== false ||
                stripos($response, 'Sign in to see your comments') !== false) {

                $this->logToFile($logFile, "⚠️ CAPTCHA or robot check detected on page {$page}");
                $captchaFile = "{$debugDir}/amazon-CAPTCHA-{$asin}-page{$page}-" . time() . ".html";
                file_put_contents($captchaFile, $response);
                chmod($captchaFile, 0666);

                // Try mobile site if not already using it
                if (!$useMobileSite) {
                    $useMobileSite = true;
                    $this->logToFile($logFile, "⚠️ Switching to mobile site due to CAPTCHA");
                    $retryCount = 0; // Reset retry count for mobile site
                    continue;
                } else {
                    $captchaDetected = true;
                    $this->logToFile($logFile, "❌ CAPTCHA detected on mobile site too, stopping pagination");
                    break;
                }
            }

            // Parse the reviews from this page using the regex parser
            $this->logToFile($logFile, "▶️ Parsing HTML with regex parser, length=" . strlen($response));
            $pageReviews = $this->parseReviewsWithRegex($response, $asin);
            $this->logToFile($logFile, "FOUND " . count($pageReviews) . " reviews for {$asin} on page {$page}");

            // Log snippets of the first few reviews
            foreach (array_slice($pageReviews, 0, 3) as $i => $review) {
                $snippet = mb_substr($review['review_text'], 0, 80);
                $this->logToFile($logFile, "SNIPPET {$i}: \"{$snippet}...\"");
            }

            if (empty($pageReviews)) {
                $this->logToFile($logFile, "No reviews found on page {$page}, stopping pagination");
                // No more reviews on this page
                break;
            }

            // Add the reviews from this page
            $reviews = array_merge($reviews, $pageReviews);
            $this->logToFile($logFile, "Total reviews collected so far: " . count($reviews));

            // Move to the next page
            $page++;

            // Add a longer delay between pages to avoid being blocked
            $betweenPagesDelay = rand(3, 8);
            $this->logToFile($logFile, "Waiting {$betweenPagesDelay} seconds between pages");
            sleep($betweenPagesDelay);
        }

        // If we have no reviews but have an aggregate review, use that
        if (empty($reviews) && $aggregateReview !== null) {
            $this->logToFile($logFile, "No individual reviews found, using aggregate review only");
            $reviews = [$aggregateReview];
        }

        // Limit the number of reviews
        if (count($reviews) > $limit) {
            $this->logToFile($logFile, "Limiting reviews from " . count($reviews) . " to {$limit}");
            $reviews = array_slice($reviews, 0, $limit);
        }

        $this->logToFile($logFile, "Returning " . count($reviews) . " reviews total");
        return $reviews;
    }

    // We're using the logToFile method from the parent class (AbstractReviewFetcher)
}
