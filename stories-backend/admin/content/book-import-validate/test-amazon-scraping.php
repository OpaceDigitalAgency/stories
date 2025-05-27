<?php
/**
 * Test script for Amazon scraping functionality
 */

// Enable debug mode for Amazon scraping
define('AMAZON_DEBUG', true);

// Include the data enrichment functions
require_once 'functions/data-enrichment-functions.php';

// Test ISBN for Coraline
$testISBN = '9780380977789';

echo "<h1>Testing Amazon Scraping for ISBN: {$testISBN}</h1>\n";
echo "<hr>\n";

// Test the scrapeAmazonBuyingOptions function
echo "<h2>Testing scrapeAmazonBuyingOptions()</h2>\n";
$buyingOptions = scrapeAmazonBuyingOptions($testISBN);

echo "<h3>Results:</h3>\n";
if (!empty($buyingOptions)) {
    echo "<pre>" . json_encode($buyingOptions, JSON_PRETTY_PRINT) . "</pre>\n";
} else {
    echo "<p><strong>No buying options found.</strong></p>\n";
}

echo "<hr>\n";

// Test the getAmazonEnrichmentData function
echo "<h2>Testing getAmazonEnrichmentData()</h2>\n";
$enrichmentData = getAmazonEnrichmentData($testISBN);

echo "<h3>Enrichment Data Results:</h3>\n";
echo "<pre>" . json_encode($enrichmentData, JSON_PRETTY_PRINT) . "</pre>\n";

echo "<hr>\n";
echo "<p><strong>Test completed.</strong></p>\n";
?>
