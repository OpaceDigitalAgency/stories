<?php
/**
 * Token Refresh Test
 * 
 * This test verifies that the token refresh functionality works correctly.
 */

namespace Tests\Auth;

use PHPUnit\Framework\TestCase;
use StoriesAPI\Core\Auth;
use StoriesAPI\Core\Database;

class TokenRefreshTest extends TestCase
{
    private $config;
    private $userId;
    private $testToken;
    
    protected function setUp(): void
    {
        // Load configuration
        $this->config = require __DIR__ . '/../../api/v1/config/config.php';
        
        // Initialize Auth with config
        Auth::init($this->config['security']);
        
        // Use a valid user ID from the database
        $this->userId = 3;
        
        // Generate a test token with a short expiry
        $this->testToken = $this->generateShortExpiryToken($this->userId);
    }
    
    public function testTokenGeneration()
    {
        // Verify that the token was generated
        $this->assertNotEmpty($this->testToken);
        
        // Verify that the token has the correct format
        $parts = explode('.', $this->testToken);
        $this->assertCount(3, $parts);
        
        // Verify that the token payload contains the expected data
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertIsArray($payload);
        $this->assertEquals($this->userId, $payload['user_id']);
        $this->assertArrayHasKey('exp', $payload);
    }
    
    public function testTokenValidation()
    {
        // Validate the token
        $payload = Auth::validateToken($this->testToken);
        
        // Verify that the token is valid
        $this->assertIsArray($payload);
        $this->assertEquals($this->userId, $payload['user_id']);
    }
    
    public function testTokenRefresh()
    {
        // Refresh the token
        $newToken = Auth::refreshToken($this->userId, true);
        
        // Verify that the token was refreshed
        $this->assertNotEmpty($newToken);
        
        // Verify that the new token has the correct format
        $parts = explode('.', $newToken);
        $this->assertCount(3, $parts);
        
        // Verify that the new token payload contains the expected data
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertIsArray($payload);
        $this->assertEquals($this->userId, $payload['user_id']);
        $this->assertArrayHasKey('exp', $payload);
        
        // Verify that the new token has a later expiration time
        $this->assertGreaterThan(time(), $payload['exp']);
    }
    
    public function testTokenRefreshWithExpiredToken()
    {
        // Create a token that has already expired
        $expiredToken = $this->generateExpiredToken($this->userId);
        
        // Set up the environment to simulate an expired token
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $expiredToken;
        $GLOBALS['token_expired'] = true;
        
        // Refresh the token
        $newToken = Auth::refreshToken($this->userId, true);
        
        // Verify that the token was refreshed
        $this->assertNotEmpty($newToken);
        
        // Verify that the new token has the correct format
        $parts = explode('.', $newToken);
        $this->assertCount(3, $parts);
        
        // Verify that the new token payload contains the expected data
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $this->assertIsArray($payload);
        $this->assertEquals($this->userId, $payload['user_id']);
        $this->assertArrayHasKey('exp', $payload);
        
        // Verify that the new token has a valid expiration time
        $this->assertGreaterThan(time(), $payload['exp']);
    }
    
    public function testSuccessfulPutAfterRefresh()
    {
        // Create a token that is about to expire
        $almostExpiredToken = $this->generateAlmostExpiredToken($this->userId);
        
        // Set up the environment to simulate a token that's about to expire
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $almostExpiredToken;
        
        // Refresh the token
        $newToken = Auth::refreshToken($this->userId, true);
        
        // Verify that the token was refreshed
        $this->assertNotEmpty($newToken);
        
        // Update the Authorization header with the new token
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $newToken;
        
        // Simulate a PUT request to update a resource
        $db = Database::getInstance();
        $success = false;
        
        try {
            // Start a transaction to avoid actually modifying the database
            $db->beginTransaction();
            
            // Try to update a record (e.g., a user's name)
            $query = "UPDATE users SET name = ? WHERE id = ?";
            $stmt = $db->query($query, ['Test User ' . time(), $this->userId]);
            
            // Check if the update was successful
            $success = $stmt->rowCount() > 0;
            
            // Rollback the transaction to avoid actually modifying the database
            $db->rollback();
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurred
            $db->rollback();
            $this->fail('Exception occurred: ' . $e->getMessage());
        }
        
        // Verify that the update was successful
        $this->assertTrue($success);
    }
    
    private function generateShortExpiryToken($userId)
    {
        // Get user data
        $db = Database::getInstance();
        $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1";
        $stmt = $db->query($query, [$userId]);
        
        if ($stmt->rowCount() === 0) {
            $this->fail("User not found with ID: $userId");
        }
        
        $user = $stmt->fetch();
        
        // Create payload with short expiry (30 seconds)
        $issuedAt = time();
        $payload = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'iat' => $issuedAt,
            'exp' => $issuedAt + 30 // 30 seconds expiry
        ];
        
        // Create JWT header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = $this->base64UrlEncode($header);
        
        // Create JWT payload
        $payloadJson = json_encode($payload);
        $payloadBase64 = $this->base64UrlEncode($payloadJson);
        
        // Create signature
        $signature = hash_hmac('sha256', "$header.$payloadBase64", $this->config['security']['jwt_secret'], true);
        $signature = $this->base64UrlEncode($signature);
        
        // Create JWT token
        return "$header.$payloadBase64.$signature";
    }
    
    private function generateExpiredToken($userId)
    {
        // Get user data
        $db = Database::getInstance();
        $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1";
        $stmt = $db->query($query, [$userId]);
        
        if ($stmt->rowCount() === 0) {
            $this->fail("User not found with ID: $userId");
        }
        
        $user = $stmt->fetch();
        
        // Create payload with expired token
        $issuedAt = time() - 3600; // 1 hour ago
        $payload = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'iat' => $issuedAt,
            'exp' => $issuedAt + 1800 // Expired 30 minutes ago
        ];
        
        // Create JWT header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = $this->base64UrlEncode($header);
        
        // Create JWT payload
        $payloadJson = json_encode($payload);
        $payloadBase64 = $this->base64UrlEncode($payloadJson);
        
        // Create signature
        $signature = hash_hmac('sha256', "$header.$payloadBase64", $this->config['security']['jwt_secret'], true);
        $signature = $this->base64UrlEncode($signature);
        
        // Create JWT token
        return "$header.$payloadBase64.$signature";
    }
    
    private function generateAlmostExpiredToken($userId)
    {
        // Get user data
        $db = Database::getInstance();
        $query = "SELECT id, name, email, role FROM users WHERE id = ? AND active = 1 LIMIT 1";
        $stmt = $db->query($query, [$userId]);
        
        if ($stmt->rowCount() === 0) {
            $this->fail("User not found with ID: $userId");
        }
        
        $user = $stmt->fetch();
        
        // Create payload with token that's about to expire
        $issuedAt = time() - 3570; // 59.5 minutes ago
        $payload = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'iat' => $issuedAt,
            'exp' => time() + 30 // Expires in 30 seconds
        ];
        
        // Create JWT header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = $this->base64UrlEncode($header);
        
        // Create JWT payload
        $payloadJson = json_encode($payload);
        $payloadBase64 = $this->base64UrlEncode($payloadJson);
        
        // Create signature
        $signature = hash_hmac('sha256', "$header.$payloadBase64", $this->config['security']['jwt_secret'], true);
        $signature = $this->base64UrlEncode($signature);
        
        // Create JWT token
        return "$header.$payloadBase64.$signature";
    }
    
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}