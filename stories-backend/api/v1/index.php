<?php
/**
 * API Entry Point
 * 
 * This file initializes the API and handles all requests
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set timezone
date_default_timezone_set('UTC');

// Autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $file = __DIR__ . '/' . str_replace(['StoriesAPI\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load configuration
$config = require __DIR__ . '/Config/config.php';

try {
    // Initialize router
    $router = new StoriesAPI\Core\Router($config);
    
    // Define routes
    $router->get('stories', 'StoriesAPI\\Endpoints\\StoriesController', 'index');
    $router->get('stories/{id}', 'StoriesAPI\\Endpoints\\StoriesController', 'show');
    
    $router->get('authors', 'StoriesAPI\\Endpoints\\AuthorsController', 'index');
    $router->get('authors/{id}', 'StoriesAPI\\Endpoints\\AuthorsController', 'show');
    
    $router->get('games', 'StoriesAPI\\Endpoints\\GamesController', 'index');
    $router->get('games/{id}', 'StoriesAPI\\Endpoints\\GamesController', 'show');
    
    $router->get('directory-items', 'StoriesAPI\\Endpoints\\DirectoryItemsController', 'index');
    $router->get('directory-items/{id}', 'StoriesAPI\\Endpoints\\DirectoryItemsController', 'show');
    
    $router->get('ai-tools', 'StoriesAPI\\Endpoints\\AiToolsController', 'index');
    $router->get('ai-tools/{id}', 'StoriesAPI\\Endpoints\\AiToolsController', 'show');
    
    // Handle request
    $router->handle();
    
} catch (Exception $e) {
    // Log error
    error_log("API Error: " . $e->getMessage());
    
    // Send error response
    StoriesAPI\Utils\Response::sendError('Internal server error', 500);
}