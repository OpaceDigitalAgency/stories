<?php
/**
 * Simple Cache Clearing Script
 *
 * This script provides a simple way to clear the validation cache
 * without relying on complex external services.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'success',
    'message' => 'Cache clearing completed',
    'actions' => []
];

// Include auth check
require_once '../../../includes/auth-check.php';

// Include database connection
require_once '../../../includes/db_connect.php';

try {

    // Clear validation cache from database
    try {
        $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE '%book_validation_%'");
        $result = $stmt->execute();

        if ($result) {
            $count = $stmt->rowCount();
            $response['actions'][] = [
                'name' => 'clear_validation_cache',
                'status' => 'success',
                'message' => "Successfully cleared $count validation cache entries"
            ];
        } else {
            $response['actions'][] = [
                'name' => 'clear_validation_cache',
                'status' => 'warning',
                'message' => "No validation cache entries found to clear"
            ];
        }
    } catch (Exception $e) {
        $response['actions'][] = [
            'name' => 'clear_validation_cache',
            'status' => 'error',
            'message' => "Error clearing validation cache: " . $e->getMessage()
        ];
    }

    // Clear Goodreads cache specifically
    try {
        $stmt = $db->prepare("DELETE FROM validation_cache WHERE cache_key LIKE '%goodreads%'");
        $result = $stmt->execute();

        if ($result) {
            $count = $stmt->rowCount();
            $response['actions'][] = [
                'name' => 'clear_goodreads_cache',
                'status' => 'success',
                'message' => "Successfully cleared $count Goodreads cache entries"
            ];
        } else {
            $response['actions'][] = [
                'name' => 'clear_goodreads_cache',
                'status' => 'warning',
                'message' => "No Goodreads cache entries found to clear"
            ];
        }
    } catch (Exception $e) {
        $response['actions'][] = [
            'name' => 'clear_goodreads_cache',
            'status' => 'error',
            'message' => "Error clearing Goodreads cache: " . $e->getMessage()
        ];
    }

    // Set environment variables to force fresh data
    putenv('VPS_BYPASS_CACHE=true');
    putenv('FORCE_FRESH_DATA=true');
    putenv('SKIP_CACHE=true');

    $response['actions'][] = [
        'name' => 'set_environment_variables',
        'status' => 'success',
        'message' => "Set environment variables to force fresh data"
    ];

    // Overall status
    $hasErrors = false;
    foreach ($response['actions'] as $action) {
        if ($action['status'] === 'error') {
            $hasErrors = true;
            break;
        }
    }

    if ($hasErrors) {
        $response['status'] = 'error';
        $response['message'] = 'Cache clearing completed with errors';
    }

} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Error clearing cache: ' . $e->getMessage(),
        'actions' => []
    ];
}

// Output the response
echo json_encode($response, JSON_PRETTY_PRINT);
