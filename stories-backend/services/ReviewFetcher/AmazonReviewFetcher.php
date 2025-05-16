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
    /** Affiliate Tag for Amazon URLs */
    private string $affiliateTag;
    /** Amazon domain, default uk */
    private string $domain = 'amazon.co.uk';

    public function __construct(PDO $db, int $sourceId)
    {
        parent::__construct($db, $sourceId, 'Amazon');
        $this->affiliateTag = getenv('AMAZON_ASSOCIATE_TAG') ?: 'storiesfro0f0-20';
        // optional override from settings
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name='amazon_domain'");
        if ($stmt->execute() && $d = $stmt->fetchColumn()) {
            $this->domain = $d;
        }
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array
    {
        $data = $this->standardizeISBN($isbn);
        $use = $data['isbn13'] ?: $data['isbn'];
        $url = $this->findProductUrl($use);
        if (!$url) {
            $this->lastError = "No book found on Amazon for ISBN: {$use}";
            return [];
        }
        if (!preg_match('/\/dp\/([A-Z0-9]{10})/', $url, $m)) {
            $this->lastError = "Failed to extract ASIN from product URL";
            return [];
        }
        $asin = $m[1];
        $reviews = $this->scrapeReviews($asin, $limit);
        if (empty($reviews)) {
            $agg = $this->getAggregateRating($asin);
            if ($agg) {
                $reviews = [$agg];
            }
        }
        return $reviews;
    }



/**
 * Convert a cleaned ISBN to an ASIN (ISBN-10).
 */
private function convertISBNtoASIN(string $isbn): ?string
{
    $clean = preg_replace('/[^0-9X]/i','',$isbn);
    // If it's already 10 chars assume ASIN
    if (strlen($clean) === 10) {
        return $clean;
    }
    // If it's a 13-digit ISBN-13 starting 978, build ISBN-10
    if (strlen($clean) === 13 && strpos($clean, '978') === 0) {
        $digits = substr($clean, 3, 9);
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int)$digits[$i]) * (10 - $i);
        }
        $check = 11 - ($sum % 11);
        if ($check === 10) $check = 'X';
        elseif ($check === 11) $check = 0;
        return $digits . $check;
    }
    return null;
}






    private function findProductUrl(string $isbn): ?string
    {
        $asin = $this->convertISBNtoASIN($isbn);
        if ($asin) {
            $test = "https://{$this->domain}/dp/{$asin}";
            $r = $this->makeRequest($test);
            if ($r && !str_contains($r, 'Page Not Found')) {
                return $test;
            }
        }
        $search = "https://{$this->domain}/s?k={$isbn}&i=stripbooks";
        $r = $this->makeRequest($search);
        if ($r && preg_match('/\/dp\/([A-Z0-9]{10})/', $r, $m)) {
            return "https://{$this->domain}/dp/{$m[1]}";
        }
        return null;
    }

    private function getAggregateRating(string $asin): ?array
    {
        $url = "https://{$this->domain}/product-reviews/{$asin}?pageNumber=1";
        $html = $this->makeRequest($url);
        if (!$html) {
            return null;
        }
        // parse average rating
        $avg = null;
        if (preg_match('/data-hook="acr-average"[^>]*>([0-9\.]+) out of 5 stars/i', $html, $m)) {
            $avg = (float)$m[1];
        }
        // parse count
        $count = 0;
        if (preg_match('/data-hook="acr-secondary-review-count"[^>]*>([0-9,]+) ratings?/i', $html, $m)) {
            $count = (int) str_replace(',', '', $m[1]);
        }
        if ($avg === null) {
            return null;
        }
        return [
            'source_id'       => $this->sourceId,
            'reviewer_name'   => 'Amazon Aggregate',
            'review_date'     => date('Y-m-d'),
            'original_rating' => "{$avg}/5",
            'rating_value'    => $avg,
            'rating_scale'    => 5,
            'rating_normalised' => $this->normalizeRating($avg, 5),
            'review_text'     => "Average rating {$avg}/5 based on {$count} ratings on Amazon.",
            'metadata'        => json_encode([
                'asin'          => $asin,
                'review_url'    => $url,
                'affiliate_url' => "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}",
                'is_aggregate'  => true,
                'ratings_count' => $count,
            ]),
        ];
    }

    private function scrapeReviews(string $asin, int $limit): array
    {
        $reviews = [];
        $page = 1;
        while (count($reviews) < $limit) {
            $url = "https://{$this->domain}/product-reviews/{$asin}?pageNumber={$page}";
            $html = $this->makeRequest($url);
            if (!$html) {
                break;
            }
            // detect CAPTCHA
            if (preg_match('/captcha|robot check/i', $html)) {
                // try mobile fallback
                $url = "https://{$this->domain}/gp/aw/review-listing/{$asin}?pageNumber={$page}";
                $html = $this->makeRequest($url);
                if (!$html || preg_match('/captcha|robot check/i', $html)) {
                    break;
                }
            }
            $pageReviews = $this->parseReviewsWithRegex($html, $asin);
            if (empty($pageReviews)) {
                break;
            }
            $reviews = array_merge($reviews, $pageReviews);
            $page++;
        }
        return array_slice($reviews, 0, $limit);
    }

    private function parseReviewsWithRegex(string $html, string $asin): array
    {
        $reviews = [];
        $pattern = '/<div[^>]+data-hook="review"[^>]*>(.*?)<\/div>\s*<\/div>/is';
        preg_match_all($pattern, $html, $blocks, PREG_SET_ORDER);
        foreach ($blocks as $blk) {
            $b = $blk[1];
            // name
            $name = 'Unknown';
            if (preg_match('/<span[^>]+class="a-profile-name"[^>]*>([^<]+)<\/span>/i', $b, $m)) {
                $name = trim($m[1]);
            }
            if (stripos($name, 'Amazon Aggregate') !== false) {
                continue;
            }
            // rating
            $rating = 0;
            if (preg_match('/([0-9]\.\d|[0-5])\s+out of 5 stars/i', $b, $m)) {
                $rating = (float)$m[1];
            }
            // date
            $date = date('Y-m-d');
            if (preg_match('/data-hook="review-date"[^>]*>([^<]+)<\/span>/i', $b, $m)) {
                $d = strtotime(trim($m[1]));
                if ($d) $date = date('Y-m-d', $d);
            }
            // body
            $text = '';
            if (preg_match('/<span[^>]+data-hook="review-body"[^>]*>(.*?)<\/span>/is', $b, $m)) {
                $text = trim(strip_tags($m[1]));
            }
            if (empty($text) || $rating <= 0) continue;
            // affiliate link
            $link = "https://{$this->domain}/dp/{$asin}?tag={$this->affiliateTag}";
            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $name,
                'review_date'   => $date,
                'original_rating' => "{$rating}/5",
                'rating_value'  => $rating,
                'rating_scale'  => 5,
                'rating_normalised' => $this->normalizeRating($rating, 5),
                'review_text'   => $text,
                'metadata'      => json_encode([
                    'asin'          => $asin,
                    'affiliate_url' => $link,
                ]),
            ];
        }
        return $reviews;
    }
}
