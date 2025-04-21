<?php
namespace StoriesAPI\Middleware;

class CorsMiddleware {
    /**
     * Handle CORS headers
     * 
     * @return bool Always returns true to continue request processing
     */
    public function handle() {
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        
        return true;
    }
}