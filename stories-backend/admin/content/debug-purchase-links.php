<?php
// Debug Purchase Links Comparison
require_once '../../../db-connect.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

echo "<h1>Debug Purchase Links Comparison</h1>";

// Test data from your issue
$currentValue = "Kindle: £3.19 \nPaperback: £2.99 Default";
$newValue = "Paperback: £2.99 Default \nKindle: £3.19";

echo "<h2>Test Data</h2>";
echo "<p><strong>Current Value:</strong><br>" . nl2br(htmlspecialchars($currentValue)) . "</p>";
echo "<p><strong>New Value:</strong><br>" . nl2br(htmlspecialchars($newValue)) . "</p>";

echo "<h2>JavaScript Test</h2>";
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/data-enrichment-utils.js"></script>
<script>
$(document).ready(function() {
    console.log('🛒 Starting purchase links comparison test...');
    
    const currentValue = "Kindle: £3.19 \nPaperback: £2.99 Default";
    const newValue = "Paperback: £2.99 Default \nKindle: £3.19";
    
    console.log('🛒 Current Value:', currentValue);
    console.log('🛒 New Value:', newValue);
    
    // Test parsing
    const currentParsed = parsePurchaseLinksDisplay(currentValue);
    const newParsed = parsePurchaseLinksDisplay(newValue);
    
    console.log('🛒 Current Parsed:', currentParsed);
    console.log('🛒 New Parsed:', newParsed);
    
    // Test normalization
    const currentNormalized = normalizePurchaseLinks(currentParsed);
    const newNormalized = normalizePurchaseLinks(newParsed);
    
    console.log('🛒 Current Normalized:', currentNormalized);
    console.log('🛒 New Normalized:', newNormalized);
    
    // Test comparison
    const isEqual = comparePurchaseLinksObjects(currentNormalized, newNormalized);
    console.log('🛒 Are Equal:', isEqual);
    
    // Test isExactMatch
    const exactMatch = isExactMatch(currentValue, newValue);
    console.log('🛒 Exact Match:', exactMatch);
    
    // Display results on page
    $('#results').html(`
        <h3>Results</h3>
        <p><strong>Current Parsed:</strong> ${JSON.stringify(currentParsed, null, 2)}</p>
        <p><strong>New Parsed:</strong> ${JSON.stringify(newParsed, null, 2)}</p>
        <p><strong>Current Normalized:</strong> ${JSON.stringify(currentNormalized, null, 2)}</p>
        <p><strong>New Normalized:</strong> ${JSON.stringify(newNormalized, null, 2)}</p>
        <p><strong>Objects Equal:</strong> ${isEqual}</p>
        <p><strong>Exact Match:</strong> ${exactMatch}</p>
        <p><strong>Expected Result:</strong> TRUE (they should match)</p>
    `);
});
</script>

<div id="results">
    <p>Loading JavaScript test results...</p>
</div>

<?php
echo "<h2>Amazon Data Test</h2>";

// Test Amazon data for the problematic ISBN
$testISBN = '9780241425428';
echo "<p>Testing Amazon data for ISBN: $testISBN</p>";

try {
    // Convert to ISBN-10 for Amazon
    $isbn10 = convertISBN13ToISBN10($testISBN);
    echo "<p>ISBN-10: $isbn10</p>";
    
    // Test Amazon enrichment data
    $amazonData = getAmazonEnrichmentData($isbn10);
    
    echo "<h3>Amazon Data Result:</h3>";
    echo "<pre>" . print_r($amazonData, true) . "</pre>";
    
    // Test specific fields
    if (!empty($amazonData['buying_options'])) {
        echo "<h3>Buying Options Found:</h3>";
        foreach ($amazonData['buying_options'] as $format => $data) {
            echo "<p><strong>$format:</strong> {$data['price']} " . ($data['is_selected'] ? '(Default)' : '') . "</p>";
        }
    }
    
    if (!empty($amazonData['selected_format'])) {
        echo "<p><strong>Selected Format:</strong> {$amazonData['selected_format']}</p>";
    }
    
    if (!empty($amazonData['selected_price'])) {
        echo "<p><strong>Selected Price:</strong> {$amazonData['selected_price']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<h2>Direct Amazon Scraping Test</h2>
<?php
try {
    echo "<p>Testing direct Amazon scraping...</p>";
    $buyingOptions = scrapeAmazonBuyingOptions($isbn10);
    echo "<h3>Direct Scraping Result:</h3>";
    echo "<pre>" . print_r($buyingOptions, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Direct scraping error: " . $e->getMessage() . "</p>";
}
?>
