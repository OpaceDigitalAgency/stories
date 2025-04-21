<?php
/**
 * API Routes Configuration
 * 
 * Simple route configuration without JavaScript dependencies
 */

// Get router instance
if (!isset($router)) {
    $config = require __DIR__ . '/Config/config.php';
    $router = new StoriesAPI\Core\Router($config);
}

// Create auth middleware
$authMiddleware = new StoriesAPI\Middleware\SimpleAuthMiddleware($config);

// Set CORS headers directly
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- Public Routes ---

// Stories
$router->get('stories', 'StoriesAPI\Endpoints\StoriesController', 'index');
$router->get('stories/{slug}', 'StoriesAPI\Endpoints\StoriesController', 'show');

// Authors
$router->get('authors', 'StoriesAPI\Endpoints\AuthorsController', 'index');
$router->get('authors/{slug}', 'StoriesAPI\Endpoints\AuthorsController', 'show');

// Tags
$router->get('tags', 'StoriesAPI\Endpoints\TagsController', 'index');
$router->get('tags/{slug}', 'StoriesAPI\Endpoints\TagsController', 'show');

// Games
$router->get('games', 'StoriesAPI\Endpoints\GamesController', 'index');
$router->get('games/{id}', 'StoriesAPI\Endpoints\GamesController', 'show');

// Directory Items
$router->get('directory-items', 'StoriesAPI\Endpoints\DirectoryItemsController', 'index');
$router->get('directory-items/{id}', 'StoriesAPI\Endpoints\DirectoryItemsController', 'show');

// AI Tools
$router->get('ai-tools', 'StoriesAPI\Endpoints\AiToolsController', 'index');
$router->get('ai-tools/{id}', 'StoriesAPI\Endpoints\AiToolsController', 'show');

// --- Protected Routes ---

// Stories Management
$router->post('stories', 'StoriesAPI\Endpoints\StoriesController', 'create', [$authMiddleware]);
$router->put('stories/{id}', 'StoriesAPI\Endpoints\StoriesController', 'update', [$authMiddleware]);
$router->delete('stories/{id}', 'StoriesAPI\Endpoints\StoriesController', 'delete', [$authMiddleware]);

// Authors Management
$router->post('authors', 'StoriesAPI\Endpoints\AuthorsController', 'create', [$authMiddleware]);
$router->put('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'update', [$authMiddleware]);
$router->delete('authors/{id}', 'StoriesAPI\Endpoints\AuthorsController', 'delete', [$authMiddleware]);

// Tags Management
$router->post('tags', 'StoriesAPI\Endpoints\TagsController', 'create', [$authMiddleware]);
$router->put('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'update', [$authMiddleware]);
$router->delete('tags/{id}', 'StoriesAPI\Endpoints\TagsController', 'delete', [$authMiddleware]);

// Games Management
$router->post('games', 'StoriesAPI\Endpoints\GamesController', 'create', [$authMiddleware]);
$router->put('games/{id}', 'StoriesAPI\Endpoints\GamesController', 'update', [$authMiddleware]);
$router->delete('games/{id}', 'StoriesAPI\Endpoints\GamesController', 'delete', [$authMiddleware]);

// Directory Items Management
$router->post('directory-items', 'StoriesAPI\Endpoints\DirectoryItemsController', 'create', [$authMiddleware]);
$router->put('directory-items/{id}', 'StoriesAPI\Endpoints\DirectoryItemsController', 'update', [$authMiddleware]);
$router->delete('directory-items/{id}', 'StoriesAPI\Endpoints\DirectoryItemsController', 'delete', [$authMiddleware]);

// AI Tools Management
$router->post('ai-tools', 'StoriesAPI\Endpoints\AiToolsController', 'create', [$authMiddleware]);
$router->put('ai-tools/{id}', 'StoriesAPI\Endpoints\AiToolsController', 'update', [$authMiddleware]);
$router->delete('ai-tools/{id}', 'StoriesAPI\Endpoints\AiToolsController', 'delete', [$authMiddleware]);

// Handle the request
$router->handle();