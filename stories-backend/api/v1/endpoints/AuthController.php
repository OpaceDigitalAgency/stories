<?php
/**
 * Authentication Controller
 * 
 * This controller handles user authentication, registration, and profile management.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\BaseController;
use StoriesAPI\Utils\Auth;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class AuthController extends BaseController {
    /**
     * Login a user
     */
    public function login() {
        // Validate required fields
        if (!Validator::required($this->request, ['email', 'password'])) {
            $this->badRequest('Email and password are required', Validator::getErrors());
            return;
        }
        
        // Validate email format
        if (!Validator::email($this->request['email'])) {
            $this->badRequest('Invalid email format', Validator::getErrors());
            return;
        }
        
        // Sanitize input
        $email = Validator::sanitizeString($this->request['email']);
        $password = $this->request['password']; // Don't sanitize password before verification
        
        // Authenticate user
        $user = Auth::authenticate($email, $password);
        
        if (!$user) {
            $this->unauthorized('Invalid email or password');
            return;
        }
        
        // Generate JWT token
        $token = Auth::generateToken([
            'user_id' => $user['id'],
            'role' => $user['role']
        ]);
        
        // Return user data and token
        Response::sendSuccess([
            'user' => $user,
            'token' => $token,
            'expires_in' => $this->config['security']['token_expiry']
        ]);
    }
    
    /**
     * Register a new user
     */
    public function register() {
        // Validate required fields
        if (!Validator::required($this->request, ['name', 'email', 'password'])) {
            $this->badRequest('Name, email, and password are required', Validator::getErrors());
            return;
        }
        
        // Validate email format
        if (!Validator::email($this->request['email'])) {
            $this->badRequest('Invalid email format', Validator::getErrors());
            return;
        }
        
        // Validate password length
        if (!Validator::length($this->request['password'], 'password', 8)) {
            $this->badRequest('Password must be at least 8 characters', Validator::getErrors());
            return;
        }
        
        // Sanitize input
        $name = Validator::sanitizeString($this->request['name']);
        $email = Validator::sanitizeString($this->request['email']);
        $password = Auth::hashPassword($this->request['password']);
        
        try {
            // Check if email already exists
            $query = "SELECT id FROM users WHERE email = ? LIMIT 1";
            $stmt = $this->db->query($query, [$email]);
            
            if ($stmt->rowCount() > 0) {
                $this->badRequest('Email already in use');
                return;
            }
            
            // Insert new user
            $query = "INSERT INTO users (name, email, password, role, active, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($query, [
                $name,
                $email,
                $password,
                'user', // Default role
                1, // Active by default
                date('Y-m-d H:i:s')
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Get the new user
            $query = "SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$userId]);
            $user = $stmt->fetch();
            
            // Generate JWT token
            $token = Auth::generateToken([
                'user_id' => $user['id'],
                'role' => $user['role']
            ]);
            
            // Return user data and token
            Response::sendSuccess([
                'user' => $user,
                'token' => $token,
                'expires_in' => $this->config['security']['token_expiry']
            ], [], 201);
        } catch (\Exception $e) {
            $this->serverError('Failed to register user');
        }
    }
    
    /**
     * Get the current user's profile
     */
    public function me() {
        // User is already authenticated by middleware
        if (!$this->user) {
            $this->unauthorized();
            return;
        }
        
        // Return user data
        Response::sendSuccess([
            'id' => $this->user['id'],
            'attributes' => [
                'name' => $this->user['name'],
                'email' => $this->user['email'],
                'role' => $this->user['role']
            ]
        ]);
    }
    
    /**
     * Update the current user's profile
     */
    public function updateProfile() {
        // User is already authenticated by middleware
        if (!$this->user) {
            $this->unauthorized();
            return;
        }
        
        // Validate input
        $updates = [];
        $params = [];
        
        // Update name if provided
        if (isset($this->request['name']) && !empty($this->request['name'])) {
            $name = Validator::sanitizeString($this->request['name']);
            $updates[] = "name = ?";
            $params[] = $name;
        }
        
        // Update email if provided
        if (isset($this->request['email']) && !empty($this->request['email'])) {
            if (!Validator::email($this->request['email'])) {
                $this->badRequest('Invalid email format', Validator::getErrors());
                return;
            }
            
            $email = Validator::sanitizeString($this->request['email']);
            
            // Check if email already exists
            $query = "SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1";
            $stmt = $this->db->query($query, [$email, $this->user['id']]);
            
            if ($stmt->rowCount() > 0) {
                $this->badRequest('Email already in use');
                return;
            }
            
            $updates[] = "email = ?";
            $params[] = $email;
        }
        
        // Update password if provided
        if (isset($this->request['password']) && !empty($this->request['password'])) {
            if (!Validator::length($this->request['password'], 'password', 8)) {
                $this->badRequest('Password must be at least 8 characters', Validator::getErrors());
                return;
            }
            
            $password = Auth::hashPassword($this->request['password']);
            $updates[] = "password = ?";
            $params[] = $password;
        }
        
        // If no updates, return current user
        if (empty($updates)) {
            $this->me();
            return;
        }
        
        try {
            // Update user
            $query = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = ? WHERE id = ?";
            $params[] = date('Y-m-d H:i:s');
            $params[] = $this->user['id'];
            
            $this->db->query($query, $params);
            
            // Get updated user
            $query = "SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1";
            $stmt = $this->db->query($query, [$this->user['id']]);
            $user = $stmt->fetch();
            
            // Return updated user data
            Response::sendSuccess([
                'id' => $user['id'],
                'attributes' => [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } catch (\Exception $e) {
            $this->serverError('Failed to update profile');
        }
    }
    
    /**
     * Refresh a user's authentication token
     */
    public function refresh() {
        // Validate required fields
        if (!Validator::required($this->request, ['user_id'])) {
            $this->badRequest('User ID is required', Validator::getErrors());
            return;
        }
        
        // Get user ID
        $userId = (int)$this->request['user_id'];
        
        try {
            // Get user by ID
            $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1";
            $stmt = $this->db->query($query, [$userId]);
            
            if ($stmt->rowCount() === 0) {
                $this->unauthorized('Invalid user ID');
                return;
            }
            
            $user = $stmt->fetch();
            
            // Generate new JWT token
            $token = Auth::generateToken([
                'user_id' => $user['id'],
                'role' => $user['role']
            ]);
            
            // Return new token
            Response::sendSuccess([
                'token' => $token,
                'expires_in' => $this->config['security']['token_expiry']
            ]);
        } catch (\Exception $e) {
            $this->serverError('Failed to refresh token');
        }
    }
}