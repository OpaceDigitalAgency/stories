<?php
/**
 * Test script to verify OpenLibrary ISBN matching fix
 */

// Set content type for web display
header('Content-Type: text/html; charset=utf-8');

// Include database connection
require_once '../../../includes/db-connect.php';
require_once 'book-import-validate/functions/open-library-validation-functions.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Test the specific ISBN that was failing
$testISBN = "9780380977789";
$title = "Coraline";
$author = "Neil Gaiman";

echo "<h1>Testing OpenLibrary ISBN Matching Fix</h1>\n";
echo "<p><strong>Test ISBN:</strong> $testISBN (Coraline by Neil Gaiman)</p>\n";

echo "<h2>1. Direct OpenLibrary API Test</h2>\n";
echo "<p>Testing fetchOpenLibraryDataNew with isForEnrichment=true...</p>\n";

$startTime = microtime(true);
$result = fetchOpenLibraryDataNew($testISBN, $title, $author, true);
$endTime = microtime(true);

echo "<p><strong>Processing time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>\n";

if ($result && isset($result['_status'])) {
    $status = $result['_status'];
    echo "<p><strong>Status:</strong> " . $status['status'] . "</p>\n";
    echo "<p><strong>Message:</strong> " . $status['message'] . "</p>\n";

    if (!empty($status['steps'])) {
        echo "<h3>Processing Steps:</h3>\n";
        echo "<ul>\n";
        foreach ($status['steps'] as $step) {
            $stepStatus = $step['status'] ?? 'unknown';
            $stepMessage = $step['message'] ?? 'No message';
            echo "<li><strong>{$step['name']}:</strong> [$stepStatus] $stepMessage</li>\n";
        }
        echo "</ul>\n";
    }

    if ($status['status'] === 'success') {
        echo "<h3>Retrieved Data:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Title:</strong> " . ($result['title'] ?? 'N/A') . "</li>\n";
        echo "<li><strong>Author:</strong> " . (isset($result['author_name']) ? implode(', ', $result['author_name']) : 'N/A') . "</li>\n";
        echo "<li><strong>ISBNs:</strong> " . (isset($result['isbn']) ? implode(', ', $result['isbn']) : 'N/A') . "</li>\n";
        echo "<li><strong>Publisher:</strong> " . (isset($result['publisher']) ? implode(', ', $result['publisher']) : 'N/A') . "</li>\n";
        echo "<li><strong>Publish Date:</strong> " . (isset($result['publish_date']) ? implode(', ', $result['publish_date']) : 'N/A') . "</li>\n";
        echo "<li><strong>Subjects:</strong> " . (isset($result['subject']) ? implode(', ', array_slice($result['subject'], 0, 5)) : 'N/A') . "</li>\n";
        echo "<li><strong>Subject Facet:</strong> " . (isset($result['subject_facet']) ? implode(', ', array_slice($result['subject_facet'], 0, 5)) : 'N/A') . "</li>\n";
        echo "</ul>\n";
    }
} else {
    echo "<p><strong>Error:</strong> No result returned or missing status</p>\n";
}

echo "<h2>2. Data Enrichment Test</h2>\n";
echo "<p>Testing full data enrichment process...</p>\n";

// Create a mock book record
$mockBook = [
    'id' => 999,
    'title' => $title,
    'author' => $author,
    'isbn13' => $testISBN,
    'publisher' => '',
    'publication_date' => '',
    'page_count' => null,
    'language' => '',
    'format' => '',
    'cover_url' => '',
    'preview_link' => '',
    'summary' => '',
    'series' => '',
    'awards' => '',
    'characters' => '',
    'settings' => ''
];

$enrichmentResult = enrichBookData($mockBook, $db);

echo "<p><strong>Enrichment Status:</strong> " . ($enrichmentResult['success'] ? 'Success' : 'Failed') . "</p>\n";

if ($enrichmentResult['success']) {
    echo "<h3>Available Fields for Enrichment:</h3>\n";
    echo "<ul>\n";
    foreach ($enrichmentResult['fields'] as $fieldName => $fieldData) {
        if (!empty($fieldData['options'])) {
            echo "<li><strong>$fieldName:</strong> " . count($fieldData['options']) . " options available</li>\n";
            foreach ($fieldData['options'] as $option) {
                $source = $option['source'] ?? 'unknown';
                $value = is_array($option['value']) ? implode(', ', array_slice($option['value'], 0, 3)) : $option['value'];
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                echo "<ul><li>[$source] $value</li></ul>\n";
            }
        }
    }
    echo "</ul>\n";

    echo "<p><strong>ISBN Validation:</strong> " . $enrichmentResult['isbn_validated'] . "</p>\n";
    echo "<p><strong>Sources Checked:</strong> " . implode(', ', $enrichmentResult['sources_checked']) . "</p>\n";
} else {
    echo "<p><strong>Error:</strong> " . ($enrichmentResult['error'] ?? 'Unknown error') . "</p>\n";
}

echo "<h2>3. Raw OpenLibrary API Response</h2>\n";
echo "<p>Direct API call to verify data structure...</p>\n";

$apiUrl = "https://openlibrary.org/search.json?q=isbn:" . urlencode($testISBN) . "&fields=*,availability&limit=1";
echo "<p><strong>API URL:</strong> <a href='$apiUrl' target='_blank'>$apiUrl</a></p>\n";

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>\n";

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "<p><strong>Number of docs:</strong> " . count($data['docs'] ?? []) . "</p>\n";
        echo "<p><strong>Query:</strong> " . ($data['q'] ?? 'N/A') . "</p>\n";
        echo "<p><strong>Num Found:</strong> " . ($data['numFound'] ?? 'N/A') . "</p>\n";

        if (!empty($data['docs'][0])) {
            $firstDoc = $data['docs'][0];
            echo "<h4>First Document ISBNs:</h4>\n";
            echo "<pre>" . print_r($firstDoc['isbn'] ?? 'No ISBNs', true) . "</pre>\n";
        }
    } else {
        echo "<p><strong>Error:</strong> Could not parse JSON response</p>\n";
    }
} else {
    echo "<p><strong>Error:</strong> API request failed with code $httpCode</p>\n";
}
