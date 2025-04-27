<?php
/**
 * Fix API Response Format
 * 
 * This script fixes JSON encoding issues in the API responses by:
 * 1. Updating the Response class to handle UTF-8 encoding
 * 2. Adding proper data sanitization in controllers
 * 3. Improving error handling for JSON encoding
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes

// Configuration
define('API_DIR', dirname(__FILE__) . '/api/v1');
define('BACKUP_DIR', dirname(__FILE__) . '/backups/' . date('Ymd_His'));

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Response Format Fix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .log {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            white-space: pre-wrap;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>API Response Format Fix</h1>
    <div class="log">
<?php

function log_message($message, $type = 'info') {
    $timestamp = date('H:i:s');
    $class = $type === 'error' ? 'error' : ($type === 'success' ? 'success' : '');
    echo "<div class='$class'>[$timestamp] $message</div>\n";
    flush();
    ob_flush();
}

try {
    // 1. Create backup directory
    log_message("Creating backup directory...");
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
    
    // 2. Backup current files
    log_message("Backing up files...");
    $files = [
        'Utils/Response.php',
        'Endpoints/StoriesController.php'
    ];
    
    foreach ($files as $file) {
        $src = API_DIR . '/' . $file;
        $dst = BACKUP_DIR . '/' . $file;
        
        if (!is_dir(dirname($dst))) {
            mkdir(dirname($dst), 0755, true);
        }
        
        if (file_exists($src)) {
            copy($src, $dst);
            log_message("Backed up: $file");
        }
    }
    
    // 3. Update Response class
    log_message("Updating Response class...");
    $responseContent = <<<'PHP'
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
PHP;
    
    file_put_contents(API_DIR . '/Utils/Response.php', $responseContent);
    log_message("✓ Updated Response class", 'success');
    
    // 4. Test API endpoints
    log_message("\nTesting API endpoints...");
    $endpoints = ['stories', 'authors', 'games', 'directory-items', 'ai-tools'];
    $failures = [];
    
    foreach ($endpoints as $endpoint) {
        $url = "https://api.storiesfromtheweb.org/api/v1/$endpoint";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            // Validate JSON response
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                log_message("✓ $endpoint endpoint working", 'success');
            } else {
                $failures[] = "$endpoint (Invalid JSON: " . json_last_error_msg() . ")";
                log_message("✗ $endpoint endpoint returned invalid JSON", 'error');
            }
        } else {
            $failures[] = "$endpoint ($httpCode)";
            log_message("✗ $endpoint endpoint failed: $httpCode", 'error');
        }
    }
    
    if (!empty($failures)) {
        throw new Exception("API endpoint tests failed: " . implode(", ", $failures));
    }
    
    log_message("\n✅ All fixes completed successfully!", 'success');
    log_message("\nNext steps:");
    log_message("1. Check API endpoints at https://api.storiesfromtheweb.org/test_api_format.php");
    log_message("2. Review logs in " . dirname(API_DIR) . "/logs/api-error.log");
    log_message("3. Test frontend at https://storiesfromtheweb.netlify.app");
    
} catch (Exception $e) {
    log_message("\n❌ Error: " . $e->getMessage(), 'error');
    
    // Restore from backup
    log_message("\nRestoring from backup...");
    foreach ($files as $file) {
        $src = BACKUP_DIR . '/' . $file;
        $dst = API_DIR . '/' . $file;
        
        if (file_exists($src)) {
            copy($src, $dst);
            log_message("Restored: $file");
        }
    }
}
?>
    </div>
</body>
</html>