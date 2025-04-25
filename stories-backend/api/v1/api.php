<?php
// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

    // Get request path and query params
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $path = str_replace('api/v1/', '', $path);
    
    // Get pagination params
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
    $offset = ($page - 1) * $per_page;

    // Simple router
    switch ($path) {
        case 'stories':
            // Get total count
            $countStmt = $db->query("SELECT COUNT(*) FROM stories WHERE is_published = 1");
            $total = $countStmt->fetchColumn();
            
            // Get stories with authors
            $sql = "SELECT s.*, GROUP_CONCAT(a.name) as author_names, GROUP_CONCAT(a.slug) as author_slugs
                   FROM stories s 
                   LEFT JOIN story_authors sa ON s.id = sa.story_id
                   LEFT JOIN authors a ON sa.author_id = a.id
                   WHERE s.is_published = 1 
                   GROUP BY s.id
                   ORDER BY s.created_at DESC
                   LIMIT $offset, $per_page";
            
            $stmt = $db->query($sql);
            $stories = [];
            
            while ($row = $stmt->fetch()) {
                $story = [
                    'id' => $row['id'],
                    'attributes' => [
                        'title' => $row['title'],
                        'slug' => $row['slug'],
                        'excerpt' => $row['excerpt'],
                        'content' => $row['content'],
                        'publishedAt' => $row['created_at'],
                        'featured' => (bool)$row['featured'],
                        'averageRating' => (float)$row['average_rating'],
                        'reviewCount' => (int)$row['review_count'],
                        'estimatedReadingTime' => $row['estimated_reading_time'],
                        'isSponsored' => (bool)$row['is_sponsored'],
                        'ageGroup' => $row['age_group'],
                        'needsModeration' => (bool)$row['needs_moderation'],
                        'isSelfPublished' => (bool)$row['is_self_published'],
                        'isAIEnhanced' => (bool)$row['is_ai_enhanced'],
                        'coverUrl' => $row['cover_url'],
                        'createdAt' => $row['created_at'],
                        'updatedAt' => $row['updated_at']
                    ]
                ];
                
                // Add author if exists
                if ($row['author_names']) {
                    $names = explode(',', $row['author_names']);
                    $slugs = explode(',', $row['author_slugs']);
                    $story['attributes']['author'] = [
                        'name' => $names[0],
                        'slug' => $slugs[0]
                    ];
                }
                
                $stories[] = $story;
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $stories,
                'meta' => [
                    'pagination' => [
                        'page' => $page,
                        'per_page' => $per_page,
                        'total' => $total,
                        'total_pages' => ceil($total / $per_page)
                    ]
                ]
            ]);
            break;
            
        case 'authors':
            $sql = "SELECT a.*, COUNT(sa.story_id) as story_count 
                   FROM authors a
                   LEFT JOIN story_authors sa ON a.id = sa.author_id
                   WHERE a.is_published = 1
                   GROUP BY a.id
                   ORDER BY a.name ASC";
            $stmt = $db->query($sql);
            $authors = [];
            
            while ($row = $stmt->fetch()) {
                $authors[] = [
                    'id' => $row['id'],
                    'attributes' => [
                        'name' => $row['name'],
                        'slug' => $row['slug'],
                        'bio' => $row['bio'],
                        'avatarUrl' => $row['avatar_url'],
                        'storyCount' => (int)$row['story_count'],
                        'createdAt' => $row['created_at'],
                        'updatedAt' => $row['updated_at']
                    ]
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $authors]);
            break;
            
        case 'games':
            $sql = "SELECT * FROM games WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $games = [];
            
            while ($row = $stmt->fetch()) {
                $games[] = [
                    'id' => $row['id'],
                    'attributes' => [
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'slug' => $row['slug'],
                        'genre' => $row['genre'],
                        'platform' => $row['platform'],
                        'developer' => $row['developer'],
                        'publisher' => $row['publisher'],
                        'releaseDate' => $row['release_date'],
                        'rating' => (float)$row['rating'],
                        'price' => (float)$row['price'],
                        'createdAt' => $row['created_at'],
                        'updatedAt' => $row['updated_at']
                    ]
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $games]);
            break;
            
        case 'directory-items':
            $sql = "SELECT * FROM directory_items WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $items = [];
            
            while ($row = $stmt->fetch()) {
                $items[] = [
                    'id' => $row['id'],
                    'attributes' => [
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'slug' => $row['slug'],
                        'websiteUrl' => $row['website_url'],
                        'category' => $row['category'],
                        'rating' => (float)$row['rating'],
                        'priceRange' => $row['price_range'],
                        'createdAt' => $row['created_at'],
                        'updatedAt' => $row['updated_at']
                    ]
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $items]);
            break;
            
        case 'ai-tools':
            $sql = "SELECT * FROM ai_tools WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $tools = [];
            
            while ($row = $stmt->fetch()) {
                $tools[] = [
                    'id' => $row['id'],
                    'attributes' => [
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'slug' => $row['slug'],
                        'websiteUrl' => $row['website_url'],
                        'category' => $row['category'],
                        'pricingType' => $row['pricing_type'],
                        'priceInfo' => $row['price_info'],
                        'features' => $row['features'],
                        'rating' => (float)$row['rating'],
                        'featured' => (bool)$row['featured'],
                        'createdAt' => $row['created_at'],
                        'updatedAt' => $row['updated_at']
                    ]
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $tools]);
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