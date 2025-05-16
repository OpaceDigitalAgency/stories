<?php
/**
 * Abstract Review Fetcher
 *
 * This abstract class provides common functionality for all review fetchers.
 */

namespace Services\ReviewFetcher;

use PDO;

abstract class AbstractReviewFetcher implements ReviewFetcherInterface {
    /**
     * @var PDO Database connection
     */
    protected $db;

    /**
     * @var int Source ID in the database
     */
    protected $sourceId;

    /**
     * @var string Source name
     */
    protected $sourceName;

    /**
     * @var string|null Last error message
     */
    protected $lastError = null;

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     * @param string $sourceName Source name
     */
    public function __construct(PDO $db, int $sourceId, string $sourceName) {
        $this->db = $db;
        $this->sourceId = $sourceId;
        $this->sourceName = $sourceName;
    }

    /**
     * Get the source ID for this fetcher
     *
     * @return int The ID of the review source in the database
     */
    public function getSourceId(): int {
        return $this->sourceId;
    }

    /**
     * Get the name of the review source
     *
     * @return string The name of the review source
     */
    public function getSourceName(): string {
        return $this->sourceName;
    }

    /**
     * Get the last error message
     *
     * @return string|null The last error message or null if no error occurred
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }

    /**
     * Normalize a rating to a 0-1 scale
     *
     * @param float $value The rating value
     * @param float $scale The maximum possible rating
     * @return float The normalized rating (0-1)
     */
    protected function normalizeRating(float $value, float $scale): float {
        if (empty($value) || empty($scale) || $scale == 0) {
            return 0;
        }
        return min(1, max(0, $value / $scale));
    }

    /**
     * Format a review date to Y-m-d format
     *
     * @param string $date The date string
     * @return string|null The formatted date or null if invalid
     */
    protected function formatDate(string $date): ?string {
        try {
            $dateObj = new \DateTime($date);
            return $dateObj->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Clean and format review text
     *
     * @param string $text The review text
     * @return string The cleaned and formatted text
     */
    protected function cleanText(string $text): string {
        // Remove HTML tags
        $text = strip_tags($text);

        // Convert entities to characters
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim whitespace
        $text = trim($text);

        return $text;
    }

    /**
     * Make an HTTP request with error handling
     *
     * @param string $url The URL to request
     * @param array $options Additional cURL options
     * @param bool $throttle Whether to throttle the request
     * @return string|false The response body or false on failure
     */
    protected function makeRequest(string $url, array $options = [], bool $throttle = true): string|false {
        // Throttle requests to avoid being blocked
        if ($throttle) {
            // Random delay between 1-3 seconds
            usleep(rand(1000000, 3000000));
        }

        $ch = curl_init($url);

        // Modern User-Agent strings
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1'
        ];

        // Set default options
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => $userAgents[array_rand($userAgents)],
            CURLOPT_ENCODING => '', // Accept all encodings
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'Cache-Control: max-age=0'
            ],
            CURLOPT_COOKIEJAR => '/tmp/cookies.txt',
            CURLOPT_COOKIEFILE => '/tmp/cookies.txt',
        ]);

        // Add custom options
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }

        // Execute the request
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            $this->lastError = curl_error($ch);
            curl_close($ch);
            return false;
        }

        // Get HTTP status code
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close the connection
        curl_close($ch);

        // Check for HTTP errors
        if ($statusCode >= 400) {
            $this->lastError = "HTTP Error: $statusCode";
            return false;
        }

        // Check for CAPTCHA or robot check pages
        if (stripos($response, 'captcha') !== false ||
            stripos($response, 'robot check') !== false ||
            stripos($response, 'security challenge') !== false ||
            stripos($response, 'verify you are a human') !== false) {
            $this->lastError = "CAPTCHA or robot check detected. Try again later or use a different IP address.";
            return false;
        }

        return $response;
    }

    /**
     * Standardize ISBN format
     *
     * @param string $isbn The ISBN to standardize
     * @return array Array with 'isbn' and 'isbn13' keys
     */
    protected function standardizeISBN(string $isbn): array {
        // Remove all non-alphanumeric characters
        $isbn = preg_replace('/[^0-9X]/i', '', $isbn);

        // Check if it's ISBN-13
        if (strlen($isbn) == 13) {
            return [
                'isbn' => '',
                'isbn13' => $isbn
            ];
        }

        // Check if it's ISBN-10
        if (strlen($isbn) == 10) {
            // Convert ISBN-10 to ISBN-13
            $isbn13 = '978' . substr($isbn, 0, 9);
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int)$isbn13[$i] * (($i % 2 == 0) ? 1 : 3);
            }
            $checkDigit = (10 - ($sum % 10)) % 10;
            $isbn13 .= $checkDigit;

            return [
                'isbn' => $isbn,
                'isbn13' => $isbn13
            ];
        }

        // Invalid ISBN
        return [
            'isbn' => $isbn,
            'isbn13' => ''
        ];
    }
}
