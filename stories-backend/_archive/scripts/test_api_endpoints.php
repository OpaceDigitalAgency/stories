<?php
/**
 * Test API Endpoints
 * 
 * This script tests the API endpoints to help debug issues.
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

echo "Testing API Endpoints...\n\n";

foreach ($endpoints as $endpoint => $ids) {
    echo "Testing $endpoint endpoint:\n";
    echo "-------------------------\n";
    
    // Test list endpoint
    echo "GET /$endpoint\n";
    $response = $apiClient->get($endpoint);
    if ($response) {
        echo "Status: 200 OK\n";
        echo "Response contains " . count($response['data']) . " items\n";
    } else {
        $error = $apiClient->getLastError();
        echo "Status: " . ($error['code'] ?? 'Unknown') . "\n";
        echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
    
    // Test individual items
    foreach ($ids as $id) {
        echo "GET /$endpoint/$id\n";
        $response = $apiClient->get("$endpoint/$id");
        if ($response) {
            echo "Status: 200 OK\n";
            if (isset($response['data']['id'])) {
                echo "Item ID: " . $response['data']['id'] . "\n";
            }
            if (isset($response['data']['attributes'])) {
                $attributes = array_keys($response['data']['attributes']);
                echo "Attributes: " . implode(', ', $attributes) . "\n";
            }
        } else {
            $error = $apiClient->getLastError();
            echo "Status: " . ($error['code'] ?? 'Unknown') . "\n";
            echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }
    
    echo "\n";
}

echo "Testing complete.\n";
