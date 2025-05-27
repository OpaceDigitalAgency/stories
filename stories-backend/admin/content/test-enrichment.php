<?php
/**
 * Test script to debug enrichment data flow
 */

require_once '../../../includes/db-connect.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Test with Coraline data
$title = "Coraline";
$author = "Neil Gaiman";
$currentISBN = "9780380977789";

echo "<h1>Testing Enrichment for: $title by $author (ISBN: $currentISBN)</h1>\n";

// Get enriched data
$enrichedData = getEnrichedBookData($title, $author, $currentISBN);

echo "<h2>Enriched Data Result:</h2>\n";
echo "<pre>" . json_encode($enrichedData, JSON_PRETTY_PRINT) . "</pre>\n";

// Check specific fields
if (isset($enrichedData['fields'])) {
    echo "<h2>Field Analysis:</h2>\n";
    
    $fieldsToCheck = ['tags', 'price_range', 'age_range', 'settings', 'maturity_rating'];
    
    foreach ($fieldsToCheck as $field) {
        if (isset($enrichedData['fields'][$field])) {
            echo "<h3>$field:</h3>\n";
            echo "<pre>" . json_encode($enrichedData['fields'][$field], JSON_PRETTY_PRINT) . "</pre>\n";
        } else {
            echo "<h3>$field: NOT FOUND</h3>\n";
        }
    }
}

echo "<h2>Error Log (last 50 lines):</h2>\n";
echo "<pre>";
$logFile = '/var/log/apache2/error.log'; // Adjust path as needed
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    foreach ($lastLines as $line) {
        if (strpos($line, 'enrichment') !== false || strpos($line, 'Field') !== false) {
            echo htmlspecialchars($line);
        }
    }
} else {
    echo "Log file not found at $logFile\n";
}
echo "</pre>";
?>
