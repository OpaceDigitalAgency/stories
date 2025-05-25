<?php
/**
 * Test the AJAX enrichment endpoint directly
 */

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

echo "<h1>Testing AJAX Enrichment Endpoint</h1>";

// Test the AJAX endpoint directly
$testData = [
    'action' => 'test'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.storiesfromtheweb.org/admin/content/book-import-validate/ajax/data-enrichment-ajax.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, ''); // Enable cookie handling
curl_setopt($ch, CURLOPT_COOKIEJAR, ''); // Enable cookie handling

// Copy session cookies if available
if (isset($_COOKIE)) {
    $cookies = [];
    foreach ($_COOKIE as $name => $value) {
        $cookies[] = $name . '=' . $value;
    }
    if (!empty($cookies)) {
        curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $cookies));
    }
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>Test Results:</h2>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";

if ($error) {
    echo "<p><strong>CURL Error:</strong> $error</p>";
}

echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Also test if the function file exists
echo "<h2>Function File Check:</h2>";
$functionFile = 'book-import-validate/functions/data-enrichment-functions.php';
if (file_exists($functionFile)) {
    echo "<p>✅ Function file exists: $functionFile</p>";
    
    // Try to include it
    try {
        require_once $functionFile;
        echo "<p>✅ Function file included successfully</p>";
        
        if (function_exists('validateISBNOnGoodreads')) {
            echo "<p>✅ validateISBNOnGoodreads function exists</p>";
        } else {
            echo "<p>❌ validateISBNOnGoodreads function NOT found</p>";
        }
        
        if (function_exists('getEnrichedBookData')) {
            echo "<p>✅ getEnrichedBookData function exists</p>";
        } else {
            echo "<p>❌ getEnrichedBookData function NOT found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error including function file: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Function file NOT found: $functionFile</p>";
}
?>
