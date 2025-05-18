<?php
/**
 * Test Direct VPS Connection
 *
 * This script tests a direct connection to the VPS scraper API.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML for better readability
header('Content-Type: text/html');

// Get parameters
$isbn = isset($_GET['isbn']) ? $_GET['isbn'] : '9780007416851';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$source = isset($_GET['source']) ? $_GET['source'] : 'goodreads';

// VPS server details
$vpsIp = '37.27.31.107';
$vpsPort = 3000;
$apiKey = 'stories-scraper-api-key-2023'; // API key from GoodreadsReviewFetcher.php

// Start timing
$startTime = microtime(true);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Direct VPS Scraper Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .container { max-width: 800px; margin: 0 auto; }
        .info { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .review { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .review-header { font-weight: bold; }
        .review-text { margin-top: 5px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Direct VPS Scraper Test</h1>

        <div class='info'>
            <h2>Test Parameters</h2>
            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($isbn); ?></p>
            <p><strong>Limit:</strong> <?php echo $limit; ?></p>
            <p><strong>Source:</strong> <?php echo htmlspecialchars($source); ?></p>
            <p><strong>VPS Server:</strong> <?php echo $vpsIp . ':' . $vpsPort; ?></p>
        </div>";

// Test server health
echo "<h2>Server Health Check</h2>";
$healthUrl = "http://{$vpsIp}:{$vpsPort}/health";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $healthUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "<p class='success'>✅ Server is healthy! Response: {$response}</p>";
} else {
    echo "<p class='error'>❌ Server health check failed. HTTP Status Code: {$httpCode}</p>";
    if (curl_errno($ch)) {
        echo "<p class='error'>Curl Error: " . curl_error($ch) . "</p>";
    }
    echo "<p>Please check if the server is running and accessible.</p>";
}

// Test scraper endpoint
echo "<h2>Scraper Test</h2>";

// Build the URL with query parameters
if ($source === 'goodreads') {
    // For Goodreads, we need to get the Goodreads URL from the ISBN
    $goodreadsUrl = "https://www.goodreads.com/book/isbn/{$isbn}";
    $scraperUrl = "http://{$vpsIp}:{$vpsPort}/scrape/{$source}?url=" . urlencode($goodreadsUrl) . "&limit={$limit}";
} else if ($source === 'amazon') {
    // For Amazon, we use the ASIN directly
    $scraperUrl = "http://{$vpsIp}:{$vpsPort}/scrape/{$source}?asin={$isbn}&limit={$limit}";
} else {
    $scraperUrl = "http://{$vpsIp}:{$vpsPort}/scrape/{$source}?isbn={$isbn}&limit={$limit}";
}

echo "<p><strong>Request URL:</strong> " . htmlspecialchars($scraperUrl) . "</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $scraperUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "x-api-key: {$apiKey}"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

if ($httpCode === 200) {
    $data = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($data['reviews'])) {
        $reviewCount = count($data['reviews']);

        echo "<p class='success'>✅ Successfully retrieved {$reviewCount} reviews in {$duration} seconds!</p>";

        echo "<h3>Reviews:</h3>";
        foreach ($data['reviews'] as $index => $review) {
            echo "<div class='review'>";
            echo "<div class='review-header'>" . ($index + 1) . ". " . htmlspecialchars($review['reviewer_name']) . " (" . $review['rating'] . "/5)</div>";
            echo "<div class='review-text'>" . htmlspecialchars(substr($review['review_text'], 0, 200)) . (strlen($review['review_text']) > 200 ? '...' : '') . "</div>";
            echo "</div>";
        }

        echo "<h3>Raw Response:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<p class='error'>❌ Invalid JSON response or missing reviews.</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<p class='error'>❌ Scraper request failed. HTTP Status Code: {$httpCode}</p>";
    if (curl_errno($ch)) {
        echo "<p class='error'>Curl Error: " . curl_error($ch) . "</p>";
    }
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}
curl_close($ch);

echo "    </div>
</body>
</html>";
