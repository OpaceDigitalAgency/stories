<?php
namespace StoriesAPI\Utils;

class Response {
    /**
     * Send a JSON response
     * 
     * @param mixed $data The data to send
     * @param int $status HTTP status code
     */
    private static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        // Clear any output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Ensure proper UTF-8 encoding
        if (is_array($data)) {
            array_walk_recursive($data, function(&$item) {
                if (is_string($item)) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            });
        }
        
        // Encode with error handling
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log("JSON encoding error: " . json_last_error_msg());
            $error = [
                'status' => 'error',
                'message' => 'Internal server error: Failed to encode response'
            ];
            echo json_encode($error);
        } else {
            echo $json;
        }
        exit;
    }
    
    /**
     * Send a success response
     * 
     * @param mixed $data The data to send
     * @param int $status HTTP status code
     */
    public static function sendSuccess($data, $status = 200) {
        $response = [
            'status' => 'success',
            'data' => $data
        ];
        
        self::json($response, $status);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $status HTTP status code
     */
    public static function sendError($message, $status = 400) {
        $response = [
            'status' => 'error',
            'message' => $message
        ];
        
        self::json($response, $status);
    }
    
    /**
     * Send a paginated response
     * 
     * @param array $data The data to send
     * @param int $page Current page number
     * @param int $perPage Items per page
     * @param int $total Total number of items
     */
    public static function sendPaginated($data, $page, $perPage, $total) {
        $response = [
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'page' => (int)$page,
                    'per_page' => (int)$perPage,
                    'total' => (int)$total,
                    'total_pages' => (int)ceil($total / $perPage)
                ]
            ]
        ];
        
        self::json($response);
    }
}
