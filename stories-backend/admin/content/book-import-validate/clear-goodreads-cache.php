<?php
/**
 * Clear Goodreads Cache
 * 
 * This script clears the Goodreads cache in both the Node.js server and the PHP validation cache.
 * It's useful when the data displayed in the validation interface is corrupted or outdated.
 */

// Include database connection
require_once __DIR__ . '/../../../../db-connect.php';

// Include cache functions
require_once __DIR__ . '/functions/cache-functions.php';

// Set header for JSON response
header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred',
    'actions' => []
];

try {
    // 1. Clear PHP validation cache
    $stmt = $db->prepare("
        DELETE FROM validation_cache 
        WHERE cache_key LIKE '%book_validation_%'
    ");
    $stmt->execute();
    $phpCacheRowsDeleted = $stmt->rowCount();
    
    $response['actions'][] = [
        'name' => 'clear_php_cache',
        'status' => 'success',
        'message' => "Cleared {$phpCacheRowsDeleted} PHP validation cache entries"
    ];

    // 2. Clear Node.js server cache by making a request to the API
    $apiUrl = getenv('HEADLESS_BROWSER_API_URL') ?: 'http://37.27.31.107:3000';
    $apiKey = getenv('HEADLESS_BROWSER_API_KEY') ?: 'stories-scraper-api-key-2023';
    
    // Build the request URL for cache clearing
    $url = "{$apiUrl}/clear-cache";
    
    // Make the request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-Key: {$apiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'source' => 'goodreads',
        'all' => true
    ]));
    
    $curlResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $jsonResponse = json_decode($curlResponse, true);
        $message = $jsonResponse['message'] ?? "Cleared Node.js server cache (HTTP {$httpCode})";
        $entriesCleared = $jsonResponse['entriesCleared'] ?? 'unknown number of';
        
        $response['actions'][] = [
            'name' => 'clear_nodejs_cache',
            'status' => 'success',
            'message' => "Cleared {$entriesCleared} Node.js server cache entries"
        ];
    } else {
        $response['actions'][] = [
            'name' => 'clear_nodejs_cache',
            'status' => 'error',
            'message' => "Failed to clear Node.js server cache (HTTP {$httpCode}): " . substr($curlResponse, 0, 200)
        ];
    }
    
    // 3. Set environment variables to force fresh data
    putenv('VPS_BYPASS_CACHE=true');
    putenv('FORCE_FRESH_DATA=true');
    putenv('SKIP_CACHE=true');
    
    $response['actions'][] = [
        'name' => 'set_environment_variables',
        'status' => 'success',
        'message' => "Set environment variables to force fresh data"
    ];
    
    // Set overall status based on actions
    $hasErrors = false;
    foreach ($response['actions'] as $action) {
        if ($action['status'] === 'error') {
            $hasErrors = true;
            break;
        }
    }
    
    $response['status'] = $hasErrors ? 'partial' : 'success';
    $response['message'] = $hasErrors 
        ? 'Cache partially cleared with some errors' 
        : 'Cache successfully cleared';
    
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = 'Error clearing cache: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
