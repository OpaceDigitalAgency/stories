<?php
/**
 * CORS Middleware
 * 
 * This middleware handles Cross-Origin Resource Sharing (CORS) headers
 * to allow requests from the Netlify-hosted Astro frontend.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Middleware;

class CorsMiddleware {
    /**
     * @var array CORS configuration
     */
    private $config;
    
    /**
     * Constructor
     * 
     * @param array $config CORS configuration
     */
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Handle the CORS headers
     * 
     * @return bool True if the request should continue, false if it should stop
     */
    public function handle() {
        // Get the origin of the request
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Check if the origin is allowed
        if ($this->isAllowedOrigin($origin)) {
            // Set the CORS headers
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Methods: " . implode(', ', $this->config['allowed_methods']));
            header("Access-Control-Allow-Headers: " . implode(', ', $this->config['allowed_headers']));
            header("Access-Control-Expose-Headers: " . implode(', ', $this->config['expose_headers']));
            header("Access-Control-Max-Age: " . $this->config['max_age']);
            header("Access-Control-Allow-Credentials: true");
        }
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204); // No content
            exit;
        }
        
        return true;
    }
    
    /**
     * Check if the origin is allowed
     * 
     * @param string $origin The origin to check
     * @return bool True if the origin is allowed
     */
    private function isAllowedOrigin($origin) {
        // If no origin is provided, deny
        if (empty($origin)) {
            return false;
        }
        
        // If '*' is in the allowed origins, allow all
        if (in_array('*', $this->config['allowed_origins'])) {
            return true;
        }
        
        // Check if the origin is in the allowed origins
        return in_array($origin, $this->config['allowed_origins']);
    }
}