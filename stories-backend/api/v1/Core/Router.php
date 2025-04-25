<?php
namespace StoriesAPI\Core;

use StoriesAPI\Utils\Response;

/**
 * Router Class
 * 
 * Handles routing of API requests to appropriate controllers
 */
class Router {
    private $routes = [];
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function addRoute($method, $path, $controller, $action, $middleware = []) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middleware' => $middleware
        ];
    }
    
    public function get($path, $controller, $action, $middleware = []) {
        $this->addRoute('GET', $path, $controller, $action, $middleware);
    }
    
    public function post($path, $controller, $action, $middleware = []) {
        $this->addRoute('POST', $path, $controller, $action, $middleware);
    }
    
    public function put($path, $controller, $action, $middleware = []) {
        $this->addRoute('PUT', $path, $controller, $action, $middleware);
    }
    
    public function delete($path, $controller, $action, $middleware = []) {
        $this->addRoute('DELETE', $path, $controller, $action, $middleware);
    }
    
    public function handle() {
        try {
            // Get request method and path
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path = trim(str_replace('/api/v1/', '', $path), '/');
            
            // Handle OPTIONS requests for CORS
            if ($method === 'OPTIONS') {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization');
                header('Access-Control-Max-Age: 86400'); // 24 hours
                exit;
            }
            
            // Find matching route
            foreach ($this->routes as $route) {
                $pattern = $this->pathToPattern($route['path']);
                
                if ($method === $route['method'] && preg_match($pattern, $path, $matches)) {
                    // Extract route parameters
                    $params = [];
                    preg_match_all('/{([^}]+)}/', $route['path'], $paramNames);
                    array_shift($matches); // Remove full match
                    
                    foreach ($paramNames[1] as $index => $name) {
                        $params[$name] = $matches[$index] ?? null;
                    }
                    
                    // Run middleware
                    foreach ($route['middleware'] as $middleware) {
                        $instance = new $middleware($this->config);
                        if (!$instance->handle()) {
                            return;
                        }
                    }
                    
                    // Create controller instance
                    $controller = new $route['controller']($this->config);
                    $controller->setParams($params);
                    
                    // Execute the action
                    return $controller->execute($route['action']);
                }
            }
            
            // No route found
            Response::sendError('Route not found', 404);
            
        } catch (\Exception $e) {
            // Log error
            error_log("Router error: " . $e->getMessage());
            
            // Send error response
            Response::sendError('Internal server error', 500);
        }
    }
    
    private function pathToPattern($path) {
        return '#^' . preg_replace('/{[^}]+}/', '([^/]+)', $path) . '$#';
    }
}