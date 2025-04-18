<?php
/**
 * Test Stories API Endpoint
 * 
 * This script tests the Stories API endpoint to help debug issues.
 */

// Include required files
require_once __DIR__ . '/admin/includes/config.php';
require_once __DIR__ . '/admin/includes/ApiClient.php';

// Initialize API client
$apiClient = new ApiClient(API_URL);

// Test endpoints
$endpoints = [
    'stories' => [1, 'the-magic-forest'],
    'tags' => [1],
    'authors' => [1],
    'blog-posts' => [1]
];

echo "Testing API Endpoints...\n";

foreach ($endpoints as $endpoint => $ids) {
    foreach ($ids as $id) {
        $url = API_URL . '/' . $endpoint . '/' . $id;
        echo "Testing: $url\n";
        
        // Make the request
        $response = $apiClient->get($endpoint . '/' . $id);
        
        // Check the response
        if ($response) {
            echo "Status: 200\n";
            echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        } else {
            $error = $apiClient->getLastError();
            echo "Status: " . ($error['code'] ?? 'Unknown') . "\n";
            echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
            if (isset($error['detail'])) {
                echo "Detail: " . $error['detail'] . "\n";
            }
        }
        
        echo "-----------------------------------\n";
    }
}

echo "Testing complete.\n";
