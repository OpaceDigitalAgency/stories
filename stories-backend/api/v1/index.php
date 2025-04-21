<?php
/**
 * API Entry Point
 * 
 * This file serves as the entry point for the Stories API v1.
 * It configures the router and sets up routes with appropriate middleware.
 */

// Error handling setup
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/api-error.log');

// Load autoloader
require_once __DIR__ . '/autoload.php';

// Load configuration
$config = require __DIR__ . '/config/config.php';

// Debug logging
error_log("API Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

try {
    // Initialize router with config
    $router = new StoriesAPI\Core\Router($config);
    
    // Load routes
    require_once __DIR__ . '/routes.php';
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'details' => $e->getMessage()
    ]);
}