<?php
/**
 * API Routes Configuration
 * 
 * This file defines all the API routes and their corresponding controllers.
 * 
 * @package Stories API
 * @version 1.0.0
 */

// Use the router passed from index.php
// If $router is not defined, create a new instance (for direct access)
if (!isset($router)) {
    $router = new StoriesAPI\Core\Router($config);
    error_log("Created new router instance in routes.php");
} else {
    error_log("Using existing router instance from index.php");
}

// Add CORS middleware globally
$corsMiddleware = new StoriesAPI\Middleware\CorsMiddleware($config['security']['cors']);
$router->addGlobalMiddleware($corsMiddleware);

// Create auth middleware instance
$authMiddleware = new StoriesAPI\Middleware\AuthMiddleware($config);

// Public routes (no auth required)
$router->get('tags', 'StoriesAPI\Endpoints\TagsController', 'index');
$router->get('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'show');

// Protected routes (auth required)
$router->post('tags', 'StoriesAPI\Endpoints\TagsController', 'create', [$authMiddleware]);
$router->put('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'update', [$authMiddleware]);
$router->delete('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'delete', [$authMiddleware]);

// Handle the request
$router->handle();