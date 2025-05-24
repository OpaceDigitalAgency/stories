<?php
/**
 * Goodreads Book Metadata Fetcher
 *
 * This class fetches clean book metadata from Goodreads by scraping HTML directly.
 * It bypasses VPS to prevent GraphQL fragment corruption in character and genre fields.
 */

namespace Services\ReviewFetcher;

use PDO;
use Exception;

class GoodreadsBookMetadataFetcher {
    
    private $db;
    
    /**
     * Constructor
     *
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Get clean book metadata from Goodreads URL
     *
     * @param string $bookUrl The Goodreads book URL
     * @return array|null Clean book metadata or null if failed
     */
    public function getCleanBookMetadata(string $bookUrl): ?array {
        // Create debug directory if it doesn't exist
        $debugDir = __DIR__ . '/debug';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        
        // Ensure we're using the main book page, not reviews page
        if (strpos($bookUrl, '/reviews') !== false) {
            $bookUrl = str_replace('/reviews', '', $bookUrl);
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "⚠️ Converted reviews URL to main book URL: {$bookUrl}");
        }
        
        $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "🔍 Fetching clean metadata from: {$bookUrl}");
        
        // Make direct HTTP request (bypass VPS to prevent GraphQL corruption)
        $response = $this->makeRequest($bookUrl);
        
        if ($response === false) {
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "❌ Failed to fetch book page");
            return null;
        }
        
        // Save HTML for debugging
        file_put_contents($debugDir . '/goodreads_book_metadata.html', substr($response, 0, 500000));
        
        // Extract clean metadata using HTML parsing only
        return $this->extractCleanMetadata($response, $bookUrl, $debugDir);
    }
    
    /**
     * Extract clean metadata from HTML response
     *
     * @param string $response HTML response
     * @param string $bookUrl Book URL
     * @param string $debugDir Debug directory
     * @return array Clean metadata
     */
    private function extractCleanMetadata(string $response, string $bookUrl, string $debugDir): array {
        $details = [];
        
        // Extract title with multiple patterns
        if (preg_match('/<h1[^>]*data-testid="bookTitle"[^>]*>(.*?)<\/h1>/is', $response, $matches)) {
            $details['title'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<h1[^>]*>(.*?)\s+by\s+.*?<\/h1>/is', $response, $matches)) {
            $details['title'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<h1[^>]*class="[^"]*gr-h1[^"]*"[^>]*>(.*?)<\/h1>/is', $response, $matches)) {
            $details['title'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $response, $matches)) {
            $title = trim(strip_tags($matches[1]));
            // Make sure it's not a navigation title or other non-book title
            if (!preg_match('/home|browse|search|sign|login/i', $title) && strlen($title) > 2) {
                $details['title'] = $title;
            }
        } else if (preg_match('/<meta[^>]*property="og:title"[^>]*content="([^"]+)"/i', $response, $matches)) {
            $details['title'] = trim($matches[1]);
        }
        
        // Extract author with multiple patterns
        if (preg_match('/<span[^>]*class="[^"]*ContributorLink__name[^"]*"[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['author'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/by\s+<a[^>]*class="[^"]*authorName[^"]*"[^>]*>(.*?)<\/a>/is', $response, $matches)) {
            $details['author'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/by\s+<a[^>]*>(.*?)<\/a>/is', $response, $matches)) {
            $details['author'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<a[^>]*class="[^"]*authorName[^"]*"[^>]*>(.*?)<\/a>/is', $response, $matches)) {
            $details['author'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/by\s+([^<\n]+)/i', $response, $matches)) {
            $author = trim($matches[1]);
            // Clean up common suffixes
            $author = preg_replace('/\s*\(.*?\)\s*$/', '', $author);
            if (strlen($author) > 2 && !preg_match('/\d{4}/', $author)) {
                $details['author'] = $author;
            }
        }
        
        // Extract cover image
        if (preg_match('/<img[^>]*class="[^"]*ResponsiveImage[^"]*"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        } else if (preg_match('/<img[^>]*id="coverImage"[^>]*src="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        } else if (preg_match('/<meta[^>]*property="og:image"[^>]*content="([^"]+)"/i', $response, $matches)) {
            $details['cover_url'] = $matches[1];
        }
        
        // Extract description
        if (preg_match('/<span[^>]*class="[^"]*Formatted[^"]*"[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<div[^>]*class="[^"]*DetailsLayoutRightParagraph[^"]*"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<div[^>]*id="description"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        }
        
        // Extract clean characters (avoiding GraphQL corruption)
        $details['characters'] = $this->extractCleanCharacters($response, $debugDir);
        
        // Extract clean genres (avoiding GraphQL corruption)
        $details['genres'] = $this->extractCleanGenres($response, $debugDir);
        
        // Extract other metadata
        $this->extractPublishingInfo($response, $details);
        $this->extractRatingInfo($response, $details);
        $this->extractSeriesInfo($response, $details, $debugDir);
        
        $details['url'] = $bookUrl;
        
        $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Extracted clean metadata successfully");
        
        return $details;
    }
    
    /**
     * Extract clean character data (no GraphQL fragments)
     *
     * @param string $response HTML response
     * @param string $debugDir Debug directory
     * @return array Clean character names
     */
    private function extractCleanCharacters(string $response, string $debugDir): array {
        $characters = [];
        
        $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "🔍 Starting character extraction...");
        
        // Pattern 1: Modern Goodreads character links (most specific first)
        // Look for character links with href="/characters/" pattern
        if (preg_match_all('/<a[^>]*href="\/characters\/[^"]*"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            foreach ($matches[1] as $char) {
                $cleanChar = trim(strip_tags($char));
                if ($this->isValidCharacterName($cleanChar)) {
                    $characters[] = $cleanChar;
                }
            }
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found characters using character links pattern: " . implode(', ', $characters));
        }
        
        // Pattern 2: Characters section with data-testid
        if (empty($characters) && preg_match('/<div[^>]*data-testid="characters"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $charactersHtml = $matches[1];
            if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/is', $charactersHtml, $charMatches)) {
                foreach ($charMatches[1] as $char) {
                    $cleanChar = trim(strip_tags($char));
                    if ($this->isValidCharacterName($cleanChar)) {
                        $characters[] = $cleanChar;
                    }
                }
                $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found characters using data-testid pattern: " . implode(', ', $characters));
            }
        }
        
        // Pattern 3: Characters in DescListItem structure
        if (empty($characters) && preg_match('/<div[^>]*class="[^"]*DescListItem[^"]*"[^>]*>.*?<dt[^>]*>Characters<\/dt>.*?<dd[^>]*>(.*?)<\/dd>/is', $response, $matches)) {
            $charactersHtml = $matches[1];
            if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/is', $charactersHtml, $charMatches)) {
                foreach ($charMatches[1] as $char) {
                    $cleanChar = trim(strip_tags($char));
                    if ($this->isValidCharacterName($cleanChar)) {
                        $characters[] = $cleanChar;
                    }
                }
                $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found characters using DescListItem pattern: " . implode(', ', $characters));
            }
        }
        
        // Pattern 4: Characters in BookPageMetadataSection
        if (empty($characters) && preg_match('/<div[^>]*class="[^"]*BookPageMetadataSection[^"]*"[^>]*>.*?Characters.*?<\/div>/is', $response, $matches)) {
            $sectionHtml = $matches[0];
            if (preg_match_all('/<a[^>]*href="\/characters\/[^"]*"[^>]*>([^<]+)<\/a>/i', $sectionHtml, $charMatches)) {
                foreach ($charMatches[1] as $char) {
                    $cleanChar = trim(strip_tags($char));
                    if ($this->isValidCharacterName($cleanChar)) {
                        $characters[] = $cleanChar;
                    }
                }
                $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found characters using BookPageMetadataSection pattern: " . implode(', ', $characters));
            }
        }
        
        // Pattern 5: Generic character section (fallback)
        if (empty($characters) && preg_match('/Characters[^<]*<[^>]*>(.*?)<\/[^>]*>/is', $response, $matches)) {
            $charactersHtml = $matches[1];
            if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/is', $charactersHtml, $charMatches)) {
                foreach ($charMatches[1] as $char) {
                    $cleanChar = trim(strip_tags($char));
                    if ($this->isValidCharacterName($cleanChar) && !$this->looksLikeReviewText($cleanChar)) {
                        $characters[] = $cleanChar;
                    }
                }
                $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found characters using generic pattern: " . implode(', ', $characters));
            }
        }
        
        if (empty($characters)) {
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "⚠️ No characters found with any pattern");
        }
        
        return $characters;
    }
    
    /**
     * Check if text looks like review content rather than character name
     *
     * @param string $text Text to check
     * @return bool True if it looks like review text
     */
    private function looksLikeReviewText(string $text): bool {
        // Check for review-like phrases
        $reviewPhrases = [
            'stars', 'rating', 'review', 'book', 'read', 'story', 'plot', 'author',
            'recommend', 'love', 'hate', 'good', 'bad', 'amazing', 'terrible',
            'reason why', 'given this', 'rather than'
        ];
        
        $lowerText = strtolower($text);
        foreach ($reviewPhrases as $phrase) {
            if (strpos($lowerText, $phrase) !== false) {
                return true;
            }
        }
        
        // Check if it's too long to be a character name
        if (strlen($text) > 50) {
            return true;
        }
        
        // Check if it contains sentence-like punctuation
        if (preg_match('/[.!?]/', $text)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Extract clean genre data (no GraphQL fragments)
     *
     * @param string $response HTML response
     * @param string $debugDir Debug directory
     * @return array Clean genre names
     */
    private function extractCleanGenres(string $response, string $debugDir): array {
        $genres = [];
        
        // Pattern 1: Genre links
        if (preg_match_all('/<a[^>]*class="[^"]*bookPageGenreLink[^"]*"[^>]*>(.*?)<\/a>/i', $response, $matches)) {
            foreach ($matches[1] as $genre) {
                $cleanGenre = trim(strip_tags($genre));
                if ($this->isValidGenreName($cleanGenre)) {
                    $genres[] = $cleanGenre;
                }
            }
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found genres using genre links: " . implode(', ', $genres));
        }
        
        // Pattern 2: Alternative genre links
        if (empty($genres) && preg_match_all('/<a[^>]*href="\/genres\/[^"]+"[^>]*>([^<]+)<\/a>/i', $response, $matches)) {
            foreach ($matches[1] as $genre) {
                $cleanGenre = trim(strip_tags($genre));
                if ($this->isValidGenreName($cleanGenre)) {
                    $genres[] = $cleanGenre;
                }
            }
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found genres using alternative pattern: " . implode(', ', $genres));
        }
        
        return $genres;
    }
    
    /**
     * Validate character name (ensure it's not a GraphQL fragment)
     *
     * @param string $character Character name to validate
     * @return bool True if valid character name
     */
    private function isValidCharacterName(string $character): bool {
        // Reject empty or very short names
        if (empty($character) || strlen($character) < 2) {
            return false;
        }
        
        // Reject GraphQL patterns
        $graphqlPatterns = [
            '__typename', 'edges', 'node', 'data', 'getReviews', 'getSimilarBooks',
            'getBasicGenres', 'kca://', 'amzn1.gr.', 'goodreads.v1', '"id":',
            '"name":', '"webUrl":', 'Paperback', 'numberOfPages'
        ];
        
        foreach ($graphqlPatterns as $pattern) {
            if (strpos($character, $pattern) !== false) {
                return false;
            }
        }
        
        // Reject if it looks like JSON
        if (json_decode($character) !== null) {
            return false;
        }
        
        // Reject very long strings (likely GraphQL responses)
        if (strlen($character) > 100) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate genre name (ensure it's not a GraphQL fragment)
     *
     * @param string $genre Genre name to validate
     * @return bool True if valid genre name
     */
    private function isValidGenreName(string $genre): bool {
        // Similar validation to characters but with genre-specific rules
        if (empty($genre) || strlen($genre) < 2) {
            return false;
        }
        
        $graphqlPatterns = [
            '__typename', 'edges', 'node', 'data', 'getBasicGenres',
            'kca://', 'amzn1.gr.', 'goodreads.v1', '"id":', '"name":', '"webUrl":'
        ];
        
        foreach ($graphqlPatterns as $pattern) {
            if (strpos($genre, $pattern) !== false) {
                return false;
            }
        }
        
        if (json_decode($genre) !== null) {
            return false;
        }
        
        if (strlen($genre) > 50) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Extract publishing information
     *
     * @param string $response HTML response
     * @param array &$details Details array to populate
     */
    private function extractPublishingInfo(string $response, array &$details): void {
        // Extract publisher and publication date with multiple patterns
        
        // Pattern 1: Modern DescListItem structure
        if (preg_match('/<div[^>]*class="[^"]*DescListItem[^"]*"[^>]*>.*?<dt[^>]*>Published<\/dt>.*?<dd[^>]*>(.*?)<\/dd>/is', $response, $matches)) {
            $publishedText = trim(strip_tags($matches[1]));
            $this->parsePublishedText($publishedText, $details);
        }
        
        // Pattern 2: BookPageMetadataSection
        if (empty($details['publisher']) && preg_match('/<div[^>]*class="[^"]*BookPageMetadataSection[^"]*"[^>]*>.*?Published.*?<\/div>/is', $response, $matches)) {
            $sectionHtml = $matches[0];
            if (preg_match('/Published[^<]*<[^>]*>([^<]+)</', $sectionHtml, $pubMatches)) {
                $publishedText = trim(strip_tags($pubMatches[1]));
                $this->parsePublishedText($publishedText, $details);
            }
        }
        
        // Pattern 3: Generic published pattern
        if (empty($details['publisher']) && preg_match('/Published\s+([^<\n]+)/i', $response, $matches)) {
            $publishedText = trim($matches[1]);
            $this->parsePublishedText($publishedText, $details);
        }
        
        // Extract page count with multiple patterns
        if (preg_match('/(\d+)\s+pages?/i', $response, $matches)) {
            $details['page_count'] = (int)$matches[1];
        } else if (preg_match('/<dt[^>]*>Pages<\/dt>\s*<dd[^>]*>(\d+)<\/dd>/i', $response, $matches)) {
            $details['page_count'] = (int)$matches[1];
        }
        
        // Extract ISBN with multiple patterns - but be more careful about false positives
        if (preg_match('/ISBN[:\s]*([0-9X-]{10,17})/i', $response, $matches)) {
            $isbn = preg_replace('/[^0-9X]/i', '', $matches[1]); // Remove hyphens
            if (strlen($isbn) == 13 && is_numeric($isbn)) {
                $details['isbn13'] = $isbn;
            } else if (strlen($isbn) == 10 && (is_numeric(substr($isbn, 0, 9)) || substr($isbn, -1) === 'X')) {
                $details['isbn'] = $isbn;
            }
        }
        
        // Extract format
        if (preg_match('/<dt[^>]*>Format<\/dt>\s*<dd[^>]*>([^<]+)<\/dd>/i', $response, $matches)) {
            $details['format'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/(\w+),\s*\d+\s+pages/i', $response, $matches)) {
            $details['format'] = trim($matches[1]);
        }
        
        // Extract language
        if (preg_match('/<dt[^>]*>Language<\/dt>\s*<dd[^>]*>([^<]+)<\/dd>/i', $response, $matches)) {
            $details['language'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/Language[:\s]*([^<\n]+)/i', $response, $matches)) {
            $details['language'] = trim($matches[1]);
        }
    }
    
    /**
     * Parse published text to extract publisher and date
     *
     * @param string $publishedText Published text
     * @param array &$details Details array to populate
     */
    private function parsePublishedText(string $publishedText, array &$details): void {
        // Try to extract publisher and date from various formats
        // Format: "Publisher on Date" or "Publisher, Date" or "Date by Publisher"
        
        if (preg_match('/^(.+?)\s+on\s+(.+)$/', $publishedText, $matches)) {
            $details['publisher'] = trim($matches[1]);
            $details['published_date'] = trim($matches[2]);
        } else if (preg_match('/^(.+?),\s*(.+)$/', $publishedText, $matches)) {
            // Check if first part looks like publisher or date
            if (preg_match('/\d{4}/', $matches[1])) {
                $details['published_date'] = trim($matches[1]);
                $details['publisher'] = trim($matches[2]);
            } else {
                $details['publisher'] = trim($matches[1]);
                $details['published_date'] = trim($matches[2]);
            }
        } else if (preg_match('/(.+?)\s+by\s+(.+)/', $publishedText, $matches)) {
            $details['published_date'] = trim($matches[1]);
            $details['publisher'] = trim($matches[2]);
        } else {
            // If we can't parse it, try to identify if it's a date or publisher
            if (preg_match('/\d{4}/', $publishedText)) {
                $details['published_date'] = $publishedText;
            } else {
                $details['publisher'] = $publishedText;
            }
        }
    }
    
    /**
     * Extract rating information
     *
     * @param string $response HTML response
     * @param array &$details Details array to populate
     */
    private function extractRatingInfo(string $response, array &$details): void {
        // Pattern 1: Modern RatingStatistics structure
        if (preg_match('/<div[^>]*class="[^"]*RatingStatistics[^"]*"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $ratingSection = $matches[1];
            
            // Extract average rating
            if (preg_match('/([0-9.]+)/i', $ratingSection, $ratingMatches)) {
                $details['average_rating'] = (float)$ratingMatches[1];
            }
            
            // Extract ratings and reviews count
            if (preg_match('/([0-9,]+)\s+ratings?\s+and\s+([0-9,]+)\s+reviews?/i', $ratingSection, $countMatches)) {
                $details['ratings_count'] = (int)str_replace(',', '', $countMatches[1]);
                $details['review_count'] = (int)str_replace(',', '', $countMatches[2]);
            }
        }
        
        // Pattern 2: RatingStars with data-testid
        if (empty($details['average_rating']) && preg_match('/<div[^>]*data-testid="ratingsCount"[^>]*>([^<]+)<\/div>/i', $response, $matches)) {
            $ratingText = trim($matches[1]);
            if (preg_match('/([0-9.]+)/', $ratingText, $ratingMatches)) {
                $details['average_rating'] = (float)$ratingMatches[1];
            }
        }
        
        // Pattern 3: Classic avg rating pattern
        if (empty($details['average_rating']) && preg_match('/([0-9.]+)\s+avg\s+rating/i', $response, $matches)) {
            $details['average_rating'] = (float)$matches[1];
        }
        
        // Pattern 4: RatingStars__RatingsValue
        if (empty($details['average_rating']) && preg_match('/<span[^>]*class="[^"]*RatingStars__RatingsValue[^"]*"[^>]*>([0-9.]+)<\/span>/i', $response, $matches)) {
            $details['average_rating'] = (float)$matches[1];
        }
        
        // Pattern 5: Meta property for rating
        if (empty($details['average_rating']) && preg_match('/<meta[^>]*property="books:rating:value"[^>]*content="([0-9.]+)"/i', $response, $matches)) {
            $details['average_rating'] = (float)$matches[1];
        }
        
        // Extract ratings count separately if not found above
        if (empty($details['ratings_count'])) {
            if (preg_match('/([0-9,]+)\s+ratings?/i', $response, $matches)) {
                $details['ratings_count'] = (int)str_replace(',', '', $matches[1]);
            }
        }
        
        // Extract review count separately if not found above
        if (empty($details['review_count'])) {
            if (preg_match('/([0-9,]+)\s+reviews?/i', $response, $matches)) {
                $details['review_count'] = (int)str_replace(',', '', $matches[1]);
            }
        }
        
        // Pattern 6: Text pattern with "ratings and reviews"
        if ((empty($details['ratings_count']) || empty($details['review_count'])) &&
            preg_match('/([0-9,]+)\s+ratings?\s+and\s+([0-9,]+)\s+reviews?/i', $response, $matches)) {
            $details['ratings_count'] = (int)str_replace(',', '', $matches[1]);
            $details['review_count'] = (int)str_replace(',', '', $matches[2]);
        }
        
        // Pattern 7: Aria-label with rating information
        if (empty($details['average_rating']) && preg_match('/aria-label="[^"]*([0-9.]+)\s+out\s+of\s+5\s+stars/i', $response, $matches)) {
            $details['average_rating'] = (float)$matches[1];
        }
    }
    
    /**
     * Extract series information
     *
     * @param string $response HTML response
     * @param array &$details Details array to populate
     * @param string $debugDir Debug directory
     */
    private function extractSeriesInfo(string $response, array &$details, string $debugDir): void {
        // Extract series information
        if (preg_match('/<dt[^>]*>Series<\/dt>\s*<dd[^>]*>.*?<a[^>]*>(.*?)<\/a>/is', $response, $matches)) {
            $details['series'] = trim(strip_tags($matches[1]));
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found series: {$details['series']}");
        }
    }
    
    /**
     * Make HTTP request
     *
     * @param string $url URL to request
     * @return string|false Response content or false on failure
     */
    private function makeRequest(string $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || $response === false) {
            return false;
        }
        
        return $response;
    }
    
    /**
     * Log message to file
     *
     * @param string $file Log file path
     * @param string $message Message to log
     */
    private function logToFile(string $file, string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($file, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
    }
}