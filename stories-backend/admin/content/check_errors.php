<?php
/**
 * Check for PHP errors and test basic functionality
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Error Check and Basic Tests</h1>\n";

// Test if the function files exist
$files = [
    'book-import-validate/functions/open-library-validation-functions.php',
    'book-import-validate/functions/google-books-validation-functions.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ File exists: $file</p>\n";
        try {
            require_once $file;
            echo "<p style='color: green;'>✓ File loaded successfully: $file</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Error loading $file: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ File missing: $file</p>\n";
    }
}

// Test if functions exist
$functions = [
    'searchOpenLibraryByTitleAuthor',
    'searchBooksByTitleAuthor'
];

foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p style='color: green;'>✓ Function exists: $func</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Function missing: $func</p>\n";
    }
}

// Test basic curl functionality
echo "<h2>Testing CURL</h2>\n";
if (function_exists('curl_init')) {
    echo "<p style='color: green;'>✓ CURL is available</p>\n";
    
    // Test a simple request
    $ch = curl_init('https://httpbin.org/get');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "<p style='color: green;'>✓ CURL request successful (HTTP $httpCode)</p>\n";
    } else {
        echo "<p style='color: red;'>✗ CURL request failed (HTTP $httpCode)</p>\n";
    }
} else {
    echo "<p style='color: red;'>✗ CURL is not available</p>\n";
}

// Test OpenLibrary API directly
echo "<h2>Testing OpenLibrary API</h2>\n";
$testUrl = 'https://openlibrary.org/search.json?title=Harry%20Potter&limit=1';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>Test URL: <a href='$testUrl' target='_blank'>$testUrl</a></p>\n";
echo "<p>HTTP Code: $httpCode</p>\n";

if ($httpCode === 200) {
    echo "<p style='color: green;'>✓ OpenLibrary API is accessible</p>\n";
    $data = json_decode($response, true);
    if ($data) {
        echo "<p style='color: green;'>✓ JSON response is valid</p>\n";
        echo "<p>Found " . (isset($data['docs']) ? count($data['docs']) : 0) . " results</p>\n";
    } else {
        echo "<p style='color: red;'>✗ Invalid JSON response</p>\n";
    }
} else {
    echo "<p style='color: red;'>✗ OpenLibrary API is not accessible (HTTP $httpCode)</p>\n";
}

echo "<h2>PHP Configuration</h2>\n";
echo "<p>PHP Version: " . phpversion() . "</p>\n";
echo "<p>User Agent: " . (ini_get('user_agent') ?: 'Not set') . "</p>\n";
echo "<p>Allow URL fopen: " . (ini_get('allow_url_fopen') ? 'Yes' : 'No') . "</p>\n";
echo "<p>Max execution time: " . ini_get('max_execution_time') . " seconds</p>\n";

?>
