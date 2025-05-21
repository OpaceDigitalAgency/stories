<?php
/**
 * Goodreads Review Fetcher
 *
 * This class fetches reviews from Goodreads by scraping the website.
 * Note: Goodreads API was deprecated, so we need to scrape the website.
 */

namespace Services\ReviewFetcher;

use PDO;
use Exception;

class GoodreadsReviewFetcher extends AbstractReviewFetcher {
    protected $sourceId = 1; // Goodreads source ID
    protected $lastError = null;
    protected $aggregateRating = null; // Store aggregate rating separately
    protected $useVpsHeadlessBrowser = true; // Whether to use the VPS Headless Browser

    // GraphQL pagination state
    protected $nextPageToken = null;
    protected $lastGraphQLPage = 0;
    protected $totalAvailable = 0;

    // Scraping configuration
    protected $maxPages = 100;
    protected $continueFromLast = false;
    protected $startPage = 1;
    protected $reviewLimit = 100;
    protected $existingReviews = [];

    // Function to check if a review is a duplicate
    protected $isDuplicateReview = null;

    /**
     * Constructor
     *
     * @param PDO $db Database connection
     * @param int $sourceId Source ID in the database
     */
    public function __construct(PDO $db, int $sourceId) {
        parent::__construct($db, $sourceId, 'Goodreads');
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
     * @param string $isbn The ISBN of the book (can be ISBN-10 or ISBN-13)
     * @param int $limit Maximum number of reviews to fetch
     * @param array $options Additional options for the fetcher
     *                      - maxPages: Maximum number of pages to scrape
     *                      - continueFromLast: Whether to continue from the last scrape
     * @return array Array of review data
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 100, array $options = []): array {
        // Get pagination state from options
        $this->maxPages = $options['maxPages'] ?? 100;
        $this->continueFromLast = $options['continueFromLast'] ?? false;
        $this->startPage = $options['startPage'] ?? 1;
        $this->reviewLimit = $limit;

        // Check if we should skip database checks (for validation mode)
        $skipDbCheck = $options['skip_db_check'] ?? false;

        // If we're in validation mode, skip database checks
        if ($skipDbCheck) {
            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                "Skipping database checks (validation mode)");
        }
        // If continuing from last and not in validation mode, get existing reviews
        else if ($this->continueFromLast) {
            // Get existing reviews for duplicate checking
            // Get the last GraphQL page token
            $stmt = $this->db->prepare("
                SELECT metadata->>'$.next_token' as next_token,
                       metadata->>'$.graphql_page' as page_number
                FROM reviews
                WHERE book_id = ? AND source_id = ?
                AND metadata->>'$.source' = 'graphql'
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$options['book_id'] ?? 0, $this->sourceId]);
            $this->existingReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt->execute([$options['book_id'] ?? 0, $this->sourceId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['next_token']) {
                $this->nextPageToken = $result['next_token'];
                $this->lastGraphQLPage = (int)$result['page_number'];
                $this->startPage = $this->lastGraphQLPage + 1;

                $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                    "Resuming from GraphQL page {$this->lastGraphQLPage} with token {$this->nextPageToken}");
            }
        }

        // Standardize ISBN format
        $isbnData = $this->standardizeISBN($isbn);

        // Try ISBN-13 first, then ISBN-10
        $isbnToUse = !empty($isbnData['isbn13']) ? $isbnData['isbn13'] : $isbnData['isbn'];

        // First, search for the book on Goodreads
        $bookUrl = $this->findBookUrl($isbnToUse);

        if (empty($bookUrl)) {
            // Try with ISBN-10 if we used ISBN-13 before
            if (!empty($isbnData['isbn']) && $isbnData['isbn13'] == $isbnToUse) {
                $isbnToUse = $isbnData['isbn'];
                $bookUrl = $this->findBookUrl($isbnToUse);
            }

            if (empty($bookUrl)) {
                $errorMsg = "No book found on Goodreads for ISBN: $isbnToUse";
                $this->lastError = $errorMsg;
                $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', "❌ {$errorMsg}");

                // Return a structured response with error information
                return [
                    [
                        'source_id' => $this->sourceId,
                        'reviewer_name' => 'Error',
                        'review_text' => $errorMsg,
                        'book_metadata' => [
                            'title' => 'Unknown',
                            'author' => 'Unknown',
                            'isbn' => $isbn,
                            'error' => $errorMsg
                        ]
                    ]
                ];
            }
        }

        $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', "✅ Found book URL: {$bookUrl}");

        // Get book details
        $bookDetails = $this->getBookDetails($bookUrl);

        if (empty($bookDetails)) {
            $errorMsg = "Failed to get book details from Goodreads for URL: {$bookUrl}";
            $this->lastError = $errorMsg;
            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', "❌ {$errorMsg}");

            // Return a structured response with error information
            return [
                [
                    'source_id' => $this->sourceId,
                    'reviewer_name' => 'Error',
                    'review_text' => $errorMsg,
                    'book_metadata' => [
                        'title' => 'Unknown',
                        'author' => 'Unknown',
                        'isbn' => $isbn,
                        'error' => $errorMsg
                    ]
                ]
            ];
        }

        // Get reviews URL - make sure we're using the correct format
        $reviewsUrl = preg_replace('/\?.*$/', '', $bookUrl); // Remove any query parameters
        $reviewsUrl = rtrim($reviewsUrl, '/'); // Remove trailing slash if present

        // Check if the URL already contains '/reviews' to avoid duplicating it
        if (strpos($reviewsUrl, '/reviews') === false) {
            $reviewsUrl = $reviewsUrl . "/reviews"; // Add reviews path
        }

        // Log the reviews URL for debugging
        $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', "📚 Reviews URL: {$reviewsUrl}");

        // Fetch reviews
        $reviews = $this->scrapeReviews($reviewsUrl, $limit, $options);

        // If no reviews found but we have average rating, add an aggregate review
        if (empty($reviews) && !empty($bookDetails['average_rating'])) {
            $averageRating = (float)$bookDetails['average_rating'];
            $ratingsCount = $bookDetails['ratings_count'] ?? 0;

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Goodreads Aggregate",
                'reviewer_age' => null,
                'review_date' => date('Y-m-d'),
                'original_rating' => "{$averageRating}/5",
                'rating_value' => $averageRating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($averageRating, 5),
                'review_text' => "This book has an average rating of {$averageRating}/5 based on {$ratingsCount} ratings on Goodreads.",
                'metadata' => json_encode([
                    'book_url' => $bookDetails['url'] ?? '',
                    'is_synthetic' => false,
                    'is_aggregate' => true,
                    'ratings_count' => $ratingsCount
                ])
            ];
        }

        // Add book metadata to each review
        foreach ($reviews as $key => $review) {
            $reviews[$key]['book_metadata'] = $bookDetails;
        }

        return $reviews;
    }

    /**
     * Find the Goodreads book URL by ISBN
     *
     * @param string $isbn The ISBN to search for
     * @return string|null The book URL or null if not found
     */
    private function findBookUrl(string $isbn): ?string {
        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        // Log the ISBN we're searching for
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Finding book URL for ISBN: {$isbn}");

        // Special handling for known problematic ISBNs (like Harry Potter)
        if ($isbn == '1408855658' || $isbn == '9781408855652') {
            $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Special handling for Harry Potter ISBN: {$isbn}");
            // Direct URL for Harry Potter and the Philosopher's Stone
            return "https://www.goodreads.com/book/show/3.Harry_Potter_and_the_Sorcerer_s_Stone";
        }

        // First, try to get the Goodreads ID from OpenLibrary
        $goodreadsId = $this->getGoodreadsIdFromOpenLibrary($isbn);

        if ($goodreadsId) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found Goodreads ID {$goodreadsId} from OpenLibrary for ISBN {$isbn}");
            return "https://www.goodreads.com/book/show/{$goodreadsId}";
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ No Goodreads ID found in OpenLibrary for ISBN {$isbn}, falling back to search");

        // If OpenLibrary doesn't have the Goodreads ID, fall back to search
        // Build the search URL with cache-busting parameter
        $cacheBuster = time() . rand(1000, 9999);
        $searchUrl = "https://www.goodreads.com/search?q={$isbn}&_cb={$cacheBuster}";

        // Make the request
        $response = $this->makeRequest($searchUrl);

        if ($response === false) {
            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', "❌ Failed to make request to Goodreads search for ISBN {$isbn}");
            return null;
        }

        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        // Debug: Save the raw HTML to a file for inspection
        file_put_contents($debugDir . '/goodreads_search_debug.html', substr($response, 0, 50000));

        // Try multiple patterns to find book URLs in the current Goodreads HTML structure

        // Pattern 1: Modern Goodreads layout with data-testid
        if (preg_match('/<a[^>]+data-testid="bookTitle"[^>]+href="([^"]+)"/i', $response, $matches)) {
            $url = 'https://www.goodreads.com' . html_entity_decode($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found book URL using Pattern 1 (data-testid): {$url}");
            return $url;
        }

        // Pattern 2: Book cover link with ISBN in URL
        if (preg_match('/<a[^>]+href="([^"]*\/book\/show\/[^"]*' . preg_quote($isbn, '/') . '[^"]*)"/i', $response, $matches)) {
            $url = 'https://www.goodreads.com' . html_entity_decode($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found book URL using Pattern 2 (ISBN in URL): {$url}");
            return $url;
        }

        // Pattern 3: Any book show link in search results
        if (preg_match('/<a[^>]+href="(\/book\/show\/[^"]+)"[^>]*>/i', $response, $matches)) {
            $url = 'https://www.goodreads.com' . html_entity_decode($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found book URL using Pattern 3 (book show link): {$url}");
            return $url;
        }

        // Pattern 4: Title link with any class
        if (preg_match('/<a[^>]+class="[^"]*Title[^"]*"[^>]+href="([^"]+)"/i', $response, $matches)) {
            $url = 'https://www.goodreads.com' . html_entity_decode($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found book URL using Pattern 4 (title link): {$url}");
            return $url;
        }

        // Pattern 5: Any link to a book page
        if (preg_match_all('/<a[^>]+href="([^"]*\/book\/show\/[^"]+)"[^>]*>/i', $response, $matches)) {
            // Return the first match
            if (!empty($matches[1][0])) {
                $url = 'https://www.goodreads.com' . html_entity_decode($matches[1][0]);

                // Note: We're keeping the /reviews URL if it exists, as it might be needed for review scraping
                // We'll handle removing it in getBookDetails() when we need the main book page for metadata
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found book URL using Pattern 5 (any book page link): {$url}");

                return $url;
            }
        }

        // If we still can't find a book URL, try a different approach
        // Look for any book show link with text content
        if (preg_match('/<a[^>]+href="(\/book\/show\/[^"]+)"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $url = 'https://www.goodreads.com' . html_entity_decode($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found book URL using Pattern 6 (book show link with text): {$url}");
            return $url;
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "❌ No book URL found for ISBN {$isbn} using any pattern");
        return null;
    }

    /**
     * Get Goodreads ID from OpenLibrary
     *
     * @param string $isbn The ISBN to search for
     * @return string|null The Goodreads ID or null if not found
     */
    private function getGoodreadsIdFromOpenLibrary(string $isbn): ?string {
        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Looking up Goodreads ID from OpenLibrary for ISBN {$isbn}");

        // Build the OpenLibrary API URL
        $url = "https://openlibrary.org/isbn/{$isbn}.json";

        // Make the request
        $response = $this->makeRequest($url);

        if ($response === false) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ Failed to make request to OpenLibrary for ISBN {$isbn}");
            return null;
        }

        // Save the raw response for debugging
        file_put_contents($debugDir . "/openlibrary_{$isbn}_response.json", $response);

        // Parse the response
        $data = json_decode($response, true);

        // Check if we have Goodreads identifiers
        if (isset($data['identifiers']['goodreads']) && !empty($data['identifiers']['goodreads'])) {
            $goodreadsId = $data['identifiers']['goodreads'][0];
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found Goodreads ID {$goodreadsId} in OpenLibrary response");
            return $goodreadsId;
        }

        // If we don't have direct Goodreads identifiers, try to get the work ID and check that
        if (isset($data['works']) && !empty($data['works'])) {
            $workKey = $data['works'][0]['key'];
            $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Found work key {$workKey}, checking for Goodreads ID");

            // Get the work data
            $workUrl = "https://openlibrary.org{$workKey}.json";
            $workResponse = $this->makeRequest($workUrl);

            if ($workResponse !== false) {
                // Create a safe filename by removing slashes
                $safeWorkKey = str_replace('/', '_', $workKey);

                // Save the raw response for debugging
                file_put_contents($debugDir . "/openlibrary_work{$safeWorkKey}_response.json", $workResponse);

                // Parse the response
                $workData = json_decode($workResponse, true);

                // Check if we have Goodreads identifiers in the work data
                if (isset($workData['identifiers']['goodreads']) && !empty($workData['identifiers']['goodreads'])) {
                    $goodreadsId = $workData['identifiers']['goodreads'][0];
                    $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found Goodreads ID {$goodreadsId} in work data");
                    return $goodreadsId;
                }
            }
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "❌ No Goodreads ID found in OpenLibrary data for ISBN {$isbn}");
        return null;
    }

    /**
     * Get book details from Goodreads
     *
     * @param string $bookUrl The book URL
     * @return array|null The book details or null if not found
     */
    public function getBookDetails(string $bookUrl): ?array {
        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        // Make sure we're not using a reviews URL when fetching book details
        // We only want the main book page for metadata extraction
        if (strpos($bookUrl, '/reviews') !== false) {
            $originalUrl = $bookUrl;
            $bookUrl = str_replace('/reviews', '', $bookUrl);
            $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Found reviews URL '{$originalUrl}', converting to main book URL for metadata extraction: '{$bookUrl}'");
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Fetching book details from URL: {$bookUrl}");

        // Make the request
        $response = $this->makeRequest($bookUrl);

        if ($response === false) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ Failed to fetch book details from URL: {$bookUrl}");
            return null;
        }

        // Save the HTML for debugging
        file_put_contents($debugDir . '/goodreads_book_page.html', substr($response, 0, 500000));
        $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Saved book page HTML to debug file");

        $details = [];

        // Try to use the VPS-based Headless Browser service first for comprehensive metadata
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Attempting to use VPS Headless Browser for comprehensive metadata");

        // Use the VPS IP address as the default if environment variable is not set
        $apiUrl = getenv('HEADLESS_BROWSER_API_URL') ?: 'http://37.27.31.107:3000';
        $apiKey = getenv('HEADLESS_BROWSER_API_KEY') ?: 'stories-scraper-api-key-2023';

        // Build the request URL
        $url = "{$apiUrl}/scrape/goodreads?url=" . urlencode($bookUrl) . "&limit=1";

        $this->logToFile($debugDir . '/goodreads-log.txt', "🔗 Using VPS Headless Browser API URL: {$apiUrl}");
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔗 Book URL being scraped: {$bookUrl}");

        // Check if we should skip VPS headless browser (for debugging or if it's known to be down)
        if (isset($_GET['skip_vps']) || isset($_POST['skip_vps']) || getenv('SKIP_VPS_HEADLESS') === 'true') {
            $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Skipping VPS Headless Browser (forced by parameter)");
            $vpsResponse = false;
        } else {
            // Make the request to the VPS Headless Browser service with API key in header
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "x-api-key: {$apiKey}"
            ]);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
            curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output

            // Create a file handle for the verbose information
            $verbose = fopen($debugDir . '/curl_verbose.log', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);

            $vpsResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            curl_close($ch);

            // Close the file handle
            fclose($verbose);

            if ($httpCode >= 400 || $curlErrno > 0) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "❌ VPS Headless Browser API error: HTTP {$httpCode}, cURL Error: {$curlError} ({$curlErrno})");
                $this->logToFile($debugDir . '/goodreads-log.txt', "❌ See curl_verbose.log for detailed connection information");
                $vpsResponse = false;
            }
        }

        if ($vpsResponse !== false) {
            // Parse the response
            $vpsData = json_decode($vpsResponse, true);

            // Check if we have valid data
            if (isset($vpsData['book_title']) && !empty($vpsData)) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Successfully fetched comprehensive metadata from VPS Headless Browser");

                // Map the VPS response to our details array
                $details['title'] = $vpsData['book_title'] ?? '';
                $details['author'] = $vpsData['book_author'] ?? '';
                $details['isbn'] = $vpsData['book_isbn'] ?? '';
                $details['isbn13'] = $vpsData['book_isbn13'] ?? '';
                $details['publisher'] = $vpsData['book_publisher'] ?? '';
                $details['published_date'] = $vpsData['book_publication_date'] ?? '';
                $details['page_count'] = $vpsData['book_page_count'] ?? '';
                $details['language'] = $vpsData['book_language'] ?? '';
                $details['format'] = $vpsData['book_format'] ?? '';
                $details['series'] = $vpsData['book_series'] ?? '';
                $details['genres'] = $vpsData['book_genres'] ?? [];
                $details['awards'] = $vpsData['book_awards'] ?? [];
                $details['characters'] = $vpsData['book_characters'] ?? [];
                $details['settings'] = $vpsData['book_settings'] ?? [];
                $details['cover_url'] = $vpsData['book_cover_url'] ?? '';
                $details['description'] = $vpsData['book_description'] ?? '';
                $details['average_rating'] = $vpsData['book_rating'] ?? '';
                $details['ratings_count'] = $vpsData['book_rating_count'] ?? '';
                $details['review_count'] = $vpsData['book_review_count'] ?? '';
                $details['url'] = $bookUrl;

                // Log the extracted metadata
                $this->logToFile($debugDir . '/goodreads-log.txt', "📚 Extracted metadata from VPS Headless Browser:");
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Title: " . ($details['title'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Author: " . ($details['author'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- ISBN: " . ($details['isbn'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- ISBN-13: " . ($details['isbn13'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Publisher: " . ($details['publisher'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Publication Date: " . ($details['published_date'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Page Count: " . ($details['page_count'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Language: " . ($details['language'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Format: " . ($details['format'] ?? 'N/A'));
                $this->logToFile($debugDir . '/goodreads-log.txt', "- Series: " . ($details['series'] ?? 'N/A'));

                return $details;
            } else {
                $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ VPS Headless Browser returned invalid data, falling back to direct scraping");
            }
        } else {
            $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Failed to fetch data from VPS Headless Browser, falling back to direct scraping");
        }

        // If VPS Headless Browser fails, fall back to direct scraping
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Falling back to direct HTML scraping for book details");

        // Log the book URL we're scraping directly
        $this->logToFile($debugDir . '/goodreads-log.txt', "📚 Direct scraping book URL: {$bookUrl}");

        // Create a more detailed log of the response for debugging
        if ($response) {
            $responseLength = strlen($response);
            $this->logToFile($debugDir . '/goodreads-log.txt', "📝 Response received: {$responseLength} bytes");

            // Save a sample of the response for debugging
            $sampleLength = min(1000, $responseLength);
            $this->logToFile($debugDir . '/goodreads-log.txt', "📝 Response sample: " . substr($response, 0, $sampleLength) . "...");
        } else {
            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ No response received from direct scraping");
        }

        // Extract book title
        if (preg_match('/<h1 id="bookTitle"[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        } else if (preg_match('/<h1[^>]*class="[^"]*BookPageTitleSection__title[^"]*"[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        } else if (preg_match('/<h1[^>]*data-testid="bookTitle"[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        }

        // Extract book author
        if (preg_match('/<a class="authorName"[^>]*><span[^>]*>([^<]+)<\/span><\/a>/i', $response, $matches)) {
            $details['author'] = trim($matches[1]);
        } else if (preg_match('/<a[^>]*class="[^"]*BookPageTitleSection__authorLink[^"]*"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['author'] = trim($matches[1]);
        } else if (preg_match('/<a[^>]*data-testid="authorLink"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['author'] = trim($matches[1]);
        }

        // Extract book cover
        if (preg_match('/<img id="coverImage"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        } else if (preg_match('/<img[^>]*class="[^"]*BookCover__image[^"]*"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        } else if (preg_match('/<img[^>]*class="[^"]*ResponsiveImage[^"]*"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        }

        // Extract book description
        if (preg_match('/<div id="description"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<div[^>]*class="[^"]*BookPageMetadataSection__description[^"]*"[^>]*>.*?<div[^>]*class="[^"]*Formatted[^"]*"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<div[^>]*data-testid="description"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        }

        // Extract average rating
        if (preg_match('/<span itemprop="ratingValue"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found average rating using itemprop pattern: {$details['average_rating']}");
        } else if (preg_match('/<div[^>]*class="[^"]*RatingStatistics__rating[^"]*"[^>]*>([^<]+)<\/div>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found average rating using RatingStatistics__rating pattern: {$details['average_rating']}");
        } else if (preg_match('/<div[^>]*data-testid="averageRating"[^>]*>([^<]+)<\/div>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found average rating using data-testid pattern: {$details['average_rating']}");
        } else if (preg_match('/aria-label="Average rating of ([0-9.]+) stars."[^>]*>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found average rating using aria-label pattern: {$details['average_rating']}");
        } else if (preg_match('/<span[^>]*class="RatingStars__RatingsValue[^"]*"[^>]*>([0-9.]+)<\/span>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found average rating using RatingStars__RatingsValue pattern: {$details['average_rating']}");
        }

        // Extract ratings count
        if (preg_match('/<meta itemprop="ratingCount" content="([^"]+)"/i', $response, $matches)) {
            $details['ratings_count'] = (int)$matches[1];
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found ratings count using itemprop pattern: {$details['ratings_count']}");
        } else if (preg_match('/<div[^>]*class="[^"]*RatingStatistics__meta[^"]*"[^>]*>.*?(\d+(?:,\d+)*)[^<]*ratings/i', $response, $matches)) {
            $details['ratings_count'] = (int)str_replace(',', '', $matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found ratings count using RatingStatistics__meta pattern: {$details['ratings_count']}");
        } else if (preg_match('/<div[^>]*data-testid="ratingsCount"[^>]*>.*?(\d+(?:,\d+)*)[^<]*ratings/i', $response, $matches)) {
            $details['ratings_count'] = (int)str_replace(',', '', $matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found ratings count using data-testid pattern: {$details['ratings_count']}");
        } else if (preg_match('/<a[^>]*href="#CommunityReviews"[^>]*>([0-9,.]+) ratings/i', $response, $matches)) {
            $details['ratings_count'] = (int)str_replace([',', '.'], '', $matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found ratings count using CommunityReviews link pattern: {$details['ratings_count']}");
        } else if (preg_match('/([0-9,.]+) ratings and ([0-9,.]+) reviews/i', $response, $matches)) {
            $details['ratings_count'] = (int)str_replace([',', '.'], '', $matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found ratings count using ratings and reviews pattern: {$details['ratings_count']}");
        }

        // Extract review count
        if (preg_match('/<meta itemprop="reviewCount" content="([^"]+)"/i', $response, $matches)) {
            $details['review_count'] = (int)$matches[1];
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review count using itemprop pattern: {$details['review_count']}");
        } else if (preg_match('/([0-9,.]+) ratings and ([0-9,.]+) reviews/i', $response, $matches)) {
            $details['review_count'] = (int)str_replace([',', '.'], '', $matches[2]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review count using ratings and reviews pattern: {$details['review_count']}");
        } else if (preg_match('/<a[^>]*href="#CommunityReviews"[^>]*>[0-9,.]+ ratings ([0-9,.]+) reviews<\/a>/i', $response, $matches)) {
            $details['review_count'] = (int)str_replace([',', '.'], '', $matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review count using CommunityReviews link pattern: {$details['review_count']}");
        } else if (preg_match('/(\d[\d,\.]*) reviews/i', $response, $matches)) {
            $details['review_count'] = (int)str_replace(',', '', $matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review count using generic reviews pattern: {$details['review_count']}");
        }

        // Extract publication info
        if (preg_match('/Published\s+(.*?)(?:\s+by\s+(.*?))?(?:\s+\(first published\s+(.*?)\))?</is', $response, $matches)) {
            if (!empty($matches[1])) {
                $details['published_date'] = trim($matches[1]);
            }
            if (!empty($matches[2])) {
                $details['publisher'] = trim($matches[2]);
            }
        } else if (preg_match('/Published\s+(.*?)(?:\s+by\s+(.*?))?/i', $response, $matches)) {
            if (!empty($matches[1])) {
                $details['published_date'] = trim($matches[1]);
            }
            if (!empty($matches[2])) {
                $details['publisher'] = trim($matches[2]);
            }
        }

        // Extract publication date from data-testid attribute (new Goodreads design)
        if (empty($details['published_date']) && preg_match('/<p[^>]*data-testid="publicationInfo"[^>]*>First published\s+(.*?)<\/p>/is', $response, $matches)) {
            $details['published_date'] = trim($matches[1]);
        } else if (empty($details['published_date']) && preg_match('/<p[^>]*data-testid="publicationInfo"[^>]*>([^<]+)<\/p>/is', $response, $matches)) {
            $details['published_date'] = trim($matches[1]);
        }

        // Extract ISBN
        if (preg_match('/ISBN\s+(\d+X?)/i', $response, $matches)) {
            $details['isbn'] = $matches[1];
        } else if (preg_match('/ISBN10:\s*(\d+X?)/i', $response, $matches)) {
            $details['isbn'] = $matches[1];
        }

        // Extract ISBN13
        if (preg_match('/ISBN13\s+(\d+)/i', $response, $matches)) {
            $details['isbn13'] = $matches[1];
        } else if (preg_match('/ISBN\s+(\d{13})/i', $response, $matches)) {
            $details['isbn13'] = $matches[1];
        }

        // Extract page count
        if (preg_match('/(\d+)\s+pages/i', $response, $matches)) {
            $details['page_count'] = (int)$matches[1];
        }

        // Extract language
        if (preg_match('/Language\s*:?\s*([^<\n]+)/i', $response, $matches)) {
            $details['language'] = trim($matches[1]);
        }

        // Extract format
        if (preg_match('/Format\s*:?\s*([^<\n]+)/i', $response, $matches)) {
            $details['format'] = trim($matches[1]);
        } else if (preg_match('/(\d+)\s+pages,\s+([^,<\n]+)/i', $response, $matches)) {
            $details['format'] = trim($matches[2]);
        }

        // Extract series
        if (preg_match('/Series\s*:?\s*([^<\n]+)/i', $response, $matches)) {
            $details['series'] = trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found series using text pattern: {$details['series']}");
        } else if (preg_match('/<a[^>]*href="\/series\/[^"]+"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['series'] = trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found series using series link pattern: {$details['series']}");
        } else if (preg_match('/<dt[^>]*>Series<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/is', $response, $matches)) {
            $details['series'] = trim(strip_tags($matches[1]));
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found series using dt/dd pattern: {$details['series']}");
        } else if (preg_match('/<span[^>]*>Series:<\/span>\s*<span[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['series'] = trim(strip_tags($matches[1]));
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found series using span pattern: {$details['series']}");
        } else if (preg_match('/<div[^>]*class="DescListItem"[^>]*>.*?<dt>Series<\/dt>.*?<dd>(.*?)<\/dd>/is', $response, $matches)) {
            // Extract series from the HTML (new Goodreads design)
            $seriesText = trim(strip_tags($matches[1]));
            // Clean up series format like "The Worst Witch (#1)"
            if (preg_match('/^(.*?)\s*\(#\d+\)$/', $seriesText, $seriesMatches)) {
                $details['series'] = trim($seriesMatches[1]);
            } else {
                $details['series'] = $seriesText;
            }
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found series using DescListItem pattern: {$details['series']}");
        } else if (preg_match('/\(([^)]+) #\d+\)/i', $response, $matches)) {
            $details['series'] = trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found series using series number pattern: {$details['series']}");
        } else if (preg_match('/<a[^>]*href="\/series\/\d+[^"]*"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['series'] = trim($matches[1]);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found series using series ID link pattern: {$details['series']}");
        }

        // Extract genres/shelves
        if (preg_match_all('/<a class="actionLinkLite bookPageGenreLink"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['genres'] = array_map('trim', $matches[1]);
        } else if (preg_match_all('/<a[^>]*href="\/genres\/[^"]+"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['genres'] = array_map('trim', $matches[1]);
        } else if (preg_match_all('/<span[^>]*class="[^"]*Button__labelItem[^"]*"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $details['genres'] = array_map('trim', $matches[1]);
        }

        // Extract awards
        if (preg_match('/Awards\s*:?\s*([^<\n]+)/i', $response, $matches)) {
            $details['awards'] = array_map('trim', explode(',', $matches[1]));
        }

        // Extract characters (if available)
        if (preg_match('/Characters\s*:?\s*([^<\n]+)/i', $response, $matches)) {
            $details['characters'] = array_map('trim', explode(',', $matches[1]));
        } else if (preg_match('/<dt[^>]*>Characters<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/is', $response, $matches)) {
            // Extract character links from the HTML
            preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $matches[1], $charMatches);
            if (!empty($charMatches[1])) {
                $details['characters'] = array_map('trim', $charMatches[1]);
            } else {
                $details['characters'] = [trim(strip_tags($matches[1]))];
            }
        } else if (preg_match('/<span[^>]*>Characters:<\/span>\s*<span[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            // Extract character links from the HTML
            preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $matches[1], $charMatches);
            if (!empty($charMatches[1])) {
                $details['characters'] = array_map('trim', $charMatches[1]);
            } else {
                $details['characters'] = [trim(strip_tags($matches[1]))];
            }
        } else if (preg_match('/<div[^>]*class="DescListItem"[^>]*>.*?<dt>Characters<\/dt>.*?<dd>(.*?)<\/dd>/is', $response, $matches)) {
            // Extract character links from the HTML (new Goodreads design)
            preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $matches[1], $charMatches);
            if (!empty($charMatches[1])) {
                $details['characters'] = array_map('trim', $charMatches[1]);
            } else {
                $details['characters'] = [trim(strip_tags($matches[1]))];
            }
        }

        // Extract settings (if available)
        if (preg_match('/Setting\s*:?\s*([^<\n]+)/i', $response, $matches)) {
            $details['settings'] = array_map('trim', explode(',', $matches[1]));
        }

        $details['url'] = $bookUrl;

        // Log the extracted metadata
        $this->logToFile($debugDir . '/goodreads-log.txt', "📚 Extracted metadata from direct HTML scraping:");
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Title: " . ($details['title'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Author: " . ($details['author'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- ISBN: " . ($details['isbn'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- ISBN-13: " . ($details['isbn13'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Publisher: " . ($details['publisher'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Publication Date: " . ($details['published_date'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Page Count: " . ($details['page_count'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Language: " . ($details['language'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Format: " . ($details['format'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Series: " . ($details['series'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Average Rating: " . ($details['average_rating'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Ratings Count: " . ($details['ratings_count'] ?? 'N/A'));
        $this->logToFile($debugDir . '/goodreads-log.txt', "- Review Count: " . ($details['review_count'] ?? 'N/A'));

        return $details;
    }

    /**
     * Scrape reviews from Goodreads
     *
     * @param string $reviewsUrl The URL to scrape reviews from
     * @param int $limit Maximum number of reviews to return
     * @return array Array of reviews
     */
    /**
     * Get GraphQL pagination state from the database
     */
    private function getGraphQLPaginationState(int $bookId): array {
        $stmt = $this->db->prepare("
            SELECT r.metadata->>'$.next_token' as next_token,
                   r.metadata->>'$.graphql_page' as page_number,
                   r.metadata->>'$.total_available' as total_available
            FROM reviews r
            WHERE r.book_id = ? AND r.source_id = ?
            AND r.metadata->>'$.source' = 'graphql'
            ORDER BY r.id DESC LIMIT 1
        ");
        $stmt->execute([$bookId, $this->sourceId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'nextPageToken' => $result['next_token'] ?? null,
            'currentPage' => (int)($result['page_number'] ?? 0),
            'totalAvailable' => (int)($result['total_available'] ?? 0)
        ];
    }

    private function scrapeReviews(string $reviewsUrl, int $limit, array $options = []): array {
        $continueFromLast = $options['continueFromLast'] ?? false;
        $bookId = $options['book_id'] ?? 0;
        $skipDbCheck = $options['skip_db_check'] ?? false;

        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        // Get existing reviews for duplicate checking
        $existingReviews = [];

        // Skip database check if we're in validation mode
        if ($skipDbCheck) {
            $this->logToFile($debugDir . '/goodreads-log.txt',
                "Skipping database check for reviews (validation mode)");
        }
        // Otherwise, get existing reviews if continuing from last
        else if ($continueFromLast && $bookId) {
            $stmt = $this->db->prepare("
                SELECT r.*, r.metadata as review_metadata
                FROM reviews r
                WHERE r.book_id = ? AND r.source_id = ?
                ORDER BY r.id DESC
            ");
            $stmt->execute([$bookId, $this->sourceId]);
            $existingReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->logToFile($debugDir . '/goodreads-log.txt',
                "Found " . count($existingReviews) . " existing reviews for duplicate checking");
        }

        // Calculate how many more reviews we need
        $reviewLimit = $limit;
        if (!empty($existingReviews)) {
            $reviewLimit = $limit + 100; // Request extra reviews to ensure we get enough new ones
            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                "Requesting {$reviewLimit} reviews from Goodreads " .
                "(reviewLimit: {$limit}, existingReviewCount: " . count($existingReviews) . ")");
        }

        // Get GraphQL pagination state if continuing
        if ($continueFromLast) {
            $state = $this->getGraphQLPaginationState($bookId);
            if ($state['nextPageToken']) {
                $options['nextPageToken'] = $state['nextPageToken'];
                $options['startPage'] = $state['currentPage'] + 1;

                $logMsg = "Resuming GraphQL pagination - " .
                    "Page: " . $options['startPage'] . ", " .
                    "Token: " . $options['nextPageToken'] . ", " .
                    "Total available: " . $state['totalAvailable'];
                $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', $logMsg);

                // Set initial state for GraphQL pagination
                $this->nextPageToken = $state['nextPageToken'];
                $this->lastGraphQLPage = $state['currentPage'];
                $this->totalAvailable = $state['totalAvailable'];
            }
        }

        // Function to check if a review is a duplicate
        $isDuplicate = function($review, $existingReviews) {
            // Skip duplicate check for GraphQL reviews
            if (isset($review['metadata'])) {
                try {
                    $metadata = json_decode($review['metadata'], true);
                    if ($metadata && isset($metadata['source']) && $metadata['source'] === 'graphql') {
                        return false;
                    }
                } catch (Exception $e) {
                    // If metadata parsing fails, continue with duplicate check
                }
            }

            // For non-GraphQL reviews, check for exact matches
            foreach ($existingReviews as $existingReview) {
                if ($existingReview['reviewer_name'] === $review['reviewer_name'] &&
                    substr($existingReview['review_text'], 0, 50) === substr($review['review_text'], 0, 50)) {
                    return true;
                }
            }
            return false;
        };
        // Get existing reviews for duplicate checking
        $existingReviews = [];
        if ($continueFromLast && $bookId) {
            $stmt = $this->db->prepare("
                SELECT r.*, r.metadata as review_metadata
                FROM reviews r
                WHERE r.book_id = ? AND r.source_id = ?
                ORDER BY r.id DESC
            ");
            $stmt->execute([$bookId, $this->sourceId]);
            $existingReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                "Found " . count($existingReviews) . " existing reviews for duplicate checking");
        }

        // Function to check if a review is a duplicate
        $isDuplicate = function($review, $existingReviews, $currentPage) {
            // Create a unique fingerprint for the new review
            $reviewFingerprint = md5($review['reviewer_name'] . '|' . substr($review['review_text'], 0, 100));

            // Log the review we're checking
            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                "Checking for duplicate: {$review['reviewer_name']} (fingerprint: {$reviewFingerprint})");

            foreach ($existingReviews as $existingReview) {
                try {
                    $metadata = json_decode($existingReview['review_metadata'], true);

                    // Create a fingerprint for the existing review
                    $existingFingerprint = md5($existingReview['reviewer_name'] . '|' . substr($existingReview['review_text'], 0, 100));

                    // If we're continuing from last scrape and this is from a previous page, it's not a duplicate
                    if (isset($metadata['graphql_page']) && $metadata['graphql_page'] < $currentPage) {
                        $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                            "Skipping comparison with review from previous page {$metadata['graphql_page']} < {$currentPage}");
                        continue;
                    }

                    // Check for exact fingerprint match
                    if ($existingFingerprint === $reviewFingerprint) {
                        $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                            "Found duplicate by fingerprint: {$existingReview['reviewer_name']}");
                        return true;
                    }

                    // If fingerprints don't match but names do, do a more detailed check
                    if ($existingReview['reviewer_name'] === $review['reviewer_name']) {
                        // Check if review texts are significantly different
                        $existingTextSample = substr($existingReview['review_text'], 0, 100);
                        $newTextSample = substr($review['review_text'], 0, 100);

                        // Calculate similarity
                        similar_text($existingTextSample, $newTextSample, $similarity);

                        // If texts are very similar (>80% match), consider it a duplicate
                        if ($similarity > 80) {
                            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                                "Found duplicate by text similarity ({$similarity}%): {$existingReview['reviewer_name']}");
                            return true;
                        } else {
                            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                                "Same reviewer but different text ({$similarity}% similar): {$existingReview['reviewer_name']}");
                        }
                    }
                } catch (Exception $e) {
                    $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                        "Error parsing metadata: " . $e->getMessage());
                }
            }

            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                "No duplicate found for: {$review['reviewer_name']}");
            return false;
        };

        // Store the duplicate check function for use in other methods
        // Define the property in the class to avoid deprecation warning
        if (!property_exists($this, 'isDuplicateReview')) {
            $this->isDuplicateReview = null;
        }
        $this->isDuplicateReview = $isDuplicate;

        // If we have a nextPageToken from previous scrape, add it to options
        if ($this->nextPageToken) {
            $options['nextPageToken'] = $this->nextPageToken;
            $options['startPage'] = $this->lastGraphQLPage + 1;
            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt',
                "Continuing GraphQL pagination from page {$this->lastGraphQLPage} with token {$this->nextPageToken}, " .
                "{$this->totalAvailable} total reviews available");
        }
        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Scraping reviews from URL: {$reviewsUrl}");

        // Extract options
        $maxPages = $options['maxPages'] ?? 20;
        $continueFromLast = $options['continueFromLast'] ?? false;

        // First try to use the VPS-based Headless Browser service
        $vpsReviews = $this->fetchReviewsWithHeadlessBrowser($reviewsUrl, $limit, [
            'maxPages' => $maxPages,
            'continueFromLast' => $continueFromLast
        ]);

        if (!empty($vpsReviews)) {
            $reviewCount = count($vpsReviews);
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Successfully fetched {$reviewCount} reviews using VPS Headless Browser");

            // Add a prominent summary message
            error_log("🎉🎉🎉 GOODREADS REVIEW IMPORT SUMMARY: {$reviewCount} REVIEWS FETCHED USING VPS PUPPETEER SCRAPER 🎉🎉🎉");

            // If we have an aggregate rating from the VPS, store it separately
            foreach ($vpsReviews as $key => $review) {
                if (isset($review['metadata'])) {
                    $metadata = json_decode($review['metadata'], true);
                    if (isset($metadata['is_aggregate']) && $metadata['is_aggregate']) {
                        $this->aggregateRating = $review;
                        unset($vpsReviews[$key]);
                        break;
                    }
                }
            }

            // Reindex the array after potentially removing the aggregate rating
            $vpsReviews = array_values($vpsReviews);

            // -- BEGIN DUPLICATE FILTERING BLOCK --
            // If we're continuing from last and have existing reviews, filter duplicates from VPS results
            if (!empty($existingReviews)) {
                $filteredReviews = [];
                $seenFingerprints = [];

                $currentPage = $options['startPage'] ?? 1;

                foreach ($vpsReviews as $i => $review) {
                    $fingerprint = md5($review['reviewer_name'] . '|' . substr($review['review_text'], 0, 100));

                    // Skip if already processed in this batch
                    if (isset($seenFingerprints[$fingerprint])) {
                        continue;
                    }
                    $seenFingerprints[$fingerprint] = true;

                    // Add graphql_page metadata to new review
                    $reviewMeta = json_decode($review['metadata'] ?? '{}', true);
                    if (!isset($reviewMeta['graphql_page'])) {
                        $reviewMeta['graphql_page'] = $currentPage;
                        $review['metadata'] = json_encode($reviewMeta);
                    }

                    if (!$isDuplicate($review, $existingReviews, $currentPage)) {
                        $filteredReviews[] = $review;
                    }
                }

                $vpsReviews = $filteredReviews;
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Filtered duplicate reviews, returning " . count($vpsReviews));
            }
            // -- END DUPLICATE FILTERING BLOCK --

            // Log the final count after processing
            $finalCount = count($vpsReviews);

            // Check if we're hitting the 30-review limit
            if ($finalCount == 30 && $limit > 30) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ WARNING: Exactly 30 reviews returned. This may indicate a Goodreads limitation.");
                $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Goodreads often limits non-authenticated users to 30 reviews per book.");
                $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Try visiting the book page in a browser to confirm if more reviews are available.");
                error_log("⚠️⚠️⚠️ GOODREADS REVIEW LIMIT DETECTED: Exactly 30 reviews returned, which is a common Goodreads limitation ⚠️⚠️⚠️");
            } else if ($finalCount > 30) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "🚀 VPS SCRAPER SUCCESS: Returning {$finalCount} reviews (more than the 30 limit of direct scraping)");
                error_log("🚀🚀🚀 VPS SCRAPER SUCCESS: Returning {$finalCount} reviews (more than the 30 limit of direct scraping) 🚀🚀🚀");
            } else if ($finalCount < $limit) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "ℹ️ Fewer reviews returned ({$finalCount}) than requested ({$limit}). This may be all that's available for this book.");
            }

            // If we're continuing from the last scrape, return all reviews
            // Otherwise, limit the number of reviews to the requested limit
            if ($options['continueFromLast'] ?? false) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Continuing from last scrape, returning all {$finalCount} reviews");
                return $vpsReviews;
            } else {
                return array_slice($vpsReviews, 0, $limit);
            }
        }

        // If VPS Headless Browser fails, try Puppeteer for better results (especially for books with many reviews)
        $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ VPS Headless Browser failed or returned no results, trying Puppeteer");

        $puppeteerReviews = $this->fetchReviewsWithPuppeteer($reviewsUrl, $limit);

        if (!empty($puppeteerReviews)) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Successfully fetched " . count($puppeteerReviews) . " reviews using Puppeteer");

            // If we have an aggregate rating from Puppeteer, store it separately
            foreach ($puppeteerReviews as $key => $review) {
                if (isset($review['metadata'])) {
                    $metadata = json_decode($review['metadata'], true);
                    if (isset($metadata['is_aggregate']) && $metadata['is_aggregate']) {
                        $this->aggregateRating = $review;
                        unset($puppeteerReviews[$key]);
                        break;
                    }
                }
            }

            // Reindex the array after potentially removing the aggregate rating
            $puppeteerReviews = array_values($puppeteerReviews);

            // If we're continuing from the last scrape, return all reviews
            // Otherwise, limit the number of reviews to the requested limit
            if ($options['continueFromLast'] ?? false) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Continuing from last scrape, returning all " . count($puppeteerReviews) . " reviews from Puppeteer");
                return $puppeteerReviews;
            } else {
                return array_slice($puppeteerReviews, 0, $limit);
            }
        }

        // If both VPS and Puppeteer fail, fall back to regex-based scraping
        $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Both VPS and Puppeteer scraping failed or returned no results, falling back to regex-based scraping");

        $reviews = [];
        $page = 1;
        // Calculate how many pages we need based on the limit
        // Goodreads typically shows 10-30 reviews per page
        // We'll use a conservative estimate of 10 reviews per page
        // Cap at 10 pages to avoid excessive scraping
        $maxPages = min(10, ceil($limit / 10));

        // First, try to extract the aggregate rating from the first page
        $firstPageResponse = $this->makeRequest($reviewsUrl);

        if ($firstPageResponse === false) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ Failed to make request to Goodreads reviews page");
            return [];
        }

        // Debug: Save the raw HTML to a file for inspection
        file_put_contents($debugDir . '/goodreads_reviews_debug.html', substr($firstPageResponse, 0, 500000));
        $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Saved reviews HTML to debug file");

        // Extract aggregate rating from the first page (for logging purposes only)
        $aggregateRating = $this->extractAggregateRating($firstPageResponse, $reviewsUrl);
        if ($aggregateRating) {
            // Store the aggregate rating separately, don't add to reviews array
            $this->aggregateRating = $aggregateRating;
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Extracted aggregate rating: {$aggregateRating['rating_value']}/5");
        }

        // Process the first page
        $firstPageReviews = $this->extractReviewsFromHtml($firstPageResponse, $reviewsUrl, $debugDir);
        $reviews = array_merge($reviews, $firstPageReviews);

        $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Extracted " . count($firstPageReviews) . " reviews from page 1");

        // Check if we need more reviews and if there are pagination links
        if (count($reviews) < $limit && $page < $maxPages) {
            // Look for pagination links - try multiple patterns
            if (preg_match('/<a[^>]*href="([^"]*page=(\d+)[^"]*)"[^>]*>Next<\/a>/i', $firstPageResponse, $matches) ||
                preg_match('/<a[^>]*href="([^"]*page=(\d+)[^"]*)"[^>]*>Show more reviews<\/a>/i', $firstPageResponse, $matches) ||
                preg_match('/<button[^>]*data-testid="loadMore"[^>]*>.*?<a[^>]*href="([^"]+)"[^>]*>/is', $firstPageResponse, $matches) ||
                preg_match('/<button[^>]*class="[^"]*Button--secondary[^"]*"[^>]*>Show more reviews<\/button>/i', $firstPageResponse)) {

                // If we found a "Show more" button without a direct link, we need to construct the URL
                if (empty($matches[1]) && strpos($firstPageResponse, 'Show more reviews') !== false) {
                    // Extract the current book ID from the URL
                    if (preg_match('/\/book\/show\/(\d+)/', $reviewsUrl, $bookIdMatch)) {
                        $bookId = $bookIdMatch[1];
                        $nextPageUrl = "https://www.goodreads.com/book/show/{$bookId}/reviews?page=2";
                        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Constructed next page URL from Show more button: {$nextPageUrl}");
                    } else {
                        $nextPageUrl = $reviewsUrl . "?page=2";
                        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Constructed fallback next page URL: {$nextPageUrl}");
                    }
                } else {
                    $nextPageUrl = html_entity_decode($matches[1]);
                    if (!preg_match('/^https?:\/\//', $nextPageUrl)) {
                        // Convert relative URL to absolute
                        $nextPageUrl = preg_replace('/^\//', 'https://www.goodreads.com/', $nextPageUrl);
                    }

                    $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Found next page URL: {$nextPageUrl}");

                    // Fetch additional pages
                    while (count($reviews) < $limit && $page < $maxPages) {
                        $page++;

                        // Add a longer pause between page requests
                        sleep(rand(3, 5));

                        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Fetching page {$page}");
                        $pageResponse = $this->makeRequest($nextPageUrl);

                        if ($pageResponse === false) {
                            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ Failed to fetch page {$page}");
                            break;
                        }

                        // Save this page's HTML for debugging
                        file_put_contents($debugDir . "/goodreads_reviews_page{$page}_debug.html", substr($pageResponse, 0, 500000));

                        // Process this page
                        $pageReviews = $this->extractReviewsFromHtml($pageResponse, $reviewsUrl, $debugDir);
                        $reviews = array_merge($reviews, $pageReviews);

                        $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Extracted " . count($pageReviews) . " reviews from page {$page}");

                        // Look for next page link
                        if (preg_match('/<a[^>]*href="([^"]*page=(\d+)[^"]*)"[^>]*>Next<\/a>/i', $pageResponse, $matches) ||
                            preg_match('/<a[^>]*href="([^"]*page=(\d+)[^"]*)"[^>]*>Show more reviews<\/a>/i', $pageResponse, $matches)) {

                            $nextPageUrl = html_entity_decode($matches[1]);
                            if (!preg_match('/^https?:\/\//', $nextPageUrl)) {
                                // Convert relative URL to absolute
                                $nextPageUrl = preg_replace('/^\//', 'https://www.goodreads.com/', $nextPageUrl);
                            }
                        } else {
                            // No more pages
                            break;
                        }
                    }
                }
            }
        }

        // If we're continuing from the last scrape, return all reviews
        // Otherwise, limit the number of reviews to the requested limit
        if ($options['continueFromLast'] ?? false) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Continuing from last scrape, returning all " . count($reviews) . " reviews from regex-based scraping");
            return $reviews;
        } else {
            return array_slice($reviews, 0, $limit);
        }
    }

    /**
     * Fetch reviews using Puppeteer via Netlify function
     *
     * @param string $reviewsUrl The URL to scrape reviews from
     * @param int $limit Maximum number of reviews to return
     * @return array Array of reviews
     */
    private function fetchReviewsWithPuppeteer(string $reviewsUrl, int $limit): array {
        $debugDir = __DIR__ . '/debug';
        $this->logToFile($debugDir . '/goodreads-log.txt', "🤖 Attempting to fetch reviews using Puppeteer for URL: {$reviewsUrl}");

        // Get the Netlify function URL from environment variable or use default
        $puppeteerUrl = getenv('GOODREADS_PUPPETEER_URL') ?: 'https://storiesfromtheweb.netlify.app/.netlify/functions/goodreads-reviews';

        // Log the Puppeteer URL for debugging
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔗 Using Puppeteer function URL: {$puppeteerUrl}");

        // Create debug directory if it doesn't exist
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0777, true);
        }

        // Start a new log file for this session
        $logFile = $debugDir . '/goodreads-puppeteer-log.txt';
        file_put_contents($logFile, "=== Starting Puppeteer scraping session at " . date('Y-m-d H:i:s') . " ===\n");
        $this->logToFile($logFile, "🔍 Target URL: {$reviewsUrl}");
        $this->logToFile($logFile, "🔗 Puppeteer function URL: {$puppeteerUrl}");

        // Check if the function exists by making a test request
        try {
            $this->logToFile($logFile, "🧪 Testing Puppeteer function availability...");
            $testCh = curl_init($puppeteerUrl);
            curl_setopt($testCh, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($testCh, CURLOPT_NOBODY, false); // Get the full response for better debugging
            curl_setopt($testCh, CURLOPT_TIMEOUT, 5);
            $testResponse = curl_exec($testCh);
            $httpCode = curl_getinfo($testCh, CURLINFO_HTTP_CODE);
            $error = curl_error($testCh);
            curl_close($testCh);

            $this->logToFile($logFile, "📊 Test response code: {$httpCode}");
            if (!empty($error)) {
                $this->logToFile($logFile, "⚠️ cURL error: {$error}");
            }

            if ($testResponse) {
                $this->logToFile($logFile, "📄 Test response body: " . substr($testResponse, 0, 500) . "...");
            }

            if ($httpCode >= 400) {
                $this->logToFile($logFile, "❌ Puppeteer function not available (HTTP {$httpCode}). Falling back to regex scraping.");
                return [];
            }
        } catch (Exception $e) {
            $this->logToFile($logFile, "❌ Error checking Puppeteer function: " . $e->getMessage());
            return [];
        }

        // Prepare the request data
        $requestData = [
            'goodreadsUrl' => $reviewsUrl,
            'limit' => $limit,
            'maxPages' => 20 // Allow up to 20 pages to get more reviews
        ];

        // Set up cURL options
        $this->logToFile($logFile, "🚀 Preparing to send request to Puppeteer function");
        $this->logToFile($logFile, "📦 Request data: " . json_encode($requestData));

        $ch = curl_init($puppeteerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90); // 90 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout

        // Execute the request
        $this->logToFile($logFile, "🚀 Sending request to Puppeteer function");
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $this->logToFile($logFile, "⏱️ Request completed in {$executionTime} seconds");
        $this->logToFile($logFile, "📊 HTTP status code: {$httpCode}");

        if (!empty($info)) {
            $this->logToFile($logFile, "ℹ️ Request info: " . json_encode($info));
        }

        // Check for errors
        if ($response === false) {
            $this->logToFile($logFile, "❌ cURL error: {$error}");
            return [];
        }

        if ($httpCode !== 200) {
            $this->logToFile($logFile, "❌ HTTP error: {$httpCode}");
            $this->logToFile($logFile, "📄 Response: " . substr($response, 0, 1000));
            return [];
        }

        // Save the raw response for debugging
        $responseFile = $debugDir . '/puppeteer_response_' . time() . '.json';
        file_put_contents($responseFile, $response);
        $this->logToFile($logFile, "💾 Saved response to {$responseFile}");

        // Parse the response
        $this->logToFile($logFile, "🔍 Parsing JSON response");
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logToFile($logFile, "❌ JSON parse error: " . json_last_error_msg());
            $this->logToFile($logFile, "📄 Raw response: " . substr($response, 0, 500));
            return [];
        }

        if (!isset($data['reviews']) || !is_array($data['reviews'])) {
            $this->logToFile($logFile, "❌ Invalid response format from Puppeteer function");
            $this->logToFile($logFile, "📄 Response structure: " . json_encode(array_keys($data)));
            return [];
        }

        $reviews = $data['reviews'];
        $totalReviews = $data['total'] ?? count($reviews);
        $bookTitle = $data['book_title'] ?? 'Unknown';

        $this->logToFile($logFile, "✅ Successfully fetched {$totalReviews} reviews for book '{$bookTitle}' using Puppeteer");

        return $reviews;
    }

    /**
     * Extract reviews from HTML content
     *
     * @param string $response The HTML response
     * @param string $reviewsUrl The reviews URL
     * @param string $debugDir The debug directory
     * @return array Array of reviews
     */
    protected function extractReviewsFromHtml(string $response, string $reviewsUrl, string $debugDir): array {
        $reviews = [];

        // Try multiple patterns for review blocks to handle different Goodreads layouts
        $reviewBlocks = [];

        // Pattern 1: Modern Goodreads layout with ReviewCard components (article tag)
        if (preg_match_all('/<article[^>]*class="ReviewCard"[^>]*>.*?<\/article>/is', $response, $matches)) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found " . count($matches[0]) . " reviews using Pattern 1 (ReviewCard article)");
            $reviewBlocks = array_merge($reviewBlocks, array_map(function($block) {
                return ['0' => $block, '1' => 'modern_' . md5($block)];
            }, $matches[0]));
        }

        // Pattern 2: Classic review layout
        if (preg_match_all('/<div class="review"[^>]*id="review_(\d+)".*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/is', $response, $matches, PREG_SET_ORDER)) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found " . count($matches) . " reviews using Pattern 2 (classic)");
            $reviewBlocks = array_merge($reviewBlocks, $matches);
        }

        // Pattern 3: Alternative review layout
        if (preg_match_all('/<div[^>]+class="[^"]*review[^"]*"[^>]*id="review_(\d+)".*?<\/div>\s*<\/div>\s*<\/div>/is', $response, $matches, PREG_SET_ORDER)) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found " . count($matches) . " reviews using Pattern 3 (alternative)");
            $reviewBlocks = array_merge($reviewBlocks, $matches);
        }

        // Pattern 4: Newer review layout with articles
        if (preg_match_all('/<article[^>]+class="[^"]*review[^"]*"[^>]*id="review_(\d+)".*?<\/article>/is', $response, $matches, PREG_SET_ORDER)) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found " . count($matches) . " reviews using Pattern 4 (article)");
            $reviewBlocks = array_merge($reviewBlocks, $matches);
        }

        // Pattern 5: Community Reviews section with ReviewsList
        if (preg_match('/<div[^>]*id="CommunityReviews"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is', $response, $communityMatch)) {
            $communitySection = $communityMatch[1];
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found CommunityReviews section");

            // Try to find ReviewsList items
            if (preg_match_all('/<div[^>]*class="ReviewsList__item[^"]*"[^>]*>(.*?)<\/div>\s*<\/div>\s*<\/div>/is', $communitySection, $matches)) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found " . count($matches[0]) . " reviews using Pattern 5a (ReviewsList__item)");
                $reviewBlocks = array_merge($reviewBlocks, array_map(function($block) {
                    return ['0' => $block, '1' => 'modern_' . md5($block)];
                }, $matches[0]));
            }

            // Try to find ReviewCard articles within CommunityReviews
            if (preg_match_all('/<article[^>]*class="ReviewCard"[^>]*>.*?<\/article>/is', $communitySection, $matches)) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found " . count($matches[0]) . " reviews using Pattern 5b (CommunityReviews > ReviewCard)");
                $reviewBlocks = array_merge($reviewBlocks, array_map(function($block) {
                    return ['0' => $block, '1' => 'modern_' . md5($block)];
                }, $matches[0]));
            }
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Total review blocks found: " . count($reviewBlocks));

        // Process the review blocks
        foreach ($reviewBlocks as $index => $block) {
            // No need to limit here as we'll limit in the parent method

            $reviewId = $block[1];
            $reviewHtml = $block[0];

            // Save individual review HTML for debugging
            file_put_contents($debugDir . "/review_block_{$index}.html", $reviewHtml);

            // Extract reviewer name with multiple patterns
            $reviewerName = 'Goodreads User';

            // Modern pattern: ReviewCard aria-label (contains reviewer name)
            if (preg_match('/aria-label="Review by ([^"]+)"/i', $reviewHtml, $matches)) {
                $reviewerName = trim($matches[1]);
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found reviewer name from aria-label: {$reviewerName}");
            }
            // Modern pattern: User link with data-testid
            else if (preg_match('/<a[^>]*data-testid="user-profile-link"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                $reviewerName = trim($matches[1]);
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found reviewer name from user-profile-link: {$reviewerName}");
            }
            // Modern pattern: ReviewCard__profile section
            else if (preg_match('/<div class="ReviewCard__profile">.*?<a[^>]*>([^<]+)<\/a>/is', $reviewHtml, $matches)) {
                $reviewerName = trim($matches[1]);
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found reviewer name from ReviewCard__profile: {$reviewerName}");
            }
            // Classic patterns
            else if (preg_match('/<a class="user"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                $reviewerName = trim($matches[1]);
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found reviewer name from user class: {$reviewerName}");
            } else if (preg_match('/<a[^>]+class="[^"]*reviewer[^"]*"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                $reviewerName = trim($matches[1]);
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found reviewer name from reviewer class: {$reviewerName}");
            } else if (preg_match('/<span[^>]+class="[^"]*reviewer[^"]*"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                $reviewerName = trim($matches[1]);
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found reviewer name from reviewer span: {$reviewerName}");
            }

            // If we still have a generic name, generate a unique one based on the review content
            if ($reviewerName === 'Goodreads User') {
                $uniqueId = substr(md5($reviewHtml), 0, 8);
                $reviewerName = "Goodreads User #{$uniqueId}";
                $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Using generated unique name: {$reviewerName}");
            }

            // Extract rating with multiple patterns
            $rating = 0;

            // Modern pattern: RatingStars with aria-label
            if (preg_match('/<span[^>]*aria-label="Rating ([0-9.]+) out of 5"[^>]*>/i', $reviewHtml, $matches)) {
                $rating = (float)$matches[1];
            }
            // Modern pattern: RatingStars with data-testid
            else if (preg_match('/<span[^>]*data-testid="rating-stars"[^>]*aria-label="([^"]*)"[^>]*>/i', $reviewHtml, $matches)) {
                if (preg_match('/([0-9.]+) out of 5/i', $matches[1], $ratingMatch)) {
                    $rating = (float)$ratingMatch[1];
                }
            }
            // Classic pattern: Static stars
            else if (preg_match('/<span class="static-stars"[^>]*title="([^"]+)"/i', $reviewHtml, $matches)) {
                if (preg_match('/(\d+)/', $matches[1], $ratingMatch)) {
                    $rating = (int)$ratingMatch[1];
                }
            }
            // Pattern: Rating value in data attribute
            else if (preg_match('/<span[^>]+data-rating="([1-5])"/i', $reviewHtml, $matches)) {
                $rating = (int)$matches[1];
            }
            // Pattern: Stars in class name
            else if (preg_match('/<span class="[^"]*p10[^"]*"[^>]*>/i', $reviewHtml)) {
                $rating = 5;
            } else if (preg_match('/<span class="[^"]*p8[^"]*"[^>]*>/i', $reviewHtml)) {
                $rating = 4;
            } else if (preg_match('/<span class="[^"]*p6[^"]*"[^>]*>/i', $reviewHtml)) {
                $rating = 3;
            } else if (preg_match('/<span class="[^"]*p4[^"]*"[^>]*>/i', $reviewHtml)) {
                $rating = 2;
            } else if (preg_match('/<span class="[^"]*p2[^"]*"[^>]*>/i', $reviewHtml)) {
                $rating = 1;
            }

            // Extract review text with multiple patterns
            $reviewText = '';

            // Debug the review text extraction
            $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Extracting review text for block {$index}");

            // Modern pattern: TruncatedContent structure with div.TruncatedContent__text--large
            if (preg_match('/<div[^>]*class="TruncatedContent__text--large[^"]*"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using TruncatedContent__text--large pattern");
            }
            // Modern pattern: TruncatedContent structure with contentContainer
            else if (preg_match('/<div[^>]*class="TruncatedContent__text[^"]*"[^>]*data-testid="contentContainer"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using TruncatedContent with contentContainer pattern");
            }
            // Modern pattern: Formatted span inside TruncatedContent
            else if (preg_match('/<div[^>]*class="TruncatedContent[^"]*"[^>]*>.*?<span[^>]*class="Formatted"[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using Formatted span inside TruncatedContent pattern");
            }
            // Modern pattern: ReviewText with data-testid
            else if (preg_match('/<div[^>]*data-testid="reviewText"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using reviewText data-testid pattern");
            }
            // Modern pattern: Review content in Formatted section (span class="Formatted")
            else if (preg_match('/<span[^>]*class="Formatted"[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using span.Formatted pattern");
            }
            // Modern pattern: Review content in Formatted section (div class="Formatted")
            else if (preg_match('/<div[^>]*class="Formatted"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using div.Formatted pattern");
            }
            // Classic patterns
            else if (preg_match('/<div class="reviewText"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using classic reviewText with span pattern");
            }
            else if (preg_match('/<div[^>]+class="[^"]*reviewText[^"]*"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using classic reviewText pattern");
            }
            else if (preg_match('/<div[^>]+class="[^"]*reviewContent[^"]*"[^>]*>(.*?)<\/div>/is', $reviewHtml, $matches)) {
                $reviewText = trim(strip_tags($matches[1]));
                $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using classic reviewContent pattern");
            }

            // If we still don't have review text, try a more aggressive approach
            if (empty($reviewText)) {
                // Try to find any content in a Formatted element
                if (preg_match_all('/<[^>]*class="Formatted"[^>]*>(.*?)<\/[^>]*>/is', $reviewHtml, $matches)) {
                    $reviewText = trim(strip_tags(implode(' ', $matches[1])));
                    $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found review text using aggressive Formatted pattern");
                }
            }

            // Extract review date with multiple patterns
            $reviewDate = null;

            // Modern pattern: Date with data-testid
            if (preg_match('/<div[^>]*data-testid="reviewDate"[^>]*>([^<]+)<\/div>/i', $reviewHtml, $matches)) {
                $reviewDate = $this->formatDate($matches[1]);
            }
            // Classic patterns
            else if (preg_match('/<a class="reviewDate"[^>]*>([^<]+)<\/a>/i', $reviewHtml, $matches)) {
                $reviewDate = $this->formatDate($matches[1]);
            }
            else if (preg_match('/<time[^>]*datetime="([^"]+)"[^>]*>/i', $reviewHtml, $matches)) {
                $reviewDate = substr($matches[1], 0, 10); // Extract YYYY-MM-DD
            }
            else if (preg_match('/<span[^>]+class="[^"]*reviewDate[^"]*"[^>]*>([^<]+)<\/span>/i', $reviewHtml, $matches)) {
                $reviewDate = $this->formatDate($matches[1]);
            }

            // Use current date if no date found
            if (empty($reviewDate)) {
                $reviewDate = date('Y-m-d');
            }

            // Skip reviews without text or rating
            if (empty($reviewText) || $rating == 0) {
                $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Skipping review block {$index} - missing text or rating");
                continue;
            }

            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Extracted review from block {$index}: {$reviewerName}, rating: {$rating}");

            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $reviewerName,
                'reviewer_age' => null,
                'review_date' => $reviewDate,
                'original_rating' => "{$rating}/5",
                'rating_value' => (float)$rating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating((float)$rating, 5),
                'review_text' => $this->cleanText($reviewText),
                'metadata' => json_encode([
                    'review_id' => $reviewId,
                    'review_url' => "{$reviewsUrl}#{$reviewId}",
                    'is_synthetic' => false
                ])
            ];
        }

        // If no reviews found but we can see we're on a CAPTCHA page, set a specific error
        if (empty($reviews) && (strpos($response, 'captcha') !== false || strpos($response, 'robot check') !== false)) {
            $this->lastError = "Goodreads is showing a CAPTCHA or robot check page. Try again later.";
            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ CAPTCHA or robot check detected");
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Total reviews extracted from this page: " . count($reviews));
        return $reviews;
    }

    /**
     * Extract aggregate rating from Goodreads page
     *
     * @param string $html The HTML content
     * @param string $bookUrl The book URL
     * @return array|null The aggregate review data or null if not found
     */
    private function extractAggregateRating(string $html, string $bookUrl): ?array {
        $debugDir = __DIR__ . '/debug';
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Extracting aggregate rating");

        $rating = 0;
        $ratingCount = 0;
        $reviewCount = 0;

        // Modern pattern: RatingStatistics with aria-label (2023 design)
        if (preg_match('/<div[^>]*class="RatingStatistics__rating"[^>]*>([0-9.]+)<\/div>/i', $html, $ratingMatch)) {
            $rating = (float)$ratingMatch[1];

            // Try to get ratings count
            if (preg_match('/<span[^>]*data-testid="ratingsCount"[^>]*>([^<]+)&nbsp;<!-- -->ratings<\/span>/i', $html, $countMatch)) {
                $ratingCount = (int)str_replace(',', '', $countMatch[1]);
            }

            // Try to get reviews count
            if (preg_match('/<span[^>]*data-testid="reviewsCount"[^>]*>([^<]+)&nbsp;<!-- -->reviews<\/span>/i', $html, $reviewsMatch)) {
                $reviewCount = (int)str_replace(',', '', $reviewsMatch[1]);
            }
        }
        // New pattern: RatingStatistics__interactive (2023-2024 design)
        else if (preg_match('/<a class="RatingStatistics__RatingStatistics__interactive[^"]*"[^>]*href="#CommunityReviews"[^>]*>([^<]*)<\/a>/i', $html, $ratingMatch)) {
            // Extract ratings and reviews count from the link text
            if (preg_match('/([0-9,.]+) ratings ([0-9,.]+) reviews/i', $ratingMatch[1], $countMatch)) {
                $ratingCount = (int)str_replace([',', '.'], '', $countMatch[1]);
                $reviewCount = (int)str_replace([',', '.'], '', $countMatch[2]);
            }

            // Extract rating from the RatingStatistics__rating div
            if (preg_match('/<div class="RatingStatistics__rating"[^>]*>([0-9.]+)<\/div>/i', $html, $ratingValueMatch)) {
                $rating = (float)$ratingValueMatch[1];
            }
        }
        // Classic pattern: Average rating in meta tag
        else if (preg_match('/<span itemprop="ratingValue"[^>]*>([^<]+)<\/span>/i', $html, $ratingMatch)) {
            $rating = (float)$ratingMatch[1];

            // Try to get ratings count
            if (preg_match('/<meta itemprop="ratingCount" content="([^"]+)"/i', $html, $countMatch)) {
                $ratingCount = (int)$countMatch[1];
            }

            // Try to get reviews count
            if (preg_match('/<meta itemprop="reviewCount" content="([^"]+)"/i', $html, $reviewsMatch)) {
                $reviewCount = (int)$reviewsMatch[1];
            }
        }
        // Pattern for aria-label with average rating (2024 design)
        else if (preg_match('/aria-label="Average rating of ([0-9.]+) stars."[^>]*>/i', $html, $ratingMatch)) {
            $rating = (float)$ratingMatch[1];

            // Try to get ratings and reviews count from the CommunityReviews link
            if (preg_match('/<a[^>]*href="#CommunityReviews"[^>]*>([0-9,.]+) ratings ([0-9,.]+) reviews<\/a>/i', $html, $countMatch)) {
                $ratingCount = (int)str_replace([',', '.'], '', $countMatch[1]);
                $reviewCount = (int)str_replace([',', '.'], '', $countMatch[2]);
            }
        }

        if ($rating > 0) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Found aggregate rating: {$rating}/5 from {$ratingCount} ratings and {$reviewCount} reviews");

            return [
                'source_id' => $this->sourceId,
                'reviewer_name' => "Goodreads Aggregate",
                'reviewer_age' => null,
                'review_date' => date('Y-m-d'),
                'original_rating' => "{$rating}/5",
                'rating_value' => $rating,
                'rating_scale' => 5,
                'rating_normalised' => $this->normalizeRating($rating, 5),
                'review_text' => "This book has an average rating of {$rating}/5 based on {$ratingCount} ratings and {$reviewCount} reviews on Goodreads.",
                'metadata' => json_encode([
                    'book_url' => $bookUrl,
                    'is_synthetic' => false,
                    'is_aggregate' => true,
                    'ratings_count' => $ratingCount,
                    'reviews_count' => $reviewCount
                ])
            ];
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ No aggregate rating found");
        return null;
    }

    /**
     * Fetch reviews using the VPS-based Headless Browser service
     *
     * @param string $goodreadsUrl The URL of the Goodreads book page
     * @param int $limit Maximum number of reviews to return
     * @param array $options Additional options for the scraper
     *                      - maxPages: Maximum number of pages to scrape
     *                      - continueFromLast: Whether to continue from the last scrape
     * @return array Array of reviews
     */
    private function fetchReviewsWithHeadlessBrowser(string $goodreadsUrl, int $limit, array $options = []): array {
        // Debug: Log the limit being requested
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 [DEBUG] Requesting {$limit} reviews from Headless Browser");
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 [DEBUG] Options: " . json_encode($options));
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "� [VPS-Scraper-Goodreads] Attempting to fetch reviews using Puppeteer with full page JS evaluation for URL: {$goodreadsUrl}");

        // Use the VPS IP address as the default if environment variable is not set
        $apiUrl = getenv('HEADLESS_BROWSER_API_URL') ?: 'http://37.27.31.107:3000';
        // Use the API key with the year suffix as specified in the server config
        $apiKey = getenv('HEADLESS_BROWSER_API_KEY') ?: 'stories-scraper-api-key-2023';

        // Log additional debug information
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Debug: Checking if VPS scraper is reachable");

        // Test if the VPS is reachable
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$apiUrl}/health");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $healthResponse = curl_exec($ch);
        $healthHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Get verbose information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);

        // Log the verbose output
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 CURL Verbose Log:\n{$verboseLog}");

        curl_close($ch);

        if ($healthHttpCode !== 200) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ VPS Headless Browser API is not reachable: HTTP {$healthHttpCode}");
            $this->logToFile($debugDir . '/goodreads-log.txt', "⚠️ Response: {$healthResponse}");
            error_log("⚠️⚠️⚠️ VPS SCRAPER NOT REACHABLE - CHECK CONNECTION TO {$apiUrl} ⚠️⚠️⚠️");

            // Try to ping the server
            $pingResult = shell_exec("ping -c 1 -W 2 37.27.31.107");
            $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Ping Result:\n{$pingResult}");

            // Try to telnet to the port
            $telnetResult = shell_exec("timeout 2 bash -c '</dev/tcp/37.27.31.107/3000' && echo 'Port is open' || echo 'Port is closed'");
            $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Telnet Result: {$telnetResult}");
        } else {
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ VPS Headless Browser API is reachable");
            $this->logToFile($debugDir . '/goodreads-log.txt', "✅ Health Response: {$healthResponse}");
        }

        // Log the API URL for debugging
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔗 Using VPS Headless Browser API URL: {$apiUrl}");

        // Request more reviews than needed to ensure we get enough
        $requestLimit = min(100, $limit * 2); // Request up to 100 reviews or double the limit

        // Extract options
        $maxPages = $options['maxPages'] ?? 20;
        $continueFromLast = $options['continueFromLast'] ?? false;

        // Build the request URL with options - use the exact parameter names expected by the Node.js server
        $url = "{$apiUrl}/scrape/goodreads?url=" . urlencode($goodreadsUrl);

        // Add limit parameter (required by Node.js server)
        $url .= "&limit={$limit}";

        // Log the actual limit being used vs requested
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Using actual limit={$limit} (original requestLimit was {$requestLimit})");

        // Add maxPages parameter (required by Node.js server)
        $url .= "&maxPages={$maxPages}";

        // Add continueFromLast parameter if true (camelCase as expected by Node.js server)
        if ($continueFromLast) {
            $url .= "&continueFromLast=1";
            $this->logToFile($debugDir . '/goodreads-log.txt', "🔄 Setting continueFromLast=1 to continue from last scrape");
        } else {
            $url .= "&continueFromLast=0";
            $this->logToFile($debugDir . '/goodreads-log.txt', "🔄 Setting continueFromLast=0 (not continuing from last scrape)");
        }

        // Add force parameter (always include it with the correct value)
        $forceValue = (isset($options['force']) && $options['force']) ? "1" : "0";
        $url .= "&force={$forceValue}";
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔄 Setting force={$forceValue} parameter");

        // Log all parameters for debugging
        $this->logToFile($debugDir . '/goodreads-log.txt', "📝 Parameters being sent to Node.js server:");
        $this->logToFile($debugDir . '/goodreads-log.txt', "   - url: " . urlencode($goodreadsUrl));
        $this->logToFile($debugDir . '/goodreads-log.txt', "   - limit: {$limit}");
        $this->logToFile($debugDir . '/goodreads-log.txt', "   - maxPages: {$maxPages}");
        $this->logToFile($debugDir . '/goodreads-log.txt', "   - continueFromLast: {$continueFromLast}" . ($continueFromLast ? " (will be sent as 1)" : " (will be sent as 0)"));
        $this->logToFile($debugDir . '/goodreads-log.txt', "   - force: " . (isset($options['force']) && $options['force'] ? "true" : "false") . " (will be sent as {$forceValue})");

        // Log the full URL for debugging
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔗 Full request URL: {$url}");

        // Make the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-api-key: stories-scraper-api-key-2023"
        ]);

        // Enable verbose output for debugging
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Get verbose information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);

        // Log the verbose output
        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 CURL Verbose Log for VPS Request:\n{$verboseLog}");

        curl_close($ch);

        // Save the response for debugging
        file_put_contents($debugDir . '/goodreads-vps-response.json', $response);

        if ($httpCode >= 400) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ VPS Headless Browser API error: HTTP {$httpCode}");
            return [];
        }

        // Parse the response
        $data = json_decode($response, true);

        if (!$data || !isset($data['reviews']) || empty($data['reviews'])) {
            $this->logToFile($debugDir . '/goodreads-log.txt', "❌ No reviews found in VPS Headless Browser response");
            return [];
        }

        $reviewCount = count($data['reviews']);
        $this->logToFile($debugDir . '/goodreads-log.txt', "✅ [VPS-Scraper-Success] Found {$reviewCount} reviews using Puppeteer-based Headless Browser");

        // Add a prominent message to the main log
        error_log("✅✅✅ GOODREADS VPS SCRAPER SUCCESSFULLY RETURNED {$reviewCount} REVIEWS ✅✅✅");

        // Process the reviews to match our expected format
        $reviews = [];
        foreach ($data['reviews'] as $review) {
            // Skip reviews without text or rating
            if (empty($review['review_text']) || !isset($review['rating'])) {
                continue;
            }

            // Convert the review to our format
            $reviews[] = [
                'source_id' => $this->sourceId,
                'reviewer_name' => $review['reviewer_name'] ?? 'Goodreads User',
                'reviewer_age' => null,
                'review_date' => $review['review_date'] ?? date('Y-m-d'),
                'original_rating' => "{$review['rating']}/5",
                'rating_value' => $review['rating'],
                'rating_scale' => 5,
                'rating_normalised' => $review['rating_normalised'] ?? $this->normalizeRating($review['rating'], 5),
                'review_text' => $review['review_text'],
                'metadata' => $review['metadata'] ?? json_encode([
                    'book_url' => $goodreadsUrl,
                    'is_synthetic' => false,
                    'is_aggregate' => isset($review['metadata']) && strpos($review['metadata'], 'is_aggregate') !== false
                ])
            ];
        }

        return $reviews;
    }
}
