<?php
/**
 * Debug Amazon HTML Structure
 * 
 * This script fetches the actual Amazon page HTML to see what structure we're dealing with
 */

// Test ISBN (Coraline by Neil Gaiman)
$testISBN10 = '0380977788';

echo "<h2>Debug Amazon HTML Structure</h2>\n";
echo "<p><strong>Test ISBN-10:</strong> $testISBN10</p>\n";
echo "<p><strong>Amazon URL:</strong> https://www.amazon.co.uk/gp/product/$testISBN10</p>\n";

echo "<hr>\n";

// Fetch the Amazon page
$url = "https://www.amazon.co.uk/gp/product/$testISBN10";
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
curl_setopt($ch, CURLOPT_ENCODING, '');
$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>HTTP Response</h3>\n";
echo "<p><strong>HTTP Status Code:</strong> $httpCode</p>\n";
echo "<p><strong>Response Length:</strong> " . strlen($body) . " characters</p>\n";

if ($httpCode === 200 && $body) {
    echo "<p><strong>✅ Successfully fetched Amazon page</strong></p>\n";
    
    // Look for tmm-grid-swatch elements
    echo "<h3>Looking for tmm-grid-swatch Elements</h3>\n";
    
    $formats = ['HARDCOVER', 'PAPERBACK', 'KINDLE', 'AUDIOBOOK'];
    foreach ($formats as $format) {
        if (preg_match('/id="tmm-grid-swatch-' . $format . '"[^>]*>(.*?)<\/div>/is', $body, $matches)) {
            echo "<h4>Found $format swatch:</h4>\n";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>\n";
        } else {
            echo "<p><strong>❌ No $format swatch found</strong></p>\n";
        }
    }
    
    // Look for any tmm-grid-swatch elements
    echo "<h3>All tmm-grid-swatch Elements</h3>\n";
    if (preg_match_all('/id="tmm-grid-swatch-[^"]*"[^>]*>.*?<\/div>/is', $body, $allMatches)) {
        echo "<p><strong>Found " . count($allMatches[0]) . " tmm-grid-swatch elements:</strong></p>\n";
        foreach ($allMatches[0] as $i => $match) {
            echo "<h4>Swatch " . ($i + 1) . ":</h4>\n";
            echo "<pre>" . htmlspecialchars($match) . "</pre>\n";
        }
    } else {
        echo "<p><strong>❌ No tmm-grid-swatch elements found at all</strong></p>\n";
    }
    
    // Look for price information
    echo "<h3>Looking for Price Information</h3>\n";
    if (preg_match_all('/aria-label="£\d+\.\d{2}"/i', $body, $priceMatches)) {
        echo "<p><strong>Found " . count($priceMatches[0]) . " price elements:</strong></p>\n";
        foreach ($priceMatches[0] as $price) {
            echo "<p>$price</p>\n";
        }
    } else {
        echo "<p><strong>❌ No price elements found</strong></p>\n";
    }
    
    // Check if we're getting a captcha or login page
    if (strpos($body, 'captcha') !== false) {
        echo "<p><strong>⚠️ CAPTCHA detected in response</strong></p>\n";
    }
    if (strpos($body, 'sign-in') !== false || strpos($body, 'signin') !== false) {
        echo "<p><strong>⚠️ Sign-in page detected</strong></p>\n";
    }
    
} else {
    echo "<p><strong>❌ Failed to fetch Amazon page</strong></p>\n";
    echo "<p>HTTP Code: $httpCode</p>\n";
}

echo "<hr>\n";
echo "<p><em>Debug completed. Check the results above to see the actual Amazon HTML structure.</em></p>\n";
