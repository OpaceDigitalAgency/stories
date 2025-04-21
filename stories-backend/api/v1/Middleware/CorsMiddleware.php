<?php
namespace StoriesAPI\Middleware;

class CorsMiddleware {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function handle() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        if (in_array($origin, $this->config['security']['cors']['allowed_origins'])) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        
        // Allow specified methods
        header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['security']['cors']['allowed_methods']));
        
        // Allow specified headers
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['security']['cors']['allowed_headers']));
        
        // Expose specified headers
        header('Access-Control-Expose-Headers: ' . implode(', ', $this->config['security']['cors']['expose_headers']));
        
        // Set max age
        header('Access-Control-Max-Age: ' . $this->config['security']['cors']['max_age']);
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
        
        return true;
    }
}