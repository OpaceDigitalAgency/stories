<?php
/**
 * Simple Authentication Solution
 * 
 * This file provides a simplified authentication system to replace the complex JWT implementation.
 * It uses standard PHP sessions and database authentication.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SimpleAuth {
    private static $db = null;
    
    /**
     * Initialize the database connection
     */
    public static function initDB($config) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']};port={$config['port']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            self::$db = new PDO($dsn, $config['user'], $config['password'], $options);
            return true;
        } catch (PDOException $e) {
            error_log("SimpleAuth DB Connection Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Login a user with email and password
     */
    public static function login($email, $password) {
        try {
            // Get user by email
            $query = "SELECT id, name, email, password, role FROM users WHERE email = ? AND active = 1 LIMIT 1";
            $stmt = self::$db->prepare($query);
            $stmt->execute([$email]);
            
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
            
            // Store user in session
            $_SESSION['auth_user'] = $user;
            $_SESSION['auth_time'] = time();
            
            // Generate a simple token for API access
            $token = self::generateSimpleToken($user['id'], $user['role']);
            $_SESSION['auth_token'] = $token;
            
            // Set cookie for persistent login
            setcookie('auth_token', $token, time() + 86400, '/', '', false, true);
            
            return $user;
        } catch (Exception $e) {
            error_log("SimpleAuth login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check() {
        // Check session first
        if (isset($_SESSION['auth_user']) && !empty($_SESSION['auth_user'])) {
            return $_SESSION['auth_user'];
        }
        
        // Check for token in cookie
        if (isset($_COOKIE['auth_token']) && !empty($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
            $userData = self::validateSimpleToken($token);
            
            if ($userData) {
                // Store in session for future checks
                $_SESSION['auth_user'] = $userData;
                $_SESSION['auth_time'] = time();
                $_SESSION['auth_token'] = $token;
                return $userData;
            }
        }
        
        return false;
    }
    
    /**
     * Logout the current user
     */
    public static function logout() {
        // Clear session
        unset($_SESSION['auth_user']);
        unset($_SESSION['auth_time']);
        unset($_SESSION['auth_token']);
        
        // Clear cookie
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);
    }
    
    /**
     * Generate a simple token for API access
     */
    private static function generateSimpleToken($userId, $role) {
        // Create a simple token format: base64(userId|role|timestamp|randomString)
        $timestamp = time();
        $random = bin2hex(random_bytes(16));
        $data = "$userId|$role|$timestamp|$random";
        
        // Add a simple signature
        $signature = hash('sha256', $data . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        $token = base64_encode("$data|$signature");
        
        // Store token in database for validation
        $query = "INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)";
        $stmt = self::$db->prepare($query);
        $stmt->execute([$userId, $token, date('Y-m-d H:i:s', time() + 86400)]);
        
        return $token;
    }
    
    /**
     * Validate a simple token
     */
    private static function validateSimpleToken($token) {
        try {
            // Decode token
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 5) {
                return false;
            }
            
            list($userId, $role, $timestamp, $random, $signature) = $parts;
            
            // Check if token is expired (24 hours)
            if (time() - $timestamp > 86400) {
                return false;
            }
            
            // Verify signature
            $data = "$userId|$role|$timestamp|$random";
            $expectedSignature = hash('sha256', $data . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
            
            if ($signature !== $expectedSignature) {
                return false;
            }
            
            // Check if token exists in database
            $query = "SELECT * FROM auth_tokens WHERE user_id = ? AND token = ? AND expires_at > NOW() LIMIT 1";
            $stmt = self::$db->prepare($query);
            $stmt->execute([$userId, $token]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            // Get user data
            $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1";
            $stmt = self::$db->prepare($query);
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("SimpleAuth token validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current authenticated user
     */
    public static function user() {
        return self::check();
    }
    
    /**
     * Check if user has a specific role
     */
    public static function hasRole($role) {
        $user = self::user();
        if (!$user) {
            return false;
        }
        
        if (is_array($role)) {
            return in_array($user['role'], $role);
        }
        
        return $user['role'] === $role;
    }
    
    /**
     * Create auth_tokens table if it doesn't exist
     */
    public static function setupTokensTable() {
        $query = "CREATE TABLE IF NOT EXISTS auth_tokens (
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            UNIQUE KEY (token)
        )";
        
        try {
            self::$db->exec($query);
            return true;
        } catch (Exception $e) {
            error_log("SimpleAuth setup error: " . $e->getMessage());
            return false;
        }
    }
}