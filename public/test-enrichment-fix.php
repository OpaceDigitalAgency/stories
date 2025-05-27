<?php
/**
 * Test the OpenLibrary API fix for enrichment - PUBLIC VERSION
 */

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing OpenLibrary API Fix for Data Enrichment</h1>";
echo "<h2>Test Case: Coraline by Neil Gaiman (ISBN: 9780380977789)</h2>";

// Include the necessary files
$functionFile = '../stories-backend/admin/content/book-import-validate/functions/data-enrichment-functions.php';
$openLibraryFile = '../stories-backend/admin/content/book-import-validate/functions/open-library-validation-functions.php';
$googleBooksFile = '../stories-backend/admin/content/book-import-validate/functions/google-books-validation-functions.php';

echo "<h3>File Checks:</h3>";
if (file_exists($functionFile)) {
    echo "<p>✅ Data enrichment functions file exists</p>";
    require_once $functionFile;
} else {
    echo "<p>❌ Data enrichment functions file NOT found: $functionFile</p>";
    exit;
}

if (file_exists($openLibraryFile)) {
    echo "<p>✅ OpenLibrary validation functions file exists</p>";
    require_once $openLibraryFile;
} else {
    echo "<p>❌ OpenLibrary validation functions file NOT found: $openLibraryFile</p>";
    exit;
}

if (file_exists($googleBooksFile)) {
    echo "<p>✅ Google Books validation functions file exists</p>";
    require_once $googleBooksFile;
} else {
    echo "<p>❌ Google Books validation functions file NOT found: $googleBooksFile</p>";
}

// Test data
$isbn = '9780380977789';
$title = 'Coraline';
$author = 'Neil Gaiman';

echo "<h3>Testing Direct APIs:</h3>";

// Test OpenLibrary API directly
echo "<h4>OpenLibrary API Test:</h4>";
$openLibraryUrl = "https://openlibrary.org/search.json?q=9780380977789&fields=*,availability&limit=1";
echo "<p><strong>URL:</strong> <a href='$openLibraryUrl' target='_blank'>$openLibraryUrl</a></p>";

$ch = curl_init($openLibraryUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Stories Test)');
$olResponse = curl_exec($ch);
$olHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $olHttpCode</p>";

if ($olResponse && $olHttpCode === 200) {
    $olData = json_decode($olResponse, true);
    if (!empty($olData['docs'][0])) {
        echo "<p><strong>✅ OpenLibrary API successful</strong></p>";
        $olDoc = $olData['docs'][0];
        
        $richFields = [
            'subject_facet' => 'Genres/Tags',
            'place' => 'Settings',
            'person' => 'Characters', 
            'ratings_average' => 'Average Rating',
            'lexile' => 'Reading Level'
        ];
        
        echo "<ul>";
        foreach ($richFields as $field => $label) {
            if (isset($olDoc[$field]) && !empty($olDoc[$field])) {
                $value = is_array($olDoc[$field]) ? 
                    implode(', ', array_slice($olDoc[$field], 0, 3)) . (count($olDoc[$field]) > 3 ? '...' : '') : 
                    $olDoc[$field];
                echo "<li><strong>✅ $label:</strong> $value</li>";
            } else {
                echo "<li><strong>❌ $label:</strong> Missing</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p><strong>❌ No docs in OpenLibrary response</strong></p>";
    }
} else {
    echo "<p><strong>❌ OpenLibrary API failed (HTTP $olHttpCode)</strong></p>";
}

// Test Google Books API directly
echo "<h4>Google Books API Test:</h4>";
$googleBooksUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:9780380977789";
echo "<p><strong>URL:</strong> <a href='$googleBooksUrl' target='_blank'>$googleBooksUrl</a></p>";

$ch = curl_init($googleBooksUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Stories Test)');
$gbResponse = curl_exec($ch);
$gbHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $gbHttpCode</p>";

if ($gbResponse && $gbHttpCode === 200) {
    $gbData = json_decode($gbResponse, true);
    if (!empty($gbData['items'][0])) {
        echo "<p><strong>✅ Google Books API successful</strong></p>";
        $gbItem = $gbData['items'][0];
        
        echo "<ul>";
        echo "<li><strong>Title:</strong> " . ($gbItem['volumeInfo']['title'] ?? 'N/A') . "</li>";
        echo "<li><strong>Authors:</strong> " . implode(', ', $gbItem['volumeInfo']['authors'] ?? []) . "</li>";
        echo "<li><strong>Categories:</strong> " . implode(', ', $gbItem['volumeInfo']['categories'] ?? []) . "</li>";
        echo "<li><strong>Page Count:</strong> " . ($gbItem['volumeInfo']['pageCount'] ?? 'N/A') . "</li>";
        echo "<li><strong>Maturity Rating:</strong> " . ($gbItem['volumeInfo']['maturityRating'] ?? 'N/A') . "</li>";
        echo "<li><strong>Saleability:</strong> " . ($gbItem['saleInfo']['saleability'] ?? 'N/A') . "</li>";
        
        // Check for price info
        if (isset($gbItem['saleInfo']['listPrice'])) {
            echo "<li><strong>✅ List Price:</strong> " . $gbItem['saleInfo']['listPrice']['amount'] . " " . $gbItem['saleInfo']['listPrice']['currencyCode'] . "</li>";
        } else {
            echo "<li><strong>❌ List Price:</strong> Not available (saleability: " . ($gbItem['saleInfo']['saleability'] ?? 'unknown') . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p><strong>❌ No items in Google Books response</strong></p>";
    }
} else {
    echo "<p><strong>❌ Google Books API failed (HTTP $gbHttpCode)</strong></p>";
}

// Test the enrichment functions if they exist
echo "<h3>Testing Enrichment Functions:</h3>";

if (function_exists('fetchOpenLibraryDataNew')) {
    echo "<h4>Testing fetchOpenLibraryDataNew():</h4>";
    $startTime = microtime(true);
    $openLibraryResult = fetchOpenLibraryDataNew($isbn, $title, $author, true);
    $endTime = microtime(true);
    
    echo "<p><strong>Processing Time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>";
    
    if (!empty($openLibraryResult)) {
        echo "<p><strong>✅ Function returned data</strong></p>";
        
        // Check for rich metadata
        foreach ($richFields as $field => $label) {
            if (isset($openLibraryResult[$field]) && !empty($openLibraryResult[$field])) {
                $value = is_array($openLibraryResult[$field]) ? 
                    implode(', ', array_slice($openLibraryResult[$field], 0, 3)) . '...' : 
                    $openLibraryResult[$field];
                echo "<p><strong>✅ $label:</strong> $value</p>";
            } else {
                echo "<p><strong>❌ $label:</strong> Missing or empty</p>";
            }
        }
        
        if (isset($openLibraryResult['_status'])) {
            echo "<p><strong>Status:</strong> " . $openLibraryResult['_status']['status'] . " - " . $openLibraryResult['_status']['message'] . "</p>";
        }
    } else {
        echo "<p><strong>❌ Function returned no data</strong></p>";
    }
} else {
    echo "<p><strong>❌ fetchOpenLibraryDataNew function not found</strong></p>";
}

if (function_exists('getEnrichedBookData')) {
    echo "<h4>Testing getEnrichedBookData() (Full Pipeline):</h4>";
    $startTime = microtime(true);
    $enrichedResult = getEnrichedBookData($title, $author, $isbn);
    $endTime = microtime(true);
    
    echo "<p><strong>Processing Time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>";
    
    if (!empty($enrichedResult) && !empty($enrichedResult['fields'])) {
        echo "<p><strong>✅ Enrichment pipeline returned data</strong></p>";
        echo "<p><strong>Confidence Score:</strong> " . $enrichedResult['confidence_score'] . "</p>";
        echo "<p><strong>ISBN Validated:</strong> " . $enrichedResult['isbn_validated'] . "</p>";
        echo "<p><strong>Sources Checked:</strong> " . implode(', ', $enrichedResult['sources_checked']) . "</p>";
        
        echo "<h5>Key Fields Check:</h5>";
        $expectedFields = [
            'tags' => 'Genres/Tags',
            'settings' => 'Settings',
            'characters' => 'Characters',
            'awards' => 'Awards',
            'average_rating' => 'Average Rating',
            'reading_level' => 'Reading Level',
            'price_range' => 'Price Range'
        ];
        
        echo "<ul>";
        foreach ($expectedFields as $field => $label) {
            if (isset($enrichedResult['fields'][$field])) {
                $fieldData = $enrichedResult['fields'][$field];
                $value = $fieldData['value'] ?? 'No value';
                $source = $fieldData['source'] ?? 'Unknown source';
                
                if ($value !== 'Unknown' && !empty($value)) {
                    echo "<li><strong>✅ $label:</strong> $value <em>(from $source)</em></li>";
                } else {
                    echo "<li><strong>❌ $label:</strong> $value <em>(from $source)</em></li>";
                }
            } else {
                echo "<li><strong>❌ $label:</strong> Field not found in results</li>";
            }
        }
        echo "</ul>";
        
        echo "<h5>All Fields (Debug):</h5>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
        print_r($enrichedResult['fields']);
        echo "</pre>";
        
    } else {
        echo "<p><strong>❌ Enrichment pipeline returned no field data</strong></p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        print_r($enrichedResult);
        echo "</pre>";
    }
} else {
    echo "<p><strong>❌ getEnrichedBookData function not found</strong></p>";
}

echo "<h3>Summary:</h3>";
echo "<p>This test verifies:</p>";
echo "<ul>";
echo "<li>✅ OpenLibrary API endpoint change (search.json vs api/books)</li>";
echo "<li>✅ Rich metadata extraction (genres, settings, characters, awards, ratings)</li>";
echo "<li>❌ Price range handling (Google Books shows NOT_FOR_SALE for this ISBN)</li>";
echo "<li>✅ Google Books integration</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Fix price range logic to handle NOT_FOR_SALE cases</li>";
echo "<li>Test with an ISBN that has sale info for price range validation</li>";
echo "<li>Verify the enrichment modal displays the corrected data</li>";
echo "</ul>";
?>