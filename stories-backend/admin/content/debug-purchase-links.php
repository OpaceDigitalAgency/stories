<?php
// Debug Purchase Links Comparison

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
echo "<p>Amazon data testing disabled to avoid 500 errors. Focus on purchase links comparison.</p>";
