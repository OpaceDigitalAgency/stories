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

// Authentication routes
$router->post('auth/login', 'StoriesAPI\Endpoints\AuthController', 'login');
$router->post('auth/register', 'StoriesAPI\Endpoints\AuthController', 'register');
$router->post('auth/refresh', 'StoriesAPI\Endpoints\AuthController', 'refresh');
$router->get('auth/me', 'StoriesAPI\Endpoints\AuthController', 'me', [$authMiddleware]);
$router->put('auth/profile', 'StoriesAPI\Endpoints\AuthController', 'updateProfile', [$authMiddleware]);

// Public routes (no auth required)
$router->get('tags', 'StoriesAPI\Endpoints\TagsController', 'index');
$router->get('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'show');

// Protected routes (auth required)
$router->post('tags', 'StoriesAPI\Endpoints\TagsController', 'create', [$authMiddleware]);
$router->put('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'update', [$authMiddleware]);
$router->delete('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'delete', [$authMiddleware]);

// --- Stories ---
$router->get('stories', 'StoriesAPI\Endpoints\StoriesController', 'index');
$router->get('stories/{slug}', 'StoriesAPI\Endpoints\StoriesController', 'show');
$router->post('stories', 'StoriesAPI\Endpoints\StoriesController', 'create', [$authMiddleware]);
$router->put('stories/{id}', 'StoriesAPI\Endpoints\StoriesController', 'update', [$authMiddleware]);
$router->delete('stories/{id}', 'StoriesAPI\Endpoints\StoriesController', 'delete', [$authMiddleware]);

// --- Authors ---
$router->get('authors', 'StoriesAPI\Endpoints\AuthorsController', 'index');
$router->get('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'show');
$router->post('authors', 'StoriesAPI\Endpoints\AuthorsController', 'create', [$authMiddleware]);
$router->put('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'update', [$authMiddleware]);
$router->delete('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'delete', [$authMiddleware]);

// --- Blog Posts ---
$router->get('blog-posts', 'StoriesAPI\Endpoints\BlogPostsController', 'index');
$router->get('blog-posts/{id}', 'StoriesAPI\Endpoints\BlogPostsController', 'show');
$router->post('blog-posts', 'StoriesAPI\Endpoints\BlogPostsController', 'create', [$authMiddleware]);
$router->put('blog-posts/{id}', 'StoriesAPI\Endpoints\BlogPostsController', 'update', [$authMiddleware]);
$router->delete('blog-posts/{id}', 'StoriesAPI\Endpoints\BlogPostsController', 'delete', [$authMiddleware]);

// --- Directory Items ---
$router->get('directory-items', 'StoriesAPI\Endpoints\DirectoryItemsController', 'index');
$router->get('directory-items/{id}', 'StoriesAPI\Endpoints\DirectoryItemsController', 'show');
$router->post('directory-items', 'StoriesAPI\Endpoints\DirectoryItemsController', 'create', [$authMiddleware]);
$router->put('directory-items/{id}', 'StoriesAPI\Endpoints\DirectoryItemsController', 'update', [$authMiddleware]);
$router->delete('directory-items/{id}', 'StoriesAPI\Endpoints\DirectoryItemsController', 'delete', [$authMiddleware]);

// --- Games ---
$router->get('games', 'StoriesAPI\Endpoints\GamesController', 'index');
$router->get('games/{id}', 'StoriesAPI\Endpoints\GamesController', 'show');
$router->post('games', 'StoriesAPI\Endpoints\GamesController', 'create', [$authMiddleware]);
$router->put('games/{id}', 'StoriesAPI\Endpoints\GamesController', 'update', [$authMiddleware]);
$router->delete('games/{id}', 'StoriesAPI\Endpoints\GamesController', 'delete', [$authMiddleware]);

// --- AI Tools ---
$router->get('ai-tools', 'StoriesAPI\Endpoints\AiToolsController', 'index');
$router->get('ai-tools/{id}', 'StoriesAPI\Endpoints\AiToolsController', 'show');
$router->post('ai-tools', 'StoriesAPI\Endpoints\AiToolsController', 'create', [$authMiddleware]);
$router->put('ai-tools/{id}', 'StoriesAPI\Endpoints\AiToolsController', 'update', [$authMiddleware]);
$router->delete('ai-tools/{id}', 'StoriesAPI\Endpoints\AiToolsController', 'delete', [$authMiddleware]);

// Handle the request
$router->handle();