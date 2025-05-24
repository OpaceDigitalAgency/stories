<?php
/**
 * Test OpenLibrary search specifically for The Peppers book
 */

require_once 'book-import-validate/functions/open-library-validation-functions.php';

$title = "The Peppers and the International Magic Guys";
$author = "Sian Pattenden";

echo "<h1>Testing OpenLibrary Search</h1>\n";
echo "<p>Title: $title</p>\n";
echo "<p>Author: $author</p>\n";
echo "<p>Looking for ISBN: 9780007430017</p>\n";

echo "<h2>OpenLibrary Search Results:</h2>\n";

$results = searchOpenLibraryByTitleAuthor($title, $author, 10);

echo "<p>Number of results: " . count($results) . "</p>\n";

if (empty($results)) {
    echo "<p style='color: red;'>NO RESULTS FOUND!</p>\n";
} else {
    foreach ($results as $i => $result) {
        echo "<div style='border: 2px solid #ccc; margin: 10px; padding: 15px;'>\n";
        echo "<h3>Result " . ($i + 1) . "</h3>\n";
        echo "<strong>Title:</strong> " . htmlspecialchars($result['title']) . "<br>\n";
        echo "<strong>Author:</strong> " . htmlspecialchars($result['author']) . "<br>\n";
        echo "<strong>Publisher:</strong> " . htmlspecialchars($result['publisher']) . "<br>\n";
        echo "<strong>Publication Date:</strong> " . htmlspecialchars($result['publication_date']) . "<br>\n";
        echo "<strong>ISBN-10:</strong> " . htmlspecialchars($result['isbn']) . "<br>\n";
        echo "<strong>ISBN-13:</strong> " . htmlspecialchars($result['isbn13']) . "<br>\n";
        echo "<strong>Source:</strong> " . htmlspecialchars($result['source']) . "<br>\n";
        
        if ($result['isbn13'] === '9780007430017') {
            echo "<p style='color: green; font-weight: bold; font-size: 18px;'>*** TARGET ISBN FOUND! ***</p>\n";
        }
        echo "</div>\n";
    }
}

echo "<h2>Manual API Test:</h2>\n";

// Test the exact API calls manually
$testQueries = [
    "title=" . urlencode($title) . "&author=" . urlencode($author),
    "title=" . urlencode($title),
    "q=" . urlencode($title . " " . $author)
];

foreach ($testQueries as $query) {
    echo "<h3>Testing Query: $query</h3>\n";
    $url = "https://openlibrary.org/search.json?" . $query . "&limit=5";
    echo "<p>URL: <a href='$url' target='_blank'>$url</a></p>\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "<p>Found " . (isset($data['docs']) ? count($data['docs']) : 0) . " works</p>\n";
        
        if (!empty($data['docs'])) {
            foreach ($data['docs'] as $doc) {
                $workKey = $doc['key'] ?? '';
                echo "<p>Work: " . htmlspecialchars($doc['title'] ?? 'No title') . " (Key: $workKey)</p>\n";
                
                if (!empty($workKey)) {
                    // Test editions API
                    $editionsUrl = "https://openlibrary.org" . $workKey . "/editions.json";
                    echo "<p>Editions URL: <a href='$editionsUrl' target='_blank'>$editionsUrl</a></p>\n";
                    
                    $editionsCh = curl_init($editionsUrl);
                    curl_setopt($editionsCh, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($editionsCh, CURLOPT_TIMEOUT, 5);
                    curl_setopt($editionsCh, CURLOPT_USERAGENT, 'Mozilla/5.0');
                    $editionsResponse = curl_exec($editionsCh);
                    $editionsHttpCode = curl_getinfo($editionsCh, CURLINFO_HTTP_CODE);
                    curl_close($editionsCh);
                    
                    echo "<p>Editions HTTP Code: $editionsHttpCode</p>\n";
                    
                    if ($editionsHttpCode === 200) {
                        $editionsData = json_decode($editionsResponse, true);
                        $editionCount = isset($editionsData['entries']) ? count($editionsData['entries']) : 0;
                        echo "<p>Found $editionCount editions</p>\n";
                        
                        if (!empty($editionsData['entries'])) {
                            foreach ($editionsData['entries'] as $edition) {
                                $isbn13s = $edition['isbn_13'] ?? [];
                                $isbn10s = $edition['isbn_10'] ?? [];
                                $publishers = $edition['publishers'] ?? [];
                                
                                echo "<div style='margin-left: 20px; border-left: 3px solid blue; padding-left: 10px;'>\n";
                                echo "<strong>Edition:</strong><br>\n";
                                echo "ISBN-13s: " . implode(', ', $isbn13s) . "<br>\n";
                                echo "ISBN-10s: " . implode(', ', $isbn10s) . "<br>\n";
                                echo "Publishers: " . implode(', ', $publishers) . "<br>\n";
                                
                                if (in_array('9780007430017', $isbn13s)) {
                                    echo "<p style='color: green; font-weight: bold;'>*** TARGET ISBN 9780007430017 FOUND! ***</p>\n";
                                }
                                echo "</div>\n";
                            }
                        }
                    } else {
                        echo "<p style='color: red;'>Failed to get editions (HTTP $editionsHttpCode)</p>\n";
                    }
                }
            }
        }
    } else {
        echo "<p style='color: red;'>Failed to get search results (HTTP $httpCode)</p>\n";
    }
    
    echo "<hr>\n";
}
?>
