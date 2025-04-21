<?php
namespace StoriesAPI\Core;

use StoriesAPI\Utils\Response;

class BaseController {
    protected $request;
    protected $query;
    protected $params;
    protected $config;
    protected $db;
    protected $method;
    
    public function __construct($config) {
        $this->config = $config;
        $this->db = new Database($config['db']);
        $this->parseRequest();
    }
    
    protected function parseRequest() {
        $this->query = $_GET;
        $this->request = [];
        
        $this->method = $_SERVER['REQUEST_METHOD'];
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
            $this->request = $data;
        } else if ($this->method !== 'GET') {
            $this->request = $_POST;
        }
    }
    
    public function setParams($params) {
        $this->params = $params;
    }
    
    protected function getPaginationParams() {
        $page = isset($this->query['page']) ? (int)$this->query['page'] : 1;
        $pageSize = isset($this->query['pageSize']) ? (int)$this->query['pageSize'] : $this->config['api']['page_size'];
        return [
            'page' => max(1, $page),
            'pageSize' => min($pageSize, $this->config['api']['max_page_size'])
        ];
    }
    
    protected function notFound($message = 'Not found') {
        Response::sendError($message, 404);
    }
    
    protected function badRequest($message = 'Bad request') {
        Response::sendError($message, 400);
    }
    
    protected function unauthorized($message = 'Unauthorized') {
        Response::sendError($message, 401);
    }
    
    protected function forbidden($message = 'Forbidden') {
        Response::sendError($message, 403);
    }
    
    protected function serverError($message = 'Internal server error') {
        Response::sendError($message, 500);
    }

    /**
     * Get sort parameters from query string
     *
     * @param array $allowedFields List of allowed sort fields
     * @return array Sort parameters
     */
    protected function getSortParams(array $allowedFields) : array {
        $field = isset($this->query['sort']) ? trim($this->query['sort']) : null;
        $direction = isset($this->query['order']) ? strtoupper(trim($this->query['order'])) : 'DESC';
        
        // Validate sort field
        if ($field && !in_array($field, $allowedFields)) {
            $field = null;
        }
        
        // Validate sort direction
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'DESC';
        }
        
        return [
            'field' => $field,
            'direction' => $direction
        ];
    }

    /**
     * Get filter parameters from query string
     *
     * @param array $allowedFields List of allowed filter fields
     * @return array Filter parameters
     */
    protected function getFilterParams(array $allowedFields) : array {
        $filters = [];
        
        foreach ($allowedFields as $field) {
            if (isset($this->query[$field])) {
                $filters[$field] = trim($this->query[$field]);
            }
        }
        
        return $filters;
    }

    /**
     * Build WHERE clause from filter parameters
     *
     * @param array $filters Filter parameters
     * @return array WHERE clause and parameters
     */
    protected function buildWhereClause(array $filters) : array {
        $where = [];
        $params = [];
        
        foreach ($filters as $field => $value) {
            if ($value === '') {
                continue;
            }
            
            // Handle boolean fields
            if (in_array($field, ['featured', 'isSponsored'])) {
                $dbField = $field === 'isSponsored' ? 'is_sponsored' : $field;
                $where[] = "s.$dbField = ?";
                $params[] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
                continue;
            }
            
            // Handle text fields
            $dbField = $field === 'ageGroup' ? 'age_group' : $field;
            $where[] = "s.$dbField LIKE ?";
            $params[] = "%$value%";
        }
        
        $clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return [
            'clause' => $clause,
            'params' => $params
        ];
    }
}
