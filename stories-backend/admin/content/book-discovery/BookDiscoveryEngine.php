<?php
/**
 * Book Discovery Engine
 * 
 * Core engine for discovering and scraping children's books from various sources
 */

require_once __DIR__ . '/scrapers/BookTrustScraper.php';
require_once __DIR__ . '/scrapers/GenericBookScraper.php';

class BookDiscoveryEngine {
    private $db;
    private $scrapers = [];
    private $discovered_sources = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->registerBuiltInScrapers();
    }
    
    private function registerBuiltInScrapers() {
        // Register known scrapers
        $this->scrapers['booktrust'] = new BookTrustScraper();
        $this->scrapers['generic'] = new GenericBookScraper();
    }
    
    /**
     * Discover books from a given URL
     */
    public function discoverFromURL($url) {
        // Clean the URL
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid URL provided");
        }
        
        // Check if we have a specific scraper for this domain
        $domain = parse_url($url, PHP_URL_HOST);
        
        foreach ($this->scrapers as $name => $scraper) {
            if ($scraper->canHandle($url)) {
                error_log("Using {$name} scraper for {$url}");
                return $scraper->scrape($url);
            }
        }
        
        // Fall back to generic scraper
        error_log("Using generic scraper for {$url}");
        return $this->scrapers['generic']->scrape($url);
    }
    
    /**
     * Discover new book sources automatically
     */
    public function discoverNewSources($searchQuery = "children's book recommendations 2024") {
        // This would use search APIs to find new sources
        // For now, return some known good sources
        return [
            [
                'url' => 'https://www.booktrust.org.uk/book-recommendations/',
                'title' => 'BookTrust Book Recommendations',
                'confidence' => 0.95
            ],
            [
                'url' => 'https://www.lovereading4kids.co.uk/',
                'title' => 'LoveReading4Kids',
                'confidence' => 0.90
            ],
            [
                'url' => 'https://www.booksfortopics.com/',
                'title' => 'Books for Topics',
                'confidence' => 0.85
            ]
        ];
    }
    
    /**
     * Check if a book already exists in the database
     */
    public function bookExists($isbn, $title, $author = '') {
        // Check by ISBN first if available
        if (!empty($isbn)) {
            $stmt = $this->db->prepare("
                SELECT di.id 
                FROM directory_items di
                JOIN books b ON di.id = b.directory_item_id
                WHERE di.type = 'book' AND (b.isbn = ? OR b.isbn13 = ?)
            ");
            $stmt->execute([$isbn, $isbn]);
            if ($stmt->fetch()) {
                return true;
            }
        }
        
        // Check by title and author
        $stmt = $this->db->prepare("
            SELECT di.id 
            FROM directory_items di
            WHERE di.type = 'book' 
            AND LOWER(di.title) = LOWER(?)
            AND (? = '' OR LOWER(di.description) LIKE LOWER(CONCAT('%', ?, '%')))
        ");
        $stmt->execute([$title, $author, $author]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Process discovered books in batches
     */
    public function processBatch($books, $options = []) {
        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => []
        ];
        
        foreach ($books as $book) {
            try {
                // Check if book exists
                if ($this->bookExists($book['isbn'] ?? '', $book['title'], $book['author'] ?? '')) {
                    $results['skipped']++;
                    $results['details'][] = [
                        'title' => $book['title'],
                        'status' => 'skipped',
                        'message' => 'Already exists'
                    ];
                    continue;
                }
                
                // Import the book
                $imported = $this->importBook($book);
                if ($imported) {
                    $results['imported']++;
                    $results['details'][] = [
                        'title' => $book['title'],
                        'status' => 'imported',
                        'id' => $imported
                    ];
                } else {
                    $results['errors']++;
                    $results['details'][] = [
                        'title' => $book['title'],
                        'status' => 'error',
                        'message' => 'Import failed'
                    ];
                }
                
            } catch (Exception $e) {
                $results['errors']++;
                $results['details'][] = [
                    'title' => $book['title'] ?? 'Unknown',
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Import a single book
     */
    private function importBook($book) {
        // This would integrate with the existing import logic
        // For now, return a mock ID
        return rand(1000, 9999);
    }
}