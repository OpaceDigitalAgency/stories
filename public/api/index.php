<?php
/**
 * Fallback API Handler
 * 
 * This file serves as a fallback for the Netlify functions API.
 * It returns static JSON files based on the requested endpoint.
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle OPTIONS request (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove '/api' prefix if present
$path = str_replace('/api', '', $path);

// Determine which JSON file to serve
if ($path === '/stories' || $path === '/stories/') {
    $jsonFile = __DIR__ . '/stories.json';
} elseif ($path === '/authors' || $path === '/authors/') {
    $jsonFile = __DIR__ . '/authors.json';
} elseif ($path === '/tags' || $path === '/tags/') {
    $jsonFile = __DIR__ . '/tags.json';
} else {
    // Default to 404 for unhandled endpoints
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}

// Check if the JSON file exists
if (file_exists($jsonFile)) {
    // Read and output the JSON file
    $jsonContent = file_get_contents($jsonFile);
    echo $jsonContent;
} else {
    // Return error if file doesn't exist
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => 'JSON file not found'
    ]);
}
