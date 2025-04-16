<?php
/**
 * API Client Utility Class
 * 
 * This class provides methods for making requests to the API.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

class ApiClient {
    /**
     * @var string API base URL
     */
    private $baseUrl;
    
    /**
     * @var string JWT token
     */
    private $token;
    
    /**
     * Constructor
     * 
     * @param string $baseUrl API base URL
     * @param string $token JWT token
     */
    public function __construct($baseUrl, $token = null) {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }
    
    /**
     * Set JWT token
     * 
     * @param string $token JWT token
     */
    public function setToken($token) {
        $this->token = $token;
    }
    
    /**
     * Make a GET request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @return array|bool Response data if successful, false otherwise
     */
    public function get($endpoint, $params = []) {
        // Build URL with query parameters
        $url = $this->baseUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Make request
        return $this->request('GET', $url);
    }
    
    /**
     * Make a POST request
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|bool Response data if successful, false otherwise
     */
    public function post($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        return $this->request('POST', $url, $data);
    }
    
    /**
     * Make a PUT request
     * 
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|bool Response data if successful, false otherwise
     */
    public function put($endpoint, $data = []) {
        $url = $this->baseUrl . $endpoint;
        return $this->request('PUT', $url, $data);
    }
    
    /**
     * Make a DELETE request
     * 
     * @param string $endpoint API endpoint
     * @return array|bool Response data if successful, false otherwise
     */
    public function delete($endpoint) {
        $url = $this->baseUrl . $endpoint;
        return $this->request('DELETE', $url);
    }
    
    /**
     * Make an API request
     * 
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Request data
     * @return array|bool Response data if successful, false otherwise
     */
    private function request($method, $url, $data = null) {
        // Initialize cURL
        $curl = curl_init();
        
        // Set cURL options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        
        // Set headers
        $headers = ['Content-Type: application/json'];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        // Set request data for POST and PUT requests
        if ($method === 'POST' || $method === 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_errno($curl)) {
            error_log('API request error: ' . curl_error($curl));
            curl_close($curl);
            return false;
        }
        
        // Close cURL
        curl_close($curl);
        
        // Parse response
        $responseData = json_decode($response, true);
        
        // Check for successful response
        if ($httpCode >= 200 && $httpCode < 300) {
            return $responseData;
        } else {
            error_log('API request failed with status code ' . $httpCode . ': ' . $response);
            return false;
        }
    }
    
    /**
     * Get statistics from the API
     * 
     * @return array|bool Statistics data if successful, false otherwise
     */
    public function getStatistics() {
        // Get counts for each content type
        $stats = [
            'stories' => $this->getCount('stories'),
            'authors' => $this->getCount('authors'),
            'blog_posts' => $this->getCount('blog-posts'),
            'directory_items' => $this->getCount('directory-items'),
            'games' => $this->getCount('games'),
            'ai_tools' => $this->getCount('ai-tools'),
            'tags' => $this->getCount('tags')
        ];
        
        // Get featured stories count
        $featuredStories = $this->get('stories', ['featured' => 1]);
        $stats['featured_stories'] = $featuredStories ? count($featuredStories['data']) : 0;
        
        // Get stories needing moderation count
        $moderationStories = $this->get('stories', ['needs_moderation' => 1]);
        $stats['moderation_stories'] = $moderationStories ? count($moderationStories['data']) : 0;
        
        return $stats;
    }
    
    /**
     * Get count of items for a content type
     * 
     * @param string $endpoint API endpoint
     * @return int Count of items
     */
    private function getCount($endpoint) {
        $response = $this->get($endpoint, ['pageSize' => 1]);
        
        if ($response && isset($response['meta']['pagination']['total'])) {
            return $response['meta']['pagination']['total'];
        }
        
        return 0;
    }
}