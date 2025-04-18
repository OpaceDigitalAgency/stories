<?php
namespace StoriesAPI\Middleware;

use StoriesAPI\Utils\Response;
use StoriesAPI\Core\Auth;

class AuthMiddleware {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function handle() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            error_log("AuthMiddleware: No valid Authorization header found");
            Response::sendError('Unauthorized. Please log in to access this resource.', 401);
            return false;
        }
        
        $token = $matches[1];
        Auth::init($this->config['security']);
        
        $payload = Auth::validateToken($token);
        if (!$payload) {
            error_log("AuthMiddleware: Invalid token");
            Response::sendError('Invalid or expired token. Please log in again.', 401);
            return false;
        }
        
        $_REQUEST['user'] = [
            'id' => $payload['user_id'],
            'role' => $payload['role']
        ];
        
        error_log("AuthMiddleware: Token validated for user {$payload['user_id']}");
        return true;
    }
}
