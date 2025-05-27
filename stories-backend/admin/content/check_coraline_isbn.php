<?php
/**
 * Quick check for Coraline ISBN in database
 */

// Set content type for web display
header('Content-Type: text/html; charset=utf-8');

// Include database connection
require_once '../../../includes/db-connect.php';

$testISBN = "9780380977789";

echo "<h1>Checking for Coraline ISBN in Database</h1>\n";
echo "<p><strong>Looking for ISBN:</strong> $testISBN</p>\n";

try {
    // Check if this ISBN exists in the books table
    $stmt = $db->prepare("
        SELECT di.id, di.title, b.isbn, b.isbn13, b.author, b.publisher, b.publication_date
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE b.isbn = ? OR b.isbn13 = ?
    ");
    $stmt->execute([$testISBN, $testISBN]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>✅ Book Found!</h3>\n";
        echo "<p><strong>ID:</strong> {$book['id']}</p>\n";
        echo "<p><strong>Title:</strong> {$book['title']}</p>\n";
        echo "<p><strong>Author:</strong> {$book['author']}</p>\n";
        echo "<p><strong>ISBN:</strong> {$book['isbn']}</p>\n";
        echo "<p><strong>ISBN-13:</strong> {$book['isbn13']}</p>\n";
        echo "<p><strong>Publisher:</strong> {$book['publisher']}</p>\n";
        echo "<p><strong>Publication Date:</strong> {$book['publication_date']}</p>\n";
        echo "</div>\n";
        
        echo "<h3>Test Links:</h3>\n";
        echo "<ul>\n";
        echo "<li><a href='book-import-validate-new.php?book_id={$book['id']}' target='_blank'>📊 Data Enrichment Interface</a></li>\n";
        echo "<li><a href='test_openlibrary_fix.php' target='_blank'>🧪 OpenLibrary Fix Test</a></li>\n";
        echo "</ul>\n";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>❌ Book Not Found</h3>\n";
        echo "<p>The ISBN $testISBN (Coraline) is not in the database.</p>\n";
        echo "</div>\n";
        
        // Check for any Coraline books
        $stmt = $db->prepare("
            SELECT di.id, di.title, b.isbn, b.isbn13, b.author
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE di.title LIKE '%Coraline%' OR b.author LIKE '%Gaiman%'
        ");
        $stmt->execute();
        $coralineBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($coralineBooks) {
            echo "<h3>📚 Related Books Found:</h3>\n";
            echo "<ul>\n";
            foreach ($coralineBooks as $relatedBook) {
                echo "<li><strong>{$relatedBook['title']}</strong> by {$relatedBook['author']} ";
                echo "(ID: {$relatedBook['id']}, ISBN: {$relatedBook['isbn']}, ISBN-13: {$relatedBook['isbn13']})</li>\n";
            }
            echo "</ul>\n";
        }
        
        // Let's check for any book with missing data that we can test with
        $stmt = $db->prepare("
            SELECT di.id, di.title, b.isbn, b.isbn13, b.author, b.publisher, b.publication_date
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE (b.isbn IS NOT NULL AND b.isbn != '') OR (b.isbn13 IS NOT NULL AND b.isbn13 != '')
            ORDER BY di.id DESC
            LIMIT 5
        ");
        $stmt->execute();
        $testBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($testBooks) {
            echo "<h3>🔬 Available Test Books:</h3>\n";
            echo "<ul>\n";
            foreach ($testBooks as $testBook) {
                $isbn = $testBook['isbn13'] ?: $testBook['isbn'];
                echo "<li><a href='book-import-validate-new.php?book_id={$testBook['id']}' target='_blank'>";
                echo "<strong>{$testBook['title']}</strong></a> by {$testBook['author']} (ISBN: $isbn)</li>\n";
            }
            echo "</ul>\n";
        }
    }
    
    // Test the OpenLibrary API directly
    echo "<h3>🌐 Direct OpenLibrary API Test:</h3>\n";
    $apiUrl = "https://openlibrary.org/search.json?q=isbn:$testISBN&fields=*,availability&limit=1";
    echo "<p><strong>API URL:</strong> <a href='$apiUrl' target='_blank'>$apiUrl</a></p>\n";
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Response:</strong> $httpCode</p>\n";
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if ($data) {
            echo "<p><strong>Query:</strong> " . ($data['q'] ?? 'N/A') . "</p>\n";
            echo "<p><strong>Number Found:</strong> " . ($data['numFound'] ?? 'N/A') . "</p>\n";
            echo "<p><strong>Number of Docs:</strong> " . count($data['docs'] ?? []) . "</p>\n";
            
            if (!empty($data['docs'][0])) {
                $firstDoc = $data['docs'][0];
                echo "<p><strong>First Result Title:</strong> " . ($firstDoc['title'] ?? 'N/A') . "</p>\n";
                echo "<p><strong>First Result Author:</strong> " . (isset($firstDoc['author_name']) ? implode(', ', $firstDoc['author_name']) : 'N/A') . "</p>\n";
                echo "<p><strong>First Result ISBNs:</strong> " . (isset($firstDoc['isbn']) ? implode(', ', $firstDoc['isbn']) : 'N/A') . "</p>\n";
                
                // Check if our target ISBN is in the list
                if (isset($firstDoc['isbn']) && in_array($testISBN, $firstDoc['isbn'])) {
                    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>\n";
                    echo "✅ <strong>Success!</strong> Target ISBN $testISBN found in OpenLibrary response!\n";
                    echo "</div>\n";
                } else {
                    echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>\n";
                    echo "⚠️ <strong>Note:</strong> Target ISBN $testISBN not found in first result's ISBN list.\n";
                    echo "</div>\n";
                }
            }
        } else {
            echo "<p><strong>Error:</strong> Could not parse JSON response</p>\n";
        }
    } else {
        echo "<p><strong>Error:</strong> API request failed</p>\n";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>❌ Database Error</h3>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    echo "</div>\n";
}
?>
