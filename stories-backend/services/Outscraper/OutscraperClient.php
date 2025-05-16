<?php

namespace Services\Outscraper;

/**
 * Simple Outscraper API client for cPanel environments
 * Based on the official SDK but simplified for direct inclusion
 */
class OutscraperClient
{
    /** @var string API key */
    private $apiKey;
    
    /** @var string API base URL */
    private $baseUrl = 'https://api.outscraper.com/api/v1';
    
    /**
     * Constructor
     * 
     * @param string $apiKey Your Outscraper API key
     */
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Get Amazon reviews
     * 
     * @param string|array $query ASIN or product URL
     * @param array $options Additional options
     * @return array Response data
     */
    public function amazonReviews($query, array $options = [])
    {
        $params = array_merge([
            'query' => $query,
        ], $options);
        
        return $this->request('/amazon/reviews', $params);
    }
    
    /**
     * Make API request
     * 
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return array Response data
     * @throws \Exception If request fails
     */
    private function request(string $endpoint, array $params)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-API-KEY: {$this->apiKey}",
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minutes timeout
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("API error: HTTP code {$httpCode}, Response: {$response}");
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }
        
        return $data;
    }
}
