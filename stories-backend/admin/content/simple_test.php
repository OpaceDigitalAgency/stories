<?php
// Simple test to debug the OpenLibrary issue

echo "Testing OpenLibrary API manually...\n\n";

// Test 1: Basic search
$searchUrl = "https://openlibrary.org/search.json?title=" . urlencode("The Peppers and the International Magic Guys") . "&limit=5";
echo "Search URL: $searchUrl\n";

$ch = curl_init($searchUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "Search Results:\n";
    print_r($data);
    
    if (!empty($data['docs'])) {
        foreach ($data['docs'] as $doc) {
            $workKey = $doc['key'] ?? '';
            echo "\nWork Key: $workKey\n";
            
            if (!empty($workKey)) {
                // Test editions API
                $editionsUrl = "https://openlibrary.org" . $workKey . "/editions.json";
                echo "Editions URL: $editionsUrl\n";
                
                $editionsCh = curl_init($editionsUrl);
                curl_setopt($editionsCh, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($editionsCh, CURLOPT_TIMEOUT, 5);
                curl_setopt($editionsCh, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $editionsResponse = curl_exec($editionsCh);
                $editionsHttpCode = curl_getinfo($editionsCh, CURLINFO_HTTP_CODE);
                curl_close($editionsCh);
                
                echo "Editions HTTP Code: $editionsHttpCode\n";
                
                if ($editionsHttpCode === 200) {
                    $editionsData = json_decode($editionsResponse, true);
                    echo "Editions Data:\n";
                    print_r($editionsData);
                } else {
                    echo "Failed to get editions\n";
                }
            }
        }
    }
} else {
    echo "Failed to get search results\n";
}
?>
