<?php
// Debug page to test BookTrust scraper with live error log output
require_once 'book-discovery/scrapers/BookTrustScraper.php';
require_once 'book-discovery/BookDiscoveryEngine.php';

// Clear previous logs
error_log("=== DEBUG SESSION START ===");

echo "<h2>BookTrust Scraper Debug Session</h2>";
echo "<p>This page will test the BookTrust scraper and show debug output.</p>";

$testUrl = 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/';

echo "<h3>Test 1: Direct Scraper Test</h3>";
echo "<p>Testing URL: " . htmlspecialchars($testUrl) . "</p>";

$scraper = new BookTrustScraper();

echo "<p>Can handle test: " . ($scraper->canHandle($testUrl) ? 'YES' : 'NO') . "</p>";

echo "<h4>Starting scrape...</h4>";
$books = $scraper->scrape($testUrl);

echo "<p><strong>Result: Found " . count($books) . " books</strong></p>";

if (!empty($books)) {
    echo "<h4>Sample Books:</h4>";
    echo "<ul>";
    foreach (array_slice($books, 0, 3) as $book) {
        echo "<li><strong>" . htmlspecialchars($book['title']) . "</strong> by " . htmlspecialchars($book['author']) . "</li>";
    }
    echo "</ul>";
}

echo "<hr>";

echo "<h3>Test 2: Discovery Engine Test</h3>";
try {
    // Mock database connection for testing
    $mockDb = new stdClass();
    $discoveryEngine = new BookDiscoveryEngine($mockDb);
    
    echo "<p>Testing through BookDiscoveryEngine...</p>";
    $engineBooks = $discoveryEngine->discoverFromURL($testUrl);
    
    echo "<p><strong>Discovery Engine Result: Found " . count($engineBooks) . " books</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Discovery Engine Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

echo "<h3>Debug Log Output</h3>";
echo "<p>Check the server error logs for detailed debug information. The logs should show:</p>";
echo "<ul>";
echo "<li>BookTrustScraper: canHandle() calls</li>";
echo "<li>BookTrustScraper: fetchPage() operations</li>";
echo "<li>BookTrustScraper: HTML parsing steps</li>";
echo "<li>BookTrustScraper: Element finding attempts</li>";
echo "</ul>";

echo "<p><em>If you see 'No books found' but no debug logs, there may be a PHP error preventing the scraper from running.</em></p>";

// Show recent error log entries if accessible
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath)) {
    echo "<h4>Recent Error Log Entries:</h4>";
    $logLines = file($errorLogPath);
    $recentLines = array_slice($logLines, -50); // Last 50 lines
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: scroll;'>";
    foreach ($recentLines as $line) {
        if (strpos($line, 'BookTrustScraper') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p><em>Error log not accessible at: " . htmlspecialchars($errorLogPath) . "</em></p>";
}
?>