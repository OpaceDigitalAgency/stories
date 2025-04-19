<?php
/**
 * Token Refresh Test
 * 
 * This file contains tests for the JWT token refresh functionality.
 * 
 * @package Stories API Tests
 * @version 1.0.0
 */

namespace StoriesAPI\Tests\Auth;

use PHPUnit\Framework\TestCase;
use StoriesAPI\Core\Auth;
use StoriesAPI\Core\Database;

class TokenRefreshTest extends TestCase
{
    /**
     * @var array Config
     */
    private $config;
    
    /**
     * @var \PDO Database connection
     */
    private $db;
    
    /**
     * Set up the test
     */
    protected function setUp(): void
    {
        // Load config
        $this->config = require __DIR__ . '/../../api/v1/config/config.php';
        
        // Initialize Auth
        Auth::init($this->config['security']);
        
        // Initialize Database
        $this->db = Database::getInstance($this->config['db']);
    }
    
    /**
     * Test token generation and validation
     */
    public function testTokenGenerationAndValidation()
    {
        // Generate a token
        $token = Auth::generateToken([
            'user_id' => 1,
            'role' => 'admin'
        ]);
        
        // Validate the token
        $payload = Auth::validateToken($token);
        
        // Assert that the token is valid
        $this->assertIsArray($payload);
        $this->assertEquals(1, $payload['user_id']);
        $this->assertEquals('admin', $payload['role']);
    }
    
    /**
     * Test token expiration
     */
    public function testTokenExpiration()
    {
        // Generate a token that expires in 1 second
        $token = $this->generateShortLivedToken(1);
        
        // Sleep for 2 seconds to ensure the token expires
        sleep(2);
        
        // Validate the token
        $payload = Auth::validateToken($token);
        
        // Assert that the token is invalid
        $this->assertFalse($payload);
        $this->assertTrue(isset($GLOBALS['token_expired']));
    }
    
    /**
     * Test token refresh
     */
    public function testTokenRefresh()
    {
        // Create a test user if it doesn't exist
        $this->createTestUserIfNotExists();
        
        // Generate a token that expires in 5 seconds
        $token = $this->generateShortLivedToken(5);
        
        // Get the payload
        $payload = Auth::validateToken($token);
        $userId = $payload['user_id'];
        
        // Refresh the token
        $newToken = Auth::refreshToken($userId);
        
        // Assert that the new token is valid
        $this->assertIsString($newToken);
        $this->assertNotEquals($token, $newToken);
        
        // Validate the new token
        $newPayload = Auth::validateToken($newToken);
        
        // Assert that the new token has the same user_id and role
        $this->assertEquals($payload['user_id'], $newPayload['user_id']);
        $this->assertEquals($payload['role'], $newPayload['role']);
        
        // Assert that the new token has a later expiration time
        $this->assertGreaterThan($payload['exp'], $newPayload['exp']);
    }
    
    /**
     * Test token refresh with expiration check
     */
    public function testTokenRefreshWithExpirationCheck()
    {
        // Create a test user if it doesn't exist
        $this->createTestUserIfNotExists();
        
        // Generate a token that expires in 60 seconds
        $token = $this->generateShortLivedToken(60);
        
        // Get the payload
        $payload = Auth::validateToken($token);
        $userId = $payload['user_id'];
        
        // Set the token in the Authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        // Try to refresh the token with expiration check (threshold = 30 seconds)
        // This should return false because the token is not about to expire
        $newToken = Auth::refreshToken($userId, true, 30);
        
        // Assert that no new token was generated
        $this->assertFalse($newToken);
        
        // Generate a token that expires in 20 seconds
        $token = $this->generateShortLivedToken(20);
        
        // Set the token in the Authorization header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        // Try to refresh the token with expiration check (threshold = 30 seconds)
        // This should return a new token because the token is about to expire
        $newToken = Auth::refreshToken($userId, true, 30);
        
        // Assert that a new token was generated
        $this->assertIsString($newToken);
        $this->assertNotEquals($token, $newToken);
    }
    
    /**
     * Generate a short-lived token for testing
     * 
     * @param int $expiresIn Expiration time in seconds
     * @return string The generated token
     */
    private function generateShortLivedToken($expiresIn)
    {
        // Create a custom payload with a short expiration time
        $issuedAt = time();
        $payload = [
            'user_id' => 1,
            'role' => 'admin',
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expiresIn
        ];
        
        // Create JWT header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = $this->base64UrlEncode($header);
        
        // Create JWT payload
        $payloadJson = json_encode($payload);
        $payloadEncoded = $this->base64UrlEncode($payloadJson);
        
        // Create signature
        $signature = hash_hmac('sha256', "$header.$payloadEncoded", $this->config['security']['jwt_secret'], true);
        $signature = $this->base64UrlEncode($signature);
        
        // Create JWT token
        return "$header.$payloadEncoded.$signature";
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Base64 URL encoded data
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Create a test user if it doesn't exist
     */
    private function createTestUserIfNotExists()
    {
        // Check if the test user exists
        $query = "SELECT id FROM users WHERE email = 'test@example.com' LIMIT 1";
        $stmt = $this->db->query($query);
        
        if ($stmt->rowCount() === 0) {
            // Create the test user
            $query = "INSERT INTO users (name, email, password, role, active, created_at) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($query, [
                'Test User',
                'test@example.com',
                password_hash('password', PASSWORD_BCRYPT),
                'admin',
                1,
                date('Y-m-d H:i:s')
            ]);
        }
    }
}