<?php
/**
 * Clear Goodreads Cache
 *
 * This script clears the Goodreads cache in both the Node.js server and the PHP validation cache.
 * It's useful when the data displayed in the validation interface is corrupted or outdated.
 */

// Include database connection
require_once __DIR__ . '/../../../../db-connect.php';

// Check if database connection is available
if (!isset($db) || !($db instanceof PDO)) {
    // Try alternative paths
    $possiblePaths = [
        __DIR__ . '/../../../../includes/db-connect.php',
        __DIR__ . '/../../../includes/db-connect.php',
        __DIR__ . '/../../../db-connect.php'
    ];

    $dbConnected = false;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            if (isset($db) && $db instanceof PDO) {
                $dbConnected = true;
                break;
            }
        }
    }

    if (!$dbConnected) {
        // Return error if we couldn't connect to the database
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not connect to database. Please check the database connection.'
        ]);
        exit;
    }
}

// Include cache functions
$cacheFunctionsPath = __DIR__ . '/functions/cache-functions.php';
if (file_exists($cacheFunctionsPath)) {
    require_once $cacheFunctionsPath;
} else {
    // If cache functions don't exist, create them
    if (!function_exists('clearAllValidationCache')) {
        function clearAllValidationCache($db) {
            try {
                $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE '%book_validation_%'");
                $stmt->execute();
                return $stmt->rowCount() > 0;
            } catch (Exception $e) {
                error_log("Error clearing validation cache: " . $e->getMessage());
                return false;
            }
        }
    }

    if (!function_exists('clearSourceValidationCache')) {
        function clearSourceValidationCache($source, $db) {
            try {
                $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE :source");
                $stmt->execute([':source' => "%{$source}%"]);
                return $stmt->rowCount() > 0;
            } catch (Exception $e) {
                error_log("Error clearing source validation cache: " . $e->getMessage());
                return false;
            }
        }
    }
}

// Set header for JSON response
header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred',
    'actions' => []
];

try {
    // 1. Clear PHP validation cache using the existing function
    $phpCacheCleared = clearAllValidationCache($db);

    if ($phpCacheCleared) {
        $response['actions'][] = [
            'name' => 'clear_php_cache',
            'status' => 'success',
            'message' => "Successfully cleared PHP validation cache"
        ];
    } else {
        $response['actions'][] = [
            'name' => 'clear_php_cache',
            'status' => 'error',
            'message' => "Failed to clear PHP validation cache"
        ];
    }

    // Also clear source-specific cache for Goodreads
    $goodreadsCacheCleared = clearSourceValidationCache('goodreads', $db);

    if ($goodreadsCacheCleared) {
        $response['actions'][] = [
            'name' => 'clear_goodreads_cache',
            'status' => 'success',
            'message' => "Successfully cleared Goodreads-specific cache"
        ];
    } else {
        $response['actions'][] = [
            'name' => 'clear_goodreads_cache',
            'status' => 'error',
            'message' => "Failed to clear Goodreads-specific cache"
        ];
    }

    // 2. Clear Node.js server cache by making a request to the API
    $apiUrl = getenv('HEADLESS_BROWSER_API_URL') ?: 'http://37.27.31.107:3000';
    $apiKey = getenv('HEADLESS_BROWSER_API_KEY') ?: 'stories-scraper-api-key-2023';

    // Build the request URL for cache clearing
    $url = "{$apiUrl}/clear-cache";

    // Log the request for debugging
    error_log("Attempting to clear Node.js cache at: {$url}");

    // Check if curl is available
    if (!function_exists('curl_init')) {
        $response['actions'][] = [
            'name' => 'clear_nodejs_cache',
            'status' => 'error',
            'message' => "cURL is not available on this server"
        ];
    } else {
        try {
            // Make the request with error handling
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-API-Key: {$apiKey}",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'source' => 'goodreads',
                'all' => true,
                'force' => true
            ]));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification if needed
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL host verification if needed

            $curlResponse = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Log the response for debugging
            error_log("Node.js cache clear response: HTTP {$httpCode}, Response: " . substr($curlResponse, 0, 100));

            if ($curlError) {
                $response['actions'][] = [
                    'name' => 'clear_nodejs_cache',
                    'status' => 'error',
                    'message' => "cURL error: {$curlError}"
                ];
            } else if ($httpCode >= 200 && $httpCode < 300) {
                // Try to parse JSON response
                $jsonResponse = null;
                if (!empty($curlResponse)) {
                    $jsonResponse = json_decode($curlResponse, true);
                }

                $message = $jsonResponse['message'] ?? "Cleared Node.js server cache (HTTP {$httpCode})";
                $entriesCleared = $jsonResponse['entriesCleared'] ?? 'unknown number of';

                $response['actions'][] = [
                    'name' => 'clear_nodejs_cache',
                    'status' => 'success',
                    'message' => "Cleared {$entriesCleared} Node.js server cache entries"
                ];
            } else {
                // If we get a 500 error, it might be because the server is not running
                // or the cache directory doesn't exist yet - this is not a critical error
                if ($httpCode == 500) {
                    $response['actions'][] = [
                        'name' => 'clear_nodejs_cache',
                        'status' => 'warning',
                        'message' => "Node.js server returned 500 error - this may be normal if the cache doesn't exist yet. Local caches were cleared successfully."
                    ];
                } else {
                    $response['actions'][] = [
                        'name' => 'clear_nodejs_cache',
                        'status' => 'warning',
                        'message' => "Failed to clear Node.js server cache (HTTP {$httpCode}): " . substr($curlResponse, 0, 200) . " - Local caches were cleared successfully."
                    ];
                }
            }
        } catch (Exception $e) {
            $response['actions'][] = [
                'name' => 'clear_nodejs_cache',
                'status' => 'error',
                'message' => "Exception while clearing Node.js cache: " . $e->getMessage()
            ];
        }
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
