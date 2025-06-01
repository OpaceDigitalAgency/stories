<?php
/**
 * Quick test to debug Amazon scraping for ISBN 9780007115617
 */

// Include required files
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Amazon Scraping Debug Test</h1>";
echo "<p>Testing ISBN: 9780007115617 (Chronicles of Narnia)</p>";

// Enable Amazon debug mode
if (!defined('AMAZON_DEBUG')) {
    define('AMAZON_DEBUG', true);
}

echo "<h2>1. Testing scrapeAmazonBuyingOptions directly</h2>";

$isbn = '9780007115617';
$isbn10 = '0007115617';

echo "<h3>Testing with ISBN-13: $isbn</h3>";
$amazonData13 = scrapeAmazonBuyingOptions($isbn);
echo "<h4>Result:</h4>";
echo "<pre>";
print_r($amazonData13);
echo "</pre>";

echo "<h3>Testing with ISBN-10: $isbn10</h3>";
$amazonData10 = scrapeAmazonBuyingOptions($isbn10);
echo "<h4>Result:</h4>";
echo "<pre>";
print_r($amazonData10);
echo "</pre>";

echo "<h2>2. Checking specific metadata fields</h2>";

$testData = $amazonData10 ?: $amazonData13;

if (!empty($testData)) {
    echo "<h3>Metadata found:</h3>";
    if (isset($testData['metadata'])) {
        foreach ($testData['metadata'] as $key => $value) {
            echo "<p><strong>$key:</strong> $value</p>";
        }
    } else {
        echo "<p><strong>❌ No metadata array found</strong></p>";
    }
    
    echo "<h3>Buying options found:</h3>";
    if (isset($testData['buying_options'])) {
        foreach ($testData['buying_options'] as $format => $data) {
            echo "<p><strong>$format:</strong> {$data['price']} - {$data['url']}</p>";
        }
    } else {
        echo "<p><strong>❌ No buying_options array found</strong></p>";
    }
} else {
    echo "<p><strong>❌ No Amazon data returned at all</strong></p>";
}

echo "<h2>3. Manual URL Test</h2>";
echo "<p>Amazon URLs being tested:</p>";
echo "<ul>";
echo "<li><a href='https://www.amazon.co.uk/dp/$isbn10' target='_blank'>Desktop: https://www.amazon.co.uk/dp/$isbn10</a></li>";
echo "<li><a href='https://www.amazon.co.uk/gp/aw/d/$isbn10' target='_blank'>Mobile: https://www.amazon.co.uk/gp/aw/d/$isbn10</a></li>";
echo "</ul>";

echo "<h2>4. Expected vs Actual</h2>";
echo "<p><strong>Expected reading_age:</strong> '6 - 9 years, from customers'</p>";
echo "<p><strong>Actual reading_age:</strong> " . ($testData['metadata']['reading_age'] ?? 'NOT FOUND') . "</p>";

echo "<p><strong>Expected publisher:</strong> 'HarperCollinsChildren'sBooks'</p>";
echo "<p><strong>Actual publisher:</strong> " . ($testData['metadata']['publisher'] ?? 'NOT FOUND') . "</p>";

echo "<p><strong>Expected language:</strong> 'English'</p>";
echo "<p><strong>Actual language:</strong> " . ($testData['metadata']['language'] ?? 'NOT FOUND') . "</p>";

?>
