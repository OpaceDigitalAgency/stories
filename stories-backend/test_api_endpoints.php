<?php
/**
 * API Endpoint Tester
 * 
 * This script tests the API endpoints directly to check if they're returning properly formatted JSON responses.
 */

// Configuration
$apiBaseUrl = 'https://api.storiesfromtheweb.org/api/v1'; // Updated to match the production URL
$endpoints = [
    '/tags/1',
    '/authors/1',
    '/blog-posts/1',
    '/stories/1'
];

// Function to make a GET request to an API endpoint
function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'url' => $url,
        'status' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Function to format JSON for display
function formatJson($json) {
    $result = json_decode($json, true);
    if ($result === null) {
        return $json;
    }
    return json_encode($result, JSON_PRETTY_PRINT);
}

// Test each endpoint
echo "Testing API Endpoints...\n\n";

foreach ($endpoints as $endpoint) {
    $url = $apiBaseUrl . $endpoint;
    echo "Testing: $url\n";
    
    $result = makeRequest($url);
    
    echo "Status: {$result['status']}\n";
    
    if ($result['error']) {
        echo "Error: {$result['error']}\n";
    } else {
        echo "Response:\n";
        echo formatJson($result['response']) . "\n";
    }
    
    echo "\n-----------------------------------\n\n";
}

echo "Testing complete.\n";
