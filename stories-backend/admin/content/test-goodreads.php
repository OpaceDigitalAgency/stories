<?php
/**
 * Test script to verify Goodreads validation is working
 */

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/db-connect.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Test if function exists
if (!function_exists('validateISBNOnGoodreads')) {
    die('Error: validateISBNOnGoodreads function not found. Check file path.');
}

// Test ISBNs
$testISBNs = [
    '9780380977789', // Coraline - should be found
    '9781408832110', // Different Coraline edition - should be found
    '9999999999999', // Invalid ISBN - should not be found
    '0380977788',    // Coraline ISBN-10 - should be found
];

echo "<h2>Goodreads Validation Test</h2>";
echo "<p>Testing Goodreads validation function with various ISBNs...</p>";

foreach ($testISBNs as $isbn) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 10px;'>";
    echo "<h4>Testing ISBN: $isbn</h4>";

    $startTime = microtime(true);
    $result = validateISBNOnGoodreads($isbn);
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    $status = $result ? 'FOUND' : 'NOT FOUND';
    $color = $result ? 'green' : 'red';

    echo "<p><strong>Result:</strong> <span style='color: $color;'>$status</span></p>";
    echo "<p><strong>Duration:</strong> {$duration}ms</p>";
    echo "<p><strong>Search URL:</strong> <a href='https://www.goodreads.com/search?q=" . urlencode($isbn) . "' target='_blank'>https://www.goodreads.com/search?q=" . urlencode($isbn) . "</a></p>";
    echo "</div>";
}

echo "<h3>Expected Results:</h3>";
echo "<ul>";
echo "<li><strong>9780380977789</strong> - Should be FOUND (Coraline original ISBN)</li>";
echo "<li><strong>9781408832110</strong> - Should be FOUND (Coraline different edition)</li>";
echo "<li><strong>9999999999999</strong> - Should be NOT FOUND (invalid ISBN)</li>";
echo "<li><strong>0380977788</strong> - Should be FOUND (Coraline ISBN-10)</li>";
echo "</ul>";

echo "<p><em>Note: Check the server error log for detailed debug information.</em></p>";
?>
