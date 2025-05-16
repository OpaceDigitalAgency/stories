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
        // Set up error log file
        $debugDir = __DIR__ . '/debug';
        $logFile = $debugDir . '/scrape-log.txt';

        // Create debug directory with proper permissions
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0777, true);
        }

        // Make sure the directory is readable and writable by the web server
        chmod($debugDir, 0777);

        // Log the request
        $this->logToFile($logFile, "🌐 Making request to: {$url}");

        // Enhanced throttling to avoid being blocked
        if ($throttle) {
            // Use a more variable delay pattern
            // For Amazon and other sites with strong anti-scraping, use longer delays
            if (stripos($url, 'amazon') !== false) {
                // Amazon: Random delay between 2-5 seconds
                $delay = rand(2000000, 5000000);
            } else {
                // Other sites: Random delay between 1-3 seconds
                $delay = rand(1000000, 3000000);
            }

            // Add some jitter to make the pattern less predictable
            $jitter = rand(-500000, 500000);
            $delay = max(500000, $delay + $jitter);

            $delaySeconds = $delay / 1000000;
            $this->logToFile($logFile, "⏱️ Throttling request for {$delaySeconds} seconds");
            usleep($delay);

            // Add a small chance of a longer pause (simulates human behavior)
            if (rand(1, 20) === 1) {  // 5% chance
                $longPause = rand(3, 8);
                $this->logToFile($logFile, "🕒 Adding a longer pause of {$longPause} seconds (simulating human behavior)");
                sleep($longPause);
            }
        }

        $ch = curl_init($url);

        // Expanded list of modern User-Agent strings
        $userAgents = [
            // Desktop browsers
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.0.0',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
            // Mobile browsers
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 13; SM-S901B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36'
        ];

        // Select a random user agent
        $userAgent = $userAgents[array_rand($userAgents)];
        $this->logToFile($logFile, "🧩 Using User-Agent: {$userAgent}");

        // Set default options with improved anti-detection measures
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_ENCODING => '', // Accept all encodings
            CURLOPT_SSL_VERIFYPEER => true, // Verify SSL certificates
            CURLOPT_SSL_VERIFYHOST => 2,    // Verify hostname
            CURLOPT_MAXREDIRS => 5,         // Maximum number of redirects to follow
            CURLOPT_AUTOREFERER => true,    // Set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 15,   // Connection timeout
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Use HTTP/1.1

            // Randomize the order of headers to avoid fingerprinting
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'Cache-Control: max-age=0',
                'Accept-Encoding: gzip, deflate, br'
            ],

            // Use a unique cookie file for each request to avoid tracking
            CURLOPT_COOKIEJAR => "{$debugDir}/cookies-" . time() . '-' . rand(1000, 9999) . '.txt',
            CURLOPT_COOKIEFILE => "{$debugDir}/cookies-" . time() . '-' . rand(1000, 9999) . '.txt',
        ]);

        // Add custom options
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }

        // Execute the request
        $this->logToFile($logFile, "⏳ Executing request...");
        $response = curl_exec($ch);

        // Check for errors
        if ($response === false) {
            $error = curl_error($ch);
            $this->lastError = $error;
            $this->logToFile($logFile, "❌ cURL Error: {$error}");
            curl_close($ch);
            return false;
        }

        // Get HTTP status code and other info
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

        $this->logToFile($logFile, "✅ Request completed in {$totalTime}s, Status: {$statusCode}, Size: {$size} bytes, Type: {$contentType}");

        // Close the connection
        curl_close($ch);

        // Check for HTTP errors
        if ($statusCode >= 400) {
            $this->lastError = "HTTP Error: $statusCode";
            $this->logToFile($logFile, "❌ HTTP Error: {$statusCode}");
            return false;
        }

        // Check for CAPTCHA or robot check pages - more comprehensive detection
        if (stripos($response, 'captcha') !== false ||
            stripos($response, 'robot check') !== false ||
            stripos($response, 'security challenge') !== false ||
            stripos($response, 'verify you are a human') !== false ||
            stripos($response, 'type the characters') !== false ||
            stripos($response, 'enter the characters') !== false ||
            stripos($response, 'automated access') !== false ||
            stripos($response, 'suspicious activity') !== false ||
            stripos($response, 'unusual traffic') !== false ||
            (stripos($response, 'sorry') !== false && stripos($response, 'temporary issue') !== false) ||
            (strlen($response) < 5000 && stripos($response, 'amazon') !== false && stripos($response, 'please') !== false)) {

            $this->lastError = "CAPTCHA or robot check detected. Try again later or use a different IP address.";
            $this->logToFile($logFile, "⚠️ CAPTCHA or robot check detected!");

            // Save the CAPTCHA page for debugging
            $debugDir = __DIR__ . '/debug';
            if (!is_dir($debugDir)) {
                mkdir($debugDir, 0777, true);
            }
            // Make sure the directory is readable and writable
            chmod($debugDir, 0777);

            $captchaFile = $debugDir . '/captcha-' . time() . '.html';
            file_put_contents($captchaFile, $response);
            chmod($captchaFile, 0666);
            $this->logToFile($logFile, "📄 Saved CAPTCHA page to {$captchaFile}");

            return false;
        }

        // Check for empty or very small responses (likely blocked)
        if (strlen($response) < 1000) {
            $this->lastError = "Response too small, likely blocked or empty page.";
            $this->logToFile($logFile, "⚠️ Response too small: " . strlen($response) . " bytes");

            $emptyFile = $debugDir . '/empty-response-' . time() . '.html';
            file_put_contents($emptyFile, $response);
            chmod($emptyFile, 0666);

            return false;
        }

        return $response;
    }

    /**
     * Log a message to a file
     *
     * @param string $file The file to log to
     * @param string $message The message to log
     * @return void
     */
    protected function logToFile(string $file, string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";

        // Create directory if it doesn't exist
        $logDir = dirname($file);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Make sure the directory is readable and writable
        chmod($logDir, 0777);

        // Append to the log file
        file_put_contents($file, $logMessage, FILE_APPEND);

        // Make sure the file is readable and writable
        if (file_exists($file)) {
            chmod($file, 0666);
        }

        // Also log to error_log for server logs
        error_log($message);
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
