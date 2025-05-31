<?php
// Test Amazon scraping for specific ISBN
require_once '../../../config/db-connect.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Test ISBN from the user's example
$testISBN = '1444004786';

echo "<h2>Testing Amazon Scraping for ISBN: $testISBN</h2>";
echo "<p>URL: https://www.amazon.co.uk/dp/$testISBN</p>";

// Enable debug mode
define('AMAZON_DEBUG', true);

echo "<h3>Amazon Scraping Results:</h3>";
$amazonData = scrapeAmazonBuyingOptions($testISBN);

echo "<h3>Raw Amazon Data:</h3>";
echo "<pre>" . htmlspecialchars(json_encode($amazonData, JSON_PRETTY_PRINT)) . "</pre>";

echo "<h3>Amazon Enrichment Data:</h3>";
$enrichmentData = getAmazonEnrichmentData($testISBN);
echo "<pre>" . htmlspecialchars(json_encode($enrichmentData, JSON_PRETTY_PRINT)) . "</pre>";

// Test the specific URL manually
echo "<h3>Manual URL Test:</h3>";
$url = "https://www.amazon.co.uk/dp/$testISBN";
echo "<p>Testing URL: <a href='$url' target='_blank'>$url</a></p>";

// Test with curl
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_ENCODING, '');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $httpCode</p>";
echo "<p><strong>Response Length:</strong> " . strlen($response) . " characters</p>";

// Look for reading age in the response
if ($response) {
    echo "<h3>Searching for Reading Age in Response:</h3>";
    
    // Search for reading age patterns
    $patterns = [
        '/<span[^>]*class="a-text-bold"[^>]*>Reading age[^<]*<\/span>[^<]*<span[^>]*>([^<]+)<\/span>/i',
        '/Reading age[^:]*:\s*([^<\n]+)/i',
        '/Reading age[^>]*>([^<]+)</i',
        '/<span>Reading age<\/span>.*?<span[^>]*>([^<]+)<\/span>/is'
    ];
    
    $found = false;
    foreach ($patterns as $i => $pattern) {
        if (preg_match($pattern, $response, $matches)) {
            echo "<p><strong>Pattern " . ($i + 1) . " found:</strong> " . htmlspecialchars($matches[1]) . "</p>";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "<p><strong>No reading age patterns found in response.</strong></p>";
        
        // Look for any mention of "age" in the response
        if (preg_match_all('/[^>]*age[^<]*/i', $response, $ageMatches)) {
            echo "<h4>All mentions of 'age' in response:</h4>";
            foreach (array_slice($ageMatches[0], 0, 10) as $match) {
                echo "<p>" . htmlspecialchars(trim($match)) . "</p>";
            }
        }
    }
    
    // Check if the page is actually for the book we want
    if (preg_match('/<title>([^<]+)<\/title>/i', $response, $titleMatch)) {
        echo "<p><strong>Page Title:</strong> " . htmlspecialchars($titleMatch[1]) . "</p>";
    }
    
    // Check for "Opal Moonbaby" in the response
    if (stripos($response, 'Opal Moonbaby') !== false) {
        echo "<p><strong>✅ Book title found in response</strong></p>";
    } else {
        echo "<p><strong>❌ Book title NOT found in response</strong></p>";
    }
}
?>
