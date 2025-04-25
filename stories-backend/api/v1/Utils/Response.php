<?php
namespace StoriesAPI\Utils;

/**
 * Response Class
 * 
 * Handles API response formatting
 */
class Response {
    /**
     * Send a success response
     */
    public static function sendSuccess($data = null, $code = 200) {
        self::send([
            'status' => 'success',
            'data' => $data
        ], $code);
    }
    
    /**
     * Send a paginated response
     */
    public static function sendPaginated($data, $page, $pageSize, $total) {
        self::send([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'page' => (int)$page,
                'pageSize' => (int)$pageSize,
                'total' => (int)$total,
                'totalPages' => ceil($total / $pageSize)
            ]
        ], 200);
    }
    
    /**
     * Send an error response
     */
    public static function sendError($message, $code = 500) {
        self::send([
            'status' => 'error',
            'message' => $message
        ], $code);
    }
    
    /**
     * Send the response
     */
    private static function send($data, $code) {
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        http_response_code($code);
        
        // Clear any previous output
        if (ob_get_length()) ob_clean();
        
        // Send response
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
