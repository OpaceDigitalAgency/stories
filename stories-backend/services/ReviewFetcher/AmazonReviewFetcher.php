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

    public function __construct(PDO $db, int $sourceId)
    {
        parent::__construct($db, $sourceId, 'Amazon');

        // Associates tag
        $this->affiliateTag = getenv('AMAZON_ASSOCIATE_TAG') ?: 'storiesfro0f0-20';

        // Optional override from settings
        $stmt = $db->prepare(
            "SELECT setting_value
               FROM settings
              WHERE setting_name = 'amazon_domain'"
        );
        if ($stmt->execute() && ($d = $stmt->fetchColumn())) {
            $this->domain = preg_match('/^www\./', $d) ? $d : 'www.' . $d;
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

        // 4) Scrape real reviews
        $reviews = $this->scrapeReviews($asin, $limit);

        // 5) If none found, fallback to aggregate “average” review
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
            return $digits . $check;
        }
        return null;
    }

    /**
     * Override makeRequest to match parent signature and use persistent cookies.
     */
    protected function makeRequest(string $url, array $options = [], bool $throttle = true): string|false
    {
        $ch = curl_init($url);

        $default = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
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

        // user options override defaults
        curl_setopt_array($ch, $options + $default);

        $html = curl_exec($ch);
        curl_close($ch);
        return $html === false ? false : $html;
    }

    /**
     * Scrape up to $limit reviews via AJAX → mobile fallback.
     */
    protected function scrapeReviews(string $asin, int $limit): array
    {
        $reviews = [];
        $page    = 1;
        $logFile = "{$this->dbgDir}/scrape-log.txt";

        // 0) Pre-fetch product page so cookie+bot-check passes
        $this->logToFile($logFile, "🌐 Pre-fetch product page for ASIN {$asin}");
        $prodHtml = $this->makeRequest("https://{$this->domain}/dp/{$asin}");
        file_put_contents("{$this->dbgDir}/amazon-{$asin}-product-raw.html", $prodHtml);

        // 1) Try AJAX loop
        $this->logToFile($logFile, "▶️ Starting AJAX scrape for ASIN {$asin}, limit={$limit}");
        while (count($reviews) < $limit) {
            $ajax = sprintf(
                'https://%s/hz/reviews-render/ajax/reviews/get'
              . '?asin=%s&pageNumber=%d&reviewerType=all_reviews&formatType=current_format',
                $this->domain, $asin, $page
            );
            $this->logToFile($logFile, "🌐 Fetch AJAX page {$page}");
            $raw = $this->makeRequest($ajax, [
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json, text/javascript, */*; q=0.01',
                    'Accept-Language: en-GB,en;q=0.9',
                    'Referer: https://' . $this->domain . '/dp/' . $asin,
                    'X-Requested-With: XMLHttpRequest',
                ]
            ]);
            file_put_contents("{$this->dbgDir}/amazon-{$asin}-ajax-page{$page}-raw.json", $raw);

            if (! $raw) {
                $this->logToFile($logFile, "❌ Empty AJAX response");
                break;
            }
            $payload = json_decode($raw, true);
            if (!is_array($payload) || !isset($payload['html'])) {
                $this->logToFile($logFile, "⚠️ Invalid AJAX JSON");
                break;
            }

            $frag = $payload['html'];
            file_put_contents("{$this->dbgDir}/amazon-{$asin}-ajax-page{$page}-fragment.html", $frag);

            $parsed = $this->parseReviewsWithRegex($frag, $asin);
            $this->logToFile($logFile, "✔️ Parsed " . count($parsed) . " reviews via AJAX");
            if (empty($parsed)) {
                break;
            }
            $reviews = array_merge($reviews, $parsed);
            $page++;
        }

        // 2) If AJAX yielded nothing, try mobile review-listing
        if (empty($reviews)) {
            $this->logToFile($logFile, "⚠️ AJAX empty → trying mobile site");
            $page = 1;
            while (count($reviews) < $limit) {
                $url = "https://{$this->domain}/gp/aw/review-listing/{$asin}?pageNumber={$page}";
                $this->logToFile($logFile, "🌐 Fetch mobile page {$page}");
                $html = $this->makeRequest($url, [
                    CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)'
                                      . ' AppleWebKit/605.1.15 (KHTML, like Gecko)'
                                      . ' Version/15.0 Mobile/15E148 Safari/604.1',
                ]);
                file_put_contents("{$this->dbgDir}/amazon-{$asin}-mobile-page{$page}-raw.html", $html);

                if (! $html || preg_match('/captcha|robot check/i', $html)) {
                    $this->logToFile($logFile, "❌ Blocked on mobile page {$page}");
                    break;
                }

                $parsed = $this->parseReviewsWithRegex($html, $asin);
                $this->logToFile($logFile, "✔️ Parsed " . count($parsed) . " reviews via mobile");
                if (empty($parsed)) {
                    break;
                }
                $reviews = array_merge($reviews, $parsed);
                $page++;
            }
        }

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
        file_put_contents("{$this->dbgDir}/amazon-{$asin}-aggregate-raw.html", $html);

        if (! $html) {
            $this->logToFile($logFile, "❌ No HTML for aggregate");
            return null;
        }

        // Extract average
        if (!preg_match('/data-hook="acr-average"[^>]*>\s*([\d.]+)\s+out of 5 stars/i', $html, $m)) {
            $this->logToFile($logFile, "❌ Could not parse aggregate rating");
            return null;
        }
        $avg = (float)$m[1];

        // Extract count
        $count = 0;
        if (preg_match(
            '/data-hook="acr-secondary-review-count"[^>]*>\s*([\d,]+)\s+ratings?/i',
            $html, $m
        )) {
            $count = (int)str_replace(',', '', $m[1]);
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

    /**
     * Simple regex parser for <div data-hook="review"> blocks.
     */
    private function parseReviewsWithRegex(string $html, string $asin): array
    {
        $reviews = [];
        $logFile = "{$this->dbgDir}/parse-debug.txt";
        $this->logToFile($logFile, "🔍 Regex parse for ASIN {$asin}");

        $pattern = '/<div[^>]+data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>(?:\s*<\/div>)?/is';
        if (preg_match_all($pattern, $html, $blocks, PREG_SET_ORDER)) {
            $this->logToFile($logFile, "✔️ Found " . count($blocks) . " blocks");
            foreach ($blocks as $blk) {
                $b = $blk[1];

                // Name
                $name = 'Unknown';
                if (preg_match('/<span[^>]+class="a-profile-name"[^>]*>([^<]+)<\/span>/i',$b,$m)) {
                    $name = trim($m[1]);
                }
                if (stripos($name,'Amazon Aggregate')!==false) {
                    continue;
                }

                // Rating
                if (!preg_match('/([\d.]+)\s+out of 5 stars/i',$b,$m)) {
                    continue;
                }
                $rating = (float)$m[1];

                // Date
                $date = date('Y-m-d');
                if (preg_match('/data-hook="review-date"[^>]*>([^<]+)<\/span>/i',$b,$m)) {
                    if ($ts = strtotime(trim($m[1]))) {
                        $date = date('Y-m-d',$ts);
                    }
                }

                // Text
                if (!preg_match('/<span[^>]+data-hook="review-body"[^>]*>(.*?)<\/span>/is',$b,$m)) {
                    continue;
                }
                $text = trim(strip_tags($m[1]));
                if ($text==='') continue;

                $link = "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}";

                $this->logToFile($logFile, "SNIP: " . mb_substr($text,0,60) . "…");

                $reviews[] = [
                    'source_id'         => $this->sourceId,
                    'reviewer_name'     => $name,
                    'review_date'       => $date,
                    'original_rating'   => "{$rating}/5",
                    'rating_value'      => $rating,
                    'rating_scale'      => 5,
                    'rating_normalised' => $this->normalizeRating($rating,5),
                    'review_text'       => $text,
                    'metadata'          => json_encode([
                        'asin'          => $asin,
                        'affiliate_url' => $link,
                    ]),
                ];
            }
        } else {
            $this->logToFile($logFile, "⚠️ Regex found 0 blocks");
        }

        return $reviews;
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
