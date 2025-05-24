<?php
/**
 * Debug script to test ISBN search for specific book
 */

require_once 'book-import-validate/functions/google-books-validation-functions.php';
require_once 'book-import-validate/functions/open-library-validation-functions.php';

$title = "The Peppers and the International Magic Guys";
$author = "Sian Pattenden";
$targetISBN = "9780007430017";

echo "<h1>Debug: ISBN Search for '$title'</h1>\n";
echo "<p>Looking for ISBN: $targetISBN</p>\n";

echo "<h2>Google Books Search Results:</h2>\n";
$googleResults = searchBooksByTitleAuthor($title, $author, 20);
echo "<p>Found " . count($googleResults) . " results from Google Books:</p>\n";

$found = false;
foreach ($googleResults as $i => $result) {
    $isbn13 = $result['isbn13'] ?? 'none';
    $isbn10 = $result['isbn'] ?? 'none';
    $publisher = $result['publisher'] ?? 'none';
    $year = $result['publication_date'] ?? 'none';
    
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
    echo "<strong>Result " . ($i + 1) . ":</strong><br>\n";
    echo "Title: " . htmlspecialchars($result['title']) . "<br>\n";
    echo "Author: " . htmlspecialchars($result['author']) . "<br>\n";
    echo "Publisher: " . htmlspecialchars($publisher) . "<br>\n";
    echo "Year: " . htmlspecialchars($year) . "<br>\n";
    echo "ISBN-10: $isbn10<br>\n";
    echo "ISBN-13: $isbn13<br>\n";
    
    if ($isbn13 === $targetISBN || $isbn10 === $targetISBN) {
        echo "<strong style='color: green;'>*** TARGET ISBN FOUND! ***</strong><br>\n";
        $found = true;
    }
    echo "</div>\n";
}

echo "<h2>OpenLibrary Search Results:</h2>\n";
$openLibraryResults = searchOpenLibraryByTitleAuthor($title, $author, 20);
echo "<p>Found " . count($openLibraryResults) . " results from OpenLibrary:</p>\n";

foreach ($openLibraryResults as $i => $result) {
    $isbn13 = $result['isbn13'] ?? 'none';
    $isbn10 = $result['isbn'] ?? 'none';
    $publisher = $result['publisher'] ?? 'none';
    $year = $result['publication_date'] ?? 'none';
    
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
    echo "<strong>Result " . ($i + 1) . ":</strong><br>\n";
    echo "Title: " . htmlspecialchars($result['title']) . "<br>\n";
    echo "Author: " . htmlspecialchars($result['author']) . "<br>\n";
    echo "Publisher: " . htmlspecialchars($publisher) . "<br>\n";
    echo "Year: " . htmlspecialchars($year) . "<br>\n";
    echo "ISBN-10: $isbn10<br>\n";
    echo "ISBN-13: $isbn13<br>\n";
    
    if ($isbn13 === $targetISBN || $isbn10 === $targetISBN) {
        echo "<strong style='color: green;'>*** TARGET ISBN FOUND! ***</strong><br>\n";
        $found = true;
    }
    echo "</div>\n";
}

echo "<h2>Direct API Tests:</h2>\n";

// Test Google Books API directly
echo "<h3>Direct Google Books API Test:</h3>\n";
$queries = [
    'intitle:"The Peppers and the International Magic Guys"+inauthor:"Sian Pattenden"',
    '"The Peppers and the International Magic Guys"+"Sian Pattenden"',
    '"The Peppers and the International Magic Guys"',
    'Peppers International Magic Guys',
    'isbn:9780007430017'
];

foreach ($queries as $query) {
    echo "<p><strong>Query:</strong> $query</p>\n";
    $url = "https://www.googleapis.com/books/v1/volumes?q=" . urlencode($query) . "&maxResults=10";
    echo "<p><strong>URL:</strong> <a href='$url' target='_blank'>$url</a></p>\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $count = isset($data['items']) ? count($data['items']) : 0;
    echo "<p>Results: $count items</p>\n";
    
    if (isset($data['items'])) {
        foreach ($data['items'] as $item) {
            $volumeInfo = $item['volumeInfo'] ?? [];
            $title = $volumeInfo['title'] ?? 'No title';
            $authors = isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : 'No author';
            
            // Check for ISBNs
            $foundISBNs = [];
            if (isset($volumeInfo['industryIdentifiers'])) {
                foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                    $foundISBNs[] = $identifier['type'] . ': ' . $identifier['identifier'];
                    if ($identifier['identifier'] === $targetISBN) {
                        echo "<p style='color: green; font-weight: bold;'>*** FOUND TARGET ISBN: $targetISBN ***</p>\n";
                        $found = true;
                    }
                }
            }
            
            echo "<div style='margin-left: 20px; border-left: 3px solid #ccc; padding-left: 10px;'>\n";
            echo "<strong>$title</strong> by $authors<br>\n";
            echo "ISBNs: " . implode(', ', $foundISBNs) . "<br>\n";
            echo "</div>\n";
        }
    }
    echo "<hr>\n";
}

if ($found) {
    echo "<h2 style='color: green;'>SUCCESS: Target ISBN $targetISBN was found in the search results!</h2>\n";
} else {
    echo "<h2 style='color: red;'>PROBLEM: Target ISBN $targetISBN was NOT found in any search results!</h2>\n";
    echo "<p>This suggests the ISBN might not be in the Google Books or OpenLibrary databases, or our search queries need improvement.</p>\n";
}
?>
