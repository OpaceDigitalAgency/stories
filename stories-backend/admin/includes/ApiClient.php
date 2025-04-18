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
        
        // Add authentication token from session or instance
        $token = $_SESSION['token'] ?? '';
        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        } elseif ($this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
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
        } else {
            // For regular JSON data
            $headers[] = 'Content-Type: application/json';
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Add request data for POST and PUT requests
        if (($method === 'POST' || $method === 'PUT') && $data !== null) {
            if ($isFileUpload) {
                // Use raw data for file uploads
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                error_log('API Request: Sending raw form data');
            } else {
                // JSON encode for regular data
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                error_log('API Request: Sending JSON data: ' . json_encode($data));
            }
        }
        
        // Execute request with output capture
        ob_start();                     // capture anything printed by the API
        $response = curl_exec($ch);
        $leak = ob_get_clean();
        if ($leak) {
            error_log('[STRAY OUTPUT] ' . $leak);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
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
            $this->lastError = [
                'type' => 'json_error',
                'message' => $errorMessage,
                'code' => json_last_error()
            ];
            return null;
        }
        
        // Enhanced debugging for specific endpoints
        $debugEndpoints = ['tags', 'blog-posts', 'authors'];
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
            }
        }
        
        // Check for API errors
        if ($httpCode >= 400) {
            $errorMessage = isset($responseData['error']) ? $responseData['error'] : 'Unknown API error';
            $errorDetail = isset($responseData['detail']) ? $responseData['detail'] : '';
            
            // Add more detailed error information based on HTTP code
            if ($httpCode == 404) {
                $errorMessage = 'Resource not found: ' . $url;
                $errorDetail = 'The requested API endpoint does not exist or is not properly configured.';
            } else if ($httpCode == 401) {
                $errorMessage = 'Authentication required';
                $errorDetail = 'Your session may have expired. Please log in again.';
            } else if ($httpCode == 403) {
                $errorMessage = 'Access denied';
                $errorDetail = 'You do not have permission to access this resource.';
            } else if ($httpCode == 500) {
                $errorMessage = 'Server error';
                $errorDetail = 'The API server encountered an internal error. Please try again later or contact support.';
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