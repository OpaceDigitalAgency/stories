<?php
namespace Admin;

use PDO;
use PDOException;

/**
 * Auth Class
 * 
 * Handles authentication for the admin interface
 */
class Auth {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->query(
                "SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1",
                [$username]
            );
            
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return false;
            }
            
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set session variables
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            
            // Update last login time
            $this->db->query(
                "UPDATE admin_users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            return true;
        } catch (PDOException $e) {
            error_log("Login failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    public function isLoggedIn() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            return false;
        }
        
        // Check session timeout
        $config = require __DIR__ . '/config.php';
        $timeout = $config['auth']['session_lifetime'];
        
        if (isset($_SESSION['admin_login_time']) && 
            (time() - $_SESSION['admin_login_time']) > $timeout) {
            $this->logout();
            return false;
        }
        
        // Update login time
        $_SESSION['admin_login_time'] = time();
        
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->db->query(
                "SELECT id, username, email, created_at, last_login 
                FROM admin_users 
                WHERE id = ? 
                LIMIT 1",
                [$_SESSION['admin_user_id']]
            );
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Failed to get current user: " . $e->getMessage());
            return null;
        }
    }
    
    public function createUser($username, $password, $email) {
        try {
            // Check if username already exists
            $stmt = $this->db->query(
                "SELECT id FROM admin_users WHERE username = ? LIMIT 1",
                [$username]
            );
            
            if ($stmt->rowCount() > 0) {
                throw new \Exception("Username already exists");
            }
            
            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $this->db->query(
                "INSERT INTO admin_users (username, password_hash, email, created_at) 
                VALUES (?, ?, ?, NOW())",
                [$username, $passwordHash, $email]
            );
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Failed to create user: " . $e->getMessage());
            throw new \Exception("Failed to create user");
        }
    }
    
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $stmt = $this->db->query(
                "SELECT password_hash FROM admin_users WHERE id = ? LIMIT 1",
                [$userId]
            );
            
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                throw new \Exception("Current password is incorrect");
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $this->db->query(
                "UPDATE admin_users SET password_hash = ? WHERE id = ?",
                [$newPasswordHash, $userId]
            );
            
            return true;
        } catch (PDOException $e) {
            error_log("Failed to update password: " . $e->getMessage());
            throw new \Exception("Failed to update password");
        }
    }
}