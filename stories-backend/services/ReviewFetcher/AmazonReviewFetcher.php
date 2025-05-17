<?php
/**
 * Amazon Review Fetcher 2
 *
 * Fetches real user reviews from Amazon's public review pages via AJAX or mobile site,
 * and falls back to an aggregate “average” review if needed.
 * All raw HTML/JSON fragments and logs go into services/ReviewFetcher/debug.
 */

namespace Services\ReviewFetcher;

use PDO;
use Exception;

class AmazonReviewFetcher extends AbstractReviewFetcher
{
    /** @var string Your Amazon Associates tag */
    protected string $affiliateTag;

    /** @var string Always include “www.” */
    protected string $domain = 'www.amazon.co.uk';

    /** @var string Path to debug folder */
    protected string $dbgDir;

    /** @var string Single, persistent cookie jar */
    protected string $cookieFile;

    /** @var string Outscraper API key */
    protected string $outscraperApiKey;

    /** @var bool Whether to use Outscraper API */
    protected bool $useOutscraper = true;

    /** @var bool Whether to use Remote Puppeteer API */
    protected bool $useRemotePuppeteer = true;

    /** @var string Remote Puppeteer API URL */
    protected string $remotePuppeteerUrl;

    /** @var bool Whether to use VPS Headless Browser API */
    protected bool $useVpsHeadlessBrowser = true;

    /** @var string VPS Headless Browser API URL */
    protected string $vpsHeadlessBrowserUrl;

    /** @var string VPS Headless Browser API Key */
    protected string $vpsHeadlessBrowserApiKey;

    public function __construct(PDO $db, int $sourceId)
    {
        parent::__construct($db, $sourceId, 'Amazon');

        // Associates tag
        $this->affiliateTag = getenv('AMAZON_ASSOCIATE_TAG') ?: 'storiesfro0f0-20';

        // Outscraper API key
        $this->outscraperApiKey = getenv('OUTSCRAPER_API_KEY') ?: 'NTNjYjkxMTUwOWI3NDBlYzg2MmI5NzY2ZTYxNDYxMTl8ZmVjODc2ZDI5ZA';

        // Remote Puppeteer API URL
        $this->remotePuppeteerUrl = getenv('REMOTE_PUPPETEER_URL') ?: 'https://storiesfromtheweb.netlify.app/.netlify/functions/amazon-reviews';

        // VPS Headless Browser API URL and key
        $this->vpsHeadlessBrowserUrl = getenv('HEADLESS_BROWSER_API_URL') ?: 'http://37.27.31.107:3000';
        $this->vpsHeadlessBrowserApiKey = getenv('HEADLESS_BROWSER_API_KEY') ?: 'your-secret-api-key-here';

        // Optional override from settings
        $stmt = $db->prepare(
            "SELECT setting_value
               FROM settings
              WHERE setting_name = 'amazon_domain'"
        );
        if ($stmt->execute() && ($d = $stmt->fetchColumn())) {
            $this->domain = preg_match('/^www\./', $d) ? $d : "www.{$d}";
        }

        // Debug directory
        $this->dbgDir = __DIR__ . '/debug';
        if (! is_dir($this->dbgDir)) {
            mkdir($this->dbgDir, 0755, true);
        }

        // One cookie file for the entire session
        $this->cookieFile = "{$this->dbgDir}/amazon-cookies.txt";
        if (! file_exists($this->cookieFile)) {
            touch($this->cookieFile);
        }
    }

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Main entry: fetch up to $limit reviews for $isbn.
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array
    {
        // 1) Clean ISBN
        $clean = preg_replace('/[^0-9X]/i','',$isbn);

        // 2) Convert to ISBN-10 / ASIN
        $asin = $this->convertISBNtoASIN($clean);

        // 3) Fallback to parent helper if needed
        if (! $asin) {
            $data = $this->standardizeISBN($isbn);
            $asin = $data['isbn'] ?: $data['isbn13'] ?? null;
        }

        if (! $asin) {
            $this->lastError = "Could not derive ASIN from ISBN {$isbn}";
            return [];
        }

        // 4) Try VPS Headless Browser API first if enabled
        $reviews = [];
        if ($this->useVpsHeadlessBrowser) {
            $this->logToFile("{$this->dbgDir}/scrape-log.txt", "🔍 Trying VPS Headless Browser API for ASIN {$asin}");
            $reviews = $this->fetchReviewsWithHeadlessBrowser($asin, $limit);

            if (!empty($reviews)) {
                $this->logToFile("{$this->dbgDir}/scrape-log.txt", "✅ Successfully fetched " . count($reviews) . " reviews with VPS Headless Browser");
                return $reviews;
            }

            $this->logToFile("{$this->dbgDir}/scrape-log.txt", "⚠️ VPS Headless Browser returned no reviews, falling back to Remote Puppeteer");
        }

        // 5) Try Remote Puppeteer API if VPS Headless Browser failed or is disabled
        if ($this->useRemotePuppeteer) {
            $this->logToFile("{$this->dbgDir}/scrape-log.txt", "🔍 Trying Remote Puppeteer API for ASIN {$asin}");
            $reviews = $this->fetchReviewsWithRemotePuppeteer($asin, $limit);

            if (!empty($reviews)) {
                $this->logToFile("{$this->dbgDir}/scrape-log.txt", "✅ Successfully fetched " . count($reviews) . " reviews with Remote Puppeteer");
                return $reviews;
            }

            $this->logToFile("{$this->dbgDir}/scrape-log.txt", "⚠️ Remote Puppeteer returned no reviews, falling back to Outscraper");
        }

        // 6) Try Outscraper API if both VPS and Remote Puppeteer failed or are disabled
        if ($this->useOutscraper) {
            $this->logToFile("{$this->dbgDir}/scrape-log.txt", "🔍 Trying Outscraper API for ASIN {$asin}");
            $reviews = $this->fetchReviewsWithOutscraper($asin, $limit);

            if (!empty($reviews)) {
                $this->logToFile("{$this->dbgDir}/scrape-log.txt", "✅ Successfully fetched " . count($reviews) . " reviews with Outscraper");
                return $reviews;
            }

            $this->logToFile("{$this->dbgDir}/scrape-log.txt", "⚠️ Outscraper returned no reviews, falling back to direct scraping");
        }

        // 7) Fallback to direct scraping if all API methods failed or are disabled
        $reviews = $this->scrapeReviews($asin, $limit);

        // 8) If none found, fallback to aggregate “average” review
        if (empty($reviews)) {
            $agg = $this->getAggregateRating($asin);
            if ($agg) {
                $reviews = [$agg];
            }
        }

        return $reviews;
    }

    /**
     * Convert ISBN-10 or ISBN-13→ASIN (ISBN-10).
     */
    private function convertISBNtoASIN(string $isbn): ?string
    {
        if (strlen($isbn) === 10) {
            return $isbn;
        }
        if (strlen($isbn) === 13 && substr($isbn,0,3)==='978') {
            $digits = substr($isbn,3,9);
            $sum = 0;
            for ($i=0; $i<9; $i++) {
                $sum += ((int)$digits[$i]) * (10-$i);
            }
            $check = 11 - ($sum % 11);
            if ($check===10)      $check='X';
            elseif ($check===11)  $check='0';
            return "{$digits}{$check}";
        }
        return null;
    }

    /**
     * Override makeRequest to match parent signature and use persistent cookies.
     */
    /**
     * Array of user agents to rotate through
     */
    protected array $userAgents = [
        // Desktop browsers
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        // Mobile browsers
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 10; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
    ];

    /**
     * Get a random user agent
     */
    protected function getRandomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }

    /**
     * Enhanced makeRequest with better anti-detection
     */
    protected function makeRequest(string $url, array $options = [], bool $throttle = true): string|false
    {
        // Add random delay to simulate human behavior if throttling is enabled
        if ($throttle) {
            // Base delay between 1-3 seconds
            $delay = mt_rand(1000, 3000);
            // Occasionally add longer delay (10% chance)
            if (mt_rand(1, 10) === 1) {
                $delay += mt_rand(2000, 5000);
            }
            // Convert to seconds and sleep
            usleep($delay * 1000);
        }

        $ch = curl_init($url);

        // Generate random browser fingerprint
        $userAgent = $options[CURLOPT_USERAGENT] ?? $this->getRandomUserAgent();

        // Create a unique cookie file for this request to avoid tracking
        $cookieFile = $options[CURLOPT_COOKIEFILE] ?? $this->cookieFile;

        // Randomize accept language slightly
        $languages = ['en-US,en;q=0.9', 'en-GB,en;q=0.9', 'en;q=0.9,en-US;q=0.8', 'en-GB,en;q=0.8,en-US;q=0.7'];
        $acceptLanguage = $languages[array_rand($languages)];

        // Base headers with some randomization
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            "Accept-Language: {$acceptLanguage}",
            "Referer: https://{$this->domain}/",
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: max-age=0',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-User: ?1',
        ];

        // Add a random IP address via X-Forwarded-For to help avoid fingerprinting
        $randomIP = mt_rand(1, 254) . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254);
        $headers[] = "X-Forwarded-For: {$randomIP}";

        // Shuffle headers to avoid fingerprinting
        shuffle($headers);

        $default = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_USERAGENT      => $userAgent,
            CURLOPT_HTTPHEADER     => $headers,
            // Add encoding to accept compressed responses
            CURLOPT_ENCODING       => 'gzip, deflate',
        ];

        // User options override defaults
        curl_setopt_array($ch, $options + $default);

        // Execute request
        $html = curl_exec($ch);

        // Check for CAPTCHA or login page before returning
        if ($html !== false) {
            $isCaptcha = preg_match('/captcha|robot check|verify you\'?re not a robot|security challenge/i', $html);
            $isLogin = preg_match('/sign\s?in|log\s?in|authentication|password|email.+?password/is', $html) &&
                       preg_match('/<form[^>]*>/i', $html);

            if ($isCaptcha) {
                $this->logToFile("{$this->dbgDir}/scrape-log.txt", "⚠️ CAPTCHA detected at URL: {$url}");
                file_put_contents("{$this->dbgDir}/amazon-captcha-" . time() . ".html", $html);
            }

            if ($isLogin) {
                $this->logToFile("{$this->dbgDir}/scrape-log.txt", "⚠️ Login page detected at URL: {$url}");
                file_put_contents("{$this->dbgDir}/amazon-login-" . time() . ".html", $html);
            }
        }

        curl_close($ch);
        return $html === false ? false : $html;
    }

    /**
     * Scrape up to $limit reviews with multiple fallback strategies
     */
    protected function scrapeReviews(string $asin, int $limit): array
    {
        $reviews = [];
        $page    = 1;
        $logFile = "{$this->dbgDir}/scrape-log.txt";
        $maxRetries = 3;
        $retryDelay = 5; // seconds

        // 0) Pre-fetch product page so cookie+bot-check passes
        $this->logToFile($logFile, "🌐 Pre-fetch product page for ASIN {$asin}");
        $prodHtml = $this->makeRequest("https://{$this->domain}/dp/{$asin}");
        file_put_contents("{$this->dbgDir}/amazon-{$asin}-product-raw.html", $prodHtml);

        // Check if we got redirected to a login page
        if ($prodHtml && preg_match('/sign\s?in|log\s?in|authentication|password|email.+?password/is', $prodHtml)) {
            $this->logToFile($logFile, "⚠️ Initial product page redirected to login - trying alternative approach");
            // Continue anyway - we'll try different methods
        }

        // 1) Try AJAX loop - most reliable when it works
        $this->logToFile($logFile, "▶️ Starting AJAX scrape for ASIN {$asin}, limit={$limit}");
        $retries = 0;
        $ajaxSuccess = false;

        while (count($reviews) < $limit && $retries < $maxRetries) {
            $ajax = "https://{$this->domain}/hz/reviews-render/ajax/reviews/get?asin={$asin}&pageNumber={$page}&reviewerType=all_reviews&formatType=current_format";
            $this->logToFile($logFile, "🌐 Fetch AJAX page {$page}" . ($retries > 0 ? " (retry {$retries})" : ""));

            // Use different user agent for each retry
            $raw = $this->makeRequest($ajax, [
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json, text/javascript, */*; q=0.01',
                    'Accept-Language: en-GB,en;q=0.9',
                    "Referer: https://{$this->domain}/dp/{$asin}",
                    'X-Requested-With: XMLHttpRequest',
                ],
                CURLOPT_USERAGENT => $this->getRandomUserAgent(),
            ]);

            $filename = "{$this->dbgDir}/amazon-{$asin}-ajax-page{$page}" .
                        ($retries > 0 ? "-retry{$retries}" : "") . "-raw.json";
            file_put_contents($filename, $raw ?: "EMPTY RESPONSE");

            if (! $raw) {
                $this->logToFile($logFile, "❌ Empty AJAX response");
                $retries++;
                if ($retries < $maxRetries) {
                    $this->logToFile($logFile, "⏱️ Waiting {$retryDelay}s before retry");
                    sleep($retryDelay);
                    continue;
                }
                break;
            }

            $payload = json_decode($raw, true);
            if (!is_array($payload) || !isset($payload['html'])) {
                $this->logToFile($logFile, "⚠️ Invalid AJAX JSON");
                $retries++;
                if ($retries < $maxRetries) {
                    $this->logToFile($logFile, "⏱️ Waiting {$retryDelay}s before retry");
                    sleep($retryDelay);
                    continue;
                }
                break;
            }

            // Reset retries on success
            $retries = 0;
            $ajaxSuccess = true;

            $frag = $payload['html'];
            file_put_contents("{$this->dbgDir}/amazon-{$asin}-ajax-page{$page}-fragment.html", $frag);

            $parsed = $this->parseReviewsWithRegex($frag, $asin);
            $this->logToFile($logFile, "✔️ Parsed " . count($parsed) . " reviews via AJAX");

            if (empty($parsed)) {
                // No more reviews to parse
                break;
            }

            $reviews = array_merge($reviews, $parsed);
            $page++;

            // Add a small delay between pages to avoid rate limiting
            usleep(mt_rand(500000, 1500000)); // 0.5-1.5 seconds
        }

        // 2) If AJAX yielded nothing, try mobile review-listing
        if (empty($reviews)) {
            $this->logToFile($logFile, "⚠️ AJAX " . ($ajaxSuccess ? "found no reviews" : "failed") . " → trying mobile site");
            $page = 1;
            $retries = 0;

            while (count($reviews) < $limit && $retries < $maxRetries) {
                $url = "https://{$this->domain}/gp/aw/review-listing/{$asin}?pageNumber={$page}";
                $this->logToFile($logFile, "🌐 Fetch mobile page {$page}" . ($retries > 0 ? " (retry {$retries})" : ""));

                // Use mobile user agent
                $mobileAgents = [
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
                    'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
                    'Mozilla/5.0 (Linux; Android 10; SM-G981B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
                ];

                $html = $this->makeRequest($url, [
                    CURLOPT_USERAGENT => $mobileAgents[array_rand($mobileAgents)],
                ]);

                $filename = "{$this->dbgDir}/amazon-{$asin}-mobile-page{$page}" .
                            ($retries > 0 ? "-retry{$retries}" : "") . "-raw.html";
                file_put_contents($filename, $html ?: "EMPTY RESPONSE");

                if (! $html) {
                    $this->logToFile($logFile, "❌ Empty mobile response");
                    $retries++;
                    if ($retries < $maxRetries) {
                        $this->logToFile($logFile, "⏱️ Waiting {$retryDelay}s before retry");
                        sleep($retryDelay);
                        continue;
                    }
                    break;
                }

                if (preg_match('/captcha|robot check|verify you\'?re not a robot|security challenge/i', $html)) {
                    $this->logToFile($logFile, "❌ CAPTCHA detected on mobile page {$page}");
                    $retries++;
                    if ($retries < $maxRetries) {
                        $this->logToFile($logFile, "⏱️ Waiting {$retryDelay}s before retry");
                        sleep($retryDelay * 2); // Double delay for CAPTCHA
                        continue;
                    }
                    break;
                }

                // Reset retries on success
                $retries = 0;

                $parsed = $this->parseReviewsWithRegex($html, $asin);
                $this->logToFile($logFile, "✔️ Parsed " . count($parsed) . " reviews via mobile");

                if (empty($parsed)) {
                    break;
                }

                $reviews = array_merge($reviews, $parsed);
                $page++;

                // Add a small delay between pages
                usleep(mt_rand(500000, 1500000)); // 0.5-1.5 seconds
            }
        }

        // 3) If still no reviews, try the standard product-reviews page
        if (empty($reviews)) {
            $this->logToFile($logFile, "⚠️ Mobile site yielded no reviews → trying standard reviews page");
            $page = 1;
            $retries = 0;

            while (count($reviews) < $limit && $retries < $maxRetries) {
                $url = "https://{$this->domain}/product-reviews/{$asin}?pageNumber={$page}";
                $this->logToFile($logFile, "🌐 Fetch standard reviews page {$page}" . ($retries > 0 ? " (retry {$retries})" : ""));

                $html = $this->makeRequest($url, [
                    CURLOPT_USERAGENT => $this->getRandomUserAgent(),
                ]);

                $filename = "{$this->dbgDir}/amazon-{$asin}-standard-page{$page}" .
                            ($retries > 0 ? "-retry{$retries}" : "") . "-raw.html";
                file_put_contents($filename, $html ?: "EMPTY RESPONSE");

                if (! $html || preg_match('/captcha|robot check|verify you\'?re not a robot|security challenge/i', $html)) {
                    $this->logToFile($logFile, "❌ " . (!$html ? "Empty response" : "CAPTCHA detected") . " on standard page {$page}");
                    $retries++;
                    if ($retries < $maxRetries) {
                        $this->logToFile($logFile, "⏱️ Waiting {$retryDelay}s before retry");
                        sleep($retryDelay * ($html ? 2 : 1)); // Double delay for CAPTCHA
                        continue;
                    }
                    break;
                }

                // Reset retries on success
                $retries = 0;

                $parsed = $this->parseReviewsWithRegex($html, $asin);
                $this->logToFile($logFile, "✔️ Parsed " . count($parsed) . " reviews via standard page");

                if (empty($parsed)) {
                    break;
                }

                $reviews = array_merge($reviews, $parsed);
                $page++;

                // Add a small delay between pages
                usleep(mt_rand(500000, 1500000)); // 0.5-1.5 seconds
            }
        }

        return array_slice($reviews, 0, $limit);
    }

    /**
     * Fallback: pull average rating & count from product pages
     * with multiple fallback strategies
     */
    private function getAggregateRating(string $asin): ?array
    {
        $logFile = "{$this->dbgDir}/scrape-log.txt";
        $maxRetries = 3;
        $retryDelay = 5; // seconds

        // Try multiple URLs to find aggregate rating
        $urlsToTry = [
            "https://{$this->domain}/product-reviews/{$asin}?pageNumber=1",
            "https://{$this->domain}/dp/{$asin}",
            "https://{$this->domain}/gp/aw/d/{$asin}" // Mobile site
        ];

        foreach ($urlsToTry as $urlIndex => $url) {
            $retries = 0;
            $attemptNum = $urlIndex + 1;
            $totalUrls = count($urlsToTry);
            $this->logToFile($logFile, "📊 Fetching aggregate from {$url} (attempt {$attemptNum}/{$totalUrls})");

            while ($retries < $maxRetries) {
                $html = $this->makeRequest($url, [
                    CURLOPT_USERAGENT => $this->getRandomUserAgent(),
                ]);

                $filename = "{$this->dbgDir}/amazon-{$asin}-aggregate-" .
                            basename(parse_url($url, PHP_URL_PATH)) .
                            ($retries > 0 ? "-retry{$retries}" : "") . "-raw.html";
                file_put_contents($filename, $html ?: "EMPTY RESPONSE");

                if (!$html) {
                    $this->logToFile($logFile, "❌ Empty response for aggregate (retry {$retries})");
                    $retries++;
                    if ($retries < $maxRetries) {
                        sleep($retryDelay);
                        continue;
                    }
                    break;
                }

                if (preg_match('/captcha|robot check|verify you\'?re not a robot|security challenge/i', $html)) {
                    $this->logToFile($logFile, "❌ CAPTCHA detected for aggregate (retry {$retries})");
                    $retries++;
                    if ($retries < $maxRetries) {
                        sleep($retryDelay * 2); // Double delay for CAPTCHA
                        continue;
                    }
                    break;
                }

                // Try multiple patterns to extract average rating
                $avgPatterns = [
                    // Standard desktop pattern
                    '/data-hook="acr-average"[^>]*>\s*([\d.]+)\s+out of 5 stars/i',
                    // Alternative desktop pattern
                    '/class="[^"]*a-icon-alt[^"]*"[^>]*>\s*([\d.]+)\s+out of 5 stars/i',
                    // Mobile pattern
                    '/class="[^"]*a-color-base[^"]*"[^>]*>\s*([\d.]+)\s+out of 5/i',
                    // Product page pattern
                    '/id="acrPopover"[^>]*title="([\d.]+)\s+out of 5 stars"/i',
                    // Another product page pattern
                    '/class="[^"]*a-size-base[^"]*"[^>]*>\s*([\d.]+) out of 5/i',
                    // Generic pattern
                    '/([\d.]+)\s+out of\s+5\s+stars/i'
                ];

                $avg = null;
                foreach ($avgPatterns as $pattern) {
                    if (preg_match($pattern, $html, $m)) {
                        $avg = (float)$m[1];
                        break;
                    }
                }

                if ($avg === null) {
                    $this->logToFile($logFile, "⚠️ Could not find rating with standard patterns, trying alternative approach");

                    // Try to find star images and count them
                    if (preg_match_all('/<i[^>]+class="[^"]*a-icon-star[^"]*"[^>]*>/i', $html, $stars)) {
                        $avg = count($stars[0]);
                        $this->logToFile($logFile, "✔️ Found rating by counting star images: {$avg}");
                    } else {
                        $this->logToFile($logFile, "❌ Could not parse aggregate rating from this URL");
                        break; // Try next URL
                    }
                }

                // Extract count with multiple patterns
                $count = 0;
                $countPatterns = [
                    // Standard pattern
                    '/data-hook="acr-secondary-review-count"[^>]*>\s*([\d,]+)\s+ratings?/i',
                    // Alternative pattern
                    '/data-hook="total-review-count"[^>]*>\s*([\d,]+)\s+(?:ratings?|reviews?)/i',
                    // Product page pattern
                    '/id="acrCustomerReviewText"[^>]*>\s*([\d,]+)\s+(?:ratings?|reviews?)/i',
                    // Mobile pattern
                    '/class="[^"]*totalReviewCount[^"]*"[^>]*>\s*([\d,]+)/i',
                    // Generic pattern
                    '/([\d,]+)\s+(?:global|customer|total)?\s*(?:ratings?|reviews?)/i'
                ];

                foreach ($countPatterns as $pattern) {
                    if (preg_match($pattern, $html, $m)) {
                        $count = (int)str_replace([',', '.'], '', $m[1]);
                        break;
                    }
                }

                $this->logToFile($logFile, "✔️ Aggregate {$avg}/5 from {$count} ratings");

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
                        'review_url'    => $url,
                        'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                        'is_aggregate'  => true,
                        'ratings_count' => $count,
                    ]),
                ];
            }
        }

        // If we get here, we couldn't find a rating on any URL
        $this->logToFile($logFile, "❌ Failed to find aggregate rating after trying all URLs");

        // Last resort: return a placeholder with default values
        return [
            'source_id'         => $this->sourceId,
            'reviewer_name'     => 'Amazon Aggregate',
            'review_date'       => date('Y-m-d'),
            'original_rating'   => "0/5",
            'rating_value'      => 0,
            'rating_scale'      => 5,
            'rating_normalised' => 0,
            'review_text'       => "Unable to retrieve Amazon ratings for this book.",
            'metadata'          => json_encode([
                'asin'          => $asin,
                'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'is_aggregate'  => true,
                'ratings_count' => 0,
                'error'         => 'Failed to retrieve ratings after multiple attempts',
            ]),
        ];
    }

    /**
     * Enhanced regex parser for review blocks with multiple patterns
     */
    private function parseReviewsWithRegex(string $html, string $asin): array
    {
        $reviews = [];
        $logFile = "{$this->dbgDir}/parse-debug.txt";
        $this->logToFile($logFile, "🔍 Regex parse for ASIN {$asin}");

        // Save a debug copy of the HTML for inspection
        $debugFile = "{$this->dbgDir}/amazon-{$asin}-parse-debug-" . time() . ".html";
        file_put_contents($debugFile, $html);

        // Try multiple patterns to extract review blocks
        $patterns = [
            // Standard desktop pattern
            '/<div[^>]+data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>(?:\s*<\/div>)?/is',

            // Mobile site pattern
            '/<div[^>]+id="customer-review-[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>(?:\s*<\/div>)?/is',

            // Alternative desktop pattern
            '/<div[^>]+class="[^"]*review[^"]*"[^>]*data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>(?:\s*<\/div>)?/is',

            // Broader pattern as fallback
            '/<div[^>]*(?:data-hook="review"|class="[^"]*review[^"]*")[^>]*>(?:(?!<div[^>]*(?:data-hook="review"|class="[^"]*review[^"]*")).)*?<\/div>\s*<\/div>/is'
        ];

        $foundBlocks = [];

        // Try each pattern until we find reviews
        foreach ($patterns as $index => $pattern) {
            if (preg_match_all($pattern, $html, $blocks, PREG_SET_ORDER)) {
                $this->logToFile($logFile, "✔️ Pattern #{$index} found " . count($blocks) . " blocks");
                $foundBlocks = $blocks;
                break;
            }
        }

        // If no blocks found with standard patterns, try a more aggressive approach
        if (empty($foundBlocks)) {
            $this->logToFile($logFile, "⚠️ No blocks found with standard patterns, trying alternative approach");

            // Look for star ratings first
            if (preg_match_all('/([\d.]+)\s+out of\s+5\s+stars/i', $html, $ratings, PREG_OFFSET_CAPTURE)) {
                $this->logToFile($logFile, "✔️ Found " . count($ratings[0]) . " star ratings");

                // For each rating, try to extract surrounding content as a review block
                foreach ($ratings[1] as $index => $ratingMatch) {
                    $rating = (float)$ratingMatch[0];
                    $position = $ratings[0][$index][1];

                    // Extract 1000 characters before and after the rating
                    $start = max(0, $position - 1000);
                    $length = 2000;
                    $context = substr($html, $start, $length);

                    // Add this as a "block" to process
                    $foundBlocks[] = [$context];
                }
            }
        }

        // Process all found blocks
        if (!empty($foundBlocks)) {
            $this->logToFile($logFile, "✔️ Processing " . count($foundBlocks) . " blocks");

            foreach ($foundBlocks as $blk) {
                $b = $blk[0];

                // Name - try multiple patterns
                $name = 'Unknown';
                $namePatterns = [
                    '/<span[^>]+class="a-profile-name"[^>]*>([^<]+)<\/span>/i',
                    '/class="[^"]*author[^"]*"[^>]*>([^<]+)<\/span>/i',
                    '/data-hook="review-author"[^>]*>([^<]+)<\/span>/i',
                    '/class="[^"]*reviewer[^"]*"[^>]*>([^<]+)<\/span>/i'
                ];

                foreach ($namePatterns as $pattern) {
                    if (preg_match($pattern, $b, $m)) {
                        $name = trim($m[1]);
                        break;
                    }
                }

                if (stripos($name, 'Amazon Aggregate') !== false) {
                    continue;
                }

                // Rating - try multiple patterns
                $rating = null;
                $ratingPatterns = [
                    '/([\d.]+)\s+out of\s+5\s+stars/i',
                    '/data-hook="review-star-rating"[^>]*>([\d.]+)\s+out of\s+5\s+stars/i',
                    '/class="[^"]*stars[^"]*"[^>]*>([\d.]+)\s+out of\s+5/i',
                    '/class="[^"]*rating[^"]*"[^>]*>([\d.]+)\s+out of\s+5/i',
                    '/aria-label="([\d.]+)\s+stars?"/i'
                ];

                foreach ($ratingPatterns as $pattern) {
                    if (preg_match($pattern, $b, $m)) {
                        $rating = (float)$m[1];
                        break;
                    }
                }

                if ($rating === null) {
                    // Try to find star images
                    if (preg_match_all('/<i[^>]+class="[^"]*a-icon-star[^"]*"[^>]*>/i', $b, $stars)) {
                        $rating = count($stars[0]);
                    } else {
                        continue; // Skip if no rating found
                    }
                }

                // Date - try multiple patterns
                $date = date('Y-m-d');
                $datePatterns = [
                    '/data-hook="review-date"[^>]*>([^<]+)<\/span>/i',
                    '/class="[^"]*review-date[^"]*"[^>]*>([^<]+)<\/span>/i',
                    '/class="[^"]*date[^"]*"[^>]*>([^<]+)<\/span>/i',
                    '/Reviewed\s+in\s+[^<]+on\s+([^<]+)</i'
                ];

                foreach ($datePatterns as $pattern) {
                    if (preg_match($pattern, $b, $m)) {
                        $dateStr = trim($m[1]);
                        // Try to handle various date formats
                        if ($ts = strtotime($dateStr)) {
                            $date = date('Y-m-d', $ts);
                            break;
                        }
                    }
                }

                // Text - try multiple patterns
                $text = '';
                $textPatterns = [
                    '/<span[^>]+data-hook="review-body"[^>]*>(.*?)<\/span>/is',
                    '/class="[^"]*review-text[^"]*"[^>]*>(.*?)<\/span>/is',
                    '/class="[^"]*review-content[^"]*"[^>]*>(.*?)<\/div>/is',
                    '/data-hook="review-body"[^>]*>(.*?)<\/span>/is'
                ];

                foreach ($textPatterns as $pattern) {
                    if (preg_match($pattern, $b, $m)) {
                        $text = trim(strip_tags($m[1]));
                        break;
                    }
                }

                // If still no text, try a more aggressive approach
                if ($text === '') {
                    // Extract all text between paragraph tags
                    if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $b, $paragraphs)) {
                        $text = trim(strip_tags(implode("\n\n", $paragraphs[0])));
                    }
                }

                // Skip if no text found
                if ($text === '') {
                    continue;
                }

                // Clean up text - remove excessive whitespace
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);

                // Generate affiliate link
                $link = "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}";

                // Log a snippet of the review
                $this->logToFile($logFile, "REVIEW: {$name} ({$rating}/5) - " . mb_substr($text, 0, 60) . "…");

                // Add to reviews array
                $reviews[] = [
                    'source_id'         => $this->sourceId,
                    'reviewer_name'     => $name,
                    'review_date'       => $date,
                    'original_rating'   => "{$rating}/5",
                    'rating_value'      => $rating,
                    'rating_scale'      => 5,
                    'rating_normalised' => $this->normalizeRating($rating, 5),
                    'review_text'       => $text,
                    'metadata'          => json_encode([
                        'asin'          => $asin,
                        'affiliate_url' => $link,
                    ]),
                ];
            }
        } else {
            $this->logToFile($logFile, "⚠️ No review blocks found with any pattern");
        }

        // Remove duplicates based on reviewer name and text
        $uniqueReviews = [];
        $seen = [];

        foreach ($reviews as $review) {
            $key = md5($review['reviewer_name'] . '|' . substr($review['review_text'], 0, 100));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueReviews[] = $review;
            }
        }

        $this->logToFile($logFile, "✅ Final unique reviews: " . count($uniqueReviews));

        return $uniqueReviews;
    }

    /**
     * Fetch reviews using VPS Headless Browser service
     *
     * @param string $asin Amazon ASIN
     * @param int $limit Maximum number of reviews to return
     * @return array Array of reviews
     */
    private function fetchReviewsWithHeadlessBrowser(string $asin, int $limit): array
    {
        $debugDir = $this->dbgDir;
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        $this->logToFile("{$debugDir}/scrape-log.txt", "📦 [VPS-Scraper-Amazon] Attempting to fetch reviews using Puppeteer with full page JS evaluation for ASIN: {$asin}");

        // Request more reviews than needed to ensure we get enough
        $requestLimit = min(100, $limit * 2); // Request up to 100 reviews or double the limit

        // Build the request URL
        $url = "{$this->vpsHeadlessBrowserUrl}/scrape/amazon?asin={$asin}&limit={$requestLimit}";

        // Make the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-api-key: {$this->vpsHeadlessBrowserApiKey}"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Save the response for debugging
        file_put_contents("{$debugDir}/amazon-vps-response-{$asin}.json", $response);

        if ($httpCode >= 400) {
            $this->logToFile("{$debugDir}/scrape-log.txt", "❌ VPS Headless Browser API error: HTTP {$httpCode}");
            return [];
        }

        // Parse the response
        $data = json_decode($response, true);

        if (!$data || !isset($data['reviews']) || empty($data['reviews'])) {
            $this->logToFile("{$debugDir}/scrape-log.txt", "❌ No reviews found in VPS Headless Browser response");
            return [];
        }

        $reviewCount = count($data['reviews']);
        $this->logToFile("{$debugDir}/scrape-log.txt", "✅ [VPS-Scraper-Success] Found {$reviewCount} reviews using Puppeteer-based Headless Browser");

        // Add a prominent message to the main log
        error_log("✅✅✅ AMAZON VPS SCRAPER SUCCESSFULLY RETURNED {$reviewCount} REVIEWS ✅✅✅");

        // Process the reviews to match our expected format
        $reviews = [];
        foreach ($data['reviews'] as $review) {
            // Skip reviews without text or rating
            if (empty($review['review_text']) || !isset($review['rating_value'])) {
                continue;
            }

            // Convert the review to our format
            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $review['reviewer_name'] ?? 'Amazon Customer',
                'reviewer_age' => null,
                'review_date' => $review['review_date'] ?? date('Y-m-d'),
                'original_rating' => $review['original_rating'] ?? "{$review['rating_value']}/5",
                'rating_value' => $review['rating_value'],
                'rating_scale' => 5,
                'rating_normalised' => $review['rating_normalised'] ?? $this->normalizeRating($review['rating_value'], 5),
                'review_text' => $review['review_text'],
                'metadata' => $review['metadata'] ?? json_encode([
                    'asin' => $asin,
                    'review_url' => "https://{$this->domain}/product-reviews/{$asin}",
                    'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                    'is_synthetic' => false,
                    'is_aggregate' => isset($review['metadata']) && strpos($review['metadata'], 'is_aggregate') !== false
                ])
            ];
        }

        return $reviews;
    }

    /**
     * Fetch reviews using Remote Puppeteer API
     *
     * @param string $asin Amazon ASIN
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of reviews
     */
    protected function fetchReviewsWithRemotePuppeteer(string $asin, int $limit): array
    {
        $logFile = "{$this->dbgDir}/scrape-log.txt";
        $this->logToFile($logFile, "🤖 Fetching reviews with Remote Puppeteer API for ASIN {$asin}");

        try {
            // Prepare request data
            $data = [
                'asin' => $asin,
                'limit' => $limit,
                'domain' => $this->domain
            ];

            // Initialize cURL session
            $ch = curl_init($this->remotePuppeteerUrl);

            // Set cURL options
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 60, // 60 seconds timeout
                CURLOPT_CONNECTTIMEOUT => 10
            ]);

            // Execute cURL request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Save raw response for debugging
            file_put_contents(
                "{$this->dbgDir}/remote-puppeteer-{$asin}-response.json",
                $response ?: "EMPTY RESPONSE"
            );

            // Check for errors
            if ($response === false) {
                $error = curl_error($ch);
                $this->logToFile($logFile, "❌ cURL error: {$error}");
                curl_close($ch);
                return [];
            }

            curl_close($ch);

            // Check HTTP status code
            if ($httpCode !== 200) {
                $this->logToFile($logFile, "❌ HTTP error: {$httpCode}");
                return [];
            }

            // Parse JSON response
            $data = json_decode($response, true);

            // Check for error in response
            if (isset($data['error'])) {
                $this->logToFile($logFile, "❌ API error: {$data['error']}");
                return [];
            }

            // Check if reviews exist
            if (!isset($data['reviews']) || !is_array($data['reviews'])) {
                $this->logToFile($logFile, "❌ No reviews found in response");
                return [];
            }

            $reviews = $data['reviews'];
            $this->logToFile($logFile, "✅ Successfully fetched " . count($reviews) . " reviews with Remote Puppeteer");

            return $reviews;

        } catch (Exception $e) {
            $this->logToFile($logFile, "❌ Exception: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Fetch reviews using Outscraper SDK
     */
    protected function fetchReviewsWithOutscraper(string $asin, int $limit): array
    {
        $logFile = "{$this->dbgDir}/outscraper-log.txt";
        $this->logToFile($logFile, "🚀 Starting Outscraper request for ASIN {$asin}");

        // IMPORTANT: Outscraper only supports amazon.com for reviews
        // Always use 'com' domain for Outscraper regardless of the site's domain
        $domainCode = 'com';

        // Log original domain for reference
        $originalDomain = 'com';
        if (strpos($this->domain, 'amazon.co.uk') !== false) {
            $originalDomain = 'co.uk';
        } elseif (strpos($this->domain, 'amazon.ca') !== false) {
            $originalDomain = 'ca';
        } elseif (strpos($this->domain, 'amazon.de') !== false) {
            $originalDomain = 'de';
        }

        $this->logToFile($logFile, "🌍 Original domain: {$originalDomain}, using {$domainCode} for Outscraper (only supported domain)");

        // Check if the book exists on amazon.com
        $this->logToFile($logFile, "🔍 Checking if book exists on amazon.com: https://www.amazon.com/dp/{$asin}");
        $checkUrl = "https://www.amazon.com/dp/{$asin}";
        $checkHtml = $this->makeRequest($checkUrl, [], false);

        // Save the check response for debugging
        file_put_contents("{$this->dbgDir}/outscraper-{$asin}-check.html", $checkHtml ?: "EMPTY RESPONSE");

        // Check if the product exists on amazon.com
        $productExists = false;
        if ($checkHtml) {
            // Check for product title or price indicators
            $productExists = preg_match('/<span id="productTitle"|<span class="a-price"|<div id="dp-container"/i', $checkHtml);
            // Check for "page not found" indicators
            $notFound = preg_match('/page you requested could not be found|we couldn\'t find that page|404 error/i', $checkHtml);

            if ($productExists) {
                $this->logToFile($logFile, "✅ Product found on amazon.com");
            } elseif ($notFound) {
                $this->logToFile($logFile, "❌ Product not found on amazon.com");
                return []; // Early return if product doesn't exist
            } else {
                $this->logToFile($logFile, "⚠️ Couldn't determine if product exists (likely a CAPTCHA or login page)");
                // Continue anyway as a best effort
            }
        } else {
            $this->logToFile($logFile, "⚠️ Couldn't check if product exists on amazon.com (empty response)");
            // Continue anyway as a best effort
        }

        // Log request details
        $this->logToFile($logFile, "📡 API Request: ASIN={$asin}, domain={$domainCode}, limit={$limit}");

        try {
            // Include the Outscraper SDK
            require_once __DIR__ . '/../Outscraper/sdk/init.php';

            // Create Outscraper client with API key
            $client = new \OutscraperClient($this->outscraperApiKey);

            // Enable detailed debugging
            $this->logToFile($logFile, "🔑 Using API key: {$this->outscraperApiKey}");
            $this->logToFile($logFile, "📊 Requesting {$limit} reviews with 'newest' sorting");

            // Make the request using the SDK
            $data = $client->amazon_reviews($asin, $limit, 'newest', $domainCode);

            // Save raw response for debugging
            file_put_contents(
                "{$this->dbgDir}/outscraper-{$asin}-response.json",
                json_encode($data, JSON_PRETTY_PRINT)
            );

            // Debug the response
            $this->logToFile($logFile, "📦 Response received: " . json_encode(array_keys($data)));

            if (empty($data)) {
                $this->logToFile($logFile, "⚠️ Empty response from Outscraper API");
                return [];
            }

            // Process reviews
            $reviews = [];
            foreach ($data as $item) {
                if (!isset($item['reviews']) || empty($item['reviews'])) {
                    $this->logToFile($logFile, "⚠️ No reviews found in item: " . json_encode(array_keys($item)));
                    continue;
                }

                // Get product info
                $productTitle = $item['title'] ?? '';
                $productUrl = $item['url'] ?? '';
                $this->logToFile($logFile, "📚 Product: {$productTitle}");
                $this->logToFile($logFile, "🔗 URL: {$productUrl}");
                $this->logToFile($logFile, "⭐ Found " . count($item['reviews']) . " reviews");

                // Process each review
                foreach ($item['reviews'] as $review) {
                    // Skip if missing essential data
                    if (!isset($review['rating']) || !isset($review['review_text'])) {
                        $this->logToFile($logFile, "⚠️ Skipping review with missing data: " . json_encode(array_keys($review)));
                        continue;
                    }

                    $rating = (float)$review['rating'];
                    $reviewerName = $review['reviewer_name'] ?? 'Anonymous';
                    $reviewDate = $review['review_date'] ?? date('Y-m-d');
                    $reviewText = $review['review_text'] ?? '';

                    // Convert date format if needed
                    if (preg_match('/^[A-Za-z]+ \d+, \d{4}$/', $reviewDate)) {
                        $timestamp = strtotime($reviewDate);
                        if ($timestamp) {
                            $reviewDate = date('Y-m-d', $timestamp);
                        }
                    }

                    $this->logToFile($logFile, "👤 Review by {$reviewerName}: {$rating}/5");

                    // Add to reviews array
                    $reviews[] = [
                        'source_id'         => $this->sourceId,
                        'reviewer_name'     => $reviewerName,
                        'review_date'       => $reviewDate,
                        'original_rating'   => "{$rating}/5",
                        'rating_value'      => $rating,
                        'rating_scale'      => 5,
                        'rating_normalised' => $this->normalizeRating($rating, 5),
                        'review_text'       => $reviewText,
                        'metadata'          => json_encode([
                            'asin'          => $asin,
                            'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                            'product_title' => $productTitle,
                            'product_url'   => $productUrl,
                            'source'        => 'Outscraper SDK (amazon.com)'
                        ]),
                    ];
                }
            }

            $this->logToFile($logFile, "✅ Processed " . count($reviews) . " reviews from Outscraper");
            return $reviews;

        } catch (Exception $e) {
            // Log any errors
            $this->logToFile($logFile, "❌ Outscraper API Error: " . $e->getMessage());
            $this->logToFile($logFile, "📋 Error trace: " . $e->getTraceAsString());
            file_put_contents("{$this->dbgDir}/outscraper-{$asin}-error.txt", $e->getMessage() . "\n\n" . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Append a timestamped line to a debug file.
     */
    protected function logToFile(string $file, string $line): void
    {
        file_put_contents(
            $file,
            date('[Y-m-d H:i:s] ') . $line . PHP_EOL,
            FILE_APPEND
        );
    }
}
