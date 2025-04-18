<?php
/**
 * API Router Class
 * 
 * This class handles routing API requests to the appropriate controllers.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Core;

use StoriesAPI\Utils\Response;
use StoriesAPI\Middleware\CorsMiddleware;

class Router {
    /**
     * @var array Routes configuration
     */
    private $routes = [];
    
    /**
     * @var array Configuration
     */
    private $config;
    
    /**
     * @var array Middleware to apply to all routes
     */
    private $globalMiddleware = [];
    
    /**
     * Constructor
     * 
     * @param array $config Configuration
     */
    public function __construct($config) {
        $this->config = $config;
        
        // Add CORS middleware by default
        $this->addGlobalMiddleware(new CorsMiddleware($config['security']['cors']));
    }
    
    /**
     * Add a route
     * 
     * @param string $method HTTP method
     * @param string $path Route path
     * @param string $controller Controller class
     * @param string $action Controller method
     * @param array $middleware Middleware to apply to this route
     * @return Router This router instance for method chaining
     */
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
    
    /**
     * Add a GET route
     * 
     * @param string $path Route path
     * @param string $controller Controller class
     * @param string $action Controller method
     * @param array $middleware Middleware to apply to this route
     * @return Router This router instance for method chaining
     */
    public function get($path, $controller, $action, $middleware = []) {
        return $this->addRoute('GET', $path, $controller, $action, $middleware);
    }
    
    /**
     * Add a POST route
     * 
     * @param string $path Route path
     * @param string $controller Controller class
     * @param string $action Controller method
     * @param array $middleware Middleware to apply to this route
     * @return Router This router instance for method chaining
     */
    public function post($path, $controller, $action, $middleware = []) {
        return $this->addRoute('POST', $path, $controller, $action, $middleware);
    }
    
    /**
     * Add a PUT route
     * 
     * @param string $path Route path
     * @param string $controller Controller class
     * @param string $action Controller method
     * @param array $middleware Middleware to apply to this route
     * @return Router This router instance for method chaining
     */
    public function put($path, $controller, $action, $middleware = []) {
        return $this->addRoute('PUT', $path, $controller, $action, $middleware);
    }
    
    /**
     * Add a DELETE route
     * 
     * @param string $path Route path
     * @param string $controller Controller class
     * @param string $action Controller method
     * @param array $middleware Middleware to apply to this route
     * @return Router This router instance for method chaining
     */
    public function delete($path, $controller, $action, $middleware = []) {
        return $this->addRoute('DELETE', $path, $controller, $action, $middleware);
    }
    
    /**
     * Add global middleware
     * 
     * @param object $middleware Middleware instance
     * @return Router This router instance for method chaining
     */
    public function addGlobalMiddleware($middleware) {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }
    
    /**
     * Handle the request
     */
    public function handle() {
        // Get request method and path
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Handle bare API root requests
        $apiRoot = "/api/{$this->config['api']['version']}";
        if (rtrim($path, '/') === $apiRoot) {
            $path = '';   // forces clean 404 without warnings
        }
        
        // Debug log
        error_log("Router handling request: {$method} {$path}");
        
        // Remove API prefix from path
        $apiPrefix = "/api/{$this->config['api']['version']}/";
        $originalPath = $path;
        
        // Handle both full URL and relative path formats
        if (strpos($path, $apiPrefix) === 0) {
            // Format: /api/v1/endpoint
            $path = substr($path, strlen($apiPrefix));
            error_log("Path after prefix removal (direct match): {$path}");
        } else {
            // Try to match the end of the path
            $pattern = "#/api/{$this->config['api']['version']}/(.*)$#";
            if (preg_match($pattern, $path, $matches)) {
                $path = $matches[1];
                error_log("Path after prefix removal (regex match): {$path}");
            } else {
                error_log("WARNING: Could not extract API path from {$originalPath}");
            }
        }
        
        // Apply global middleware
        foreach ($this->globalMiddleware as $middleware) {
            if (!$middleware->handle()) {
                return;
            }
        }
        
        // Find matching route
        $matchedRoute = null;
        $params = [];
        
        error_log("Looking for route matching: {$method} {$path}");
        error_log("Available routes: " . count($this->routes));
        
        foreach ($this->routes as $index => $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            $pattern = $this->pathToPattern($route['path']);
            error_log("Checking route #{$index}: {$route['method']} {$route['path']} (pattern: {$pattern})");
            
            if (preg_match($pattern, $path, $matches)) {
                $matchedRoute = $route;
                error_log("Route matched: {$route['controller']}::{$route['action']}");
                
                // Extract named parameters
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                
                error_log("Route parameters: " . print_r($params, true));
                break;
            }
        }
        
        // If no route matches, return 404
        if (!$matchedRoute) {
            error_log("No route matched for {$method} {$path}");
            Response::sendError('Route not found', 404);
            return;
        }
        
        // Apply route middleware
        foreach ($matchedRoute['middleware'] as $middleware) {
            if (!$middleware->handle()) {
                return;
            }
        }
        
        // Create controller instance
        $controllerClass = $matchedRoute['controller'];
        error_log("Creating controller instance: {$controllerClass}");
        
        try {
            $controller = new $controllerClass($this->config);
            
            // Set URL parameters using the setter method
            $controller->setParams($params);
            
            // Call controller action
            $action = $matchedRoute['action'];
            error_log("Calling controller action: {$action}");
            $controller->$action();
        } catch (\Exception $e) {
            error_log("Exception in controller: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Response::sendError('Internal server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Convert a path to a regex pattern
     * 
     * @param string $path Path with placeholders
     * @return string Regex pattern
     */
    private function pathToPattern($path) {
        // Replace placeholders with regex patterns
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?<$1>[^/]+)', $path);
        
        // Add start and end anchors
        $pattern = '#^' . $pattern . '$#i'; // Add 'i' flag for case-insensitivity
        
        return $pattern;
    }
}