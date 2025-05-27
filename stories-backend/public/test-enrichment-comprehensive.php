<?php
/**
 * Comprehensive test for data enrichment issues
 * Tests all reported problems: ISBN matching, location deduplication, price range, tags, etc.
 */

// Include required files
require_once '../admin/content/book-import-validate/functions/data-enrichment-functions.php';
require_once '../admin/content/book-import-validate/functions/open-library-validation-functions.php';
require_once '../admin/content/book-import-validate/functions/google-books-validation-functions.php';

// Test case: Coraline by Neil Gaiman
$title = "Coraline";
$author = "Neil Gaiman";
$isbn = "9780380977789";

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Comprehensive Enrichment Test</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .data-dump { background: #f5f5f5; padding: 10px; margin: 10px 0; overflow-x: auto; }
    pre { margin: 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style></head><body>";

echo "<h1>Comprehensive Data Enrichment Test</h1>";
echo "<p>Testing book: <strong>$title</strong> by <strong>$author</strong> (ISBN: <strong>$isbn</strong>)</p>";

// Test 1: Direct API calls
echo "<div class='test-section'>";
echo "<h2>Test 1: Direct API Calls</h2>";

// Test OpenLibrary API with ISBN prefix
$openLibraryUrl = "https://openlibrary.org/search.json?q=isbn:" . urlencode($isbn) . "&fields=*,availability&limit=1";
echo "<h3>OpenLibrary API (with isbn: prefix)</h3>";
echo "<p>URL: <code>$openLibraryUrl</code></p>";

$ch = curl_init($openLibraryUrl);
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
        echo "<p class='success'>✓ API returned data</p>";
        
        // Check ISBN match
        $returnedISBNs = [];
        if (isset($book['isbn'])) {
            $returnedISBNs = is_array($book['isbn']) ? $book['isbn'] : [$book['isbn']];
        }
        
        $hasCorrectISBN = false;
        foreach ($returnedISBNs as $returnedISBN) {
            if (str_replace('-', '', $returnedISBN) === str_replace('-', '', $isbn)) {
                $hasCorrectISBN = true;
                break;
            }
        }
        
        if ($hasCorrectISBN) {
            echo "<p class='success'>✓ Correct ISBN match found</p>";
        } else {
            echo "<p class='error'>✗ Wrong ISBN returned: " . implode(', ', $returnedISBNs) . "</p>";
        }
        
        // Show key fields
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($book['title'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>Author</td><td>" . htmlspecialchars(implode(', ', $book['author_name'] ?? [])) . "</td></tr>";
        echo "<tr><td>ISBNs</td><td>" . htmlspecialchars(implode(', ', $returnedISBNs)) . "</td></tr>";
        echo "<tr><td>Publisher</td><td>" . htmlspecialchars(implode(', ', $book['publisher'] ?? [])) . "</td></tr>";
        echo "<tr><td>Place</td><td>" . htmlspecialchars(implode(', ', $book['place'] ?? [])) . "</td></tr>";
        echo "<tr><td>Subject</td><td>" . htmlspecialchars(implode(', ', array_slice($book['subject'] ?? [], 0, 5))) . "...</td></tr>";
        echo "</table>";
    } else {
        echo "<p class='error'>✗ No results found</p>";
    }
} else {
    echo "<p class='error'>✗ API request failed (HTTP $httpCode)</p>";
}

echo "</div>";

// Test 2: Enrichment function
echo "<div class='test-section'>";
echo "<h2>Test 2: getEnrichedBookData() Function</h2>";

$startTime = microtime(true);
$enrichedData = getEnrichedBookData($title, $author, $isbn);
$endTime = microtime(true);
$processingTime = round($endTime - $startTime, 2);

echo "<p>Processing time: {$processingTime} seconds</p>";
echo "<p>Confidence Score: <strong>{$enrichedData['confidence_score']}</strong></p>";
echo "<p>ISBN Validated: <strong>{$enrichedData['isbn_validated']}</strong></p>";

// Check specific issues
echo "<h3>Issue Checks:</h3>";
echo "<table>";
echo "<tr><th>Issue</th><th>Status</th><th>Details</th></tr>";

// Issue 1: Wrong ISBN
$isbnField = $enrichedData['fields']['isbn'] ?? null;
if ($isbnField) {
    if (isset($isbnField['options'])) {
        $hasCorrectISBN = false;
        foreach ($isbnField['options'] as $option) {
            if (str_replace('-', '', $option['value']) === str_replace('-', '', $isbn)) {
                $hasCorrectISBN = true;
                break;
            }
        }
        if (!$hasCorrectISBN) {
            echo "<tr><td>Wrong ISBN</td><td class='error'>✗ FAILED</td><td>Options don't include correct ISBN</td></tr>";
        } else {
            echo "<tr><td>Wrong ISBN</td><td class='success'>✓ PASSED</td><td>Correct ISBN found in options</td></tr>";
        }
    } else {
        $returnedISBN = $isbnField['value'] ?? '';
        if (str_replace('-', '', $returnedISBN) !== str_replace('-', '', $isbn)) {
            echo "<tr><td>Wrong ISBN</td><td class='error'>✗ FAILED</td><td>Got: $returnedISBN</td></tr>";
        } else {
            echo "<tr><td>Wrong ISBN</td><td class='success'>✓ PASSED</td><td>Correct ISBN returned</td></tr>";
        }
    }
} else {
    echo "<tr><td>Wrong ISBN</td><td class='warning'>⚠ WARNING</td><td>No ISBN field found</td></tr>";
}

// Issue 2: Location deduplication
$settingsField = $enrichedData['fields']['settings'] ?? null;
if ($settingsField) {
    $settings = $settingsField['value'] ?? '';
    if (preg_match('/(\w+),\s*\1/i', $settings)) {
        echo "<tr><td>Location Deduplication</td><td class='error'>✗ FAILED</td><td>Duplicates found: $settings</td></tr>";
    } else {
        echo "<tr><td>Location Deduplication</td><td class='success'>✓ PASSED</td><td>$settings</td></tr>";
    }
} else {
    echo "<tr><td>Location Deduplication</td><td class='warning'>⚠ WARNING</td><td>No settings field found</td></tr>";
}

// Issue 3: Price Range
$priceField = $enrichedData['fields']['price_range'] ?? null;
if ($priceField) {
    $priceValue = $priceField['value'] ?? '';
    if (empty($priceValue) || $priceValue === 'Unknown') {
        echo "<tr><td>Price Range</td><td class='error'>✗ FAILED</td><td>Empty or Unknown</td></tr>";
    } else {
        echo "<tr><td>Price Range</td><td class='success'>✓ PASSED</td><td>$priceValue</td></tr>";
    }
} else {
    echo "<tr><td>Price Range</td><td class='warning'>⚠ WARNING</td><td>No price_range field found</td></tr>";
}

// Issue 4: Tags/Genres
$tagsField = $enrichedData['fields']['tags'] ?? null;
if ($tagsField) {
    if (isset($tagsField['options'])) {
        echo "<tr><td>Tags Merging</td><td class='error'>✗ FAILED</td><td>Still showing separate sources</td></tr>";
    } else {
        $tags = $tagsField['value'] ?? '';
        if (strpos($tags, 'google_books') !== false || strpos($tags, 'open_library') !== false) {
            echo "<tr><td>Tags Merging</td><td class='error'>✗ FAILED</td><td>Source names in tags</td></tr>";
        } else {
            echo "<tr><td>Tags Merging</td><td class='success'>✓ PASSED</td><td>Tags properly merged</td></tr>";
        }
    }
} else {
    echo "<tr><td>Tags Merging</td><td class='warning'>⚠ WARNING</td><td>No tags field found</td></tr>";
}

// Issue 5: Alternative ISBNs
$altISBNsField = $enrichedData['fields']['alternative_isbns'] ?? null;
if ($altISBNsField && !empty($altISBNsField['value'])) {
    echo "<tr><td>Alternative ISBNs</td><td class='success'>✓ PASSED</td><td>Found alternative ISBNs</td></tr>";
} else {
    echo "<tr><td>Alternative ISBNs</td><td class='error'>✗ FAILED</td><td>No alternative ISBNs found</td></tr>";
}

echo "</table>";

// Show all fields
echo "<h3>All Enriched Fields:</h3>";
echo "<div class='data-dump'><pre>";
print_r($enrichedData['fields']);
echo "</pre></div>";

echo "</div>";

// Test 3: Price scraping
echo "<div class='test-section'>";
echo "<h2>Test 3: Price Range Scraping</h2>";

// Test Amazon price scraping
$amazonUrl = "https://www.amazon.co.uk/dp/$isbn";
echo "<p>Testing Amazon UK price for ISBN: $isbn</p>";
echo "<p>URL: <code>$amazonUrl</code></p>";

// Try to get price using the existing function
if (function_exists('scrapeAmazonPrice')) {
    $price = scrapeAmazonPrice($isbn);
    if ($price && $price !== 'Unknown') {
        echo "<p class='success'>✓ Price found: $price</p>";
    } else {
        echo "<p class='error'>✗ Price scraping failed</p>";
    }
} else {
    echo "<p class='warning'>⚠ scrapeAmazonPrice function not found</p>";
}

echo "</div>";

echo "</body></html>";