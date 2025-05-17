<?php
/**
 * Goodreads Review Fetcher
 *
 * This class fetches reviews from Goodreads by scraping the website.
 * Note: Goodreads API was deprecated, so we need to scrape the website.
 */

namespace Services\ReviewFetcher;

use PDO;

class GoodreadsReviewFetcher extends AbstractReviewFetcher {
    protected $aggregateRating = null; // Store aggregate rating separately
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
     */
    public function fetchReviewsByISBN(string $isbn, int $limit = 10): array {
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
                $this->lastError = "No book found on Goodreads for ISBN: $isbnToUse";
                return [];
            }
        }

        // Get book details
        $bookDetails = $this->getBookDetails($bookUrl);

        if (empty($bookDetails)) {
            $this->lastError = "Failed to get book details from Goodreads";
            return [];
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
        $reviews = $this->scrapeReviews($reviewsUrl, $limit);

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
        // First, try to get the Goodreads ID from OpenLibrary
        $goodreadsId = $this->getGoodreadsIdFromOpenLibrary($isbn);

        if ($goodreadsId) {
            $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', "✅ Found Goodreads ID {$goodreadsId} from OpenLibrary for ISBN {$isbn}");
            return "https://www.goodreads.com/book/show/{$goodreadsId}";
        }

        $this->logToFile(__DIR__ . '/debug/goodreads-log.txt', "⚠️ No Goodreads ID found in OpenLibrary for ISBN {$isbn}, falling back to search");

        // If OpenLibrary doesn't have the Goodreads ID, fall back to search
        // Build the search URL
        $searchUrl = "https://www.goodreads.com/search?q={$isbn}";

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
     * Log a message to a file (implementation from parent class)
     *
     * This method is already defined in the parent class with protected access,
     * so we don't need to redefine it here.
     */

    /**
     * Get book details from Goodreads
     *
     * @param string $bookUrl The book URL
     * @return array|null The book details or null if not found
     */
    private function getBookDetails(string $bookUrl): ?array {
        // Make the request
        $response = $this->makeRequest($bookUrl);

        if ($response === false) {
            return null;
        }

        $details = [];

        // Extract book title
        if (preg_match('/<h1 id="bookTitle"[^>]*>([^<]+)<\/h1>/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        }

        // Extract book author
        if (preg_match('/<a class="authorName"[^>]*><span[^>]*>([^<]+)<\/span><\/a>/i', $response, $matches)) {
            $details['author'] = trim($matches[1]);
        }

        // Extract book cover
        if (preg_match('/<img id="coverImage"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        }

        // Extract book description
        if (preg_match('/<div id="description"[^>]*>.*?<span[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        }

        // Extract average rating
        if (preg_match('/<span itemprop="ratingValue"[^>]*>([^<]+)<\/span>/i', $response, $matches)) {
            $details['average_rating'] = (float)trim($matches[1]);
        }

        // Extract ratings count
        if (preg_match('/<meta itemprop="ratingCount" content="([^"]+)"/i', $response, $matches)) {
            $details['ratings_count'] = (int)$matches[1];
        }

        // Extract publication info
        if (preg_match('/Published\s+(.*?)(?:\s+by\s+(.*?))?(?:\s+\(first published\s+(.*?)\))?</is', $response, $matches)) {
            if (!empty($matches[1])) {
                $details['published_date'] = trim($matches[1]);
            }
            if (!empty($matches[2])) {
                $details['publisher'] = trim($matches[2]);
            }
        }

        // Extract ISBN
        if (preg_match('/ISBN\s+(\d+X?)/i', $response, $matches)) {
            $details['isbn'] = $matches[1];
        }

        // Extract ISBN13
        if (preg_match('/ISBN13\s+(\d+)/i', $response, $matches)) {
            $details['isbn13'] = $matches[1];
        }

        // Extract page count
        if (preg_match('/(\d+)\s+pages/i', $response, $matches)) {
            $details['page_count'] = (int)$matches[1];
        }

        // Extract genres/shelves
        if (preg_match_all('/<a class="actionLinkLite bookPageGenreLink"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            $details['genres'] = array_map('trim', $matches[1]);
        }

        $details['url'] = $bookUrl;

        return $details;
    }

    /**
     * Scrape reviews from Goodreads
     *
     * @param string $reviewsUrl The reviews URL
     * @param int $limit Maximum number of reviews to fetch
     * @return array Array of review data
     */
    private function scrapeReviews(string $reviewsUrl, int $limit): array {
        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }

        $this->logToFile($debugDir . '/goodreads-log.txt', "🔍 Scraping reviews from URL: {$reviewsUrl}");

        $reviews = [];
        $page = 1;
        $maxPages = min(3, ceil($limit / 10)); // Limit to 3 pages maximum to avoid rate limiting

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
            // Look for pagination links
            if (preg_match('/<a[^>]*href="([^"]*page=(\d+)[^"]*)"[^>]*>Next<\/a>/i', $firstPageResponse, $matches) ||
                preg_match('/<a[^>]*href="([^"]*page=(\d+)[^"]*)"[^>]*>Show more reviews<\/a>/i', $firstPageResponse, $matches)) {

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

        // Limit the number of reviews to the requested limit
        return array_slice($reviews, 0, $limit);
    }

    /**
     * Extract reviews from HTML content
     *
     * @param string $response The HTML response
     * @param string $reviewsUrl The reviews URL
     * @param string $debugDir The debug directory
     * @return array Array of reviews
     */
    private function extractReviewsFromHtml(string $response, string $reviewsUrl, string $debugDir): array {
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

        // Modern pattern: RatingStatistics with aria-label
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


}
