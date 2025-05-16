<?php
/**
 * Amazon Review Fetcher
 *
 * Fetches real user reviews from Amazon’s public review pages,
 * falls back to an aggregate “average” review, and writes
 * all raw HTML & debug logs into services/ReviewFetcher/debug.
 */

namespace Services\ReviewFetcher;

use PDO;

class AmazonReviewFetcher extends AbstractReviewFetcher
{
    /** @var string Your Amazon Associates tag */
    private string $affiliateTag;

    /** @var string Always include “www.” */
    private string $domain = 'www.amazon.co.uk';

    /** @var string Path to debug folder */
    private string $dbgDir;

    /** @var string Single, persistent cookie jar */
    private string $cookieFile;

    public function __construct(PDO $db, int $sourceId)
    {
        parent::__construct($db, $sourceId, 'Amazon');

        $this->affiliateTag = getenv('AMAZON_ASSOCIATE_TAG') ?: 'storiesfro0f0-20';

        // Optional override from settings
        $stmt = $db->prepare(
            "SELECT setting_value
               FROM settings
              WHERE setting_name = 'amazon_domain'"
        );
        if ($stmt->execute() && ($d = $stmt->fetchColumn())) {
            // Ensure it starts with www.
            $this->domain = preg_match('/^www\./', $d) ? $d : 'www.' . $d;
        }

        // Prepare debug directory
        $this->dbgDir = __DIR__ . '/debug';
        if (! is_dir($this->dbgDir)) {
            mkdir($this->dbgDir, 0755, true);
        }

        // Use one cookie file for the entire scraping session
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
     * Fetch up to $limit reviews for a given ISBN.
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array
    {
        // 1. Clean ISBN
        $clean = preg_replace('/[^0-9X]/i', '', $isbn);

        // 2. Try ISBN→ASIN (ISBN-10)
        $asin = $this->convertISBNtoASIN($clean);

        // 3. Fallback to parent helper if needed
        if (! $asin) {
            $data = $this->standardizeISBN($isbn);
            $asin = $data['isbn'] ?: $data['isbn13'] ?? null;
        }

        if (! $asin) {
            $this->lastError = "Could not derive ASIN from ISBN {$isbn}";
            return [];
        }

        // 4. Scrape real reviews
        $reviews = $this->scrapeReviews($asin, $limit);

        // 5. If none found, fallback to aggregate “average” review
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
        // Already 10 chars?
        if (strlen($isbn) === 10) {
            return $isbn;
        }

        // ISBN-13 starting 978 → compute ISBN-10
        if (strlen($isbn) === 13 && substr($isbn, 0, 3) === '978') {
            $digits = substr($isbn, 3, 9);
            $sum    = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += ((int)$digits[$i]) * (10 - $i);
            }
            $check = 11 - ($sum % 11);
            if ($check === 10)      $check = 'X';
            elseif ($check === 11)  $check = '0';
            return $digits . $check;
        }

        return null;
    }




    /**
 * Override makeRequest to match parent signature and use persistent cookies.
 *
 * @param string $url The URL to fetch.
 * @param array $options Optional cURL options to merge.
 * @param bool $throttle Whether to apply any throttle (unused).
 * @return string|false The response HTML or false on failure.
 */
protected function makeRequest(string $url, array $options = [], bool $throttle = true): string|false
{
    // Initialize cURL
    $ch = curl_init($url);

    // Default cURL options
    $defaultOpts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_COOKIEJAR      => $this->cookieFile,
        CURLOPT_COOKIEFILE     => $this->cookieFile,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                                 . ' AppleWebKit/537.36 (KHTML, like Gecko)'
                                 . ' Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-GB,en;q=0.9',
            'Referer: https://' . $this->domain . '/',
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: max-age=0',
        ],
    ];

    // Merge any user-supplied options (user-supplied keys override defaults)
    curl_setopt_array($ch, $options + $defaultOpts);

    // Execute request
    $html = curl_exec($ch);
    curl_close($ch);

    return $html === false ? false : $html;
}





/**
 * Scrape up to $limit reviews using Amazon’s AJAX endpoint,
 * decoding the JSON response to get the HTML fragment.
 */
protected function scrapeReviews(string $asin, int $limit): array
{
    $reviews   = [];
    $page      = 1;
    $debugDir  = __DIR__ . '/debug';
    $logFile   = "{$debugDir}/scrape-log.txt";

    $this->logToFile($logFile, "▶️ Starting AJAX scrape for ASIN {$asin}, limit={$limit}");

    while (count($reviews) < $limit) {
        // 1) Build the AJAX URL
        $ajaxUrl = sprintf(
            'https://%s/hz/reviews-render/ajax/reviews/get'
          . '?asin=%s&pageNumber=%d&reviewerType=all_reviews&formatType=current_format',
            $this->domain,
            $asin,
            $page
        );

        $this->logToFile($logFile, "🌐 Fetching AJAX page {$page}: {$ajaxUrl}");

        // 2) Request with X-Requested-With header
        $response = $this->makeRequest($ajaxUrl, [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json, text/javascript, */*; q=0.01',
                'Accept-Language: en-GB,en;q=0.9',
                'Referer: https://' . $this->domain . '/dp/' . $asin,
                'X-Requested-With: XMLHttpRequest',
            ]
        ]);

        // 3) Always save raw JSON for debugging
        file_put_contents("{$debugDir}/amazon-{$asin}-ajax-page{$page}-raw.json", $response);
        $this->logToFile($logFile, "💾 Saved raw JSON to amazon-{$asin}-ajax-page{$page}-raw.json");

        // 4) Decode JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logToFile(
                $logFile,
                "❌ JSON decode error on page {$page}: " . json_last_error_msg()
            );
            break;
        }

        // 5) Extract the HTML fragment
        $htmlFragment = $data['reviewsHtml'] 
                     ?? $data['html'] 
                     ?? $data['results'] 
                     ?? '';
        if (! $htmlFragment) {
            $this->logToFile($logFile, "⚠️ No HTML fragment returned on page {$page}, stopping");
            break;
        }

        // 6) Dump fragment for inspection
        file_put_contents(
            "{$debugDir}/amazon-{$asin}-ajax-page{$page}-fragment.html",
            $htmlFragment
        );
        $this->logToFile(
            $logFile,
            "💾 Saved HTML fragment to amazon-{$asin}-ajax-page{$page}-fragment.html"
        );

        // 7) Parse out individual reviews
        $pageReviews = $this->parseReviewsWithRegex($htmlFragment, $asin);
        $found = count($pageReviews);
        $this->logToFile(
            $logFile,
            "✔️ Parsed {$found} reviews from AJAX page {$page}"
        );

        if ($found === 0) {
            break;
        }

        $reviews = array_merge($reviews, $pageReviews);
        $page++;
    }

    $this->logToFile(
        $logFile,
        "✅ Finished AJAX scrape—total reviews collected: " . count($reviews)
    );

    return array_slice($reviews, 0, $limit);
}









    /**
     * Fallback: pull average rating & count from the same first page.
     */
    private function getAggregateRating(string $asin): ?array
    {
        $url     = "https://{$this->domain}/product-reviews/{$asin}?pageNumber=1";
        $html    = $this->makeRequest($url);
        $logFile = "{$this->dbgDir}/scrape-log.txt";

        $this->logToFile($logFile, "📊 Fetching aggregate from {$url}");
        file_put_contents(
            "{$this->dbgDir}/amazon-{$asin}-aggregate-raw.html",
            $html
        );

        if (! $html) {
            $this->logToFile($logFile, "❌ No HTML for aggregate—giving up");
            return null;
        }

        // Extract average
        $avg = null;
        if (preg_match(
            '/data-hook="acr-average"[^>]*>\s*([\d.]+)\s+out of 5 stars/i',
            $html, $m
        )) {
            $avg = (float)$m[1];
        }

        // Extract count
        $count = 0;
        if (preg_match(
            '/data-hook="acr-secondary-review-count"[^>]*>\s*([\d,]+)\s+ratings?/i',
            $html, $m
        )) {
            $count = (int) str_replace(',', '', $m[1]);
        }

        if ($avg === null) {
            $this->logToFile($logFile, "❌ Could not parse aggregate rating");
            return null;
        }

        $this->logToFile(
            $logFile,
            "✔️ Aggregate rating {$avg}/5 from {$count} reviews"
        );

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

    /**
     * Simple regex parser for <div data-hook="review"> blocks.
     */
    private function parseReviewsWithRegex(string $html, string $asin): array
    {
        $reviews = [];
        $logFile = "{$this->dbgDir}/parse-debug.txt";

        $this->logToFile($logFile, "🔍 Running regex parse for ASIN {$asin}");
        $pattern = '/<div[^>]+data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>(?:\s*<\/div>)?/is';

        if (preg_match_all($pattern, $html, $blocks, PREG_SET_ORDER)) {
            $this->logToFile(
                $logFile,
                "✔️ Found " . count($blocks) . " review blocks"
            );
            foreach ($blocks as $i => $blk) {
                $b = $blk[1];

                // Reviewer name
                $name = 'Unknown';
                if (preg_match(
                    '/<span[^>]+class="a-profile-name"[^>]*>([^<]+)<\/span>/i',
                    $b, $m
                )) {
                    $name = trim($m[1]);
                }
                if (stripos($name, 'Amazon Aggregate') !== false) {
                    continue;
                }

                // Rating
                $rating = 0.0;
                if (preg_match('/([\d.]+)\s+out of 5 stars/i', $b, $m)) {
                    $rating = (float)$m[1];
                }
                if ($rating <= 0) {
                    continue;
                }

                // Date
                $date = date('Y-m-d');
                if (preg_match(
                    '/data-hook="review-date"[^>]*>([^<]+)<\/span>/i',
                    $b, $m
                )) {
                    if ($ts = strtotime(trim($m[1]))) {
                        $date = date('Y-m-d', $ts);
                    }
                }

                // Text
                $text = '';
                if (preg_match(
                    '/<span[^>]+data-hook="review-body"[^>]*>(.*?)<\/span>/is',
                    $b, $m
                )) {
                    $text = trim(strip_tags($m[1]));
                }
                if ($text === '') {
                    continue;
                }

                // Affiliate link
                $link = "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}";

                $this->logToFile(
                    $logFile,
                    "SNIPPET {$i}: “" . mb_substr($text, 0, 60) . "…”"
                );

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
            $this->logToFile($logFile, "⚠️ Regex parse found 0 blocks");
        }

        return $reviews;
    }



    /**
 * Append a timestamped line to a debug file.
 *
 * @param string $file Full path to the debug file.
 * @param string $line The text to append.
 * @return void
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
