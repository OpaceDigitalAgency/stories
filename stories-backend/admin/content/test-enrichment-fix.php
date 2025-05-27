<?php
/**
 * Test the OpenLibrary API fix for enrichment
 */

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing OpenLibrary API Fix for Data Enrichment</h1>";
echo "<h2>Test Case: Coraline by Neil Gaiman (ISBN: 9780380977789)</h2>";

// Include the necessary files
$functionFile = 'book-import-validate/functions/data-enrichment-functions.php';
$openLibraryFile = 'book-import-validate/functions/open-library-validation-functions.php';

echo "<h3>File Checks:</h3>";
if (file_exists($functionFile)) {
    echo "<p>✅ Data enrichment functions file exists</p>";
    require_once $functionFile;
} else {
    echo "<p>❌ Data enrichment functions file NOT found: $functionFile</p>";
    exit;
}

if (file_exists($openLibraryFile)) {
    echo "<p>✅ OpenLibrary validation functions file exists</p>";
    require_once $openLibraryFile;
} else {
    echo "<p>❌ OpenLibrary validation functions file NOT found: $openLibraryFile</p>";
    exit;
}

// Test data
$isbn = '9780380977789';
$title = 'Coraline';
$author = 'Neil Gaiman';

echo "<h3>Testing fetchOpenLibraryDataNew() directly:</h3>";
echo "<p><strong>ISBN:</strong> $isbn</p>";
echo "<p><strong>Title:</strong> $title</p>";
echo "<p><strong>Author:</strong> $author</p>";

$startTime = microtime(true);
$openLibraryResult = fetchOpenLibraryDataNew($isbn, $title, $author, true);
$endTime = microtime(true);

echo "<p><strong>Processing Time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>";

if (!empty($openLibraryResult)) {
    echo "<h4>✅ OpenLibrary API returned data</h4>";
    
    // Check for key rich metadata fields
    $richFields = [
        'subject_facet' => 'Genres/Tags',
        'place' => 'Settings',
        'person' => 'Characters', 
        'ratings_average' => 'Average Rating',
        'lexile' => 'Reading Level'
    ];
    
    echo "<h4>Rich Metadata Check:</h4>";
    echo "<ul>";
    foreach ($richFields as $field => $label) {
        if (isset($openLibraryResult[$field]) && !empty($openLibraryResult[$field])) {
            $value = is_array($openLibraryResult[$field]) ? 
                implode(', ', array_slice($openLibraryResult[$field], 0, 3)) . (count($openLibraryResult[$field]) > 3 ? '...' : '') : 
                $openLibraryResult[$field];
            echo "<li><strong>✅ $label:</strong> $value</li>";
        } else {
            echo "<li><strong>❌ $label:</strong> Missing or empty</li>";
        }
    }
    echo "</ul>";
    
    // Show status if available
    if (isset($openLibraryResult['_status'])) {
        echo "<h4>API Status:</h4>";
        echo "<p><strong>Status:</strong> " . $openLibraryResult['_status']['status'] . "</p>";
        echo "<p><strong>Message:</strong> " . $openLibraryResult['_status']['message'] . "</p>";
    }
} else {
    echo "<h4>❌ OpenLibrary API returned no data</h4>";
}

echo "<h3>Testing getEnrichedBookData() (full pipeline):</h3>";

if (function_exists('getEnrichedBookData')) {
    $startTime = microtime(true);
    $enrichedResult = getEnrichedBookData($title, $author, $isbn);
    $endTime = microtime(true);
    
    echo "<p><strong>Processing Time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>";
    
    if (!empty($enrichedResult) && !empty($enrichedResult['fields'])) {
        echo "<h4>✅ Enrichment pipeline returned data</h4>";
        echo "<p><strong>Confidence Score:</strong> " . $enrichedResult['confidence_score'] . "</p>";
        echo "<p><strong>ISBN Validated:</strong> " . $enrichedResult['isbn_validated'] . "</p>";
        echo "<p><strong>Sources Checked:</strong> " . implode(', ', $enrichedResult['sources_checked']) . "</p>";
        
        echo "<h4>Enriched Fields:</h4>";
        echo "<ul>";
        
        // Check for the specific fields that should show rich data instead of "Unknown"
        $expectedFields = [
            'tags' => 'Genres/Tags',
            'settings' => 'Settings',
            'characters' => 'Characters',
            'awards' => 'Awards',
            'average_rating' => 'Average Rating',
            'reading_level' => 'Reading Level'
        ];
        
        foreach ($expectedFields as $field => $label) {
            if (isset($enrichedResult['fields'][$field])) {
                $fieldData = $enrichedResult['fields'][$field];
                $value = $fieldData['value'] ?? 'No value';
                $source = $fieldData['source'] ?? 'Unknown source';
                
                if ($value !== 'Unknown' && !empty($value)) {
                    echo "<li><strong>✅ $label:</strong> $value <em>(from $source)</em></li>";
                } else {
                    echo "<li><strong>❌ $label:</strong> $value <em>(from $source)</em></li>";
                }
            } else {
                echo "<li><strong>❌ $label:</strong> Field not found in results</li>";
            }
        }
        echo "</ul>";
        
        // Show all fields for debugging
        echo "<h4>All Enriched Fields (Debug):</h4>";
        echo "<pre>" . print_r($enrichedResult['fields'], true) . "</pre>";
        
    } else {
        echo "<h4>❌ Enrichment pipeline returned no field data</h4>";
        echo "<pre>" . print_r($enrichedResult, true) . "</pre>";
    }
} else {
    echo "<p>❌ getEnrichedBookData function not found</p>";
}

echo "<h3>Direct API Test:</h3>";
$testUrl = "https://openlibrary.org/search.json?q=9780380977789&fields=*,availability&limit=1";
echo "<p><strong>Testing URL:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>";

$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Stories Test)');
$directResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($directResponse && $httpCode === 200) {
    $directData = json_decode($directResponse, true);
    if (!empty($directData['docs'][0])) {
        echo "<p><strong>✅ Direct API call successful</strong></p>";
        echo "<p><strong>Number of docs:</strong> " . count($directData['docs']) . "</p>";
        
        $doc = $directData['docs'][0];
        echo "<p><strong>Key fields in direct response:</strong></p>";
        echo "<ul>";
        foreach ($richFields as $field => $label) {
            if (isset($doc[$field])) {
                $value = is_array($doc[$field]) ? count($doc[$field]) . ' items: ' . implode(', ', array_slice($doc[$field], 0, 2)) . '...' : $doc[$field];
                echo "<li><strong>$label ($field):</strong> $value</li>";
            } else {
                echo "<li><strong>$label ($field):</strong> Not found</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p><strong>❌ No docs found in direct API response</strong></p>";
    }
} else {
    echo "<p><strong>❌ Direct API call failed (HTTP $httpCode)</strong></p>";
}
?>