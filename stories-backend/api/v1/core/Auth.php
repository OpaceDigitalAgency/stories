<?php
namespace StoriesAPI\core;

use StoriesAPI\Utils\Response;

class Auth {
    private static $config;
    
    public static function init($config) {
        self::$config = $config;
    }
    
    public static function validateToken($token) {
        try {
            if (empty($token)) {
                return false;
            }
            
            $db = new Database(self::$config['db']);
            
            $query = "SELECT * FROM users WHERE auth_token = ? AND token_expires > NOW() LIMIT 1";
            $stmt = $db->query($query, [$token]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            return $stmt->fetch();
        } catch (\Exception $e) {
            error_log("Token validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function generateToken($userId) {
        try {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $db = new Database(self::$config['db']);
            
            $query = "UPDATE users SET auth_token = ?, token_expires = ? WHERE id = ?";
            $db->query($query, [$token, $expires, $userId]);
            
            return $token;
        } catch (\Exception $e) {
            error_log("Token generation failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function login($email, $password) {
        try {
            $db = new Database(self::$config['db']);
            
            $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
            $stmt = $db->query($query, [$email]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            $user = $stmt->fetch();
            
            if (!password_verify($password, $user['password'])) {
                return false;
            }
            
            $token = self::generateToken($user['id']);
            
            if (!$token) {
                return false;
            }
            
            return [
                'user' => $user,
                'token' => $token
            ];
        } catch (\Exception $e) {
            error_log("Login failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function logout($token) {
        try {
            $db = new Database(self::$config['db']);
            
            $query = "UPDATE users SET auth_token = NULL, token_expires = NULL WHERE auth_token = ?";
            $db->query($query, [$token]);
            
            return true;
        } catch (\Exception $e) {
            error_log("Logout failed: " . $e->getMessage());
            return false;
        }
    }
}
