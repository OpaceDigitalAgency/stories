<?php
/**
 * Authentication Middleware
 * 
 * This middleware handles authentication for protected API endpoints.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Middleware;

use StoriesAPI\Core\Auth;
use StoriesAPI\Utils\Response;

class AuthMiddleware {
    /**
     * @var array Required roles for the endpoint
     */
    private $requiredRoles;
    
    /**
     * Constructor
     * 
     * @param string|array $roles Required roles for the endpoint
     */
    public function __construct($roles = null) {
        $this->requiredRoles = $roles;
    }
    
    /**
     * Handle the authentication
     * 
     * @return bool True if the request should continue, false if it should stop
     */
    public function handle() {
        // Get the current user
        $user = Auth::getCurrentUser();
        
        // If no user is authenticated, return 401 Unauthorized
        if (!$user) {
            Response::sendError('Unauthorized. Please log in to access this resource.', 401);
            return false;
        }
        
        // If roles are required, check if the user has the required role
        if ($this->requiredRoles !== null && !Auth::hasRole($user, $this->requiredRoles)) {
            Response::sendError('Forbidden. You do not have permission to access this resource.', 403);
            return false;
        }
        
        // Set the user in the request for later use
        $_REQUEST['user'] = $user;
        
        return true;
    }
}