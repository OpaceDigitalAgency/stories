<?php
namespace StoriesAPI\core;

use StoriesAPI\Utils\Response;

class BaseController {
    protected $request;
    protected $query;
    protected $params;
    protected $config;
    protected $db;
    
    public function __construct($config) {
        $this->config = $config;
        $this->db = new Database($config['db']);
        $this->parseRequest();
    }
    
    protected function parseRequest() {
        $this->query = $_GET;
        $this->request = [];
        
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true) ?? [];
            $this->request = $data;
        } else if ($method !== 'GET') {
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
}
