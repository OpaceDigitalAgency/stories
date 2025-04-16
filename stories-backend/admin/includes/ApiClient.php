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
        // Build URL
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');
        
        // Add query parameters
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        // Set headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Add authentication token if available
        if ($this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Add request data for POST and PUT requests
        if (($method === 'POST' || $method === 'PUT') && $data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        // Execute request
        $response = curl_exec($ch);
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
        
        // Check for API errors
        if ($httpCode >= 400) {
            $errorMessage = isset($responseData['error']) ? $responseData['error'] : 'Unknown API error';
            $errorDetail = isset($responseData['detail']) ? $responseData['detail'] : '';
            error_log('API error: ' . $errorMessage . ($errorDetail ? ' - ' . $errorDetail : ''));
            $this->lastError = [
                'type' => 'api_error',
                'message' => $errorMessage,
                'detail' => $errorDetail,
                'code' => $httpCode,
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
                return $message;
            
            default:
                return "Unknown error occurred";
        }
    }
}