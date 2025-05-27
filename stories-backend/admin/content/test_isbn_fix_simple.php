<?php
/**
 * Simple test of the OpenLibrary ISBN fix
 */

// Set content type for web display
header('Content-Type: text/html; charset=utf-8');

// Include required functions
require_once 'book-import-validate/functions/open-library-validation-functions.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

$testISBN = "9780380977789";
$title = "Coraline";
$author = "Neil Gaiman";

echo "<h1>🧪 OpenLibrary ISBN Fix Test</h1>\n";
echo "<p><strong>Testing ISBN:</strong> $testISBN</p>\n";
echo "<p><strong>Book:</strong> $title by $author</p>\n";

echo "<hr>\n";

echo "<h2>1. 🌐 Direct OpenLibrary API Call</h2>\n";
$apiUrl = "https://openlibrary.org/search.json?q=isbn:$testISBN&fields=*,availability&limit=1";
echo "<p><strong>URL:</strong> <a href='$apiUrl' target='_blank'>$apiUrl</a></p>\n";

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
        echo "<p><strong>✅ API Response Successful</strong></p>\n";
        echo "<p><strong>Query:</strong> " . ($data['q'] ?? 'N/A') . "</p>\n";
        echo "<p><strong>Number Found:</strong> " . ($data['numFound'] ?? 'N/A') . "</p>\n";
        echo "<p><strong>Number of Docs:</strong> " . count($data['docs'] ?? []) . "</p>\n";
        
        if (!empty($data['docs'])) {
            echo "<h3>📚 Available Editions:</h3>\n";
            foreach ($data['docs'] as $index => $doc) {
                echo "<div style='background: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 3px solid #007bff;'>\n";
                echo "<strong>Edition " . ($index + 1) . ":</strong><br>\n";
                echo "<strong>Title:</strong> " . ($doc['title'] ?? 'N/A') . "<br>\n";
                echo "<strong>Author:</strong> " . (isset($doc['author_name']) ? implode(', ', $doc['author_name']) : 'N/A') . "<br>\n";
                echo "<strong>ISBNs:</strong> " . (isset($doc['isbn']) ? implode(', ', $doc['isbn']) : 'N/A') . "<br>\n";
                echo "<strong>Publisher:</strong> " . (isset($doc['publisher']) ? implode(', ', $doc['publisher']) : 'N/A') . "<br>\n";
                echo "<strong>Publish Date:</strong> " . (isset($doc['publish_date']) ? implode(', ', $doc['publish_date']) : 'N/A') . "<br>\n";
                
                // Check if our target ISBN is in this edition
                if (isset($doc['isbn']) && in_array($testISBN, $doc['isbn'])) {
                    echo "<span style='background: #d4edda; padding: 2px 5px; border-radius: 3px;'>✅ Contains target ISBN</span><br>\n";
                }
                echo "</div>\n";
            }
        }
    } else {
        echo "<p><strong>❌ Error:</strong> Could not parse JSON response</p>\n";
    }
} else {
    echo "<p><strong>❌ Error:</strong> API request failed with code $httpCode</p>\n";
}

echo "<hr>\n";

echo "<h2>2. 🔧 Our Fixed Function Test</h2>\n";
echo "<p>Testing <code>fetchOpenLibraryDataNew()</code> with <code>isForEnrichment=true</code>...</p>\n";

$startTime = microtime(true);
$result = fetchOpenLibraryDataNew($testISBN, $title, $author, true);
$endTime = microtime(true);

echo "<p><strong>Processing Time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>\n";

if ($result && isset($result['_status'])) {
    $status = $result['_status'];
    echo "<p><strong>Status:</strong> " . $status['status'] . "</p>\n";
    echo "<p><strong>Message:</strong> " . $status['message'] . "</p>\n";
    
    if ($status['status'] === 'success') {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>✅ Success! Data Retrieved:</h3>\n";
        echo "<p><strong>Title:</strong> " . ($result['title'] ?? 'N/A') . "</p>\n";
        echo "<p><strong>Author:</strong> " . (isset($result['author_name']) ? implode(', ', $result['author_name']) : 'N/A') . "</p>\n";
        echo "<p><strong>ISBNs:</strong> " . (isset($result['isbn']) ? implode(', ', $result['isbn']) : 'N/A') . "</p>\n";
        echo "<p><strong>Publisher:</strong> " . (isset($result['publisher']) ? implode(', ', $result['publisher']) : 'N/A') . "</p>\n";
        echo "<p><strong>Publish Date:</strong> " . (isset($result['publish_date']) ? implode(', ', $result['publish_date']) : 'N/A') . "</p>\n";
        echo "<p><strong>Subjects (first 5):</strong> " . (isset($result['subject']) ? implode(', ', array_slice($result['subject'], 0, 5)) : 'N/A') . "</p>\n";
        echo "<p><strong>Subject Facet (first 5):</strong> " . (isset($result['subject_facet']) ? implode(', ', array_slice($result['subject_facet'], 0, 5)) : 'N/A') . "</p>\n";
        
        // Verify our target ISBN is in the result
        if (isset($result['isbn']) && in_array($testISBN, $result['isbn'])) {
            echo "<div style='background: #d1ecf1; padding: 10px; border: 1px solid #bee5eb; border-radius: 3px; margin: 10px 0;'>\n";
            echo "🎯 <strong>Perfect!</strong> Our target ISBN $testISBN is confirmed in the returned data.\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 3px; margin: 10px 0;'>\n";
            echo "⚠️ <strong>Warning:</strong> Target ISBN $testISBN not found in returned ISBNs.\n";
            echo "</div>\n";
        }
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h3>❌ Error:</h3>\n";
        echo "<p>" . $status['message'] . "</p>\n";
        echo "</div>\n";
    }
    
    if (!empty($status['steps'])) {
        echo "<h3>🔍 Processing Steps:</h3>\n";
        echo "<ol>\n";
        foreach ($status['steps'] as $step) {
            $stepStatus = $step['status'] ?? 'unknown';
            $stepMessage = $step['message'] ?? 'No message';
            $statusIcon = $stepStatus === 'success' ? '✅' : ($stepStatus === 'error' ? '❌' : '⏳');
            echo "<li>$statusIcon <strong>{$step['name']}:</strong> [$stepStatus] $stepMessage</li>\n";
        }
        echo "</ol>\n";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h3>❌ Function Error</h3>\n";
    echo "<p>No result returned or missing status information.</p>\n";
    echo "</div>\n";
}

echo "<hr>\n";

echo "<h2>3. 🧪 ISBN Validation Test</h2>\n";
echo "<p>Testing the <code>validateOpenLibraryISBNMatch()</code> function...</p>\n";

if ($result && $result['_status']['status'] === 'success') {
    $isValid = validateOpenLibraryISBNMatch($result, $testISBN);
    if ($isValid) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>\n";
        echo "✅ <strong>ISBN Validation Passed!</strong> The returned data contains our target ISBN.\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>\n";
        echo "❌ <strong>ISBN Validation Failed!</strong> The returned data does not contain our target ISBN.\n";
        echo "</div>\n";
    }
} else {
    echo "<p>⏭️ <strong>Skipped:</strong> No valid data to test validation against.</p>\n";
}

echo "<hr>\n";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
