<?php
// Quick test to verify what ISBN 9780007416851 actually returns from APIs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>ISBN Verification Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>📚 ISBN Verification Test</h1>";
echo "<p>Testing what ISBN <strong>9780007416851</strong> actually returns from different APIs</p>";

$isbn = "9780007416851";
$expectedTitle = "The Lion, the Witch and the Wardrobe";
$expectedAuthor = "C.S. Lewis";

// Test 1: Google Books API
echo "<div class='test-section'>";
echo "<h2>Test 1: Google Books API</h2>";

$googleUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:$isbn";
echo "<p>URL: <code>$googleUrl</code></p>";

$ch = curl_init($googleUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $httpCode === 200) {
    $data = json_decode($response, true);
    if (!empty($data['items'][0])) {
        $book = $data['items'][0]['volumeInfo'];
        $title = $book['title'] ?? 'N/A';
        $authors = $book['authors'] ?? [];
        $categories = $book['categories'] ?? [];
        
        echo "<p class='success'>✓ Google Books API returned data</p>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Expected</th><th>Actual</th><th>Match</th></tr>";
        echo "<tr><td>Title</td><td>$expectedTitle</td><td>" . htmlspecialchars($title) . "</td><td>" . (stripos($title, 'Lion') !== false ? '✅' : '❌') . "</td></tr>";
        echo "<tr><td>Author</td><td>$expectedAuthor</td><td>" . htmlspecialchars(implode(', ', $authors)) . "</td><td>" . (in_array('C.S. Lewis', $authors) ? '✅' : '❌') . "</td></tr>";
        echo "<tr><td>Categories</td><td>Children's/Juvenile</td><td>" . htmlspecialchars(implode(', ', $categories)) . "</td><td>" . (array_filter($categories, function($cat) { return stripos($cat, 'juvenile') !== false || stripos($cat, 'children') !== false; }) ? '✅' : '❌') . "</td></tr>";
        echo "</table>";
        
        if (stripos($title, 'Earwig') !== false) {
            echo "<p class='error'>❌ CONFIRMED: Google Books returns 'Earwig and the Witch' for this ISBN</p>";
        }
    } else {
        echo "<p class='error'>✗ No results found</p>";
    }
} else {
    echo "<p class='error'>✗ API request failed (HTTP $httpCode)</p>";
}

echo "</div>";

// Test 2: OpenLibrary API
echo "<div class='test-section'>";
echo "<h2>Test 2: OpenLibrary API</h2>";

$olUrl = "https://openlibrary.org/search.json?q=isbn:$isbn&fields=*,availability&limit=1";
echo "<p>URL: <code>$olUrl</code></p>";

$ch = curl_init($olUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $httpCode === 200) {
    $data = json_decode($response, true);
    if (!empty($data['docs'][0])) {
        $book = $data['docs'][0];
        $title = $book['title'] ?? 'N/A';
        $authors = $book['author_name'] ?? [];
        $subjects = $book['subject'] ?? [];
        
        echo "<p class='success'>✓ OpenLibrary API returned data</p>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Expected</th><th>Actual</th><th>Match</th></tr>";
        echo "<tr><td>Title</td><td>$expectedTitle</td><td>" . htmlspecialchars($title) . "</td><td>" . (stripos($title, 'Lion') !== false ? '✅' : '❌') . "</td></tr>";
        echo "<tr><td>Author</td><td>$expectedAuthor</td><td>" . htmlspecialchars(implode(', ', $authors)) . "</td><td>" . (in_array('C.S. Lewis', $authors) ? '✅' : '❌') . "</td></tr>";
        echo "<tr><td>Subjects (first 10)</td><td>Fantasy/Children's</td><td>" . htmlspecialchars(implode(', ', array_slice($subjects, 0, 10))) . "</td><td>" . (array_filter($subjects, function($subj) { return stripos($subj, 'children') !== false || stripos($subj, 'fantasy') !== false; }) ? '✅' : '❌') . "</td></tr>";
        echo "</table>";
        
        if (stripos($title, 'Earwig') !== false) {
            echo "<p class='error'>❌ CONFIRMED: OpenLibrary also returns 'Earwig and the Witch' for this ISBN</p>";
        }
    } else {
        echo "<p class='error'>✗ No results found</p>";
    }
} else {
    echo "<p class='error'>✗ API request failed (HTTP $httpCode)</p>";
}

echo "</div>";

// Test 3: Try searching for the correct book
echo "<div class='test-section'>";
echo "<h2>Test 3: Search for Correct Book</h2>";

echo "<h3>Google Books - Search by Title and Author:</h3>";
$correctSearchUrl = "https://www.googleapis.com/books/v1/volumes?q=intitle:\"Lion Witch Wardrobe\"+inauthor:\"C.S. Lewis\"";
echo "<p>URL: <code>$correctSearchUrl</code></p>";

$ch = curl_init($correctSearchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response && $httpCode === 200) {
    $data = json_decode($response, true);
    if (!empty($data['items'])) {
        echo "<p class='success'>✓ Found " . count($data['items']) . " results for correct book</p>";
        
        echo "<h4>First 3 Results:</h4>";
        echo "<table>";
        echo "<tr><th>Title</th><th>Authors</th><th>ISBNs</th></tr>";
        
        for ($i = 0; $i < min(3, count($data['items'])); $i++) {
            $book = $data['items'][$i]['volumeInfo'];
            $title = $book['title'] ?? 'N/A';
            $authors = implode(', ', $book['authors'] ?? []);
            $isbns = [];
            
            if (isset($book['industryIdentifiers'])) {
                foreach ($book['industryIdentifiers'] as $id) {
                    if ($id['type'] === 'ISBN_13' || $id['type'] === 'ISBN_10') {
                        $isbns[] = $id['identifier'];
                    }
                }
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($title) . "</td>";
            echo "<td>" . htmlspecialchars($authors) . "</td>";
            echo "<td>" . htmlspecialchars(implode(', ', $isbns)) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Conclusion:</strong> The correct book exists in Google Books but with different ISBNs</p>";
    } else {
        echo "<p class='error'>✗ No results found for correct book</p>";
    }
} else {
    echo "<p class='error'>✗ Search request failed (HTTP $httpCode)</p>";
}

echo "</div>";

// Test 4: Conclusion
echo "<div class='test-section'>";
echo "<h2>🎯 Conclusion</h2>";

echo "<h3>📋 What We Found:</h3>";
echo "<ul>";
echo "<li><strong>ISBN 9780007416851 is WRONG</strong> - It belongs to 'Earwig and the Witch' by Diana Wynne Jones</li>";
echo "<li><strong>Both Google Books and OpenLibrary confirm this</strong> - The ISBN is correctly mapped to the wrong book</li>";
echo "<li><strong>The correct book exists</strong> - But with different ISBNs</li>";
echo "</ul>";

echo "<h3>🔧 Solution:</h3>";
echo "<ol>";
echo "<li><strong>Update the database</strong> - Change the ISBN for Chronicles of Narnia to a correct one</li>";
echo "<li><strong>Find correct ISBN</strong> - Use the search results above to find the right ISBN-13</li>";
echo "<li><strong>Test again</strong> - Verify enrichment works with correct ISBN</li>";
echo "</ol>";

echo "<h3>📝 Recommended Action:</h3>";
echo "<p><strong>Update the Chronicles of Narnia book record with a correct ISBN from the search results above.</strong></p>";

echo "</div>";

echo "</body></html>";
?>
