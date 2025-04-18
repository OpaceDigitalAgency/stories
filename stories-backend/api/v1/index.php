<?php
/**
 * API Entry Point
 * 
 * This file serves as the entry point for the Stories API v1.
 * It configures the router and sets up routes with appropriate middleware.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/stories/api.storiesfromtheweb.org/logs/api-error.log');

// Load required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use StoriesAPI\Core\Router;
use StoriesAPI\Middleware\AuthMiddleware;
use StoriesAPI\Middleware\CorsMiddleware;

// Initialize router with config
$router = new Router($config);

// Create middleware instances
$authMiddleware = new AuthMiddleware($config);
$corsMiddleware = new CorsMiddleware($config['security']['cors']);

// Add global CORS middleware
$router->addGlobalMiddleware($corsMiddleware);

// Public routes (no auth required)
$router->get('stories', 'StoriesAPI\Endpoints\StoriesController', 'index');
$router->get('stories/{id}', 'StoriesAPI\Endpoints\StoriesController', 'show');
$router->get('authors', 'StoriesAPI\Endpoints\AuthorsController', 'index');
$router->get('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'show');
$router->get('tags', 'StoriesAPI\Endpoints\TagsController', 'index');

// Protected routes (auth required)
$router->post('stories', 'StoriesAPI\Endpoints\StoriesController', 'create', [$authMiddleware]);
$router->put('stories/{id}', 'StoriesAPI\Endpoints\StoriesController', 'update', [$authMiddleware]);
$router->delete('stories/{id}', 'StoriesAPI\Endpoints\StoriesController', 'delete', [$authMiddleware]);

$router->post('authors', 'StoriesAPI\Endpoints\AuthorsController', 'create', [$authMiddleware]);
$router->put('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'update', [$authMiddleware]);
$router->delete('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'delete', [$authMiddleware]);

$router->post('tags', 'StoriesAPI\Endpoints\TagsController', 'create', [$authMiddleware]);
$router->put('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'update', [$authMiddleware]);
$router->delete('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'delete', [$authMiddleware]);

// Handle the request
try {
    $router->handle();
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}