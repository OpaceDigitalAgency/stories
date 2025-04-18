<?php
/**
 * Authentication Utility Class
 *
 * This class handles JWT token generation, validation, and user authentication.
 *
 * @package Stories Admin
 * @version 1.0.0
 */

// Prevent any output before headers are sent
if (ob_get_level() == 0) ob_start();

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
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verify signature
        $valid = hash_hmac('sha256', "$header.$payload", self::$jwtSecret, true);
        $valid = self::base64UrlEncode($valid);
        
        if ($signature !== $valid) {
            return false;
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payload), true);
        
        // Check if token has expired
        if (isset($payload['exp']) && $payload['exp'] < time()) {
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
            
            // Check if user has admin or editor role
            if (!in_array($user['role'], ['admin', 'editor'])) {
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
            $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 AND role IN ('admin', 'editor') LIMIT 1";
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
     * Check if user is authenticated
     * 
     * @return array|bool User data if authenticated, false otherwise
     */
    public static function checkAuth() {
        // Check if user is already authenticated in session
        if (isset($_SESSION['user']) && !empty($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        
        // Check for JWT token in cookie
        if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
            $payload = self::validateToken($token);
            
            if ($payload && isset($payload['user_id'])) {
                // Get user data
                $user = self::getUserById($payload['user_id']);
                
                if ($user) {
                    // Store user in session
                    $_SESSION['user'] = $user;
                    return $user;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Login a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @param bool $remember Whether to remember the user
     * @return array|bool User data if authenticated, false otherwise
     */
    public static function login($email, $password, $remember = false) {
        $user = self::authenticate($email, $password);
        
        if (!$user) {
            return false;
        }
        
        // Store user in session
        $_SESSION['user'] = $user;
        
        // Generate JWT token
        $token = self::generateToken([
            'user_id' => $user['id'],
            'role' => $user['role']
        ]);
        
        // Store token in both session and cookie for consistency
        $_SESSION['token'] = $token;
        
        // Set token in cookie
        $cookieExpiry = $remember ? time() + self::$tokenExpiry : 0;
        setcookie('auth_token', $token, $cookieExpiry, '/', '', false, true);
        
        return $user;
    }
    
    /**
     * Logout a user
     */
    public static function logout() {
        // Clear session
        unset($_SESSION['user']);
        unset($_SESSION['token']);
        
        // Clear cookie
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);
    }
    
    /**
     * Refresh the authentication token
     *
     * @param array $user User data
     * @param bool $remember Whether to remember the user
     * @return string|bool New token if successful, false otherwise
     */
    public static function refreshToken($user, $remember = false) {
        if (!$user || !isset($user['id'])) {
            error_log("Auth::refreshToken - Invalid user data");
            return false;
        }
        
        error_log("Auth::refreshToken - Refreshing token for user ID: {$user['id']}, role: {$user['role']}");
        
        try {
            // Generate new JWT token
            $token = self::generateToken([
                'user_id' => $user['id'],
                'role' => $user['role']
            ]);
            
            // Store token in session
            $_SESSION['token'] = $token;
            
            // Determine if we're using HTTPS
            $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                     (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            
            // Set token in cookie with secure flag if using HTTPS
            $cookieExpiry = $remember ? time() + self::$tokenExpiry : 0;
            if ($cookieExpiry === 0) {
                // If not remembering, set expiry to session end (browser close)
                $cookieExpiry = 0;
            } else {
                // If remembering, ensure expiry is not too far in the future
                // Maximum 30 days
                $maxExpiry = time() + (30 * 24 * 60 * 60);
                $cookieExpiry = min($cookieExpiry, $maxExpiry);
            }
            
            // Set the cookie with appropriate security settings
            setcookie(
                'auth_token',
                $token,
                $cookieExpiry,
                '/',
                '',  // Domain - empty for current domain
                $secure,  // Secure - only send over HTTPS if available
                true  // HttpOnly - prevent JavaScript access
            );
            
            error_log("Auth::refreshToken - Token refreshed successfully");
            
            // Also try to refresh the token on the API side
            self::refreshApiToken($user['id'], $token);
            
            return $token;
        } catch (\Exception $e) {
            error_log("Auth::refreshToken - Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Attempt to refresh the token on the API side
     *
     * @param int $userId User ID
     * @param string $token New token
     * @return bool Success status
     */
    private static function refreshApiToken($userId, $token) {
        try {
            // Get API URL from config
            $apiUrl = defined('API_URL') ? API_URL : '';
            if (empty($apiUrl)) {
                error_log("Auth::refreshApiToken - API_URL not defined");
                return false;
            }
            
            // Initialize cURL
            $ch = curl_init();
            $url = rtrim($apiUrl, '/') . '/auth/refresh';
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            
            // Set headers
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token  // Use the new token for authorization
            ];
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            // Set request data
            $jsonData = json_encode(['user_id' => $userId]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            
            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Check for errors
            if (curl_errno($ch)) {
                error_log("Auth::refreshApiToken - cURL error: " . curl_error($ch));
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            // Check response
            if ($httpCode == 200) {
                error_log("Auth::refreshApiToken - API token refresh successful");
                return true;
            } else {
                error_log("Auth::refreshApiToken - API token refresh failed: HTTP $httpCode");
                return false;
            }
        } catch (\Exception $e) {
            error_log("Auth::refreshApiToken - Error: " . $e->getMessage());
            return false;
        }
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
}