<?php
/**
 * Test Amazon Scraping for Data Enrichment
 * 
 * This script tests the Amazon scraping functionality to verify:
 * 1. Multiple formats are detected (Kindle, Hardcover, Paperback, Audio CD)
 * 2. Correct URLs are generated using /gp/product/ and ISBN-10
 * 3. Price ranges are calculated correctly
 */

require_once 'admin/content/book-import-validate/functions/data-enrichment-functions.php';

// Test ISBN (Coraline by Neil Gaiman)
$testISBN13 = '9780380977789';
$testISBN10 = '0380977788';

echo "<h2>Testing Amazon Scraping for Data Enrichment</h2>\n";
echo "<p><strong>Test ISBN-13:</strong> $testISBN13</p>\n";
echo "<p><strong>Test ISBN-10:</strong> $testISBN10</p>\n";

echo "<hr>\n";

// Test 1: Amazon buying options scraping
echo "<h3>1. Testing Amazon Buying Options Scraping</h3>\n";
echo "<p>Testing with ISBN-10: $testISBN10</p>\n";

$buyingOptions = scrapeAmazonBuyingOptions($testISBN10);

if (!empty($buyingOptions)) {
    echo "<p><strong>✅ Success!</strong> Found " . count($buyingOptions) . " buying options:</p>\n";
    echo "<ul>\n";
    foreach ($buyingOptions as $format => $price) {
        echo "<li><strong>$format:</strong> $price</li>\n";
    }
    echo "</ul>\n";
} else {
    echo "<p><strong>❌ Failed!</strong> No buying options found.</p>\n";
}

echo "<hr>\n";

// Test 2: Purchase links generation
echo "<h3>2. Testing Purchase Links Generation</h3>\n";

// Create a mock match array
$mockMatch = [
    'isbn13' => $testISBN13,
    'isbn' => $testISBN10
];

$purchaseLinks = extractFieldValue($mockMatch, 'purchase_links', '');

if (!empty($purchaseLinks)) {
    echo "<p><strong>✅ Success!</strong> Generated purchase links:</p>\n";
    $linksArray = json_decode($purchaseLinks, true);
    if ($linksArray) {
        echo "<ul>\n";
        foreach ($linksArray as $format => $url) {
            echo "<li><strong>$format:</strong> <a href=\"$url\" target=\"_blank\">$url</a></li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p>Raw JSON: $purchaseLinks</p>\n";
    }
} else {
    echo "<p><strong>❌ Failed!</strong> No purchase links generated.</p>\n";
}

echo "<hr>\n";

// Test 3: Format extraction
echo "<h3>3. Testing Format Extraction</h3>\n";

$format = extractFieldValue($mockMatch, 'format', '');

if (!empty($format)) {
    echo "<p><strong>✅ Success!</strong> Detected format: <strong>$format</strong></p>\n";
} else {
    echo "<p><strong>❌ Failed!</strong> No format detected.</p>\n";
}

echo "<hr>\n";

// Test 4: Price range extraction
echo "<h3>4. Testing Price Range Extraction</h3>\n";

$priceRange = extractFieldValue($mockMatch, 'price_range', '');

if (!empty($priceRange)) {
    echo "<p><strong>✅ Success!</strong> Detected price range: <strong>$priceRange</strong></p>\n";
} else {
    echo "<p><strong>❌ Failed!</strong> No price range detected.</p>\n";
}

echo "<hr>\n";

// Test 5: ISBN conversion
echo "<h3>5. Testing ISBN Conversion</h3>\n";

$convertedISBN10 = convertISBN13ToISBN10($testISBN13);
$convertedISBN13 = convertToISBN13($testISBN10);

echo "<p><strong>ISBN-13 to ISBN-10:</strong> $testISBN13 → $convertedISBN10</p>\n";
echo "<p><strong>ISBN-10 to ISBN-13:</strong> $testISBN10 → $convertedISBN13</p>\n";

if ($convertedISBN10 === $testISBN10) {
    echo "<p><strong>✅ ISBN conversion working correctly!</strong></p>\n";
} else {
    echo "<p><strong>❌ ISBN conversion failed!</strong></p>\n";
}

echo "<hr>\n";
echo "<p><em>Test completed. Check the results above to verify Amazon scraping functionality.</em></p>\n";
?>
