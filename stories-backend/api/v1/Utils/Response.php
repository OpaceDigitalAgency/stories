<?php
/**
 * API Response Utility Class
 * 
 * This class handles formatting API responses to match the expected format
 * by the Astro frontend.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Utils;

class Response {
    /**
     * @var bool Debug mode flag
     */
    public static $debugMode = false;
    /**
     * Format a successful response
     * 
     * @param array $data The data to include in the response
     * @param array $meta Additional metadata
     * @param int $statusCode HTTP status code
     * @return array The formatted response
     */
    public static function success($data, $meta = [], $statusCode = 200) {
        // Set the HTTP response code
        http_response_code($statusCode);
        
        // Format the response to match Strapi format expected by the frontend
        $response = [
            'data' => $data,
            'meta' => $meta
        ];
        
        // If meta doesn't include pagination and data is an array, add default pagination
        if (!isset($meta['pagination']) && is_array($data)) {
            $response['meta']['pagination'] = [
                'page' => 1,
                'pageSize' => count($data),
                'pageCount' => 1,
                'total' => count($data)
            ];
        }
        
        return $response;
    }
    
    /**
     * Format a paginated response
     * 
     * @param array $data The data to include in the response
     * @param int $page Current page number
     * @param int $pageSize Items per page
     * @param int $total Total number of items
     * @param array $additionalMeta Additional metadata
     * @param int $statusCode HTTP status code
     * @return array The formatted response
     */
    public static function paginated($data, $page, $pageSize, $total, $additionalMeta = [], $statusCode = 200) {
        // Set the HTTP response code
        http_response_code($statusCode);
        
        // Calculate page count
        $pageCount = ceil($total / $pageSize);
        
        // Format pagination metadata
        $pagination = [
            'page' => (int)$page,
            'pageSize' => (int)$pageSize,
            'pageCount' => (int)$pageCount,
            'total' => (int)$total
        ];
        
        // Add pagination headers for frontend consumption
        header('X-Total-Count: ' . $total);
        header('X-Pagination-Page: ' . $page);
        header('X-Pagination-Page-Size: ' . $pageSize);
        header('X-Pagination-Page-Count: ' . $pageCount);
        
        // Merge additional metadata with pagination
        $meta = array_merge(['pagination' => $pagination], $additionalMeta);
        
        // Return the formatted response
        return self::success($data, $meta, $statusCode);
    }
    
    /**
     * Format an error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Detailed error information
     * @return array The formatted error response
     */
    public static function error($message, $statusCode = 400, $errors = []) {
        // Set the HTTP response code
        http_response_code($statusCode);
        
        // Format the error response
        $response = [
            'error' => true,
            'message' => $message,
            'statusCode' => $statusCode
        ];
        
        // Add detailed errors if provided
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }
    
    /**
     * Send the response as JSON
     * 
     * @param array $data The response data
     */
    public static function json($data) {
        // Set content type header - ALWAYS set to JSON regardless of debug mode
        header('Content-Type: application/json; charset=UTF-8');
        
        // Debug: Log the data being encoded
        error_log("Response data before encoding: " . print_r($data, true));
        
        // Encode the data
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        
        // Check for JSON encoding errors
        if ($json === false) {
            error_log("JSON encoding error: " . json_last_error_msg());
            
            // Try to identify problematic data
            $cleanData = self::sanitizeDataForJson($data);
            $json = json_encode($cleanData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            if ($json === false) {
                // If still failing, return a simple error response
                error_log("JSON encoding still failing after sanitization");
                $errorJson = '{"error":true,"message":"Internal server error: Unable to encode response","statusCode":500}';
                
                // Always output JSON error response regardless of debug mode
                echo $errorJson;
                exit;
            }
        }
        
        // Output the JSON response - ALWAYS output JSON regardless of debug mode
        echo $json;
        exit;
    }
    
    /**
     * Sanitize data for JSON encoding
     *
     * @param mixed $data The data to sanitize
     * @return mixed Sanitized data
     */
    private static function sanitizeDataForJson($data) {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $key => $value) {
                $clean[$key] = self::sanitizeDataForJson($value);
            }
            return $clean;
        } elseif (is_object($data)) {
            $clean = new \stdClass();
            foreach (get_object_vars($data) as $key => $value) {
                $clean->$key = self::sanitizeDataForJson($value);
            }
            return $clean;
        } elseif (is_string($data)) {
            // Remove invalid UTF-8 characters
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        } else {
            return $data;
        }
    }
    
    /**
     * Send a success response as JSON
     * 
     * @param array $data The data to include in the response
     * @param array $meta Additional metadata
     * @param int $statusCode HTTP status code
     */
    public static function sendSuccess($data, $meta = [], $statusCode = 200) {
        self::json(self::success($data, $meta, $statusCode));
    }
    
    /**
     * Send a paginated response as JSON
     * 
     * @param array $data The data to include in the response
     * @param int $page Current page number
     * @param int $pageSize Items per page
     * @param int $total Total number of items
     * @param array $additionalMeta Additional metadata
     * @param int $statusCode HTTP status code
     */
    public static function sendPaginated($data, $page, $pageSize, $total, $additionalMeta = [], $statusCode = 200) {
        self::json(self::paginated($data, $page, $pageSize, $total, $additionalMeta, $statusCode));
    }
    
    /**
     * Send an error response as JSON
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Detailed error information
     */
    public static function sendError($message, $statusCode = 400, $errors = []) {
        self::json(self::error($message, $statusCode, $errors));
    }
}