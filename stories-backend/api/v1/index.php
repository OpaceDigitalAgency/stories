<?php
/**
 * API Entry Point
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/api-error.log');

// Load configuration
try {
    // Get real path of config directory
    $configDir = realpath(__DIR__ . '/Config');
    if (!$configDir) {
        throw new Exception("Config directory not found at: " . __DIR__ . '/Config');
    }
    
    $configFile = $configDir . '/config.php';
    if (!file_exists($configFile)) {
        throw new Exception("Config file not found at: $configFile");
    }
    
    $config = require $configFile;
    if (!is_array($config)) {
        throw new Exception("Invalid config format");
    }
    
    error_log("Config loaded successfully from: $configFile");
} catch (Exception $e) {
    error_log("Config load error: " . $e->getMessage());
    error_log("Current directory: " . __DIR__);
    error_log("Attempted config path: " . __DIR__ . '/Config/config.php');
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Configuration error: ' . $e->getMessage()
    ]);
    exit;
}

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Autoloader
require_once __DIR__ . '/autoload.php';

try {
    // Initialize router
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
        'debug' => $e->getMessage()
    ]);
}