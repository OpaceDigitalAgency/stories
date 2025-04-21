<?php
namespace StoriesAPI\Core;

class Auth {
    private $db;
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
        $this->db = new Database($config['db']);
    }
    
    public function validateToken($token) {
        try {
            // Verify token format
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            // Get token parts
            list($header, $payload, $signature) = $parts;
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', "$header.$payload", $this->config['security']['jwt_secret']);
            if (!hash_equals(base64_decode($signature), $expectedSignature)) {
                return false;
            }
            
            // Decode payload
            $payload = json_decode(base64_decode($payload), true);
            if (!$payload) {
                return false;
            }
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function generateToken($userId, $expiry = null) {
        if (!$expiry) {
            $expiry = time() + $this->config['security']['token_expiry'];
        }
        
        // Create token parts
        $header = base64_encode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]));
        
        $payload = base64_encode(json_encode([
            'sub' => $userId,
            'exp' => $expiry,
            'iat' => time()
        ]));
        
        // Create signature
        $signature = base64_encode(
            hash_hmac('sha256', "$header.$payload", $this->config['security']['jwt_secret'])
        );
        
        // Return complete token
        return "$header.$payload.$signature";
    }
}