<?php
/**
 * Amazon Headless Review Fetcher
 *
 * Fetches real user reviews from Amazon using a headless browser to bypass
 * anti-scraping measures. Falls back to the regular fetcher if needed.
 */

namespace Services\ReviewFetcher;

use PDO;
use Services\HeadlessBrowser\HeadlessBrowserService;

class AmazonHeadlessFetcher extends AmazonReviewFetcher
{
    /** @var HeadlessBrowserService Headless browser service */
    protected HeadlessBrowserService $browser;

    /** @var bool Whether the headless browser is available */
    protected bool $headlessAvailable = false;

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId)
    {
        parent::__construct($db, $sourceId);

        // Create debug directory if it doesn't exist
        if (!is_dir($this->dbgDir)) {
            mkdir($this->dbgDir, 0755, true);
        }

        // Check if required classes exist
        if (!class_exists('Services\\HeadlessBrowser\\HeadlessBrowserService')) {
            $this->logToFile("{$this->dbgDir}/headless-log.txt", "❌ HeadlessBrowserService class not found");
            $this->headlessAvailable = false;
            return;
        }

        // Check if PHP WebDriver is installed
        if (!class_exists('Facebook\\WebDriver\\Chrome\\ChromeOptions')) {
            $this->logToFile("{$this->dbgDir}/headless-log.txt", "❌ PHP WebDriver not installed. Run: composer require php-webdriver/webdriver");
            $this->headlessAvailable = false;
            return;
        }

        // Initialize headless browser service
        try {
            $this->browser = new HeadlessBrowserService(null, $this->dbgDir);
            $this->headlessAvailable = true;
            $this->logToFile("{$this->dbgDir}/headless-log.txt", "✅ Headless browser service initialized");
        } catch (\Exception $e) {
            $this->logToFile("{$this->dbgDir}/headless-log.txt", "❌ Failed to initialize headless browser: {$e->getMessage()}");
            $this->headlessAvailable = false;
        }
    }

    /**
     * Destructor - ensure browser is closed
     */
    public function __destruct()
    {
        if ($this->headlessAvailable) {
            $this->browser->close();
        }
    }

    /**
     * Main entry: fetch up to $limit reviews for $isbn.
     * Overrides parent method to use headless browser first, then fall back.
     *
     * @param string $isbn The ISBN of the book (can be ISBN-10 or ISBN-13)
     * @param int $limit Maximum number of reviews to fetch
     * @param array $options Additional options for the fetcher
     * @return array Array of review data
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10, array $options = []): array
    {
        // 1) Clean ISBN and convert to ASIN (same as parent)
        $clean = preg_replace('/[^0-9X]/i', '', $isbn);
        $asin = $this->convertISBNtoASIN($clean);

        // 2) Fallback to parent helper if needed
        if (!$asin) {
            $data = $this->standardizeISBN($isbn);
            $asin = $data['isbn'] ?: $data['isbn13'] ?? null;
        }

        if (!$asin) {
            $this->lastError = "Could not derive ASIN from ISBN {$isbn}";
            return [];
        }

        $this->logToFile("{$this->dbgDir}/headless-log.txt", "🔍 Fetching reviews for ASIN: {$asin}");

        // 3) Try headless browser approach first
        $reviews = [];
        if ($this->headlessAvailable) {
            $reviews = $this->fetchReviewsWithHeadlessBrowser($asin, $limit);

            if (!empty($reviews)) {
                $this->logToFile("{$this->dbgDir}/headless-log.txt", "✅ Successfully fetched " . count($reviews) . " reviews with headless browser");
                return $reviews;
            }

            $this->logToFile("{$this->dbgDir}/headless-log.txt", "⚠️ Headless browser approach failed, falling back to regular method");
        }

        // 4) Fall back to parent implementation if headless approach failed
        return parent::fetchReviewsByISBN($isbn, $limit);
    }

    /**
     * Fetch reviews using headless browser
     *
     * @param string $asin Amazon ASIN
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of reviews
     */
    protected function fetchReviewsWithHeadlessBrowser(string $asin, int $limit): array
    {
        $reviews = [];
        $logFile = "{$this->dbgDir}/headless-log.txt";

        // Initialize browser if not already done
        if (!$this->browser->initialize()) {
            $this->logToFile($logFile, "❌ Failed to initialize browser");
            return [];
        }

        // 1) First try the product reviews page
        $reviewsUrl = "https://{$this->domain}/product-reviews/{$asin}";
        $this->logToFile($logFile, "🌐 Navigating to reviews page: {$reviewsUrl}");

        if (!$this->browser->navigateTo($reviewsUrl)) {
            $this->logToFile($logFile, "❌ Failed to navigate to reviews page");
            return [];
        }

        // 2) Check for CAPTCHA or login page
        if ($this->browser->hasCaptcha()) {
            $this->logToFile($logFile, "⚠️ CAPTCHA detected on reviews page");
            $this->browser->takeScreenshot("amazon-{$asin}-captcha.png");
            return [];
        }

        if ($this->browser->isLoginPage()) {
            $this->logToFile($logFile, "⚠️ Redirected to login page");
            $this->browser->takeScreenshot("amazon-{$asin}-login.png");
            return [];
        }

        // 3) Get page source and save it
        $html = $this->browser->getPageSource();
        file_put_contents("{$this->dbgDir}/amazon-{$asin}-headless-reviews.html", $html);

        // 4) Parse reviews from the page
        $parsedReviews = $this->parseReviewsWithRegex($html, $asin);
        $this->logToFile($logFile, "✅ Parsed " . count($parsedReviews) . " reviews from first page");
        $reviews = array_merge($reviews, $parsedReviews);

        // 5) If we need more reviews and have pagination, fetch more pages
        $page = 2;
        while (count($reviews) < $limit && count($parsedReviews) > 0 && $page <= 5) {
            $nextPageUrl = "https://{$this->domain}/product-reviews/{$asin}?pageNumber={$page}";
            $this->logToFile($logFile, "🌐 Navigating to page {$page}: {$nextPageUrl}");

            if (!$this->browser->navigateTo($nextPageUrl)) {
                $this->logToFile($logFile, "❌ Failed to navigate to page {$page}");
                break;
            }

            // Check for CAPTCHA or login page
            if ($this->browser->hasCaptcha() || $this->browser->isLoginPage()) {
                $this->logToFile($logFile, "⚠️ CAPTCHA or login page detected on page {$page}");
                break;
            }

            // Get and save page source
            $html = $this->browser->getPageSource();
            file_put_contents("{$this->dbgDir}/amazon-{$asin}-headless-reviews-page{$page}.html", $html);

            // Parse reviews
            $parsedReviews = $this->parseReviewsWithRegex($html, $asin);
            $this->logToFile($logFile, "✅ Parsed " . count($parsedReviews) . " reviews from page {$page}");
            $reviews = array_merge($reviews, $parsedReviews);

            // Increment page counter
            $page++;

            // Add a random delay between requests (2-5 seconds)
            sleep(rand(2, 5));
        }

        // 6) If we couldn't get individual reviews, try to get aggregate rating
        if (empty($reviews)) {
            $this->logToFile($logFile, "⚠️ No individual reviews found, trying to get aggregate rating");
            $aggregate = $this->getAggregateRatingWithHeadlessBrowser($asin);
            if ($aggregate) {
                $reviews = [$aggregate];
            }
        }

        return array_slice($reviews, 0, $limit);
    }

    /**
     * Get aggregate rating using headless browser
     *
     * @param string $asin Amazon ASIN
     * @return array|null Aggregate review data or null if not found
     */
    protected function getAggregateRatingWithHeadlessBrowser(string $asin): ?array
    {
        $logFile = "{$this->dbgDir}/headless-log.txt";

        // Navigate to product page
        $productUrl = "https://{$this->domain}/dp/{$asin}";
        $this->logToFile($logFile, "🌐 Navigating to product page for aggregate rating: {$productUrl}");

        if (!$this->browser->navigateTo($productUrl)) {
            $this->logToFile($logFile, "❌ Failed to navigate to product page");
            return null;
        }

        // Check for CAPTCHA or login page
        if ($this->browser->hasCaptcha() || $this->browser->isLoginPage()) {
            $this->logToFile($logFile, "⚠️ CAPTCHA or login page detected on product page");
            return null;
        }

        // Get page source
        $html = $this->browser->getPageSource();
        file_put_contents("{$this->dbgDir}/amazon-{$asin}-headless-product.html", $html);

        // Extract average rating
        if (!preg_match('/(\d+\.\d+) out of 5 stars/i', $html, $ratingMatch)) {
            $this->logToFile($logFile, "❌ Could not find rating on product page");
            return null;
        }

        $avg = (float)$ratingMatch[1];

        // Extract number of ratings
        if (!preg_match('/(\d+(?:,\d+)*) ratings?/i', $html, $countMatch)) {
            $this->logToFile($logFile, "⚠️ Could not find ratings count, using default");
            $count = 1;
        } else {
            $count = (int)str_replace(',', '', $countMatch[1]);
        }

        $this->logToFile($logFile, "✅ Found aggregate rating: {$avg}/5 from {$count} ratings");

        return [
            'source_id'         => $this->sourceId,
            'reviewer_name'     => 'Amazon Aggregate',
            'review_date'       => date('Y-m-d'),
            'original_rating'   => "{$avg}/5",
            'rating_value'      => $avg,
            'rating_scale'      => 5,
            'rating_normalised' => $this->normalizeRating($avg, 5),
            'review_text'       => "Average rating {$avg}/5 based on {$count} ratings on Amazon.",
            'metadata'          => json_encode([
                'asin'          => $asin,
                'review_url'    => $productUrl,
                'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'is_aggregate'  => true,
                'ratings_count' => $count,
            ]),
        ];
    }
}
