<?php
// Test the fixed BookTrust scraper
require_once 'book-discovery/scrapers/BookTrustScraper.php';

echo "<h2>Testing BookTrust Scraper with Fixed Selector</h2>";

$scraper = new BookTrustScraper();
$testUrl = 'https://www.booktrust.org.uk/book-recommendations/booklists/great-books-guide-2024-25-for-4-to-5-year-olds/';

echo "<p>Testing URL: " . htmlspecialchars($testUrl) . "</p>";
echo "<p>Can handle: " . ($scraper->canHandle($testUrl) ? 'Yes' : 'No') . "</p>";

echo "<h3>Scraping Results:</h3>";
$books = $scraper->scrape($testUrl);

echo "<p>Found " . count($books) . " books</p>";

if (!empty($books)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Title</th><th>Author</th><th>Age Range</th><th>Year</th><th>Tags</th><th>Description</th></tr>";
    
    foreach (array_slice($books, 0, 5) as $book) { // Show first 5 books
        echo "<tr>";
        echo "<td>" . htmlspecialchars($book['title']) . "</td>";
        echo "<td>" . htmlspecialchars($book['author']) . "</td>";
        echo "<td>" . htmlspecialchars($book['age_range']) . "</td>";
        echo "<td>" . htmlspecialchars($book['year']) . "</td>";
        echo "<td>" . htmlspecialchars(implode(', ', $book['tags'])) . "</td>";
        echo "<td>" . htmlspecialchars(substr($book['description'], 0, 100)) . "...</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    if (count($books) > 5) {
        echo "<p>... and " . (count($books) - 5) . " more books</p>";
    }
} else {
    echo "<p style='color: red;'>No books found! This indicates the scraper is still not working correctly.</p>";
}
?>