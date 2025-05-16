<?php
/**
 * Amazon Review Fetcher
 *
 * Fetches reviews from Amazon using public review listing pages and a robust regex parser.
 */

namespace Services\ReviewFetcher;

use PDO;

class AmazonReviewFetcher extends AbstractReviewFetcher
{
    /** @var string Affiliate Tag for Amazon URLs */
    private string $affiliateTag;

    /** @var string Amazon domain, default to UK */
    private string $domain = 'amazon.co.uk';

    public function __construct(PDO $db, int $sourceId)
    {
        parent::__construct($db, $sourceId, 'Amazon');
        $this->affiliateTag = getenv('AMAZON_ASSOCIATE_TAG') ?: 'storiesfro0f0-20';

        // Optional override from settings
        $stmt = $db->prepare(
            "SELECT setting_value 
             FROM settings 
             WHERE setting_name='amazon_domain'"
        );
        if ($stmt->execute() && ($d = $stmt->fetchColumn())) {
            $this->domain = $d;
        }

        // Ensure debug directory exists
        $dbg = __DIR__ . '/debug';
        if (! is_dir($dbg)) {
            mkdir($dbg, 0755, true);
        }
    }

    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Main entry: fetch reviews by ISBN
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array
    {
        // Turn ISBN‐13 or ISBN‐10 into the ASIN (ISBN‐10)
        $data = $this->standardizeISBN($isbn);
        $asin = $data['isbn'] ?: $data['isbn13'] ?? null;
        if (! $asin) {
            $this->lastError = "Invalid ISBN: {$isbn}";
            return [];
        }

        // Scrape individual reviews
        $reviews = $this->scrapeReviews($asin, $limit);

        // Fallback to aggregate if none found
        if (empty($reviews)) {
            $agg = $this->getAggregateRating($asin);
            if ($agg) {
                $reviews = [ $agg ];
            }
        }

        return $reviews;
    }

    /**
     * Get an aggregate “average” review by parsing the reviews page
     */
    private function getAggregateRating(string $asin): ?array
    {
        $url  = "https://{$this->domain}/product-reviews/{$asin}?pageNumber=1";
        $html = $this->makeRequest($url);
        if (! $html) {
            return null;
        }

        // Average rating
        $avg = null;
        if (preg_match(
            '/data-hook="acr-average"[^>]*>\s*([\d.]+)\s+out of 5 stars/i',
            $html, $m
        )) {
            $avg = (float)$m[1];
        }

        // Total ratings
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
                'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'is_aggregate'  => true,
                'ratings_count' => $count,
            ]),
        ];
    }

    /**
     * Scrape up to $limit reviews, page by page
     */
    private function scrapeReviews(string $asin, int $limit): array
    {
        $reviews       = [];
        $page          = 1;
        $useMobileSite = false;

        while (count($reviews) < $limit) {
            // Build desktop or mobile listing URL
            $url = $useMobileSite
                ? "https://{$this->domain}/gp/aw/review-listing/{$asin}?pageNumber={$page}"
                : "https://{$this->domain}/product-reviews/{$asin}?pageNumber={$page}";

            // Fetch HTML
            $html = $this->makeRequest($url);
            if (! $html) {
                break;
            }

            // Detect bot‐block/CAPTCHA
            if (preg_match('/captcha|robot check/i', $html)) {
                if (! $useMobileSite) {
                    // try mobile next
                    $useMobileSite = true;
                    continue;
                }
                // both blocked → give up
                break;
            }

            // Parse reviews
            $pageReviews = $this->parseReviewsWithRegex($html, $asin);
            if (empty($pageReviews)) {
                break;
            }

            $reviews = array_merge($reviews, $pageReviews);
            $page++;
        }

        // Trim to the requested limit
        return array_slice($reviews, 0, $limit);
    }

    /**
     * Extract each <div data-hook="review">…</div> block via regex
     */
    private function parseReviewsWithRegex(string $html, string $asin): array
    {
        $reviews  = [];
        $pattern  = '/<div[^>]+data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>/is';

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

                // Rating (e.g. “4.0 out of 5 stars”)
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

                // Date
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

                // Body text
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

                // Build review record
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
