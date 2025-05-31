<?php
// Test maturity rating mapping to identify 18+ years issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files (no database needed for this test)
require_once 'book-import-validate/functions/data-enrichment-functions.php';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Maturity Rating Test</title>";
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

echo "<h1>🔍 Age Range Issue Test</h1>";
echo "<p>Testing Chronicles of Narnia to identify why it shows '18+ years' instead of children's age range</p>";

// Test case: Chronicles of Narnia
$title = "The Lion, the Witch and the Wardrobe";
$author = "C.S. Lewis";
$isbn = "9780007416851";

echo "<div class='test-section'>";
echo "<h2>Test 1: Direct Enrichment Function</h2>";
echo "<p>Testing book: <strong>$title</strong> by <strong>$author</strong> (ISBN: <strong>$isbn</strong>)</p>";

try {
    $startTime = microtime(true);
    $enrichedData = getEnrichedBookData($title, $author, $isbn);
    $endTime = microtime(true);
    $processingTime = round($endTime - $startTime, 2);

    echo "<p>Processing time: {$processingTime} seconds</p>";
    echo "<p>Confidence Score: <strong>{$enrichedData['confidence_score']}</strong></p>";

    // Check age_range field specifically
    $ageRangeField = $enrichedData['fields']['age_range'] ?? null;
    if ($ageRangeField) {
        echo "<h3>Age Range Field Analysis:</h3>";
        echo "<table>";
        echo "<tr><th>Property</th><th>Value</th></tr>";
        echo "<tr><td>Value</td><td><strong>" . htmlspecialchars($ageRangeField['value'] ?? 'null') . "</strong></td></tr>";
        echo "<tr><td>Source</td><td>" . htmlspecialchars($ageRangeField['source'] ?? 'null') . "</td></tr>";
        echo "<tr><td>Confidence</td><td>" . htmlspecialchars($ageRangeField['confidence'] ?? 'null') . "</td></tr>";
        echo "</table>";

        if (($ageRangeField['value'] ?? '') === '18+ years') {
            echo "<p class='error'>❌ PROBLEM FOUND: Age range is '18+ years' for a children's book!</p>";
            echo "<p>Source: " . htmlspecialchars($ageRangeField['source'] ?? 'unknown') . "</p>";
        } else {
            echo "<p class='success'>✅ Age range looks appropriate: " . htmlspecialchars($ageRangeField['value'] ?? 'null') . "</p>";
        }
    } else {
        echo "<p class='warning'>⚠ No age_range field found</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Test 2: Show all enriched fields
echo "<div class='test-section'>";
echo "<h2>Test 2: All Enriched Fields</h2>";

if (isset($enrichedData) && isset($enrichedData['fields'])) {
    echo "<h3>All Fields from Enrichment:</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th><th>Source</th><th>Confidence</th></tr>";

    foreach ($enrichedData['fields'] as $fieldName => $fieldData) {
        if (is_array($fieldData)) {
            $value = $fieldData['value'] ?? 'N/A';
            $source = $fieldData['source'] ?? 'N/A';
            $confidence = $fieldData['confidence'] ?? 'N/A';

            $rowClass = '';
            if ($fieldName === 'age_range' && $value === '18+ years') {
                $rowClass = ' style="background-color: #ffcccc;"';
            }

            echo "<tr$rowClass>";
            echo "<td><strong>" . htmlspecialchars($fieldName) . "</strong></td>";
            echo "<td>" . htmlspecialchars($value) . "</td>";
            echo "<td>" . htmlspecialchars($source) . "</td>";
            echo "<td>" . htmlspecialchars($confidence) . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p class='warning'>⚠ No enriched data available</p>";
}

echo "</div>";

// Test 3: Raw API data
echo "<div class='test-section'>";
echo "<h2>Test 3: Raw Google Books API Data</h2>";

$googleBooksUrl = "https://www.googleapis.com/books/v1/volumes?q=isbn:$isbn";
echo "<p>Testing Google Books API directly for ISBN: $isbn</p>";
echo "<p>URL: <code>$googleBooksUrl</code></p>";

$ch = curl_init($googleBooksUrl);
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
        echo "<p class='success'>✓ Google Books API returned data</p>";

        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($book['title'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>Authors</td><td>" . htmlspecialchars(implode(', ', $book['authors'] ?? [])) . "</td></tr>";
        echo "<tr><td>Categories</td><td>" . htmlspecialchars(implode(', ', $book['categories'] ?? [])) . "</td></tr>";
        echo "<tr><td>Maturity Rating</td><td><strong>" . htmlspecialchars($book['maturityRating'] ?? 'N/A') . "</strong></td></tr>";
        echo "<tr><td>Publisher</td><td>" . htmlspecialchars($book['publisher'] ?? 'N/A') . "</td></tr>";
        echo "</table>";

        // Check maturity rating specifically
        $maturityRating = $book['maturityRating'] ?? null;
        if ($maturityRating === 'NOT_MATURE') {
            echo "<p class='warning'>⚠ Google Books returns maturityRating: 'NOT_MATURE' - this should map to children's age range, not 18+!</p>";
        } elseif ($maturityRating === 'MATURE') {
            echo "<p class='error'>❌ Google Books returns maturityRating: 'MATURE' - this would correctly map to 18+</p>";
        } else {
            echo "<p>Maturity rating: " . htmlspecialchars($maturityRating ?? 'null') . "</p>";
        }
    } else {
        echo "<p class='error'>✗ No results found in Google Books</p>";
    }
} else {
    echo "<p class='error'>✗ Google Books API request failed (HTTP $httpCode)</p>";
}

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🎯 Debugging Instructions</h2>";
echo "<p><strong>To see detailed AGE_TEST debugging:</strong></p>";
echo "<ol>";
echo "<li>Open the data enrichment modal for Chronicles of Narnia</li>";
echo "<li>Open browser console (F12)</li>";
echo "<li>Filter console by 'AGE_TEST' to see only age-related logs</li>";
echo "<li>Look for logs showing where '18+ years' is coming from</li>";
echo "</ol>";
echo "<p><strong>Expected logs to look for:</strong></p>";
echo "<ul>";
echo "<li><code>AGE_TEST: MATURITY_MAPPING: Processing 'NOT_MATURE'</code></li>";
echo "<li><code>AGE_TEST: AGE_RANGE_EXTRACT_DEBUG: Starting age range extraction</code></li>";
echo "<li><code>AGE_TEST: AGE_RANGE_DEBUG: age_range field exists with value</code></li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
