<?php
/**
 * Test BookTrust Scraper Debug
 */

// Include the scraper
require_once 'book-discovery/scrapers/BookTrustScraper.php';

$url = 'https://www.booktrust.org.uk/booklists/g/great-books-guide-2024-25-for-0-5/';

echo "<h1>BookTrust Scraper Debug</h1>";
echo "<p>Testing URL: " . htmlspecialchars($url) . "</p>";

$scraper = new BookTrustScraper();

// Test if it can handle the URL
if ($scraper->canHandle($url)) {
    echo "<p>✓ Scraper can handle this URL</p>";
} else {
    echo "<p>✗ Scraper cannot handle this URL</p>";
    exit;
}

// Test fetching the page
echo "<h2>Fetching Page...</h2>";
$html = file_get_contents($url);

if ($html) {
    echo "<p>✓ Successfully fetched page (" . strlen($html) . " bytes)</p>";
    
    // Look for common book-related patterns
    echo "<h2>HTML Analysis</h2>";
    
    // Check for various possible selectors
    $patterns = [
        'book-item' => '/class="[^"]*book-item[^"]*"/',
        'book' => '/class="[^"]*book[^"]*"/',
        'title' => '/class="[^"]*title[^"]*"/',
        'item' => '/class="[^"]*item[^"]*"/',
        'card' => '/class="[^"]*card[^"]*"/',
        'list' => '/class="[^"]*list[^"]*"/',
    ];
    
    foreach ($patterns as $name => $pattern) {
        $matches = preg_match_all($pattern, $html, $found);
        echo "<p>Pattern '$name': Found $matches matches</p>";
        if ($matches > 0 && $matches < 20) {
            echo "<pre>" . htmlspecialchars(implode("\n", array_slice($found[0], 0, 5))) . "</pre>";
        }
    }
    
    // Look for specific text that might indicate books
    $bookIndicators = ['author', 'isbn', 'publisher', 'age', 'years'];
    foreach ($bookIndicators as $indicator) {
        $count = substr_count(strtolower($html), $indicator);
        echo "<p>Text '$indicator': Found $count occurrences</p>";
    }
    
    // Show a sample of the HTML
    echo "<h2>HTML Sample (first 2000 chars)</h2>";
    echo "<pre>" . htmlspecialchars(substr($html, 0, 2000)) . "</pre>";
    
} else {
    echo "<p>✗ Failed to fetch page</p>";
}

// Test the actual scraper
echo "<h2>Testing Scraper</h2>";
try {
    $books = $scraper->scrape($url);
    echo "<p>Scraper returned " . count($books) . " books</p>";
    
    if (!empty($books)) {
        echo "<h3>First book found:</h3>";
        echo "<pre>" . print_r($books[0], true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>