<?php
namespace StoriesAPI\Middleware;

use StoriesAPI\Utils\Response;

class SimpleAuthMiddleware {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function handle() {
        // Include the simple auth file
        require_once __DIR__ . '/../../../simple_auth.php';
        
        // Initialize the database connection
        \SimpleAuth::initDB($this->config['db']);
        
        // Get token from Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = null;
        
        if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
        
        // Get token from cookie if not in header
        if (!$token && isset($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
        }
        
        // If no token found, return unauthorized
        if (!$token) {
            Response::sendError('Unauthorized. Please log in to access this resource.', 401);
            return false;
        }
        
        // Validate token
        $user = \SimpleAuth::validateSimpleToken($token);
        
        if (!$user) {
            Response::sendError('Invalid or expired token. Please log in again.', 401);
            return false;
        }
        
        // Store user in request for controllers to access
        $_REQUEST['user'] = [
            'id' => $user['id'],
            'role' => $user['role']
        ];
        
        return true;
    }
}