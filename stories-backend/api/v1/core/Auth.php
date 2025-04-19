<?php
/**
 * Authentication Core Class
 * 
 * This class handles JWT token generation, validation, and user authentication.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Core;

use Exception;
use StoriesAPI\Core\Database;

class Auth {
    /**
     * @var string JWT secret key
     */
    private static $jwtSecret;
    
    /**
     * @var int Token expiry time in seconds
     */
    private static $tokenExpiry;
    
    /**
     * Initialize the Auth class with configuration
     * 
     * @param array $config Security configuration
     */
    public static function init($config) {
        self::$jwtSecret = $config['jwt_secret'];
        self::$tokenExpiry = $config['token_expiry'];
    }
    
    /**
     * Generate a JWT token
     * 
     * @param array $payload Token payload data
     * @return string The generated JWT token
     */
    public static function generateToken($payload) {
        // Add issued at and expiry time to payload
        $issuedAt = time();
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $issuedAt + self::$tokenExpiry;
        
        // Create JWT header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = self::base64UrlEncode($header);
        
        // Create JWT payload
        $payload = json_encode($payload);
        $payload = self::base64UrlEncode($payload);
        
        // Create signature
        $signature = hash_hmac('sha256', "$header.$payload", self::$jwtSecret, true);
        $signature = self::base64UrlEncode($signature);
        
        // Create JWT token
        return "$header.$payload.$signature";
    }
    
    /**
     * Validate a JWT token
     * 
     * @param string $token The JWT token to validate
     * @return array|bool The token payload if valid, false otherwise
     */
    public static function validateToken($token) {
        // Split token into parts
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            error_log("Token validation failed: Invalid token format");
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verify signature
        $valid = hash_hmac('sha256', "$header.$payload", self::$jwtSecret, true);
        $valid = self::base64UrlEncode($valid);
        
        if ($signature !== $valid) {
            error_log("Token validation failed: Invalid signature");
            return false;
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payload), true);
        
        // Check if token has expired
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            // Set a global variable to indicate token expiration
            // This will be used by the Response class to include an expiration message
            $GLOBALS['token_expired'] = true;
            error_log("Token validation failed: Token expired at " . date('Y-m-d H:i:s', $payload['exp']));
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Authenticate a user with email and password
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|bool User data if authenticated, false otherwise
     */
    public static function authenticate($email, $password) {
        try {
            $db = Database::getInstance();
            
            // Get user by email
            $query = "SELECT id, name, email, password, role FROM users WHERE email = ? AND active = 1 LIMIT 1";
            $stmt = $db->query($query, [$email]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            $user = $stmt->fetch();
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return false;
            }
            
            // Remove password from user data
            unset($user['password']);
            
            return $user;
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|bool User data if found, false otherwise
     */
    public static function getUserById($userId) {
        try {
            $db = Database::getInstance();
            
            // Get user by ID
            $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1";
            $stmt = $db->query($query, [$userId]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hash a password
     * 
     * @param string $password Password to hash
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Get the current authenticated user from the request
     * 
     * @return array|bool User data if authenticated, false otherwise
     */
    public static function getCurrentUser() {
        // Get Authorization header
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        // Check if token exists
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return false;
        }
        
        // Validate token
        $token = $matches[1];
        $payload = self::validateToken($token);
        
        if (!$payload || !isset($payload['user_id'])) {
            return false;
        }
        
        // Get user data
        return self::getUserById($payload['user_id']);
    }
    
    /**
     * Check if user has required role
     * 
     * @param array $user User data
     * @param string|array $roles Required roles
     * @return bool True if user has required role
     */
    public static function hasRole($user, $roles) {
        if (!$user || !isset($user['role'])) {
            return false;
        }
        
        if (is_array($roles)) {
            return in_array($user['role'], $roles);
        }
        
        return $user['role'] === $roles;
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Base64 URL encoded data
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string Decoded data
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Validate CSRF token
     *
     * @param string $token CSRF token to validate
     * @return bool True if token is valid
     */
    public static function validateCsrfToken($token) {
        // For now, return true to bypass CSRF validation
        // This will be properly implemented later
        return true;
        
        // Proper implementation would look something like this:
        /*
        try {
            // Get the session token
            $sessionToken = $_SESSION['csrf_token'] ?? null;
            
            // Check if token matches
            if (!$sessionToken || $token !== $sessionToken) {
                error_log("CSRF token validation failed: Token mismatch");
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("CSRF token validation error: " . $e->getMessage());
            return false;
        }
        */
        /**
         * Refresh a user's authentication token
         *
         * @param int $userId User ID
         * @param bool $checkExpiration Whether to check if token is about to expire
         * @param int $expirationThreshold Seconds threshold for token expiration (default: 30)
         * @return string|bool New token if successful, false otherwise
         */
        public static function refreshToken($userId, $checkExpiration = false, $expirationThreshold = 30) {
            try {
                // Get user data
                $user = self::getUserById($userId);
                
                if (!$user) {
                    error_log("Auth::refreshToken - Invalid user ID: $userId");
                    return false;
                }
                
                // If checking expiration, validate the current token first
                if ($checkExpiration) {
                    // Get Authorization header
                    $headers = getallheaders();
                    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
                    
                    // Check if token exists
                    if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                        $token = $matches[1];
                        $payload = self::validateToken($token);
                        
                        // If token is valid and not about to expire, return false
                        if ($payload && isset($payload['exp'])) {
                            $expiresIn = $payload['exp'] - time();
                            if ($expiresIn > $expirationThreshold) {
                                // Token is still valid and not about to expire
                                return false;
                            }
                            // Otherwise, token is about to expire, so continue with refresh
                            error_log("Auth::refreshToken - Token expires in $expiresIn seconds, refreshing");
                        }
                    }
                }
                
                // Generate new JWT token
                $token = self::generateToken([
                    'user_id' => $user['id'],
                    'role' => $user['role']
                ]);
                
                error_log("Auth::refreshToken - Token refreshed successfully for user ID: $userId");
                
                return $token;
            } catch (\Exception $e) {
                error_log("Auth::refreshToken - Error: " . $e->getMessage());
                return false;
            }
        }
    }
}