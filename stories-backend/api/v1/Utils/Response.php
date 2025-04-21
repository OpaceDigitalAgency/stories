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

        // Process data before encoding
        $processedData = self::processDataForJson($data);
        
        // Encode with error handling
        $json = json_encode($processedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log("JSON encoding error: " . json_last_error_msg());
            error_log("Data that failed to encode: " . print_r($processedData, true));
            $error = [
                'status' => 'error',
                'message' => 'Internal server error: Failed to encode response',
                'debug' => json_last_error_msg()
            ];
            echo json_encode($error);
        } else {
            echo $json;
        }
        exit;
    }

    /**
     * Process data before JSON encoding
     *
     * @param mixed $data The data to process
     * @return mixed The processed data
     */
    private static function processDataForJson($data) {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                // Process each array element
                $result[$key] = self::processDataForJson($value);
            }
            return $result;
        } elseif (is_object($data)) {
            // Convert objects to arrays
            return self::processDataForJson((array)$data);
        } elseif (is_string($data)) {
            // Handle strings
            $processed = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            return $processed === '' ? null : $processed;
        } elseif ($data === '') {
            // Convert empty strings to null
            return null;
        } elseif (is_bool($data)) {
            // Ensure booleans are actual booleans
            return (bool)$data;
        } elseif (is_numeric($data)) {
            // Handle numbers
            return is_float($data) ? (float)$data : (int)$data;
        } elseif ($data === null) {
            // Keep null values as null
            return null;
        }
        
        return $data;
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
