<?php
/**
 * Directory Items and AI Tools Endpoint Test
 * 
 * This script tests the directory-items and ai-tools endpoints to ensure they return
 * data in the expected format.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base URL for API
$baseUrl = 'http://localhost:8000/api/v1'; // Adjust as needed for your environment

// Test directory-items endpoint
echo "Testing directory-items endpoint...\n";
$directoryItemsUrl = $baseUrl . '/directory-items?populate=*';
$directoryItemsResponse = file_get_contents($directoryItemsUrl);

if ($directoryItemsResponse === false) {
    echo "Error: Failed to fetch directory items\n";
    exit(1);
}

$directoryItems = json_decode($directoryItemsResponse, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Failed to parse directory items JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Verify directory items structure
if (!is_array($directoryItems)) {
    echo "Error: Directory items response is not an array\n";
    exit(1);
}

if (count($directoryItems) === 0) {
    echo "Warning: No directory items found\n";
} else {
    $item = $directoryItems[0];
    
    // Check required fields
    $requiredFields = ['id', 'name', 'description', 'url', 'category', 'logo'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($item[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (count($missingFields) > 0) {
        echo "Error: Directory item missing required fields: " . implode(', ', $missingFields) . "\n";
        exit(1);
    }
    
    echo "Directory items endpoint test passed!\n";
    echo "Sample directory item:\n";
    print_r($item);
}

// Test ai-tools endpoint
echo "\nTesting ai-tools endpoint...\n";
$aiToolsUrl = $baseUrl . '/ai-tools?populate=*';
$aiToolsResponse = file_get_contents($aiToolsUrl);

if ($aiToolsResponse === false) {
    echo "Error: Failed to fetch AI tools\n";
    exit(1);
}

$aiTools = json_decode($aiToolsResponse, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Failed to parse AI tools JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

// Verify AI tools structure
if (!is_array($aiTools)) {
    echo "Error: AI tools response is not an array\n";
    exit(1);
}

if (count($aiTools) === 0) {
    echo "Warning: No AI tools found\n";
} else {
    $tool = $aiTools[0];
    
    // Check required fields
    $requiredFields = ['id', 'name', 'description', 'url', 'category', 'logo'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($tool[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (count($missingFields) > 0) {
        echo "Error: AI tool missing required fields: " . implode(', ', $missingFields) . "\n";
        exit(1);
    }
    
    echo "AI tools endpoint test passed!\n";
    echo "Sample AI tool:\n";
    print_r($tool);
}

echo "\nAll tests passed successfully!\n";