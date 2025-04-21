<?php
namespace StoriesAPI\Middleware;

use StoriesAPI\Core\Auth;
use StoriesAPI\Utils\Response;

class AuthMiddleware {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function handle() {
        // Check for Authorization header
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            Response::sendError('Authorization header required', 401);
            return false;
        }
        
        // Extract token
        $auth = $headers['Authorization'];
        if (!preg_match('/^Bearer\s+(.+)$/', $auth, $matches)) {
            Response::sendError('Invalid authorization format', 401);
            return false;
        }
        
        $token = $matches[1];
        
        // Validate token
        $auth = new Auth($this->config);
        $payload = $auth->validateToken($token);
        
        if (!$payload) {
            Response::sendError('Invalid or expired token', 401);
            return false;
        }
        
        // Store user ID for controllers
        $_SERVER['USER_ID'] = $payload['sub'];
        
        return true;
    }
}