<?php
/**
 * Simplified AJAX Test - Minimal version to test basic functionality
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Log the request
    error_log("Simple AJAX Test: Request received");
    error_log("POST data: " . print_r($_POST, true));
    
    $action = $_POST['action'] ?? 'test';
    
    if ($action === 'discover_all') {
        // Return a simple test response
        $response = [
            'success' => true,
            'message' => 'AJAX endpoint is working!',
            'books' => [
                [
                    'title' => 'Test Book 1',
                    'author' => 'Test Author 1',
                    'age_range' => '4-5 years'
                ],
                [
                    'title' => 'Test Book 2', 
                    'author' => 'Test Author 2',
                    'age_range' => '4-5 years'
                ]
            ],
            'total' => 2,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        error_log("Simple AJAX Test: Sending response");
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action: ' . $action
        ]);
    }
    
} catch (Exception $e) {
    error_log("Simple AJAX Test Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>