<?php
namespace StoriesAPI\Core;

use StoriesAPI\Utils\Response;

/**
 * Base Controller Class
 * 
 * All API controllers extend this class to inherit common functionality
 */
class BaseController {
    protected $db;
    protected $config;
    protected $request;
    protected $params;
    
    public function __construct($config = null) {
        try {
            // Load config if not provided
            if ($config === null) {
                $config = require __DIR__ . '/../Config/config.php';
            }
            $this->config = $config;
            
            // Initialize database
            $this->db = new Database($config);
            
            // Parse request data
            $this->parseRequest();
            
        } catch (\Exception $e) {
            // Log error
            error_log("Controller initialization failed: " . $e->getMessage());
            
            // Send error response
            Response::sendError("Internal server error", 500);
        }
    }
    
    /**
     * Execute controller action with error handling
     */
    public function execute($action) {
        try {
            // Check if action exists
            if (!method_exists($this, $action)) {
                Response::sendError("Action not found", 404);
                return;
            }
            
            // Call the action
            return $this->$action();
            
        } catch (\PDOException $e) {
            // Log database error
            error_log("Database error: " . $e->getMessage());
            Response::sendError("Database error occurred", 500);
            
        } catch (\Exception $e) {
            // Log general error
            error_log("Controller error: " . $e->getMessage());
            Response::sendError("Internal server error", 500);
        }
    }
    
    protected function parseRequest() {
        // Get request body
        $input = file_get_contents('php://input');
        
        // Parse JSON input
        if (!empty($input)) {
            $this->request = json_decode($input, true);
            
            // Handle JSON decode errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::sendError("Invalid JSON in request body", 400);
            }
        } else {
            $this->request = [];
        }
        
        // Initialize params
        $this->params = [];
    }
    
    public function setParams($params) {
        $this->params = $params;
    }
    
    protected function getPaginationParams() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : $this->config['api']['page_size'];
        
        // Validate page
        if ($page < 1) {
            $page = 1;
        }
        
        // Validate page size
        if ($pageSize < 1) {
            $pageSize = $this->config['api']['page_size'];
        }
        if ($pageSize > $this->config['api']['max_page_size']) {
            $pageSize = $this->config['api']['max_page_size'];
        }
        
        return [
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
    
    protected function getSortParams($allowedFields = []) {
        $field = isset($_GET['sortBy']) ? $_GET['sortBy'] : null;
        $direction = isset($_GET['sortDirection']) ? strtoupper($_GET['sortDirection']) : null;
        
        // Validate sort field
        if ($field && !in_array($field, $allowedFields)) {
            Response::sendError("Invalid sort field: $field", 400);
        }
        
        // Validate sort direction
        if ($direction && !in_array($direction, ['ASC', 'DESC'])) {
            Response::sendError("Invalid sort direction: $direction", 400);
        }
        
        return [
            'field' => $field,
            'direction' => $direction
        ];
    }
    
    protected function getFilterParams($allowedFields = []) {
        $filters = [];
        
        foreach ($_GET as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $filters[$key] = $value;
            }
        }
        
        return $filters;
    }
    
    protected function validateRequired($data, $fields) {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                Response::sendError("Missing required field: $field", 400);
            }
        }
        return true;
    }
    
    protected function sanitizeString($string) {
        return htmlspecialchars(strip_tags(trim($string)));
    }
}