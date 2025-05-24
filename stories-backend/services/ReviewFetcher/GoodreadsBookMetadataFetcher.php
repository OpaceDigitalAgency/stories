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
        
        // Extract title
        if (preg_match('/<h1[^>]*data-testid="bookTitle"[^>]*>(.*?)<\/h1>/is', $response, $matches)) {
            $details['title'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/<h1[^>]*>(.*?)\s+by\s+.*?<\/h1>/is', $response, $matches)) {
            $details['title'] = trim(strip_tags($matches[1]));
        }
        
        // Extract author
        if (preg_match('/<span[^>]*class="[^"]*ContributorLink__name[^"]*"[^>]*>(.*?)<\/span>/is', $response, $matches)) {
            $details['author'] = trim(strip_tags($matches[1]));
        } else if (preg_match('/by\s+<a[^>]*>(.*?)<\/a>/is', $response, $matches)) {
            $details['author'] = trim(strip_tags($matches[1]));
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
        
        // Pattern 1: Characters in description list
        if (preg_match('/<dt[^>]*>Characters<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/is', $response, $matches)) {
            $charactersHtml = $matches[1];
            if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/is', $charactersHtml, $charMatches)) {
                foreach ($charMatches[1] as $char) {
                    $cleanChar = trim(strip_tags($char));
                    if ($this->isValidCharacterName($cleanChar)) {
                        $characters[] = $cleanChar;
                    }
                }
                $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found characters using dt/dd pattern: " . implode(', ', $characters));
            }
        }
        
        // Pattern 2: Characters in text format
        if (empty($characters) && preg_match('/Characters\s*:?\s*([^<\n]+)/i', $response, $matches)) {
            $charText = trim($matches[1]);
            $charList = array_map('trim', explode(',', $charText));
            foreach ($charList as $char) {
                if ($this->isValidCharacterName($char)) {
                    $characters[] = $char;
                }
            }
            $this->logToFile($debugDir . '/goodreads-metadata-log.txt', "✅ Found characters using text pattern: " . implode(', ', $characters));
        }
        
        return $characters;
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
        // Extract publisher and publication date
        if (preg_match('/<dt[^>]*>Published<\/dt>\s*<dd[^>]*>.*?data-testid="contentContainer"[^>]*>(.*?)<\/div>/is', $response, $matches)) {
            $publishedText = trim(strip_tags($matches[1]));
            
            // Extract publisher
            if (preg_match('/^([^,]+?)(?:\s+on\s+|\s*,\s*|\s+\d)/', $publishedText, $pubMatches)) {
                $details['publisher'] = trim($pubMatches[1]);
            }
            
            // Extract publication date
            if (preg_match('/(\w+\s+\d+,?\s*\d{4})/', $publishedText, $dateMatches)) {
                $details['published_date'] = trim($dateMatches[1]);
            }
        }
        
        // Extract page count
        if (preg_match('/(\d+)\s+pages?/i', $response, $matches)) {
            $details['page_count'] = (int)$matches[1];
        }
        
        // Extract ISBN
        if (preg_match('/ISBN[:\s]*([0-9X]{10,13})/i', $response, $matches)) {
            $isbn = trim($matches[1]);
            if (strlen($isbn) == 13) {
                $details['isbn13'] = $isbn;
            } else if (strlen($isbn) == 10) {
                $details['isbn'] = $isbn;
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
        // Extract average rating and counts
        if (preg_match('/([0-9.]+)\s+avg\s+rating.*?([0-9,]+)\s+ratings?/is', $response, $matches)) {
            $details['average_rating'] = (float)$matches[1];
            $details['ratings_count'] = (int)str_replace(',', '', $matches[2]);
        }
        
        if (preg_match('/([0-9,]+)\s+reviews?/i', $response, $matches)) {
            $details['review_count'] = (int)str_replace(',', '', $matches[1]);
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