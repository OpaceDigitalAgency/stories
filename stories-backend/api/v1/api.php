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
    $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 25;
    $offset = ($page - 1) * $pageSize;

    // Get sort params
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'publishedAt:desc';
    list($sortField, $sortDir) = explode(':', $sort);
    // Map frontend field names to database columns
    $sortFieldMap = [
        'publishedAt' => 'created_at',
        'createdAt' => 'created_at',
        'updatedAt' => 'updated_at',
        'title' => 'title',
        'name' => 'title'
    ];
    $sortField = $sortFieldMap[$sortField] ?? 'created_at';
    $sortDir = strtoupper($sortDir);

    // Simple router
    switch ($path) {
        case 'stories':
            // Get total count
            $countStmt = $db->query("SELECT COUNT(*) FROM stories WHERE is_published = 1");
            $total = $countStmt->fetchColumn();
            
            // Get stories with authors and tags
            $sql = "SELECT s.*, GROUP_CONCAT(DISTINCT a.name) as author_names, 
                          GROUP_CONCAT(DISTINCT a.slug) as author_slugs,
                          GROUP_CONCAT(DISTINCT a.avatar_url) as author_avatars,
                          GROUP_CONCAT(DISTINCT t.name) as tag_names,
                          GROUP_CONCAT(DISTINCT t.slug) as tag_slugs
                   FROM stories s 
                   LEFT JOIN story_authors sa ON s.id = sa.story_id
                   LEFT JOIN authors a ON sa.author_id = a.id
                   LEFT JOIN story_tags st ON s.id = st.story_id
                   LEFT JOIN tags t ON st.tag_id = t.id
                   WHERE s.is_published = 1 
                   GROUP BY s.id
                   ORDER BY s.$sortField $sortDir
                   LIMIT $offset, $pageSize";
            
            $stmt = $db->query($sql);
            $stories = [];
            
            while ($row = $stmt->fetch()) {
                $story = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'excerpt' => $row['excerpt'],
                    'content' => $row['content'],
                    'publishedAt' => $row['created_at'],
                    'rating' => (float)$row['average_rating'],
                    'coverImage' => $row['cover_url']
                ];
                
                // Add author if exists
                if ($row['author_names']) {
                    $names = explode(',', $row['author_names']);
                    $slugs = explode(',', $row['author_slugs']);
                    $avatars = explode(',', $row['author_avatars']);
                    
                    $story['author'] = [
                        'name' => $names[0],
                        'slug' => $slugs[0],
                        'avatar' => $avatars[0]
                    ];
                }

                // Add tags if exist
                if ($row['tag_names']) {
                    $tagNames = explode(',', $row['tag_names']);
                    $story['tags'] = $tagNames;
                }
                
                $stories[] = $story;
            }
            
            echo json_encode([
                'data' => $stories,
                'meta' => [
                    'pagination' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'pageCount' => ceil($total / $pageSize),
                        'total' => $total
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
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'bio' => $row['bio'],
                    'avatar' => $row['avatar_url'],
                    'storyCount' => (int)$row['story_count']
                ];
            }
            
            echo json_encode([
                'data' => $authors,
                'meta' => [
                    'pagination' => [
                        'page' => 1,
                        'pageSize' => count($authors),
                        'pageCount' => 1,
                        'total' => count($authors)
                    ]
                ]
            ]);
            break;

        case 'blog-posts':
            $sql = "SELECT p.*, a.name as author_name, a.slug as author_slug, a.avatar_url as author_avatar,
                          GROUP_CONCAT(t.name) as tag_names, GROUP_CONCAT(t.slug) as tag_slugs
                   FROM blog_posts p
                   LEFT JOIN authors a ON p.author_id = a.id
                   LEFT JOIN post_tags pt ON p.id = pt.post_id
                   LEFT JOIN tags t ON pt.tag_id = t.id
                   WHERE p.is_published = 1
                   GROUP BY p.id
                   ORDER BY p.$sortField $sortDir
                   LIMIT $offset, $pageSize";
            $stmt = $db->query($sql);
            $posts = [];
            
            while ($row = $stmt->fetch()) {
                $post = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'content' => $row['content'],
                    'excerpt' => $row['excerpt'],
                    'publishedAt' => $row['created_at'],
                    'coverImage' => $row['cover_url']
                ];

                // Add author if exists
                if ($row['author_name']) {
                    $post['author'] = [
                        'name' => $row['author_name'],
                        'slug' => $row['author_slug'],
                        'avatar' => $row['author_avatar']
                    ];
                }

                // Add tags if exist
                if ($row['tag_names']) {
                    $tagNames = explode(',', $row['tag_names']);
                    $post['tags'] = $tagNames;
                }

                $posts[] = $post;
            }
            
            echo json_encode([
                'data' => $posts,
                'meta' => [
                    'pagination' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'pageCount' => ceil($total / $pageSize),
                        'total' => $total
                    ]
                ]
            ]);
            break;
            
        case 'games':
            $sql = "SELECT * FROM games WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $games = [];
            
            while ($row = $stmt->fetch()) {
                $games[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'url' => $row['website_url'],
                    'category' => $row['genre'],
                    'thumbnail' => $row['cover_url']
                ];
            }
            
            echo json_encode([
                'data' => $games,
                'meta' => [
                    'pagination' => [
                        'page' => 1,
                        'pageSize' => count($games),
                        'pageCount' => 1,
                        'total' => count($games)
                    ]
                ]
            ]);
            break;
            
        case 'directory-items':
            $sql = "SELECT * FROM directory_items WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $items = [];
            
            while ($row = $stmt->fetch()) {
                $items[] = [
                    'id' => $row['id'],
                    'name' => $row['title'],
                    'description' => $row['description'],
                    'url' => $row['website_url'],
                    'category' => $row['category'],
                    'logo' => $row['cover_url']
                ];
            }
            
            echo json_encode([
                'data' => $items,
                'meta' => [
                    'pagination' => [
                        'page' => 1,
                        'pageSize' => count($items),
                        'pageCount' => 1,
                        'total' => count($items)
                    ]
                ]
            ]);
            break;
            
        case 'ai-tools':
            $sql = "SELECT * FROM ai_tools WHERE is_published = 1 ORDER BY title ASC";
            $stmt = $db->query($sql);
            $tools = [];
            
            while ($row = $stmt->fetch()) {
                $tools[] = [
                    'id' => $row['id'],
                    'name' => $row['title'],
                    'description' => $row['description'],
                    'url' => $row['website_url'],
                    'category' => $row['category'],
                    'logo' => $row['cover_url']
                ];
            }
            
            echo json_encode([
                'data' => $tools,
                'meta' => [
                    'pagination' => [
                        'page' => 1,
                        'pageSize' => count($tools),
                        'pageCount' => 1,
                        'total' => count($tools)
                    ]
                ]
            ]);
            break;

        case 'tags':
            $sql = "SELECT t.*, COUNT(DISTINCT st.story_id) as story_count, COUNT(DISTINCT pt.post_id) as post_count
                   FROM tags t
                   LEFT JOIN story_tags st ON t.id = st.tag_id
                   LEFT JOIN post_tags pt ON t.id = pt.tag_id
                   GROUP BY t.id
                   ORDER BY t.name ASC";
            $stmt = $db->query($sql);
            $tags = [];
            
            while ($row = $stmt->fetch()) {
                $tags[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'storyCount' => (int)$row['story_count'],
                    'postCount' => (int)$row['post_count']
                ];
            }
            
            echo json_encode([
                'data' => $tags,
                'meta' => [
                    'pagination' => [
                        'page' => 1,
                        'pageSize' => count($tags),
                        'pageCount' => 1,
                        'total' => count($tags)
                    ]
                ]
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'error' => [
                    'status' => 404,
                    'message' => 'Endpoint not found'
                ]
            ]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => [
            'status' => 500,
            'message' => 'Internal server error',
            'debug' => $e->getMessage() // Remove this in production
        ]
    ]);
}