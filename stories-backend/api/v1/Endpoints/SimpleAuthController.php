<?php
// Include SimpleAuth class
require_once __DIR__ . '/../../../simple_auth.php';

// Database configuration
$config = [
    'db' => [
        'host'     => 'localhost',
        'name'     => 'stories_db',
        'user'     => 'stories_user',
        'password' => '$tw1cac3*sOt',
        'charset'  => 'utf8mb4',
        'port'     => 3306
    ]
];

// Initialize SimpleAuth
SimpleAuth::initDB($config['db']);

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$path = str_replace('api/v1/auth/', '', $path);

// Set JSON response headers
header('Content-Type: application/json');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Handle auth endpoints
switch ($path) {
    case 'login':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        // Get request body
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            exit;
        }
        
        $user = SimpleAuth::login($data['email'], $data['password']);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user,
                'token' => $_SESSION['auth_token']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
        break;
        
    case 'logout':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        SimpleAuth::logout();
        echo json_encode(['success' => true]);
        break;
        
    case 'me':
        if ($method !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        $user = SimpleAuth::check();
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}