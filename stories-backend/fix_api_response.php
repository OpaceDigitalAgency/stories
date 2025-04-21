<?php
/**
 * Fix API Response
 * 
 * This script fixes the Response class to properly format JSON responses
 * and handle errors correctly.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix API Response</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$utilsPath = $apiPath . '/Utils';

// Check if Utils directory exists
if (!is_dir($utilsPath)) {
    $utilsPath = $apiPath . '/utils';
    if (!is_dir($utilsPath)) {
        echo "<p style='color:red'>❌ Utils directory not found!</p>";
        exit;
    }
}

echo "<p>Using Utils directory: $utilsPath</p>";

// Find the Response class
$responsePath = $utilsPath . '/Response.php';
if (!file_exists($responsePath)) {
    echo "<p style='color:red'>❌ Response class not found at: $responsePath</p>";
    exit;
}

echo "<p>Found Response class at: $responsePath</p>";

// Create a backup
$backupFile = $responsePath . '.bak.' . date('YmdHis');
if (copy($responsePath, $backupFile)) {
    echo "<p>Created backup at: $backupFile</p>";
}

// Get the namespace based on the utils directory name
$utilsNamespaceSuffix = basename($utilsPath);

// Update the Response class
$responseContent = <<<EOD
<?php
/**
 * Response Class
 * 
 * Handles API response formatting and error handling
 */

namespace StoriesAPI\\$utilsNamespaceSuffix;

class Response {
    /**
     * Send a success response
     * 
     * @param mixed \$data The data to send
     * @param int \$status The HTTP status code (default: 200)
     */
    public static function sendSuccess(\$data, \$status = 200) {
        self::setHeaders(\$status);
        
        \$response = [
            'status' => 'success',
            'data' => \$data
        ];
        
        echo json_encode(\$response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send an error response
     * 
     * @param string \$message The error message
     * @param int \$status The HTTP status code (default: 400)
     */
    public static function sendError(\$message, \$status = 400) {
        self::setHeaders(\$status);
        
        \$response = [
            'status' => 'error',
            'message' => \$message
        ];
        
        echo json_encode(\$response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send a paginated response
     * 
     * @param array \$data The data to send
     * @param int \$page The current page number
     * @param int \$pageSize The page size
     * @param int \$total The total number of items
     * @param int \$status The HTTP status code (default: 200)
     */
    public static function sendPaginated(\$data, \$page, \$pageSize, \$total, \$status = 200) {
        self::setHeaders(\$status);
        
        \$totalPages = ceil(\$total / \$pageSize);
        
        \$response = [
            'status' => 'success',
            'data' => \$data,
            'pagination' => [
                'page' => \$page,
                'pageSize' => \$pageSize,
                'total' => \$total,
                'totalPages' => \$totalPages
            ]
        ];
        
        // Set pagination headers
        header('X-Total-Count: ' . \$total);
        header('X-Pagination-Total-Pages: ' . \$totalPages);
        
        echo json_encode(\$response, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Format data for response
     * 
     * @param mixed \$data The data to format
     * @return mixed The formatted data
     */
    public static function formatData(\$data) {
        if (is_array(\$data)) {
            if (isset(\$data[0]) && is_array(\$data[0])) {
                // Format array of items
                return array_map([self::class, 'formatItem'], \$data);
            } else {
                // Format single item
                return self::formatItem(\$data);
            }
        }
        return \$data;
    }
    
    /**
     * Format a single item for response
     * 
     * @param array \$item The item to format
     * @return array The formatted item
     */
    private static function formatItem(\$item) {
        \$formatted = [];
        
        foreach (\$item as \$key => \$value) {
            // Convert snake_case to camelCase
            \$key = lcfirst(str_replace('_', '', ucwords(\$key, '_')));
            
            // Format date fields
            if (in_array(\$key, ['publishedAt', 'createdAt', 'updatedAt']) && \$value) {
                \$value = date('Y-m-d\\TH:i:s\\Z', strtotime(\$value));
            }
            
            // Convert boolean fields
            if (in_array(\$key, ['featured', 'isPublished']) && \$value !== null) {
                \$value = (bool)\$value;
            }
            
            \$formatted[\$key] = \$value;
        }
        
        return \$formatted;
    }
    
    /**
     * Set response headers
     * 
     * @param int \$status The HTTP status code
     */
    private static function setHeaders(\$status) {
        http_response_code(\$status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Expose-Headers: X-Total-Count, X-Pagination-Total-Pages');
    }
}
EOD;

if (file_put_contents($responsePath, $responseContent)) {
    echo "<p style='color:green'>✅ Updated Response class successfully!</p>";
    
    echo "<h2>Response Class Updates</h2>";
    echo "<ul>";
    echo "<li>Added proper JSON response formatting</li>";
    echo "<li>Added CORS headers for API access</li>";
    echo "<li>Added pagination support with headers</li>";
    echo "<li>Added data formatting for consistent response structure</li>";
    echo "<li>Added proper error handling</li>";
    echo "</ul>";
    
    echo "<h2>Example Response Formats</h2>";
    
    echo "<h3>Success Response</h3>";
    echo "<pre>";
    echo htmlspecialchars(<<<EOT
{
    "status": "success",
    "data": {
        "id": 1,
        "title": "Example Item",
        "description": "This is an example",
        "featured": true,
        "isPublished": true,
        "publishedAt": "2025-04-21T08:00:00Z",
        "createdAt": "2025-04-21T08:00:00Z",
        "updatedAt": "2025-04-21T08:00:00Z"
    }
}
EOT
    );
    echo "</pre>";
    
    echo "<h3>Error Response</h3>";
    echo "<pre>";
    echo htmlspecialchars(<<<EOT
{
    "status": "error",
    "message": "Item not found"
}
EOT
    );
    echo "</pre>";
    
    echo "<h3>Paginated Response</h3>";
    echo "<pre>";
    echo htmlspecialchars(<<<EOT
{
    "status": "success",
    "data": [
        {
            "id": 1,
            "title": "First Item",
            "description": "This is the first item"
        },
        {
            "id": 2,
            "title": "Second Item",
            "description": "This is the second item"
        }
    ],
    "pagination": {
        "page": 1,
        "pageSize": 10,
        "total": 25,
        "totalPages": 3
    }
}
EOT
    );
    echo "</pre>";
    
    echo "<h2>Next Steps</h2>";
    echo "<p>The API responses should now be properly formatted. Test the endpoints:</p>";
    echo "<ul>";
    echo "<li><a href='/api/v1/stories'>/api/v1/stories</a></li>";
    echo "<li><a href='/api/v1/authors'>/api/v1/authors</a></li>";
    echo "<li><a href='/api/v1/games'>/api/v1/games</a></li>";
    echo "<li><a href='/api/v1/directory-items'>/api/v1/directory-items</a></li>";
    echo "<li><a href='/api/v1/ai-tools'>/api/v1/ai-tools</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ Failed to update Response class.</p>";
}