<?php
/**
 * Debug the 12+ issue - find where it's coming from
 */

require_once '../includes/db-connect.php';

echo "<h1>🔍 Debug 12+ Issue</h1>";

try {
    // 1. Check if any books still have 12+ in database
    echo "<h2>📚 Books with 12+ in Database</h2>";
    
    $stmt = $db->query("
        SELECT b.directory_item_id, di.title, b.age_range, b.reading_level
        FROM books b
        JOIN directory_items di ON b.directory_item_id = di.id
        WHERE b.age_range LIKE '%12+%'
        ORDER BY di.title
    ");
    $books12Plus = $stmt->fetchAll();
    
    if ($books12Plus) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Book ID</th><th>Title</th><th>Age Range</th><th>Reading Level</th></tr>";
        foreach ($books12Plus as $book) {
            echo "<tr>";
            echo "<td>{$book['directory_item_id']}</td>";
            echo "<td>" . htmlspecialchars($book['title']) . "</td>";
            echo "<td style='background: #fff3cd; font-family: monospace;'>[{$book['age_range']}]</td>";
            echo "<td>" . htmlspecialchars($book['reading_level']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>✅ No books found with 12+ in age_range field</p>";
    }
    
    // 2. Check validation cache for 12+ values
    echo "<h2>🗄️ Validation Cache with 12+ Values</h2>";
    
    $stmt = $db->query("
        SELECT cache_key, cache_data, created_at
        FROM validation_cache
        WHERE cache_data LIKE '%12+%'
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $cachedData = $stmt->fetchAll();
    
    if ($cachedData) {
        echo "<p><strong>Found " . count($cachedData) . " cache entries with '12+' values:</strong></p>";
        foreach ($cachedData as $cache) {
            echo "<div style='background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "<strong>Cache Key:</strong> " . htmlspecialchars($cache['cache_key']) . "<br>";
            echo "<strong>Created:</strong> " . $cache['created_at'] . "<br>";
            echo "<strong>Data (first 200 chars):</strong><br>";
            echo "<pre style='background: white; padding: 5px; font-size: 12px;'>";
            echo htmlspecialchars(substr($cache['cache_data'], 0, 200)) . "...";
            echo "</pre>";
            echo "</div>";
        }
    } else {
        echo "<p>✅ No validation cache entries found with '12+' values</p>";
    }
    
    // 3. Check for the specific book "A Hen in the Wardrobe"
    echo "<h2>🐔 Specific Book: 'A Hen in the Wardrobe'</h2>";
    
    $stmt = $db->prepare("
        SELECT b.directory_item_id, di.title, b.age_range, b.reading_level, b.isbn, b.isbn13
        FROM books b
        JOIN directory_items di ON b.directory_item_id = di.id
        WHERE di.title LIKE '%Hen in the Wardrobe%'
    ");
    $stmt->execute();
    $henBook = $stmt->fetch();
    
    if ($henBook) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Book ID</td><td>{$henBook['directory_item_id']}</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($henBook['title']) . "</td></tr>";
        echo "<tr><td>Age Range</td><td style='background: #fff3cd; font-family: monospace;'>[{$henBook['age_range']}]</td></tr>";
        echo "<tr><td>Reading Level</td><td>" . htmlspecialchars($henBook['reading_level']) . "</td></tr>";
        echo "<tr><td>ISBN-10</td><td>" . htmlspecialchars($henBook['isbn']) . "</td></tr>";
        echo "<tr><td>ISBN-13</td><td>" . htmlspecialchars($henBook['isbn13']) . "</td></tr>";
        echo "</table>";
        
        // Check cache for this specific book
        $cacheKey = "book_validation_" . $henBook['directory_item_id'];
        $stmt = $db->prepare("SELECT cache_data FROM validation_cache WHERE cache_key = ?");
        $stmt->execute([$cacheKey]);
        $bookCache = $stmt->fetch();
        
        if ($bookCache) {
            echo "<h3>📦 Cache Data for This Book:</h3>";
            echo "<pre style='background: #f8f9fa; padding: 10px; font-size: 12px; max-height: 300px; overflow-y: auto;'>";
            echo htmlspecialchars($bookCache['cache_data']);
            echo "</pre>";
        } else {
            echo "<p>✅ No cache data found for this book</p>";
        }
    } else {
        echo "<p>❌ Book 'A Hen in the Wardrobe' not found in database</p>";
    }
    
    // 4. Test the Google Books API response for this book
    echo "<h2>🌐 Test Google Books API Response</h2>";
    
    $isbn = "1847802257";
    $googleUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:$isbn";
    
    echo "<p><strong>Testing URL:</strong> <a href='$googleUrl' target='_blank'>$googleUrl</a></p>";
    
    $response = file_get_contents($googleUrl);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['items'][0])) {
            $book = $data['items'][0]['volumeInfo'];
            echo "<h3>📖 Google Books Response:</h3>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><td>Title</td><td>" . htmlspecialchars($book['title'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Categories</td><td>" . htmlspecialchars(json_encode($book['categories'] ?? [])) . "</td></tr>";
            echo "<tr><td>Maturity Rating</td><td>" . htmlspecialchars($book['maturityRating'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Age Range</td><td>" . htmlspecialchars($book['age_range'] ?? 'NOT PRESENT') . "</td></tr>";
            echo "</table>";
            
            echo "<h3>🔍 Full Response (first 500 chars):</h3>";
            echo "<pre style='background: #f8f9fa; padding: 10px; font-size: 12px;'>";
            echo htmlspecialchars(substr(json_encode($data, JSON_PRETTY_PRINT), 0, 500)) . "...";
            echo "</pre>";
        } else {
            echo "<p>❌ No book data found in Google Books response</p>";
        }
    } else {
        echo "<p>❌ Failed to fetch data from Google Books API</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<p><a href="book-import-validate.php">← Back to Book Validation</a></p>
