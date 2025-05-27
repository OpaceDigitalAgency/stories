<?php
// Test script to verify OpenLibrary API fix
require_once __DIR__ . '/book-import-validate/functions/open-library-validation-functions.php';

echo "<h1>Testing OpenLibrary API Fix</h1>\n";
echo "<h2>Test Case: Coraline by Neil Gaiman (ISBN: 9780380977789)</h2>\n";

$isbn = '9780380977789';
$title = 'Coraline';
$author = 'Neil Gaiman';

echo "<p><strong>Testing ISBN:</strong> $isbn</p>\n";
echo "<p><strong>Expected rich metadata:</strong> Genres, Settings, Characters, Awards, Rating, Reading Level</p>\n";

echo "<h3>Calling fetchOpenLibraryDataNew()...</h3>\n";

$result = fetchOpenLibraryDataNew($isbn, $title, $author, true);

echo "<h3>Raw Result:</h3>\n";
echo "<pre>" . print_r($result, true) . "</pre>\n";

if (!empty($result)) {
    echo "<h3>Key Fields Check:</h3>\n";
    echo "<ul>\n";
    
    // Check for rich metadata fields
    $fieldsToCheck = [
        'subject_facet' => 'Genres/Tags',
        'place' => 'Settings', 
        'person' => 'Characters',
        'ratings_average' => 'Average Rating',
        'lexile' => 'Reading Level'
    ];
    
    foreach ($fieldsToCheck as $field => $label) {
        if (isset($result[$field]) && !empty($result[$field])) {
            $value = is_array($result[$field]) ? implode(', ', array_slice($result[$field], 0, 3)) . '...' : $result[$field];
            echo "<li><strong>✅ $label ($field):</strong> $value</li>\n";
        } else {
            echo "<li><strong>❌ $label ($field):</strong> Missing or empty</li>\n";
        }
    }
    
    echo "</ul>\n";
    
    // Check status
    if (isset($result['_status'])) {
        echo "<h3>API Status:</h3>\n";
        echo "<p><strong>Status:</strong> " . $result['_status']['status'] . "</p>\n";
        echo "<p><strong>Message:</strong> " . $result['_status']['message'] . "</p>\n";
        echo "<p><strong>Processing Time:</strong> " . $result['_status']['processing_time'] . "s</p>\n";
    }
} else {
    echo "<p><strong>❌ ERROR:</strong> No data returned from API</p>\n";
}

echo "<h3>Direct API Test:</h3>\n";
$testUrl = "https://openlibrary.org/search.json?q=9780380977789&fields=*,availability&limit=1";
echo "<p><strong>Testing URL:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>\n";

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$directResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>\n";

if ($directResponse && $httpCode === 200) {
    $directData = json_decode($directResponse, true);
    if (!empty($directData['docs'][0])) {
        echo "<p><strong>✅ Direct API call successful</strong></p>\n";
        echo "<p><strong>Number of docs:</strong> " . count($directData['docs']) . "</p>\n";
        
        $doc = $directData['docs'][0];
        echo "<p><strong>Sample fields in response:</strong></p>\n";
        echo "<ul>\n";
        foreach ($fieldsToCheck as $field => $label) {
            if (isset($doc[$field])) {
                $value = is_array($doc[$field]) ? count($doc[$field]) . ' items' : $doc[$field];
                echo "<li><strong>$label ($field):</strong> $value</li>\n";
            }
        }
        echo "</ul>\n";
    } else {
        echo "<p><strong>❌ No docs found in direct API response</strong></p>\n";
    }
} else {
    echo "<p><strong>❌ Direct API call failed</strong></p>\n";
}
?>