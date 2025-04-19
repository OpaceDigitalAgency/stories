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
        error_log("AuthMiddleware: Starting authentication check");
        
        // Initialize Auth with config
        Auth::init($this->config['security']);
        
        // EMERGENCY FIX: Ensure JWT secret is properly set
        global $config;
        if (!isset($GLOBALS['jwt_secret_initialized'])) {
            error_log("EMERGENCY FIX: Ensuring JWT secret is properly set in AuthMiddleware");
            // Force re-initialization with hardcoded secret if needed
            Auth::init([
                'jwt_secret' => 'a8f5e167d9f8b3c2e7b6d4a1c9e8d7f6',
                'token_expiry' => 86400
            ]);
            $GLOBALS['jwt_secret_initialized'] = true;
            error_log("EMERGENCY FIX: JWT secret forcefully set in AuthMiddleware");
        }
        
        // Get token from Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $headerToken = null;
        
        if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $headerToken = $matches[1];
            error_log("AuthMiddleware: Found token in Authorization header");
        } else {
            error_log("AuthMiddleware: No valid Authorization header found");
        }
        
        // Get token from cookie
        $cookieToken = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : null;
        if ($cookieToken) {
            error_log("AuthMiddleware: Found token in cookie");
        } else {
            error_log("AuthMiddleware: No cookie token found");
        }
        
        // Get token from session (if available)
        $sessionToken = isset($_SESSION['token']) ? $_SESSION['token'] : null;
        if ($sessionToken) {
            error_log("AuthMiddleware: Found token in session");
        }
        
        // Determine which token to use
        $token = null;
        
        if ($headerToken && $cookieToken && $headerToken !== $cookieToken) {
            error_log("AuthMiddleware: Token mismatch between header and cookie");
            // Will handle this case below
        } else if ($headerToken) {
            $token = $headerToken;
            error_log("AuthMiddleware: Using header token");
        } else if ($cookieToken) {
            $token = $cookieToken;
            error_log("AuthMiddleware: Using cookie token");
        } else {
            error_log("AuthMiddleware: No token found in any source");
            Response::sendError('Unauthorized. Please log in to access this resource.', 401);
            return false;
        }
        
        // If we have tokens from different sources that don't match, check all of them
        if ($headerToken && $cookieToken && $headerToken !== $cookieToken) {
            error_log("AuthMiddleware: Token mismatch between header and cookie");
            
            // Validate both tokens
            $headerPayload = Auth::validateToken($headerToken);
            $cookiePayload = Auth::validateToken($cookieToken);
            
            error_log("AuthMiddleware: Header token valid: " . ($headerPayload ? "Yes" : "No"));
            error_log("AuthMiddleware: Cookie token valid: " . ($cookiePayload ? "Yes" : "No"));
            
            // If both are valid, use the newer one (later expiration)
            if ($headerPayload && $cookiePayload) {
                error_log("AuthMiddleware: Both tokens are valid, checking expiration");
                error_log("AuthMiddleware: Header token expires at: " . date('Y-m-d H:i:s', $headerPayload['exp']));
                error_log("AuthMiddleware: Cookie token expires at: " . date('Y-m-d H:i:s', $cookiePayload['exp']));
                
                if ($headerPayload['exp'] > $cookiePayload['exp']) {
                    error_log("AuthMiddleware: Using header token (newer)");
                    $payload = $headerPayload;
                    $token = $headerToken;
                    
                    // Update cookie with header token
                    setcookie('auth_token', $token, $headerPayload['exp'], '/', '', false, true);
                    error_log("AuthMiddleware: Updated cookie with header token");
                    
                    // Set X-Token-Updated header to notify client
                    header('X-Token-Updated: true');
                } else {
                    error_log("AuthMiddleware: Using cookie token (newer)");
                    $payload = $cookiePayload;
                    $token = $cookieToken;
                    
                    // Set X-Token-Updated header to notify client
                    header('X-Token-Updated: true');
                }
            } else if ($headerPayload) {
                error_log("AuthMiddleware: Only header token is valid");
                $payload = $headerPayload;
                $token = $headerToken;
                
                // Update cookie with header token
                setcookie('auth_token', $token, $headerPayload['exp'], '/', '', false, true);
                error_log("AuthMiddleware: Updated cookie with header token");
                
                // Set X-Token-Updated header to notify client
                header('X-Token-Updated: true');
            } else if ($cookiePayload) {
                error_log("AuthMiddleware: Only cookie token is valid");
                $payload = $cookiePayload;
                $token = $cookieToken;
                
                // Set X-Token-Updated header to notify client
                header('X-Token-Updated: true');
            } else {
                error_log("AuthMiddleware: Neither token is valid");
                $payload = null;
            }
        } else {
            // Validate the token
            $payload = Auth::validateToken($token);
            error_log("AuthMiddleware: Token validation result: " . ($payload ? "Valid" : "Invalid"));
        }
        
        // If token is invalid, check if it's expired and try to refresh
        if (!$payload) {
            error_log("AuthMiddleware: Token validation failed, checking if expired");
            
            // Check if token_expired global was set by Auth::validateToken
            $tokenExpired = isset($GLOBALS['token_expired']) && $GLOBALS['token_expired'];
            
            // Even if the global isn't set, try to decode the token to check expiration
            if (!$tokenExpired) {
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    try {
                        $decodedPayload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        if ($decodedPayload && isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
                            $tokenExpired = true;
                            error_log("AuthMiddleware: Token is expired based on payload check");
                        }
                    } catch (\Exception $e) {
                        error_log("AuthMiddleware: Error checking token expiration: " . $e->getMessage());
                    }
                }
            }
            
            if ($tokenExpired) {
                error_log("AuthMiddleware: Token expired, attempting to refresh");
                
                // Try to decode the token to get the user ID
                $parts = explode('.', $token);
                if (count($parts) === 3) {
                    try {
                        $decodedPayload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        
                        if ($decodedPayload && isset($decodedPayload['user_id'])) {
                            $userId = $decodedPayload['user_id'];
                            error_log("AuthMiddleware: Extracted user ID from token: $userId");
                            
                            // Attempt to refresh the token
                            $newToken = Auth::refreshToken($userId, true); // Force refresh
                            
                            if ($newToken) {
                                error_log("AuthMiddleware: Token refreshed successfully");
                                
                                // Set the new token in the response headers
                                header('X-New-Token: ' . $newToken);
                                
                                // Update cookie with new token
                                $newPayload = Auth::validateToken($newToken);
                                if ($newPayload && isset($newPayload['exp'])) {
                                    setcookie('auth_token', $newToken, $newPayload['exp'], '/', '', false, true);
                                    error_log("AuthMiddleware: Updated cookie with new token, expires at: " . date('Y-m-d H:i:s', $newPayload['exp']));
                                }
                                
                                // Use the new token's payload
                                $payload = $newPayload;
                                
                                // Set X-Token-Refreshed header to notify client
                                header('X-Token-Refreshed: true');
                            } else {
                                error_log("AuthMiddleware: Token refresh failed");
                            }
                        } else {
                            error_log("AuthMiddleware: Could not extract user ID from token");
                        }
                    } catch (\Exception $e) {
                        error_log("AuthMiddleware: Error decoding token: " . $e->getMessage());
                    }
                }
            } else {
                error_log("AuthMiddleware: Token is invalid but not expired");
            }
            
            // If payload is still null after refresh attempt, return error
            if (!$payload) {
                error_log("AuthMiddleware: Invalid token, authentication failed");
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
