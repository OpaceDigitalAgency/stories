<?php
/**
 * CORS Fix for AI API Endpoints
 * 
 * This file ensures that CORS headers are properly set for AI API endpoints.
 * Include this file at the top of any AI API endpoint file.
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
