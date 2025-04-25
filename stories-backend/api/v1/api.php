<?php
// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable errors temporarily for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php-errors.log');

try {
    // Database connection
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // CORS headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf8mb4');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    // Get request path
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $path = str_replace('api/v1/', '', $path);

    // Simple router
    switch ($path) {
        case 'stories':
            $sql = "SELECT s.*, GROUP_CONCAT(a.name) as author_names, GROUP_CONCAT(a.slug) as author_slugs
                   FROM stories s 
                   LEFT JOIN story_authors sa ON s.id = sa.story_id
                   LEFT JOIN authors a ON sa.author_id = a.id
                   WHERE s.is_published = 1 
                   GROUP BY s.id
                   ORDER BY s.created_at DESC";
            $stmt = $db->query($sql);
            $stories = $stmt->fetchAll();

            // Format author data
            foreach ($stories as &$story) {
                if ($story['author_names']) {
                    $names = explode(',', $story['author_names']);
                    $slugs = explode(',', $story['author_slugs']);
                    $story['authors'] = array_map(function($name, $slug) {
                        return ['name' => $name, 'slug' => $slug];
                    }, $names, $slugs);
                } else {
                    $story['authors'] = [];
                }
                unset($story['author_names']);
                unset($story['author_slugs']);
            }

            echo json_encode(['status' => 'success', 'data' => $stories]);
            break;
            
        case 'authors':
            $sql = "SELECT a.*, COUNT(sa.story_id) as story_count 
                   FROM authors a
                   LEFT JOIN story_authors sa ON a.id = sa.author_id
                   WHERE a.is_published = 1
                   GROUP BY a.id
                   ORDER BY a.name ASC";
            $stmt = $db->query($sql);
            $data = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        case 'games':
            $sql = "SELECT * FROM games WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $data = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        case 'directory-items':
            $sql = "SELECT * FROM directory_items WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $data = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        case 'ai-tools':
            $sql = "SELECT * FROM ai_tools WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $data = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Endpoint not found'
            ]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'debug' => $e->getMessage() // Remove this in production
    ]);
}