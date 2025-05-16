<?php
/**
 * Amazon Review Fetcher
 *
 * Fetches real user reviews from Amazon's public review pages,
 * and falls back to an aggregate "average" review if needed.
 */

namespace Services\ReviewFetcher;

use PDO;

class AmazonReviewFetcher extends AbstractReviewFetcher
{
    /** @var string Your Amazon Associates tag */
    private string $affiliateTag;

    /** @var string Which Amazon domain to use */
    private string $domain = 'amazon.co.uk';

    public function __construct(PDO $db, int $sourceId)
    {
        parent::__construct($db, $sourceId, 'Amazon');
        $this->affiliateTag = getenv('AMAZON_ASSOCIATE_TAG') ?: 'storiesfro0f0-20';

        // Optional override from settings table
        $stmt = $db->prepare("
            SELECT setting_value
            FROM settings
            WHERE setting_name = 'amazon_domain'
        ");
        if ($stmt->execute() && ($d = $stmt->fetchColumn())) {
            $this->domain = $d;
        }

        // Ensure debug directory exists
        $dbg = __DIR__ . '/debug';
        if (!is_dir($dbg)) {
            mkdir($dbg, 0755, true);
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
        // 1) Clean ISBN string
        $clean = preg_replace('/[^0-9X]/i', '', $isbn);

        // 2) Convert to ISBN-10 / ASIN
        $asin = $this->convertISBNtoASIN($clean);

        // 3) If conversion failed, fallback to standardizeISBN()
        if (! $asin) {
            $data = $this->standardizeISBN($isbn);
            $asin = $data['isbn'] ?: $data['isbn13'] ?? null;
        }

        if (! $asin) {
            $this->lastError = "Could not derive ASIN from ISBN {$isbn}";
            return [];
        }

        // 4) Scrape individual reviews
        $reviews = $this->scrapeReviews($asin, $limit);

        // 5) If none found, get an aggregate "average" review
        if (empty($reviews)) {
            $agg = $this->getAggregateRating($asin);
            if ($agg) {
                $reviews = [ $agg ];
            }
        }

        return $reviews;
    }

    /**
     * Convert ISBN-10 or ISBN-13 (starting with 978) into the ASIN (ISBN-10).
     */
    private function convertISBNtoASIN(string $isbn): ?string
    {
        // If already 10 chars, assume it's the ASIN
        if (strlen($isbn) === 10) {
            return $isbn;
        }

        // If 13-digit starting 978, compute ISBN-10
        if (strlen($isbn) === 13 && substr($isbn, 0, 3) === '978') {
            $digits = substr($isbn, 3, 9);
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += ((int)$digits[$i]) * (10 - $i);
            }
            $check = 11 - ($sum % 11);
            if ($check === 10) {
                $check = 'X';
            } elseif ($check === 11) {
                $check = '0';
            }
            return $digits . $check;
        }

        return null;
    }

    /**
     * Scrape up to $limit reviews page by page.
     */
    private function scrapeReviews(string $asin, int $limit): array
    {
        $reviews       = [];
        $page          = 1;
        $useMobileSite = false;

        while (count($reviews) < $limit) {
            // Build the desktop or mobile reviews URL
            $url = $useMobileSite
                ? "https://www.{$this->domain}/gp/aw/review-listing/{$asin}?pageNumber={$page}"
                : "https://www.{$this->domain}/product-reviews/{$asin}?pageNumber={$page}";

            // Fetch HTML
            $html = $this->makeRequest($url);
            if (! $html) {
                break;
            }

            // Detect CAPTCHA/robot check
            if (preg_match('/captcha|robot check/i', $html)) {
                if (! $useMobileSite) {
                    // Try the mobile review page next
                    $useMobileSite = true;
                    continue;
                }
                // Still blocked on mobile → abort
                break;
            }

            // Parse out real individual review blocks
            $pageReviews = $this->parseReviewsWithRegex($html, $asin);
            if (empty($pageReviews)) {
                break;
            }

            $reviews = array_merge($reviews, $pageReviews);
            $page++;
        }

        // Trim to limit
        return array_slice($reviews, 0, $limit);
    }

    /**
     * If no real reviews, parse an aggregate average review.
     */
    private function getAggregateRating(string $asin): ?array
    {
        $url  = "https://www.{$this->domain}/product-reviews/{$asin}?pageNumber=1";
        $html = $this->makeRequest($url);
        if (! $html) {
            return null;
        }

        // Extract average rating: <span data-hook="acr-average">4.5 out of 5 stars</span>
        $avg = null;
        if (preg_match(
            '/data-hook="acr-average"[^>]*>\s*([\d.]+)\s+out of 5 stars/i',
            $html, $m
        )) {
            $avg = (float)$m[1];
        }

        // Extract total ratings: <span data-hook="acr-secondary-review-count">123 ratings</span>
        $count = 0;
        if (preg_match(
            '/data-hook="acr-secondary-review-count"[^>]*>\s*([\d,]+)\s+ratings?/i',
            $html, $m
        )) {
            $count = (int)str_replace(',', '', $m[1]);
        }

        if ($avg === null) {
            return null;
        }

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
                'affiliate_url' => "https://www.{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'is_aggregate'  => true,
                'ratings_count' => $count,
            ]),
        ];
    }

    /**
     * Regex‐based parser for individual review blocks.
     */
    private function parseReviewsWithRegex(string $html, string $asin): array
    {
        $reviews = [];
        $pattern = '/<div[^>]+data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>(?:\s*<\/div>)?/is';

        if (preg_match_all($pattern, $html, $blocks, PREG_SET_ORDER)) {
            foreach ($blocks as $blk) {
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

                // Rating e.g. “4.0 out of 5 stars”
                $rating = 0.0;
                if (preg_match(
                    '/([\d.]+)\s+out of 5 stars/i',
                    $b, $m
                )) {
                    $rating = (float)$m[1];
                }
                if ($rating <= 0) {
                    continue;
                }

                // Review date
                $date = date('Y-m-d');
                if (preg_match(
                    '/data-hook="review-date"[^>]*>([^<]+)<\/span>/i',
                    $b, $m
                )) {
                    $ts = strtotime(trim($m[1]));
                    if ($ts) {
                        $date = date('Y-m-d', $ts);
                    }
                }

                // Review text
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
                $link = "https://www.{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}";

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
        }

        return $reviews;
    }
}
