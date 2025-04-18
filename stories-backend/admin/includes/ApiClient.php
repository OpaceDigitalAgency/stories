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
     * @var int|null Last HTTP status code
     */
    private $lastStatusCode = null;

    /**
     * @var string|null Last raw response body
     */
    private $lastRawResponse = null;
    
    /**
     * Constructor
     * 
     * @param string $apiUrl API URL
     * @param string|null $authToken Authentication token
     */
    public function __construct($apiUrl = null, $authToken = null) {
        // Use provided URL or fall back to config
        if (!$apiUrl && defined('API_URL')) {
            $apiUrl = API_URL;
            error_log("Using API_URL from config: " . $apiUrl);
        } elseif (!$apiUrl) {
            // Construct URL from current server if no URL provided
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $apiUrl = $scheme . '://' . $host . '/api/v1';
            error_log("Constructed API URL: " . $apiUrl);
        }

        $this->apiUrl = rtrim($apiUrl, '/');
        $this->authToken = $authToken;
        
        error_log("ApiClient initialized with URL: " . $this->apiUrl);
        error_log("Auth token status: " . ($authToken ? "Present" : "Missing"));
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
        
        // Log the request details
        error_log("API Request Details:");
        error_log("- Method: $method");
        error_log("- URL: $url");
        error_log("- Params: " . json_encode($params));
        if ($data !== null) {
            error_log("- Data: " . json_encode($data));
        }
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Set timeout to prevent hanging requests
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds connection timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds total timeout
        
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
        
        // Get the best available token
        $activeToken = null;
        
        // Try instance token first as it's most reliable
        if ($this->authToken) {
            $activeToken = $this->authToken;
            error_log("API Request Auth - Using instance token");
        }
        // Then try session token
        elseif (!empty($token)) {
            $activeToken = $token;
            error_log("API Request Auth - Using session token");
        }
        // Finally try cookie token
        elseif (!empty($cookieToken)) {
            $activeToken = $cookieToken;
            error_log("API Request Auth - Using cookie token");
        }
        
        // If we have a token, use it and ensure consistency
        if ($activeToken) {
            $headers[] = 'Authorization: Bearer ' . $activeToken;
            
            // Ensure token is consistent across storage methods
            $_SESSION['token'] = $activeToken;
            $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            setcookie('auth_token', $activeToken, time() + 86400, '/', '', $secure, true);
            
            error_log("API Request Auth - Token synchronized across storage methods");
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
        error_log("API Request: Executing cURL request to $url");
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        error_log("API Request: cURL execution completed in $executionTime seconds");
        
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
        
        // Store last status code and raw response
        $this->lastStatusCode = $httpCode;
        $this->lastRawResponse = $response;

        // Parse response
        $responseData = json_decode($response, true);
        
        // Log detailed response information
        error_log("API Response Details:");
        error_log("- Status Code: $httpCode");
        error_log("- Raw Response: " . substr($response, 0, 500) . (strlen($response) > 500 ? '...' : ''));
        error_log("- Parsed Response: " . json_encode($responseData));
        
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
                error_log("AUTH ERROR: Cookie token: " . (isset($_COOKIE['auth_token']) ? "Present" : "Missing"));
                error_log("AUTH ERROR: Instance token: " . ($this->authToken ? "Present" : "Missing"));
                
                // Always attempt to refresh the token on 401 errors
                // This handles both explicit expiration messages and other auth failures
                error_log("AUTH: Attempting token refresh due to 401 error");
                $refreshed = $this->refreshToken();
                
                if ($refreshed) {
                    // Retry the request with the new token
                    error_log("AUTH: Token refreshed successfully, retrying request");
                    return $this->request($method, $endpoint, $params, $data);
                } else {
                    $errorDetail = 'Your authentication token has expired or is invalid. Please log in again.';
                    
                    // Clear the expired tokens
                    if (isset($_SESSION['token'])) {
                        unset($_SESSION['token']);
                        error_log("AUTH ERROR: Cleared expired session token");
                    }
                    if (isset($_COOKIE['auth_token'])) {
                        setcookie('auth_token', '', time() - 3600, '/', '', true, true);
                        error_log("AUTH ERROR: Cleared expired cookie token");
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
                    $errorDetails = [];
                    foreach ($responseData['errors'] as $field => $errors) {
                        if (is_array($errors)) {
                            foreach ($errors as $error) {
                                $errorDetails[] = "$field: $error";
                            }
                        } else {
                            $errorDetails[] = "$field: $errors";
                        }
                    }
                    $errorDetail = implode("\n", $errorDetails);
                    
                    // Log validation errors for debugging
                    error_log("Validation errors:\n" . $errorDetail);
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
        error_log("REFRESH TOKEN DEBUG: " . date('Y-m-d H:i:s'));
        error_log("REFRESH TOKEN DEBUG: " . date('Y-m-d H:i:s'));
        
        try {
            // Get the current active token
            $currentToken = $this->authToken ?? $_SESSION['token'] ?? $_COOKIE['auth_token'] ?? null;
            
            if (!$currentToken) {
                error_log("Cannot refresh token: No active token found");
                return false;
            }
            
            // Try to extract user ID from the token
            $userId = null;
            $parts = explode('.', $currentToken);
            if (count($parts) === 3) {
                try {
                    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                    if ($payload && isset($payload['user_id'])) {
                        $userId = $payload['user_id'];
                        error_log("Extracted user ID from token: $userId");
                    }
                } catch (\Exception $e) {
                    error_log("Error decoding token payload: " . $e->getMessage());
                }
            }
            
            if (!$userId) {
                // Try to get user ID from session
                if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                    $userId = $_SESSION['user']['id'];
                    error_log("Using user ID from session: $userId");
                } else {
                    error_log("Cannot refresh token: Unable to determine user ID");
                    return false;
                }
            }
            
            // Initialize cURL for the refresh request
            $ch = curl_init();
            $url = rtrim($this->apiUrl, '/') . '/auth/refresh';
            
            error_log("Token refresh URL: " . $url);
            
            // Set up the refresh request
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $currentToken
                ]
            ]);
            
            // Prepare request data with user ID and force refresh
            $requestData = [
                'user_id' => $userId,
                'force' => true,
                'threshold' => 60 // 1 minute threshold
            ];
            
            $jsonData = json_encode($requestData);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            
            error_log("Token refresh request data: " . $jsonData);
            error_log("Token refresh user ID: " . $userId);
            
            // Set verbose debugging
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
            
            // Execute refresh request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Get verbose debug information
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            fclose($verbose);
            
            // Log verbose output for debugging
            error_log("Token refresh verbose log: " . $verboseLog);
            
            // Check for cURL errors
            if (curl_errno($ch)) {
                error_log("Token refresh cURL error: " . curl_error($ch));
                curl_close($ch);
                return false;
            }
            
            curl_close($ch);
            
            // Parse response
            $responseData = json_decode($response, true);
            
            // Log the response for debugging
            error_log("Token refresh response (HTTP $httpCode): " . substr($response, 0, 500));
            error_log("Token refresh parsed response: " . json_encode($responseData));
            
            if ($httpCode === 200) {
                // Check if token was actually refreshed
                if (isset($responseData['refreshed']) && $responseData['refreshed'] === false) {
                    error_log("Token refresh skipped: Current token is still valid");
                    return true; // Token is still valid, no need to refresh
                }
                
                // Check if we have a new token
                if (isset($responseData['token'])) {
                    $newToken = $responseData['token'];
                    
                    // Update token in all storage locations
                    $this->authToken = $newToken;
                    $_SESSION['token'] = $newToken;
                    
                    // Set cookie with appropriate expiry
                    $cookieExpiry = isset($responseData['expires_in']) ? time() + $responseData['expires_in'] : time() + 86400;
                    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                    setcookie('auth_token', $newToken, $cookieExpiry, '/', '', $secure, true);
                    
                    error_log("Token refresh successful");
                    return true;
                }
            }
            
            error_log("Token refresh failed: Invalid response (HTTP $httpCode)");
            error_log("Response: " . $response);
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

    /**
     * Get the last HTTP status code from the most recent request.
     *
     * @return int|null
     */
    public function getLastStatusCode() {
        return $this->lastStatusCode;
    }

    /**
     * Get the last raw response body from the most recent request.
     *
     * @return string|null
     */
    public function getLastRawResponse() {
        return $this->lastRawResponse;
    }
}
