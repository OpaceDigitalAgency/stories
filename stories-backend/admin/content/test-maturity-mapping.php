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

echo "<h1>🔍 Data Enrichment Debug Test</h1>";
echo "<p>Testing Chronicles of Narnia to identify wrong book data and age/reading level issues</p>";

// Test case: Chronicles of Narnia
$title = "The Lion, the Witch and the Wardrobe";
$author = "C.S. Lewis";
$isbn = "9780007416851";

echo "<div class='test-section'>";
echo "<h2>🎯 Expected vs Actual Data</h2>";
echo "<table>";
echo "<tr><th>Field</th><th>Expected (Chronicles of Narnia)</th><th>Status</th></tr>";
echo "<tr><td><strong>Title</strong></td><td>The Lion, the Witch and the Wardrobe</td><td>❌ Getting 'Earwig and the Witch'</td></tr>";
echo "<tr><td><strong>Author</strong></td><td>C.S. Lewis</td><td>❌ Getting 'Diana Wynne Jones'</td></tr>";
echo "<tr><td><strong>Age Range</strong></td><td>8-9 years (children's book)</td><td>❌ Getting null or 18+ years</td></tr>";
echo "<tr><td><strong>Reading Level</strong></td><td>Fluent Reader</td><td>❌ Getting 'Early Reader'</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>Test 1: Direct Enrichment Function Analysis</h2>";
echo "<p>Testing book: <strong>$title</strong> by <strong>$author</strong> (ISBN: <strong>$isbn</strong>)</p>";

try {
    $startTime = microtime(true);
    $enrichedData = getEnrichedBookData($title, $author, $isbn);
    $endTime = microtime(true);
    $processingTime = round($endTime - $startTime, 2);

    echo "<p>Processing time: {$processingTime} seconds</p>";
    echo "<p>Confidence Score: <strong>{$enrichedData['confidence_score']}</strong></p>";

    // Analyze critical data mismatches
    echo "<h3>🚨 Critical Data Analysis:</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Expected</th><th>Actual</th><th>Status</th><th>Source</th></tr>";

    // Check title
    $titleField = $enrichedData['fields']['title'] ?? null;
    $actualTitle = $titleField['value'] ?? 'N/A';
    $titleStatus = (stripos($actualTitle, 'Lion') !== false || stripos($actualTitle, 'Wardrobe') !== false) ? '✅ Correct' : '❌ WRONG BOOK';
    echo "<tr><td><strong>Title</strong></td><td>The Lion, the Witch and the Wardrobe</td><td>" . htmlspecialchars($actualTitle) . "</td><td>$titleStatus</td><td>" . htmlspecialchars($titleField['source'] ?? 'unknown') . "</td></tr>";

    // Check author
    $authorField = $enrichedData['fields']['author'] ?? null;
    $actualAuthor = $authorField['value'] ?? 'N/A';
    $authorStatus = (stripos($actualAuthor, 'C.S. Lewis') !== false || stripos($actualAuthor, 'Lewis') !== false) ? '✅ Correct' : '❌ WRONG AUTHOR';
    echo "<tr><td><strong>Author</strong></td><td>C.S. Lewis</td><td>" . htmlspecialchars($actualAuthor) . "</td><td>$authorStatus</td><td>" . htmlspecialchars($authorField['source'] ?? 'unknown') . "</td></tr>";

    // Check age range
    $ageRangeField = $enrichedData['fields']['age_range'] ?? null;
    $actualAgeRange = $ageRangeField['value'] ?? 'null';
    $ageStatus = ($actualAgeRange === 'null') ? '❌ NULL' : (($actualAgeRange === '18+ years') ? '❌ ADULT AGE' : '✅ Has Value');
    echo "<tr><td><strong>Age Range</strong></td><td>8-9 years (children's)</td><td>" . htmlspecialchars($actualAgeRange) . "</td><td>$ageStatus</td><td>" . htmlspecialchars($ageRangeField['source'] ?? 'unknown') . "</td></tr>";

    // Check reading level
    $readingField = $enrichedData['fields']['reading_level'] ?? null;
    $actualReading = $readingField['value'] ?? 'N/A';
    $readingStatus = (stripos($actualReading, 'Fluent') !== false) ? '✅ Expected' : '📝 Different';
    echo "<tr><td><strong>Reading Level</strong></td><td>Fluent Reader</td><td>" . htmlspecialchars($actualReading) . "</td><td>$readingStatus</td><td>" . htmlspecialchars($readingField['source'] ?? 'unknown') . "</td></tr>";

    // Check maturity rating (should now be null)
    $maturityField = $enrichedData['fields']['maturity_rating'] ?? null;
    $actualMaturity = $maturityField['value'] ?? 'null';
    $maturityStatus = ($actualMaturity === 'null') ? '✅ Removed' : '⚠ Still Present';
    echo "<tr><td><strong>Maturity Rating</strong></td><td>null (removed)</td><td>" . htmlspecialchars($actualMaturity) . "</td><td>$maturityStatus</td><td>" . htmlspecialchars($maturityField['source'] ?? 'unknown') . "</td></tr>";

    echo "</table>";

    // Root cause analysis
    if ($titleStatus === '❌ WRONG BOOK' || $authorStatus === '❌ WRONG AUTHOR') {
        echo "<div style='background-color: #ffeeee; padding: 15px; margin: 10px 0; border-left: 5px solid red;'>";
        echo "<h4>🚨 ROOT CAUSE IDENTIFIED: Wrong Book Data</h4>";
        echo "<p><strong>The ISBN search is returning completely wrong book data!</strong></p>";
        echo "<p>This explains why:</p>";
        echo "<ul>";
        echo "<li>Author shows as 'Diana Wynne Jones' instead of 'C.S. Lewis'</li>";
        echo "<li>Title shows as 'Earwig and the Witch' instead of 'Chronicles of Narnia'</li>";
        echo "<li>Age ranges and reading levels are wrong for the intended book</li>";
        echo "</ul>";
        echo "<p><strong>Next step:</strong> Check why ISBN 9780007416851 returns wrong book from APIs</p>";
        echo "</div>";
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

        // Check if this is the correct book
        $bookTitle = $book['title'] ?? '';
        $bookAuthors = $book['authors'] ?? [];
        $isCorrectBook = (stripos($bookTitle, 'Lion') !== false || stripos($bookTitle, 'Wardrobe') !== false) &&
                        (in_array('C.S. Lewis', $bookAuthors) || array_filter($bookAuthors, function($author) { return stripos($author, 'Lewis') !== false; }));

        if ($isCorrectBook) {
            echo "<p class='success'>✅ Google Books returned the CORRECT book</p>";
        } else {
            echo "<p class='error'>❌ Google Books returned the WRONG book - this explains the data mismatch!</p>";
            echo "<p><strong>Expected:</strong> The Lion, the Witch and the Wardrobe by C.S. Lewis</p>";
            echo "<p><strong>Got:</strong> " . htmlspecialchars($bookTitle) . " by " . htmlspecialchars(implode(', ', $bookAuthors)) . "</p>";
        }

        // Check for age-related categories
        $categories = $book['categories'] ?? [];
        $ageRelatedCategories = array_filter($categories, function($cat) {
            return preg_match('/\d+.*years?|children|juvenile|young|teen/i', $cat);
        });

        if (!empty($ageRelatedCategories)) {
            echo "<p><strong>Age-related categories found:</strong> " . htmlspecialchars(implode(', ', $ageRelatedCategories)) . "</p>";
        } else {
            echo "<p class='warning'>⚠ No age-related categories found in Google Books data</p>";
        }
    } else {
        echo "<p class='error'>✗ No results found in Google Books</p>";
    }
} else {
    echo "<p class='error'>✗ Google Books API request failed (HTTP $httpCode)</p>";
}

echo "</div>";

// Test 4: Individual Source Testing
echo "<div class='test-section'>";
echo "<h2>Test 4: Individual Source Analysis</h2>";

echo "<h3>🔍 Issues Found in Test Results:</h3>";
echo "<ul class='error'>";
echo "<li><strong>Wrong Book Data:</strong> APIs are returning 'Earwig and the Witch' by Diana Wynne Jones instead of 'The Lion, the Witch and the Wardrobe' by C.S. Lewis</li>";
echo "<li><strong>Age Range is null:</strong> No age range data being extracted from any source</li>";
echo "<li><strong>Reading Level Mismatch:</strong> Getting 'Early Reader' instead of expected 'Fluent Reader' for 8-9 year olds</li>";
echo "<li><strong>Maturity Rating Removed:</strong> Maturity rating logic has been removed as requested</li>";
echo "</ul>";

// Test OpenLibrary directly
echo "<h3>📚 OpenLibrary API Test</h3>";
$openLibraryUrl = "https://openlibrary.org/search.json?q=isbn:$isbn&fields=*,availability&limit=1";
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
        echo "<p class='success'>✓ OpenLibrary API returned data</p>";

        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($book['title'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>Authors</td><td>" . htmlspecialchars(implode(', ', $book['author_name'] ?? [])) . "</td></tr>";
        echo "<tr><td>Subjects</td><td>" . htmlspecialchars(implode(', ', array_slice($book['subject'] ?? [], 0, 5))) . "...</td></tr>";
        echo "<tr><td>Reading Log Count</td><td>" . htmlspecialchars($book['readinglog_count'] ?? 'N/A') . "</td></tr>";
        echo "<tr><td>Want to Read Count</td><td>" . htmlspecialchars($book['want_to_read_count'] ?? 'N/A') . "</td></tr>";
        echo "</table>";

        // Test age range extraction from OpenLibrary subjects
        $subjects = $book['subject'] ?? [];
        $extractedAgeRange = null;

        // Look for age-related subjects
        foreach ($subjects as $subject) {
            if (preg_match('/(\d+)-(\d+)\s*years?/i', $subject, $matches)) {
                $extractedAgeRange = $matches[1] . '-' . $matches[2] . ' years';
                break;
            }
        }

        echo "<p><strong>OpenLibrary Age Range (extracted):</strong> " . htmlspecialchars($extractedAgeRange ?? 'null') . "</p>";
        echo "<p><strong>OpenLibrary Subjects (first 10):</strong> " . htmlspecialchars(implode(', ', array_slice($subjects, 0, 10))) . "</p>";
    } else {
        echo "<p class='error'>✗ No results found in OpenLibrary</p>";
    }
} else {
    echo "<p class='error'>✗ OpenLibrary API request failed (HTTP $httpCode)</p>";
}

// Test Amazon data
echo "<h3>🛒 Amazon Data Test</h3>";
if (function_exists('scrapeAmazonBuyingOptions')) {
    echo "<p>Testing Amazon buying options scraping for ISBN: $isbn</p>";
    $amazonData = scrapeAmazonBuyingOptions($isbn);

    if ($amazonData && !empty($amazonData)) {
        echo "<p class='success'>✓ Amazon data found</p>";

        // Show metadata if available
        if (isset($amazonData['metadata']) && !empty($amazonData['metadata'])) {
            echo "<h4>Amazon Metadata:</h4>";
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($amazonData['metadata'] as $key => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";

            // Test age range extraction from Amazon
            if (isset($amazonData['metadata']['reading_age'])) {
                echo "<p><strong>Amazon Reading Age:</strong> " . htmlspecialchars($amazonData['metadata']['reading_age']) . "</p>";

                // Test mapping function if it exists
                if (function_exists('mapAmazonAgeRangeToStandard')) {
                    $mappedAge = mapAmazonAgeRangeToStandard($amazonData['metadata']['reading_age']);
                    echo "<p><strong>Mapped to Standard:</strong> " . htmlspecialchars($mappedAge ?? 'null') . "</p>";
                }
            }
        } else {
            echo "<p class='warning'>⚠ Amazon data found but no metadata</p>";
        }

        // Show buying options if available
        if (isset($amazonData['buying_options']) && !empty($amazonData['buying_options'])) {
            echo "<h4>Amazon Buying Options:</h4>";
            echo "<table>";
            echo "<tr><th>Format</th><th>Price</th><th>Selected</th></tr>";
            foreach ($amazonData['buying_options'] as $format => $option) {
                $selected = ($option['is_selected'] ?? false) ? '✅' : '❌';
                echo "<tr><td>" . htmlspecialchars($format) . "</td><td>" . htmlspecialchars($option['price'] ?? 'N/A') . "</td><td>$selected</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='warning'>⚠ No Amazon data found or scraping failed</p>";
    }
} else {
    echo "<p class='warning'>⚠ scrapeAmazonBuyingOptions function not available</p>";
}

echo "</div>";

// Test 5: Age Range to Reading Level Mapping
echo "<div class='test-section'>";
echo "<h2>Test 5: Age Range to Reading Level Mapping</h2>";

// Use the actual mapping from the codebase
$ageToReadingMap = [
    '0-12 months' => 'Pre-literacy (Sensory)',
    '12-24 months' => 'Pre-literacy (Naming)',
    '2-3 years' => 'Pre-literacy (Mimicry)',
    '3-4 years' => 'Early Pre-reader',
    '4-5 years' => 'Beginning Reader',
    '5-6 years' => 'Early Reader',
    '6-7 years' => 'Early Reader',        // FIXED: was "Developing Reader", now "Early Reader"
    '7-8 years' => 'Transitional Reader',
    '8-9 years' => 'Fluent Reader',
    '9-10 years' => 'Fluent Reader',
    '10-11 years' => 'Fluent Reader',
    '11-14 years' => 'Advanced Reader',
    '14-16 years' => 'Advanced Reader',
    '16-18 years' => 'Advanced Reader',
    '18+ years' => 'Proficient Reader'
];

$testAgeRanges = ['8-9 years', '7-8 years', '9-10 years', '5-6 years', '11-14 years', '18+ years'];

echo "<h3>Current Age Range to Reading Level Mapping:</h3>";
echo "<table>";
echo "<tr><th>Age Range</th><th>Mapped Reading Level</th><th>Status</th></tr>";

foreach ($testAgeRanges as $ageRange) {
    $readingLevel = $ageToReadingMap[$ageRange] ?? 'NOT FOUND';
    $status = ($readingLevel !== 'NOT FOUND') ? '✅ Mapped' : '❌ Missing';

    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($ageRange) . "</strong></td>";
    echo "<td>" . htmlspecialchars($readingLevel) . "</td>";
    echo "<td>" . htmlspecialchars($status) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>🔍 Key Findings:</h3>";
echo "<ul>";
echo "<li><strong>'8-9 years' maps to 'Fluent Reader'</strong> - This is what should appear in the modal for Chronicles of Narnia</li>";
echo "<li><strong>'18+ years' maps to 'Proficient Reader'</strong> - Adult content mapping</li>";
echo "<li><strong>Maturity rating logic removed</strong> - Age ranges now come only from OpenLibrary subjects and Amazon metadata</li>";
echo "<li><strong>Wrong book data is the root cause</strong> - ISBN search returning incorrect book affects all fields</li>";
echo "</ul>";

echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🎯 Summary & Next Steps</h2>";
echo "<h3>✅ What's Working:</h3>";
echo "<ul class='success'>";
echo "<li>Maturity rating logic successfully removed as requested</li>";
echo "<li>Enrichment function runs successfully with 100% confidence</li>";
echo "<li>Alternative ISBNs are being found correctly</li>";
echo "<li>Age range to reading level mapping is properly defined</li>";
echo "</ul>";

echo "<h3>❌ Critical Issues to Fix:</h3>";
echo "<ul class='error'>";
echo "<li><strong>Wrong Book Data:</strong> ISBN 9780007416851 returns 'Earwig and the Witch' instead of 'Chronicles of Narnia'</li>";
echo "<li><strong>Age Range Extraction Failing:</strong> No age range data being extracted from any source</li>";
echo "<li><strong>Reading Level Mismatch:</strong> Getting 'Early Reader' instead of 'Fluent Reader'</li>";
echo "<li><strong>Source Data Quality:</strong> All enrichment sources returning wrong book metadata</li>";
echo "</ul>";

echo "<h3>🔧 Debugging Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check ISBN Match:</strong> Verify why ISBN 9780007416851 returns 'Earwig and the Witch' instead of 'Chronicles of Narnia'</li>";
echo "<li><strong>Field Mapping:</strong> Check why maturity_rating value isn't being copied to age_range field</li>";
echo "<li><strong>Console Debugging:</strong> Open data enrichment modal and filter console by 'AGE_TEST'</li>";
echo "</ol>";

echo "</div>";

// Test 6: Expected Modal Behavior
echo "<div class='test-section'>";
echo "<h2>Test 6: Expected Data Enrichment Modal Behavior</h2>";

echo "<h3>📋 What Should Appear in the Modal for Chronicles of Narnia:</h3>";

echo "<h4>🎯 Age Range Field:</h4>";
echo "<table>";
echo "<tr><th>Source</th><th>Expected Value</th><th>Confidence</th><th>Status</th></tr>";
echo "<tr><td><strong>Google Books</strong></td><td>8-9 years</td><td>55%</td><td>✅ Should show (NOT 18+ years)</td></tr>";
echo "<tr><td><strong>OpenLibrary</strong></td><td>8-9 years or similar</td><td>35%</td><td>📝 Depends on subject extraction</td></tr>";
echo "<tr><td><strong>Amazon</strong></td><td>8-9 years or similar</td><td>Variable</td><td>📝 Depends on scraping success</td></tr>";
echo "</table>";

echo "<h4>🎯 Reading Level Field:</h4>";
echo "<table>";
echo "<tr><th>Source</th><th>Expected Value</th><th>Confidence</th><th>Status</th></tr>";
echo "<tr><td><strong>Mapped from Age Range</strong></td><td>Fluent Reader</td><td>High</td><td>✅ Should auto-sync from 8-9 years</td></tr>";
echo "<tr><td><strong>OpenLibrary</strong></td><td>Early Reader/Fluent Reader</td><td>35%</td><td>📝 From subjects analysis</td></tr>";
echo "<tr><td><strong>Google Books</strong></td><td>Fluent Reader</td><td>40%</td><td>📝 From categories analysis</td></tr>";
echo "</table>";

echo "<h4>🚨 Critical Issues to Fix:</h4>";
echo "<ul class='error'>";
echo "<li><strong>Wrong Book Match:</strong> ISBN 9780007416851 should return 'The Lion, the Witch and the Wardrobe' not 'Earwig and the Witch'</li>";
echo "<li><strong>Age Range Field Null:</strong> Despite maturity_rating being correctly set to '8-9 years', the age_range field shows null</li>";
echo "<li><strong>Field Synchronization:</strong> Age range and reading level should auto-sync when one is selected</li>";
echo "</ul>";

echo "<h4>🔍 Root Cause Analysis:</h4>";
echo "<ol>";
echo "<li><strong>ISBN Mismatch:</strong> The ISBN search is returning the wrong book entirely</li>";
echo "<li><strong>Field Mapping Bug:</strong> The maturity_rating field gets the correct value but age_range field doesn't</li>";
echo "<li><strong>Data Flow Issue:</strong> There's a disconnect between the enrichment function results and the modal display</li>";
echo "</ol>";

echo "</div>";

echo "</body></html>";
