<?php
namespace StoriesAPI\Middleware;

use StoriesAPI\Utils\Response;
use StoriesAPI\Core\Auth;

class AuthMiddleware {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function handle() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            error_log("AuthMiddleware: No valid Authorization header found");
            Response::sendError('Unauthorized. Please log in to access this resource.', 401);
            return false;
        }
        
        $token = $matches[1];
        Auth::init($this->config['security']);
        
        // Check for token in cookie for consistency
        $cookieToken = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
        
        // If we have both tokens but they differ, check both
        if ($cookieToken && $token !== $cookieToken) {
            error_log("AuthMiddleware: Token mismatch between header and cookie");
            
            // Validate both tokens
            $headerPayload = Auth::validateToken($token);
            $cookiePayload = Auth::validateToken($cookieToken);
            
            // If both are valid, use the newer one (later expiration)
            if ($headerPayload && $cookiePayload) {
                error_log("AuthMiddleware: Both tokens are valid, checking expiration");
                
                if ($headerPayload['exp'] > $cookiePayload['exp']) {
                    error_log("AuthMiddleware: Using header token (newer)");
                    $payload = $headerPayload;
                    // Update cookie with header token
                    setcookie('auth_token', $token, $headerPayload['exp'], '/', '', false, true);
                } else {
                    error_log("AuthMiddleware: Using cookie token (newer)");
                    $payload = $cookiePayload;
                    $token = $cookieToken;
                }
            } else if ($headerPayload) {
                error_log("AuthMiddleware: Only header token is valid");
                $payload = $headerPayload;
                // Update cookie with header token
                setcookie('auth_token', $token, $headerPayload['exp'], '/', '', false, true);
            } else if ($cookiePayload) {
                error_log("AuthMiddleware: Only cookie token is valid");
                $payload = $cookiePayload;
                $token = $cookieToken;
            } else {
                $payload = null;
            }
        } else {
            // Validate the token
            $payload = Auth::validateToken($token);
        }
        
        // If token is invalid, check if it's expired and try to refresh
        if (!$payload) {
            // Check if token_expired global was set by Auth::validateToken
            if (isset($GLOBALS['token_expired']) && $GLOBALS['token_expired']) {
                error_log("AuthMiddleware: Token expired, attempting to refresh");
                
                // Try to decode the token to get the user ID
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    try {
                        $decodedPayload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        
                        if ($decodedPayload && isset($decodedPayload['user_id'])) {
                            $userId = $decodedPayload['user_id'];
                            
                            // Attempt to refresh the token
                            $newToken = Auth::refreshToken($userId);
                            
                            if ($newToken) {
                                error_log("AuthMiddleware: Token refreshed successfully");
                                
                                // Set the new token in the response headers
                                header('X-New-Token: ' . $newToken);
                                
                                // Update cookie with new token
                                $newPayload = Auth::validateToken($newToken);
                                if ($newPayload && isset($newPayload['exp'])) {
                                    setcookie('auth_token', $newToken, $newPayload['exp'], '/', '', false, true);
                                }
                                
                                // Use the new token's payload
                                $payload = $newPayload;
                            } else {
                                error_log("AuthMiddleware: Token refresh failed");
                            }
                        }
                    } catch (\Exception $e) {
                        error_log("AuthMiddleware: Error decoding token: " . $e->getMessage());
                    }
                }
            }
            
            // If payload is still null after refresh attempt, return error
            if (!$payload) {
                error_log("AuthMiddleware: Invalid token");
                Response::sendError('Invalid or expired token. Please log in again.', 401);
                return false;
            }
        }
        
        $_REQUEST['user'] = [
            'id' => $payload['user_id'],
            'role' => $payload['role']
        ];
        
        error_log("AuthMiddleware: Token validated for user {$payload['user_id']}");
        return true;
    }
}
