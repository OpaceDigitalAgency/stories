<?php
// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include SimpleAuth
require_once __DIR__ . '/simple_auth.php';

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

// Function to output JSON response
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Get action from query string
$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'login':
        // Test login with default admin credentials
        $result = SimpleAuth::login('admin@storiesfromtheweb.org', 'admin123');
        jsonResponse([
            'action' => 'login',
            'success' => (bool)$result,
            'user' => $result,
            'session' => [
                'auth_user' => $_SESSION['auth_user'] ?? null,
                'auth_token' => $_SESSION['auth_token'] ?? null,
                'auth_time' => $_SESSION['auth_time'] ?? null
            ]
        ]);
        break;

    case 'logout':
        // Test logout
        SimpleAuth::logout();
        jsonResponse([
            'action' => 'logout',
            'success' => true,
            'session' => $_SESSION
        ]);
        break;

    default:
        // Check current auth status
        $user = SimpleAuth::check();
        jsonResponse([
            'action' => 'status',
            'authenticated' => (bool)$user,
            'user' => $user,
            'session' => [
                'auth_user' => $_SESSION['auth_user'] ?? null,
                'auth_token' => $_SESSION['auth_token'] ?? null,
                'auth_time' => $_SESSION['auth_time'] ?? null
            ],
            'cookie' => [
                'auth_token' => $_COOKIE['auth_token'] ?? null
            ]
        ]);
        break;
}