<?php
/**
 * Debug Price Range Scraping Issue
 */

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Price Range Scraping Debug Test</h1>";
echo "<h2>Test Case: Coraline by Neil Gaiman (ISBN: 9780380977789)</h2>";

// Include the necessary files
$functionFile = '../stories-backend/admin/content/book-import-validate/functions/data-enrichment-functions.php';

if (file_exists($functionFile)) {
    echo "<p>✅ Data enrichment functions file exists</p>";
    require_once $functionFile;
} else {
    echo "<p>❌ Data enrichment functions file NOT found: $functionFile</p>";
    exit;
}

$isbn = '9780380977789';

echo "<h3>1. Testing scrapePriceFromAmazon() Function Directly:</h3>";

if (function_exists('scrapePriceFromAmazon')) {
    echo "<p>✅ scrapePriceFromAmazon function exists</p>";
    
    echo "<p><strong>Testing with ISBN:</strong> $isbn</p>";
    
    $startTime = microtime(true);
    $priceResult = scrapePriceFromAmazon($isbn);
    $endTime = microtime(true);
    
    echo "<p><strong>Processing Time:</strong> " . round($endTime - $startTime, 2) . " seconds</p>";
    echo "<p><strong>Result:</strong> " . ($priceResult ?? 'NULL') . "</p>";
    
    if ($priceResult) {
        echo "<p><strong>✅ Price scraping successful:</strong> $priceResult</p>";
    } else {
        echo "<p><strong>❌ Price scraping failed</strong></p>";
    }
} else {
    echo "<p>❌ scrapePriceFromAmazon function not found</p>";
}

echo "<h3>2. Testing Google Search URL Manually:</h3>";
$query = "amazon.co.uk " . $isbn;
$searchUrl = "https://www.google.com/search?q=" . urlencode($query);
echo "<p><strong>Search Query:</strong> $query</p>";
echo "<p><strong>Search URL:</strong> <a href='$searchUrl' target='_blank'>$searchUrl</a></p>";

// Test the actual HTTP request
echo "<h4>Testing HTTP Request:</h4>";
$ch = curl_init($searchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
    'Accept-Language: en-GB,en;q=0.5',
    'Accept-Encoding: gzip, deflate',
    'Connection: keep-alive',
    'Cache-Control: no-cache',
    'Pragma: no-cache'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
if ($curlError) {
    echo "<p><strong>cURL Error:</strong> $curlError</p>";
}

if ($response && $httpCode === 200) {
    echo "<p><strong>✅ HTTP request successful</strong></p>";
    echo "<p><strong>Response Length:</strong> " . strlen($response) . " characters</p>";
    
    // Check for common blocking indicators
    if (strpos($response, 'captcha') !== false || strpos($response, 'unusual traffic') !== false) {
        echo "<p><strong>❌ Google is blocking requests (CAPTCHA detected)</strong></p>";
    } else {
        echo "<p><strong>✅ No obvious blocking detected</strong></p>";
    }
    
    // Test the price patterns
    echo "<h4>Testing Price Patterns:</h4>";
    $patterns = [
        '/£(\d+)\.(\d{2})\s*·\s*(in stock|available)/i' => 'In stock pattern',
        '/£(\d+)\.(\d{2})\s*·\s*\d+\.\d+\(\d+\)/i' => 'Rating pattern',
        '/£(\d+)\.(\d{2})\s*·.*?(delivery|returns)/i' => 'Delivery pattern',
        '/£(\d+)\.(\d{2})\s*(?:·|,|\s).*?amazon\.co\.uk/i' => 'Amazon pattern',
        '/amazon\.co\.uk.*?£(\d+)\.(\d{2})/i' => 'Fallback pattern 1',
        '/£(\d+)\.(\d{2}).*?amazon\.co\.uk/i' => 'Fallback pattern 2'
    ];
    
    $foundPrice = false;
    foreach ($patterns as $pattern => $description) {
        if (preg_match($pattern, $response, $matches)) {
            $price = floatval($matches[1] . '.' . $matches[2]);
            echo "<p><strong>✅ $description matched:</strong> £$price</p>";
            $foundPrice = true;
            break;
        } else {
            echo "<p><strong>❌ $description:</strong> No match</p>";
        }
    }
    
    if (!$foundPrice) {
        echo "<p><strong>❌ No price patterns matched</strong></p>";
        
        // Look for any £ symbols
        $poundMatches = [];
        preg_match_all('/£\d+\.?\d*/i', $response, $poundMatches);
        if (!empty($poundMatches[0])) {
            echo "<p><strong>Found £ symbols:</strong> " . implode(', ', array_slice($poundMatches[0], 0, 5)) . "</p>";
        } else {
            echo "<p><strong>❌ No £ symbols found in response</strong></p>";
        }
        
        // Look for amazon.co.uk mentions
        if (strpos($response, 'amazon.co.uk') !== false) {
            echo "<p><strong>✅ amazon.co.uk found in response</strong></p>";
        } else {
            echo "<p><strong>❌ amazon.co.uk NOT found in response</strong></p>";
        }
    }
    
    // Show a snippet of the response for debugging
    echo "<h4>Response Sample (first 500 chars):</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 200px; overflow-y: auto;'>";
    echo htmlspecialchars(substr($response, 0, 500));
    echo "</pre>";
    
} else {
    echo "<p><strong>❌ HTTP request failed (Code: $httpCode)</strong></p>";
}

echo "<h3>3. Testing Data Structure Issue:</h3>";

// Test with mock data structures to see if ISBN extraction works
$mockGoogleData = [
    'isbn' => $isbn,
    'isbn13' => $isbn,
    'industryIdentifiers' => [
        ['type' => 'ISBN_13', 'identifier' => $isbn]
    ]
];

$mockOpenLibraryData = [
    'isbn' => [$isbn],
    'title' => 'Coraline'
];

echo "<h4>Testing extractFieldValue with Mock Data:</h4>";

if (function_exists('extractFieldValue')) {
    echo "<p><strong>Testing with Google Books structure:</strong></p>";
    $googlePrice = extractFieldValue($mockGoogleData, 'price_range');
    echo "<p>Result: " . ($googlePrice ?? 'NULL') . "</p>";
    
    echo "<p><strong>Testing with OpenLibrary structure:</strong></p>";
    $olPrice = extractFieldValue($mockOpenLibraryData, 'price_range');
    echo "<p>Result: " . ($olPrice ?? 'NULL') . "</p>";
} else {
    echo "<p>❌ extractFieldValue function not found</p>";
}

echo "<h3>Alternative Price Sources:</h3>";
echo "<p><strong>Recommendations:</strong></p>";
echo "<ul>";
echo "<li>Consider using Amazon Product Advertising API (requires approval)</li>";
echo "<li>Try scraping Amazon directly instead of Google search results</li>";
echo "<li>Use alternative price comparison APIs (PriceAPI, RapidAPI)</li>";
echo "<li>Implement rotating user agents and proxy support</li>";
echo "<li>Add delay between requests to avoid rate limiting</li>";
echo "</ul>";
?>