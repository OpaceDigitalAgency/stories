<?php
/**
 * API Client Class
 * 
 * This class handles API requests to the backend.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class ApiClient {
    /**
     * @var string API URL
     */
    private $apiUrl;
    
    /**
     * @var string|null Authentication token
     */
    private $authToken;
    
    /**
     * @var array Last error details
     */
    private $lastError = [];
    
    /**
     * Constructor
     * 
     * @param string $apiUrl API URL
     * @param string|null $authToken Authentication token
     */
    public function __construct($apiUrl, $authToken = null) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->authToken = $authToken;
    }
    
    /**
     * Make a GET request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array|null Response data
     */
    public function get($endpoint, $params = []) {
        return $this->request('GET', $endpoint, $params);
    }
    
    /**
     * Make a POST request
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param array $params Query parameters
     * @return array|null Response data
     */
    public function post($endpoint, $data = [], $params = []) {
        return $this->request('POST', $endpoint, $params, $data);
    }
    
    /**
     * Make a PUT request
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param array $params Query parameters
     * @return array|null Response data
     */
    public function put($endpoint, $data = [], $params = []) {
        return $this->request('PUT', $endpoint, $params, $data);
    }
    
    /**
     * Make a DELETE request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array|null Response data
     */
    public function delete($endpoint, $params = []) {
        return $this->request('DELETE', $endpoint, $params);
    }
    
    /**
     * Make an API request
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param array $data Request data
     * @return array|null Response data
     */
    private function request($method, $endpoint, $params = [], $data = null) {
        // Build URL - ensure no double slashes
        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');
        
        // Add query parameters
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Log the request for debugging
        error_log("API Request: $method $url");
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Set headers
        $headers = [
            'Accept: application/json'
        ];
        
        // Add authentication token from session, cookie, or instance
        $token = $_SESSION['token'] ?? '';
        $cookieToken = isset($_COOKIE['auth_token']) ? $_COOKIE['auth_token'] : '';
        
        // Debug token information
        error_log("API Request Auth - Session token: " . (!empty($token) ? "Present" : "Missing"));
        error_log("API Request Auth - Cookie token: " . (!empty($cookieToken) ? "Present" : "Missing"));
        error_log("API Request Auth - Instance token: " . ($this->authToken ? "Present" : "Missing"));
        
        // Use token in this priority: session, cookie, instance
        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
            error_log("API Request Auth - Using session token");
        } elseif (!empty($cookieToken)) {
            $headers[] = 'Authorization: Bearer ' . $cookieToken;
            error_log("API Request Auth - Using cookie token");
            // Store in session for consistency
            $_SESSION['token'] = $cookieToken;
        } elseif ($this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
            error_log("API Request Auth - Using instance token");
            // Store in session for consistency
            $_SESSION['token'] = $this->authToken;
        } else {
            error_log("API Request Auth - WARNING: No authentication token available");
        }
        
        // Check if we're dealing with a file upload
        $isFileUpload = false;
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) && isset($value['tmp_name']) && file_exists($value['tmp_name'])) {
                    $isFileUpload = true;
                    break;
                }
            }
        }
        
        // Set content type based on data
        if ($isFileUpload) {
            // Don't set Content-Type for multipart/form-data, cURL will set it with boundary
            error_log('API Request: File upload detected, using multipart/form-data');
            
            // Add debug header to help track file uploads
            $headers[] = 'X-Debug-Upload: true';
        } else {
            // For regular JSON data
            $headers[] = 'Content-Type: application/json';
            
            // Add request ID for tracking
            $requestId = uniqid('req_');
            $headers[] = 'X-Request-ID: ' . $requestId;
            error_log("API Request ID: $requestId - $method $url");
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Add request data for POST and PUT requests
        if (($method === 'POST' || $method === 'PUT' || $method === 'DELETE') && $data !== null) {
            if ($isFileUpload) {
                // Use raw data for file uploads
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                error_log('API Request: Sending raw form data');
                
                // Log file information for debugging
                foreach ($data as $key => $value) {
                    if (is_array($value) && isset($value['tmp_name'])) {
                        error_log("API Request: File upload field '$key', size: " . filesize($value['tmp_name']) . " bytes");
                    }
                }
            } else {
                // JSON encode for regular data
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                error_log('API Request: Sending JSON data: ' . $jsonData);
            }
        }
        
        // For DELETE requests, ensure the method is properly set
        if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            error_log('API Request: Using DELETE method');
        }
        
        // Set verbose debugging
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        // Execute request with output capture
        ob_start();                     // capture anything printed by the API
        $response = curl_exec($ch);
        $leak = ob_get_clean();
        if ($leak) {
            error_log('[STRAY OUTPUT] ' . $leak);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Get verbose debug information
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);
        
        // Log verbose output for debugging
        error_log("API Request Verbose Log: " . $verboseLog);
        
        // Log request and response timing
        $info = curl_getinfo($ch);
        error_log(sprintf(
            "API Request Timing: Connect: %2.2f s, Total: %2.2f s, Speed: %2.2f bytes/s",
            $info['connect_time'],
            $info['total_time'],
            $info['speed_download']
        ));
        
        // Check for errors
        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            error_log('API request error: ' . $errorMessage);
            $this->lastError = [
                'type' => 'curl_error',
                'message' => $errorMessage,
                'code' => curl_errno($ch)
            ];
            curl_close($ch);
            return null;
        }
        
        // Close cURL
        curl_close($ch);
        
        // Parse response
        $responseData = json_decode($response, true);
        
        // Log the response for debugging
        error_log("API Response: $httpCode - " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''));
        
        // Check for JSON parsing errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMessage = json_last_error_msg();
            error_log('API response JSON parsing error: ' . $errorMessage);
            error_log('Raw response: ' . $response);
            $this->lastError = [
                'type' => 'json_error',
                'message' => $errorMessage,
                'code' => json_last_error()
            ];
            return null;
        }
        
        // Enhanced debugging for specific endpoints
        $debugEndpoints = ['tags', 'blog-posts', 'authors', 'stories'];
        $isDebugEndpoint = false;
        foreach ($debugEndpoints as $endpoint) {
            if (strpos($url, $endpoint) !== false) {
                $isDebugEndpoint = true;
                break;
            }
        }
        
        if ($isDebugEndpoint) {
            error_log("DETAILED API RESPONSE for $url: " . json_encode($responseData, JSON_PRETTY_PRINT));
            
            // Check if response has the expected structure
            if (isset($responseData['data'])) {
                error_log("RESPONSE HAS DATA KEY");
                
                // Check if data has attributes
                if (isset($responseData['data']['attributes'])) {
                    error_log("DATA HAS ATTRIBUTES KEY");
                } else {
                    error_log("DATA MISSING ATTRIBUTES KEY");
                    
                    // Try to fix the response structure
                    if (!empty($responseData['data'])) {
                        if (!isset($responseData['data']['attributes']) && is_array($responseData['data'])) {
                            // If data is an array but doesn't have attributes, create it
                            if (!isset($responseData['data']['id']) && isset($responseData['data'][0])) {
                                // This is a collection, don't modify
                                error_log("DATA IS A COLLECTION, NOT MODIFYING");
                            } else {
                                // This is a single item, add attributes if missing
                                error_log("ADDING MISSING ATTRIBUTES TO DATA");
                                $id = $responseData['data']['id'] ?? null;
                                $attributes = [];
                                
                                // Move non-special fields to attributes
                                foreach ($responseData['data'] as $key => $value) {
                                    if (!in_array($key, ['id', 'type', 'links', 'meta', 'relationships', 'attributes'])) {
                                        $attributes[$key] = $value;
                                    }
                                }
                                
                                // Only modify if we have attributes to add
                                if (!empty($attributes)) {
                                    $responseData['data']['attributes'] = $attributes;
                                    error_log("MODIFIED RESPONSE: " . json_encode($responseData, JSON_PRETTY_PRINT));
                                }
                            }
                        }
                    }
                }
            } else {
                error_log("RESPONSE MISSING DATA KEY");
                
                // Try to fix the response structure if it's missing the data key
                if (!empty($responseData) && isset($responseData['id'])) {
                    error_log("ADDING MISSING DATA KEY");
                    $responseData = ['data' => $responseData];
                    error_log("MODIFIED RESPONSE: " . json_encode($responseData, JSON_PRETTY_PRINT));
                }
            }
        }
        
        // Check for API errors
        if ($httpCode >= 400) {
            $errorMessage = isset($responseData['error']) ? $responseData['error'] : 'Unknown API error';
            $errorDetail = isset($responseData['detail']) ? $responseData['detail'] : '';
            
            // Check for message in different formats
            if (empty($errorMessage) && isset($responseData['message'])) {
                $errorMessage = $responseData['message'];
            }
            
            // Check for errors array
            if (empty($errorMessage) && isset($responseData['errors']) && is_array($responseData['errors'])) {
                if (isset($responseData['errors'][0])) {
                    $errorMessage = $responseData['errors'][0];
                } else {
                    $errorMessage = 'Validation errors occurred';
                    $errorDetail = json_encode($responseData['errors']);
                }
            }
            
            // Add more detailed error information based on HTTP code
            if ($httpCode == 404) {
                $errorMessage = 'Resource not found: ' . $url;
                $errorDetail = 'The requested API endpoint does not exist or is not properly configured.';
            } else if ($httpCode == 401) {
                $errorMessage = 'Authentication required';
                $errorDetail = 'Your session may have expired. Please log in again.';
                
                // Log token information for debugging authentication issues
                error_log("AUTH ERROR: Session token: " . (isset($_SESSION['token']) ? "Present" : "Missing"));
                error_log("AUTH ERROR: Instance token: " . ($this->authToken ? "Present" : "Missing"));
                
                // Check for token expiration
                if (isset($responseData['message']) && strpos($responseData['message'], 'expired') !== false) {
                    $errorDetail = 'Your authentication token has expired.';
                    
                    // Try to refresh the token
                    $refreshed = $this->refreshToken();
                    if ($refreshed) {
                        // Retry the request with the new token
                        error_log("AUTH: Token refreshed, retrying request");
                        return $this->request($method, $endpoint, $params, $data);
                    } else {
                        $errorDetail .= ' Please log in again.';
                        
                        // Clear the expired tokens
                        if (isset($_SESSION['token'])) {
                            unset($_SESSION['token']);
                            error_log("AUTH ERROR: Cleared expired session token");
                        }
                        if (isset($_COOKIE['auth_token'])) {
                            setcookie('auth_token', '', time() - 3600, '/', '', false, true);
                            error_log("AUTH ERROR: Cleared expired cookie token");
                        }
                    }
                }
            } else if ($httpCode == 403) {
                $errorMessage = 'Access denied';
                $errorDetail = 'You do not have permission to access this resource.';
            } else if ($httpCode == 500) {
                $errorMessage = 'Server error';
                $errorDetail = 'The API server encountered an internal error. Please try again later or contact support.';
                
                // Add more detailed error information for 500 errors
                error_log("SERVER ERROR (500) DETAILS: " . $response);
                
                // Try to extract more information from the response
                if (strpos($response, 'PHP Fatal error') !== false ||
                    strpos($response, 'PHP Parse error') !== false ||
                    strpos($response, 'PHP Warning') !== false) {
                    preg_match('/PHP .*?: (.+?) in /', $response, $matches);
                    if (!empty($matches[1])) {
                        $errorDetail .= ' PHP Error: ' . $matches[1];
                    }
                }
            } else if ($httpCode == 422) {
                $errorMessage = 'Validation error';
                
                // Try to extract validation errors
                if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                    $errorDetail = 'Please check the following fields: ';
                    $fields = array_keys($responseData['errors']);
                    $errorDetail .= implode(', ', $fields);
                }
            }
            
            error_log('API error: ' . $errorMessage . ($errorDetail ? ' - ' . $errorDetail : '') . ' - URL: ' . $url);
            $this->lastError = [
                'type' => 'api_error',
                'message' => $errorMessage,
                'detail' => $errorDetail,
                'code' => $httpCode,
                'url' => $url,
                'response' => $responseData
            ];
            return null;
        }
        
        // Clear last error if request was successful
        $this->lastError = [];
        
        return $responseData;
    }
    
    /**
     * Attempt to refresh the authentication token
     *
     * @return bool True if token was refreshed successfully
     */
    private function refreshToken() {
        error_log("Attempting to refresh authentication token");
        
        // Get user ID from session if available
        $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
        
        if (!$userId) {
            error_log("Cannot refresh token: No user ID in session");
            return false;
        }
        
        try {
            // Create a new API client without authentication for the refresh request
            $tempClient = new ApiClient($this->apiUrl);
            
            // Call the refresh token endpoint
            $response = $tempClient->post('auth/refresh', [
                'user_id' => $userId
            ]);
            
            if ($response && isset($response['token'])) {
                $newToken = $response['token'];
                
                // Update session token
                $_SESSION['token'] = $newToken;
                
                // Update cookie token
                $cookieExpiry = isset($response['expires_in']) ? time() + $response['expires_in'] : time() + 3600;
                setcookie('auth_token', $newToken, $cookieExpiry, '/', '', false, true);
                
                // Update instance token
                $this->authToken = $newToken;
                
                error_log("Token refreshed successfully");
                return true;
            }
            
            error_log("Token refresh failed: Invalid response");
            return false;
        } catch (Exception $e) {
            error_log("Token refresh error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the last error
     *
     * @return array|null Last error details or null if no error
     */
    public function getLastError() {
        return !empty($this->lastError) ? $this->lastError : null;
    }
    
    /**
     * Get a formatted error message from the last error
     *
     * @return string Formatted error message
     */
    public function getFormattedError() {
        if (empty($this->lastError)) {
            return '';
        }
        
        $error = $this->lastError;
        
        switch ($error['type']) {
            case 'curl_error':
                return "Connection error: {$error['message']} (Code: {$error['code']})";
            
            case 'json_error':
                return "Response parsing error: {$error['message']}";
            
            case 'api_error':
                $message = "API error: {$error['message']} (Status: {$error['code']})";
                if (!empty($error['detail'])) {
                    $message .= " - {$error['detail']}";
                }
                
                // Add troubleshooting tips based on error code
                if ($error['code'] == 404) {
                    $message .= "\nTroubleshooting: Check that the API endpoint exists and is correctly configured in config.php.";
                } else if ($error['code'] == 401) {
                    $message .= "\nTroubleshooting: Try logging out and logging back in to refresh your session.";
                } else if ($error['code'] == 500) {
                    $message .= "\nTroubleshooting: Check the server logs for more details or contact the administrator.";
                }
                
                return $message;
            
            default:
                return "Unknown error occurred";
        }
    }
}
