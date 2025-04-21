<?php
namespace StoriesAPI\Utils;

class Response {
    /**
     * Send a success response
     * 
     * @param mixed $data The data to send
     * @param int $status The HTTP status code
     */
    public static function sendSuccess($data, $status = 200) {
        self::setHeaders($status);
        
        $response = [
            'status' => 'success',
            'data' => $data
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send an error response
     * 
     * @param string $message The error message
     * @param int $status The HTTP status code
     */
    public static function sendError($message, $status = 400) {
        self::setHeaders($status);
        
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a paginated response
     * 
     * @param array $data The data to send
     * @param int $page The current page number
     * @param int $pageSize The page size
     * @param int $total The total number of items
     * @param int $status The HTTP status code
     */
    public static function sendPaginated($data, $page, $pageSize, $total, $status = 200) {
        self::setHeaders($status);
        
        $totalPages = ceil($total / $pageSize);
        
        $response = [
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => $totalPages
            ]
        ];
        
        // Set pagination headers
        header('X-Total-Count: ' . $total);
        header('X-Pagination-Total-Pages: ' . $totalPages);
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Format data for response
     * 
     * @param mixed $data The data to format
     * @return mixed The formatted data
     */
    public static function formatData($data) {
        if (is_array($data)) {
            if (isset($data[0]) && is_array($data[0])) {
                // Format array of items
                return array_map([self::class, 'formatItem'], $data);
            } else {
                // Format single item
                return self::formatItem($data);
            }
        }
        return $data;
    }
    
    /**
     * Format a single item for response
     * 
     * @param array $item The item to format
     * @return array The formatted item
     */
    private static function formatItem($item) {
        $formatted = [];
        
        foreach ($item as $key => $value) {
            // Convert snake_case to camelCase
            $key = lcfirst(str_replace('_', '', ucwords($key, '_')));
            
            // Format date fields
            if (in_array($key, ['publishedAt', 'createdAt', 'updatedAt']) && $value) {
                $value = date('Y-m-d\\TH:i:s\\Z', strtotime($value));
            }
            
            // Convert boolean fields
            if (in_array($key, ['featured', 'isPublished']) && $value !== null) {
                $value = (bool)$value;
            }
            
            $formatted[$key] = $value;
        }
        
        return $formatted;
    }
    
    /**
     * Set response headers
     * 
     * @param int $status The HTTP status code
     */
    private static function setHeaders($status) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Expose-Headers: X-Total-Count, X-Pagination-Total-Pages');
    }
}
