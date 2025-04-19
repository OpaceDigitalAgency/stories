<?php
namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class SimpleAuthController extends BaseController {
    /**
     * Login a user
     */
    public function login() {
        // Include the simple auth file
        require_once __DIR__ . '/../../../simple_auth.php';
        
        // Initialize the database connection
        \SimpleAuth::initDB($this->config['db']);
        
        // Validate required fields
        if (!Validator::required($this->request, ['email', 'password'])) {
            $this->badRequest('Email and password are required');
            return;
        }
        
        // Sanitize input
        $email = Validator::sanitizeEmail($this->request['email']);
        $password = $this->request['password'];
        $remember = isset($this->request['remember']) ? (bool)$this->request['remember'] : false;
        
        // Authenticate user
        $user = \SimpleAuth::login($email, $password);
        
        if (!$user) {
            $this->badRequest('Invalid email or password');
            return;
        }
        
        // Get token from session
        $token = $_SESSION['auth_token'];
        
        // Return user data and token
        Response::sendSuccess([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $token
        ]);
    }
    
    /**
     * Logout a user
     */
    public function logout() {
        // Include the simple auth file
        require_once __DIR__ . '/../../../simple_auth.php';
        
        // Initialize the database connection
        \SimpleAuth::initDB($this->config['db']);
        
        // Logout user
        \SimpleAuth::logout();
        
        // Return success
        Response::sendSuccess(['message' => 'Logged out successfully']);
    }
    
    /**
     * Get the current authenticated user
     */
    public function me() {
        // Get user from request (set by middleware)
        $user = $_REQUEST['user'] ?? null;
        
        if (!$user) {
            $this->unauthorized('Not authenticated');
            return;
        }
        
        // Get full user data
        require_once __DIR__ . '/../../../simple_auth.php';
        \SimpleAuth::initDB($this->config['db']);
        
        $userData = \SimpleAuth::user();
        
        if (!$userData) {
            $this->unauthorized('User not found');
            return;
        }
        
        // Return user data
        Response::sendSuccess([
            'user' => [
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'role' => $userData['role']
            ]
        ]);
    }
}