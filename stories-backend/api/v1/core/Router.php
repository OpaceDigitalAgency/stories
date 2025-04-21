<?php
namespace StoriesAPI\Core;

use StoriesAPI\Utils\Response;

class Router {
    private $routes = [];
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function addRoute($method, $path, $controller, $action, $middleware = []) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
            'middleware' => $middleware
        ];
        return $this;
    }
    
    public function get($path, $controller, $action, $middleware = []) {
        return $this->addRoute('GET', $path, $controller, $action, $middleware);
    }
    
    public function post($path, $controller, $action, $middleware = []) {
        return $this->addRoute('POST', $path, $controller, $action, $middleware);
    }
    
    public function put($path, $controller, $action, $middleware = []) {
        return $this->addRoute('PUT', $path, $controller, $action, $middleware);
    }
    
    public function delete($path, $controller, $action, $middleware = []) {
        return $this->addRoute('DELETE', $path, $controller, $action, $middleware);
    }
    
    public function handle() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            
            $apiPrefix = "/api/{$this->config['api']['version']}/";
            $path = preg_replace("#^" . preg_quote($apiPrefix, '#') . "#", '', $path);
            
            foreach ($this->routes as $route) {
                if ($route['method'] !== $method) {
                    continue;
                }
                
                $pattern = $this->pathToPattern($route['path']);
                
                if (preg_match($pattern, $path, $matches)) {
                    $params = [];
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    
                    foreach ($route['middleware'] as $middleware) {
                        if (!$middleware->handle()) {
                            return;
                        }
                    }
                    
                    $controllerClass = $route['controller'];
                    $controller = new $controllerClass($this->config);
                    
                    if (method_exists($controller, 'setParams')) {
                        $controller->setParams($params);
                    }
                    
                    $action = $route['action'];
                    $controller->$action();
                    return;
                }
            }
            
            Response::sendError('Route not found', 404);
            
        } catch (\Exception $e) {
            error_log("Router error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Response::sendError('Internal server error', 500);
        }
    }
    
    private function pathToPattern($path) {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#i';
    }
}
