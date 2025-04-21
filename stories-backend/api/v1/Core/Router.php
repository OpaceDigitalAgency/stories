<?php
namespace StoriesAPI\Core;

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
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim(str_replace('/api/v1/', '', $path), '/');
        
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
                    $result = $middleware->handle();
                    if ($result === false) {
                        return;
                    }
                }
                
                // Create controller instance
                $controller = new $route['controller']($this->config);
                $controller->setParams($params);
                
                // Call action
                return $controller->{$route['action']}();
            }
        }
        
        // No route found
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Route not found'
        ]);
    }
    
    private function pathToPattern($path) {
        return '#^' . preg_replace('/{[^}]+}/', '([^/]+)', $path) . '$#';
    }
}