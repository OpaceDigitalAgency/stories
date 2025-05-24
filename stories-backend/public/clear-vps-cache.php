<?php
/**
 * Clear VPS Cache for Goodreads Reviews
 * 
 * This script forces a cache clear by making a request with force=1
 * to bypass the VPS cache system.
 */

// VPS Configuration
$vpsUrl = 'http://37.27.31.107:3000';
$apiKey = 'stories-scraper-api-key-2023';

// Book URL to clear cache for (Harry Potter)
$bookUrl = 'https://www.goodreads.com/book/show/3.Harry_Potter_and_the_Sorcerer_s_Stone/reviews';

echo "<h1>🗑️ VPS Cache Clear Tool</h1>";
echo "<p>Clearing VPS cache for: <strong>" . htmlspecialchars($bookUrl) . "</strong></p>";

// Build the request URL with force=1 to bypass cache
$requestUrl = $vpsUrl . '/scrape/goodreads?' . http_build_query([
    'url' => $bookUrl,
    'limit' => 1, // Just request 1 review to minimize processing
    'maxPages' => 1,
    'force' => 1, // This is the key - forces cache bypass
    'continueFromLast' => 0
]);

echo "<p>🔗 Request URL: <code>" . htmlspecialchars($requestUrl) . "</code></p>";

// Make the request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $requestUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-key: {$apiKey}"
]);

echo "<p>⏳ Making request to VPS...</p>";
flush();

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p>❌ <strong>CURL Error:</strong> " . htmlspecialchars($error) . "</p>";
} else {
    echo "<p>✅ <strong>HTTP Response Code:</strong> {$httpCode}</p>";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data) {
            echo "<p>✅ <strong>Cache Clear Successful!</strong></p>";
            echo "<p>📊 <strong>Response Summary:</strong></p>";
            echo "<ul>";
            echo "<li>Reviews returned: " . (isset($data['reviews']) ? count($data['reviews']) : 'N/A') . "</li>";
            echo "<li>Has more: " . (isset($data['hasMore']) ? ($data['hasMore'] ? 'Yes' : 'No') : 'N/A') . "</li>";
            echo "<li>Next page token: " . (isset($data['nextPageToken']) ? htmlspecialchars($data['nextPageToken']) : 'None') . "</li>";
            echo "</ul>";
            
            echo "<p>🎉 <strong>The VPS cache has been cleared! You can now try importing reviews again.</strong></p>";
        } else {
            echo "<p>⚠️ <strong>Warning:</strong> Response received but could not parse JSON</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>";
        }
    } else {
        echo "<p>❌ <strong>HTTP Error {$httpCode}:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Go back to your book import page</li>";
echo "<li>Try importing reviews again</li>";
echo "<li>The system should now fetch fresh reviews instead of using cached data</li>";
echo "</ol>";

echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
?>