<?php
namespace StoriesAPI\Core;

use StoriesAPI\Core\Database;
use StoriesAPI\Core\Auth;
use StoriesAPI\Utils\Response;
use StoriesAPI\Utils\Validator;

class BaseController {
    /**
     * @var array Request data
     */
    protected $request;
    
    /**
     * @var array Query parameters
     */
    protected $query;
    
    /**
     * @var array URL parameters
     */
    protected $params;
    
    /**
     * @var array Configuration
     */
    protected $config;
    
    /**
     * @var Database Database instance
     */
    protected $db;
    
    /**
     * @var array Current authenticated user
     */
    protected $user;
    
    /**
     * Constructor
     * 
     * @param array $config Configuration
     */
    public function __construct($config = null) {
        if ($config) {
            $this->config = $config;
        } else {
            // Load config if not provided
            $this->config = require __DIR__ . '/../config/config.php';
        }

        // Initialize database connection
        try {
            $this->db = new Database($this->config['db']);
        } catch (\PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }

        $this->parseRequest();
    }
    
    /**
     * Parse the request data
     */
    protected function parseRequest() {
        // Get request method, checking for method override
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        // Parse query parameters
        $this->query = $_GET;
        
        // Parse request body
        $this->request = [];
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // Parse JSON request body
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
            
            // Extract data from Strapi-style structure
            if (isset($data['data'])) {
                if (isset($data['data']['attributes'])) {
                    $this->request = $data['data']['attributes'];
                    // Preserve ID if present
                    if (isset($data['data']['id'])) {
                        $this->request['id'] = $data['data']['id'];
                    }
                } else {
                    $this->request = $data['data'];
                }
            } else {
                $this->request = $data;
            }
            
            error_log("Parsed JSON request: " . json_encode($this->request));
        } else if (strpos($contentType, 'multipart/form-data') !== false) {
            // Handle multipart form data (including files)
            $this->request = $_POST;
            if (!empty($_FILES)) {
                foreach ($_FILES as $key => $file) {
                    $this->request[$key] = $file;
                }
            }
        } else if ($method !== 'GET') {
            // For other content types, try to parse POST data
            $this->request = $_POST;
        }
        
        // Remove method override and CSRF token from request data if present
        if (isset($this->request['_method'])) {
            unset($this->request['_method']);
        }
        if (isset($this->request['_csrf_token'])) {
            unset($this->request['_csrf_token']);
        }
        
        // Get authenticated user from Authorization header
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            // Validate token and get user
            $this->user = Auth::validateToken($token);
            
            // If token validation fails, log the error
            if ($this->user === false) {
                error_log("Token validation failed in BaseController");
            }
        }
    }

    /**
     * Set URL parameters
     *
     * @param array $params URL parameters
     */
    public function setParams($params) {
        $this->params = $params;
    }
    
    /**
     * Get pagination parameters
     * 
     * @return array Pagination parameters
     */
    protected function getPaginationParams() {
        $page = isset($this->query['page']) ? (int)$this->query['page'] : 1;
        $pageSize = isset($this->query['pageSize']) ? (int)$this->query['pageSize'] : $this->config['api']['page_size'];
        
        // Validate pagination parameters
        return Validator::validatePagination($page, $pageSize, $this->config['api']['max_page_size']);
    }
    
    /**
     * Get sort parameters
     * 
     * @param array $allowedFields Allowed fields to sort by
     * @return array|null Sort parameters
     */
    protected function getSortParams($allowedFields) {
        $sort = isset($this->query['sort']) ? $this->query['sort'] : null;
        
        // Validate sort parameters
        return Validator::validateSort($sort, $allowedFields);
    }
    
    /**
     * Get filter parameters
     * 
     * @param array $allowedFields Allowed fields to filter by
     * @return array Filter parameters
     */
    protected function getFilterParams($allowedFields) {
        $filters = [];
        
        foreach ($this->query as $key => $value) {
            // Skip pagination and sort parameters
            if (in_array($key, ['page', 'pageSize', 'sort'])) {
                continue;
            }
            
            // Check if the field is allowed
            if (in_array($key, $allowedFields)) {
                $filters[$key] = $value;
            }
        }
        
        return $filters;
    }
    
    /**
     * Build a WHERE clause from filter parameters
     * 
     * @param array $filters Filter parameters
     * @return array WHERE clause and parameters
     */
    protected function buildWhereClause($filters) {
        $where = [];
        $params = [];
        
        foreach ($filters as $field => $value) {
            // Handle special operators
            if (is_array($value)) {
                if (isset($value['gt'])) {
                    $where[] = "$field > ?";
                    $params[] = $value['gt'];
                } else if (isset($value['gte'])) {
                    $where[] = "$field >= ?";
                    $params[] = $value['gte'];
                } else if (isset($value['lt'])) {
                    $where[] = "$field < ?";
                    $params[] = $value['lt'];
                } else if (isset($value['lte'])) {
                    $where[] = "$field <= ?";
                    $params[] = $value['lte'];
                } else if (isset($value['like'])) {
                    $where[] = "$field LIKE ?";
                    $params[] = "%{$value['like']}%";
                } else if (isset($value['in']) && is_array($value['in'])) {
                    $placeholders = implode(',', array_fill(0, count($value['in']), '?'));
                    $where[] = "$field IN ($placeholders)";
                    $params = array_merge($params, $value['in']);
                }
            } else {
                $where[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return ['clause' => $whereClause, 'params' => $params];
    }
}