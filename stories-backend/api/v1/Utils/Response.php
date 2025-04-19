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
        // Add debugging
        error_log("Response::json - Starting JSON encoding");
        
        // Set content type header - ALWAYS set to JSON regardless of debug mode
        header('Content-Type: application/json; charset=UTF-8');
        
        // Debug: Log the data being encoded
        error_log("Response::json - Data type: " . gettype($data));
        if (is_array($data)) {
            error_log("Response::json - Top-level keys: " . implode(", ", array_keys($data)));
            if (isset($data['data'])) {
                error_log("Response::json - Data structure: " . json_encode(array_keys($data['data'])));
            }
        }
        
        // Make sure there's no output before JSON
        if (ob_get_length() > 0) {
            $output = ob_get_clean();
            error_log("Response::json - Cleared output buffer: " . substr($output, 0, 200));
        }
        
        // Encode the data with simpler options to avoid encoding issues
        error_log("Response::json - Encoding data to JSON");
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        // Check for JSON encoding errors
        if ($json === false) {
            error_log("Response::json - JSON encoding error: " . json_last_error_msg());
            
            // Try to identify problematic data
            error_log("Response::json - Attempting to sanitize data");
            $cleanData = self::sanitizeDataForJson($data);
            $json = json_encode($cleanData);
            
            if ($json === false) {
                // If still failing, return a simple error response
                error_log("Response::json - JSON encoding still failing after sanitization");
                $errorJson = '{"error":true,"message":"Internal server error: Unable to encode response","statusCode":500}';
                
                // Always output JSON error response regardless of debug mode
                error_log("Response::json - Sending error JSON response");
                echo $errorJson;
                exit;
            } else {
                error_log("Response::json - Sanitization successful");
            }
        }
        
        // Output the JSON response - ALWAYS output JSON regardless of debug mode
        error_log("Response::json - Sending JSON response (length: " . strlen($json) . ")");
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
     * Format data to ensure it has the correct structure with attributes
     * 
     * @param array $data The data to format
     * @return array The formatted data
     */
    private static function formatData($data) {
        // If data is already in the correct format, check if attributes needs fixing
        if (isset($data['id']) && isset($data['attributes'])) {
            // Check for nested attributes
            if (isset($data['attributes']['attributes'])) {
                $data['attributes'] = $data['attributes']['attributes'];
            }
            return $data;
        }
        
        // If data is an array of items, format each item
        if (is_array($data) && !isset($data['id']) && !isset($data['attributes']) && !empty($data)) {
            $formattedData = [];
            foreach ($data as $item) {
                if (is_array($item) && isset($item['id'])) {
                    // Format each item
                    $attributes = [];
                    foreach ($item as $key => $value) {
                        if ($key !== 'id') {
                            $attributes[$key] = $value;
                        }
                    }
                    
                    $formattedData[] = [
                        'id' => $item['id'],
                        'attributes' => $attributes
                    ];
                } else {
                    // If item doesn't have an ID, keep it as is
                    $formattedData[] = $item;
                }
            }
            return $formattedData;
        }
        
        // Format a single item
        $id = $data['id'] ?? null;
        if ($id === null) {
            // If no ID, return data as is
            return $data;
        }
        
        // Create attributes array
        $attributes = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $attributes[$key] = $value;
            }
        }
        
        // Return formatted data
        return [
            'id' => $id,
            'attributes' => $attributes
        ];
    }
    
    /**
     * Send a success response as JSON
     * 
     * @param array $data The data to include in the response
     * @param array $meta Additional metadata
     * @param int $statusCode HTTP status code
     */
    public static function sendSuccess($data, $meta = [], $statusCode = 200) {
        // Add debugging
        error_log("Response::sendSuccess - Starting with status code: " . $statusCode);
        error_log("Response::sendSuccess - Data type: " . gettype($data));
        if (is_array($data)) {
            error_log("Response::sendSuccess - Data keys: " . implode(", ", array_keys($data)));
        }
        
        // Check if data is already formatted with a 'data' key
        if (is_array($data) && isset($data['id']) && !isset($data['data'])) {
            // This is a single entity response, don't wrap it in another 'data' key
            error_log("Response::sendSuccess - Single entity response detected");
            $formatted = self::formatData($data);
            error_log("Response::sendSuccess - Formatted data: " . json_encode($formatted));
            self::json(['data' => $formatted, 'meta' => $meta]);
        } else {
            // Use the standard success method for other cases
            error_log("Response::sendSuccess - Standard response");
            $formatted = self::formatData($data);
            error_log("Response::sendSuccess - Formatted data: " . json_encode($formatted));
            self::json(self::success($formatted, $meta, $statusCode));
        }
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
        // Check if data is already formatted
        $isFormatted = true;
        if (is_array($data)) {
            foreach ($data as $item) {
                if (!isset($item['id']) || !isset($item['attributes'])) {
                    $isFormatted = false;
                    break;
                }
            }
        }
        
        $formattedData = $isFormatted ? $data : self::formatData($data);
        self::json(self::paginated($formattedData, $page, $pageSize, $total, $additionalMeta, $statusCode));
    }
    
    /**
     * Send an error response as JSON
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Detailed error information
     */
    public static function sendError($message, $statusCode = 400, $errors = []) {
        // Check if token expiration was detected
        if ($statusCode == 401 && isset($GLOBALS['token_expired']) && $GLOBALS['token_expired']) {
            // Add token expiration information to the message
            $message = 'Authentication token has expired. Please refresh your token or log in again.';
            
            // Add a specific error code for token expiration
            $errors['code'] = 'token_expired';
            
            error_log("Sending token expired error response");
        }
        
        self::json(self::error($message, $statusCode, $errors));
    }
}
