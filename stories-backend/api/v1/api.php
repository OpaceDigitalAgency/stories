<?php
/**
 * Stories API v1 – single-file router
 * ---------------------------------------------------------------
 * Full replacement for api/v1/api.php
 */

/* -----------------------------------------------------------------
   1. Never leak PHP warnings / notices into JSON responses
   ----------------------------------------------------------------- */
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');                      // hide from output
ini_set('log_errors', '1');                          // but log them
ini_set('error_log', __DIR__ . '/../../logs/php-errors.log');

/* -----------------------------------------------------------------
   2. Global response headers
   ----------------------------------------------------------------- */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    /* -----------------------------------------------------------------
       3. Database connection
       ----------------------------------------------------------------- */
    $db = new PDO(
        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
        'stories_user',
        '$tw1cac3*sOt',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    /* -----------------------------------------------------------------
       4. Helper: safely parse "field:dir"
       ----------------------------------------------------------------- */
    function parseSort(string $sort, string $defaultField = 'publishedAt', string $defaultDir = 'desc'): array
    {
        $parts     = explode(':', $sort, 2);          // max 2 parts
        $field     = $parts[0] ?? $defaultField;
        $direction = strtoupper($parts[1] ?? $defaultDir);
        return [$field, $direction === 'ASC' ? 'ASC' : 'DESC'];
    }

    /* -----------------------------------------------------------------
       5. Request basics
       ----------------------------------------------------------------- */
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $path = preg_replace('#^api/v1/#', '', $path);    // strip prefix if present

    $page     = isset($_GET['page'])     ? max((int)$_GET['page'], 1) : 1;
    $pageSize = isset($_GET['pageSize']) ? max((int)$_GET['pageSize'], 1) : 25;
    $offset   = ($page - 1) * $pageSize;

    [$sortAlias, $sortDir] = parseSort($_GET['sort'] ?? 'publishedAt:desc');

    /* map frontend aliases to DB columns */
    $sortMap = [
        'publishedAt' => 'created_at',
        'createdAt'   => 'created_at',
        'updatedAt'   => 'updated_at',
        'title'       => 'title',
        'name'        => 'title',
    ];
    $sortColumn = $sortMap[$sortAlias] ?? 'created_at';

    $shouldPopulate = ($_GET['populate'] ?? '') === '*';

    /* -----------------------------------------------------------------
       6. Simple router
       ----------------------------------------------------------------- */

    switch ($path) {

        /* -----------------------------------
           AUTH ROUTES (unchanged)
           ----------------------------------- */
        case 'auth/login':    /* … */      break;
        case 'auth/logout':   /* … */      break;
        case 'auth/me':       /* … */      break;

        /* -----------------------------------
           STORIES (paginated, with sort/limit)
           ----------------------------------- */
        case 'media':
            // Media upload endpoint
            try {
                // Check if this is a small file or a large file
                $input = file_get_contents('php://input');
                $filename = $_SERVER['HTTP_X_FILENAME'] ?? uniqid() . '.dat';
                $filetype = $_SERVER['HTTP_X_FILETYPE'] ?? 'application/octet-stream';
                
                // For large files, we'll use the default cover
                if (strlen($input) > 1000000) { // Over 1MB
                    error_log("Large file detected: " . $filename . " (" . strlen($input) . " bytes). Using default cover.");
                    echo json_encode(['url' => '/images/default-cover.svg']);
                    break;
                }
                
                $uploadDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filepath = $uploadDir . basename($filename);
                file_put_contents($filepath, $input);
                $url = '/uploads/' . basename($filename);
                
                // Insert into media table
                $stmt = $db->prepare("INSERT INTO media (entity_type, type, filename, url, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute(['story', $filetype, basename($filename), $url]);
                
                echo json_encode(['url' => $url]);
            } catch (Exception $e) {
                error_log("Media upload error: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => ['status' => 500, 'message' => 'Internal server error']]);
            }
            break;

        case 'story-authors':
            // POST handler for associating stories with authors
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!is_array($input) || !isset($input['story_id']) || !isset($input['author_id'])) {
                        http_response_code(400);
                        echo json_encode(['error' => ['status' => 400, 'message' => 'Missing required fields']]);
                        break;
                    }
                    
                    $storyId = (int)$input['story_id'];
                    $authorId = (int)$input['author_id'];
                    
                    $stmt = $db->prepare("INSERT INTO story_authors (story_id, author_id) VALUES (?, ?)");
                    $stmt->execute([$storyId, $authorId]);
                    
                    http_response_code(201);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    error_log("Story-author association error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => ['status' => 500, 'message' => 'Internal server error']]);
                }
                break;
            }
            break;
            
        case 'authors':
            // POST handler for author creation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!is_array($input)) {
                        http_response_code(400);
                        echo json_encode(['error' => ['status' => 400, 'message' => 'Invalid JSON']]);
                        break;
                    }
                    $fields = array_keys($input);
                    $columns = implode(',', $fields);
                    $placeholders = implode(',', array_map(fn($f) => ":$f", $fields));
                    $insertSql = "INSERT INTO authors ($columns) VALUES ($placeholders)";
                    $insertStmt = $db->prepare($insertSql);
                    foreach ($input as $key => $value) {
                        $insertStmt->bindValue(":$key", $value);
                    }
                    $insertStmt->execute();
                    $newId = (int)$db->lastInsertId();
                    http_response_code(201);
                    echo json_encode(['id' => $newId]);
                } catch (Exception $e) {
                    error_log("Author creation error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => ['status' => 500, 'message' => 'Internal server error']]);
                }
                break;
            }
            
            // GET handler for authors
            $whereConditions = ["is_published = 1"];
            $params = [];
            
            // Filter by slug if provided
            if (isset($_GET['slug'])) {
                $whereConditions[] = "slug = :slug";
                $params[':slug'] = $_GET['slug'];
            }
            
            // Combine all conditions
            $whereClause = implode(' AND ', $whereConditions);
            
            // Build final query with filters
            $sql = "SELECT * FROM authors WHERE $whereClause ORDER BY name ASC";
            
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $authors = $stmt->fetchAll();
            echo json_encode($authors);
            break;
            
        case 'stories':
            // POST handler for story creation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!is_array($input)) {
                        http_response_code(400);
                        echo json_encode(['error' => ['status' => 400, 'message' => 'Invalid JSON']]);
                        break;
                    }
                    $fields = array_keys($input);
                    $columns = implode(',', $fields);
                    $placeholders = implode(',', array_map(fn($f) => ":$f", $fields));
                    $insertSql = "INSERT INTO stories ($columns) VALUES ($placeholders)";
                    $insertStmt = $db->prepare($insertSql);
                    foreach ($input as $key => $value) {
                        $insertStmt->bindValue(":$key", $value);
                    }
                    $insertStmt->execute();
                    $newId = (int)$db->lastInsertId();
                    http_response_code(201);
                    echo json_encode(['id' => $newId]);
                } catch (Exception $e) {
                    error_log("Story creation error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => ['status' => 500, 'message' => 'Internal server error']]);
                }
                break;
            }
        case 'stories':
            // Build WHERE clause with filters
            $whereConditions = ["s.is_published = 1"];
            $params = [];
            
            // Debug log all GET parameters
            error_log("API GET parameters: " . json_encode($_GET));
            
            // Add filter for featured stories
            if (isset($_GET['featured']) && $_GET['featured'] == 1) {
                $whereConditions[] = "s.featured = 1";
                error_log("Adding featured=1 filter");
            }
            
            // Add filter for sponsored stories
            if (isset($_GET['is_sponsored']) && $_GET['is_sponsored'] == 1) {
                $whereConditions[] = "s.is_sponsored = 1";
                error_log("Adding is_sponsored=1 filter");
            }
            
            // Add filter for self-published stories
            if (isset($_GET['is_self_published']) && $_GET['is_self_published'] == 1) {
                $whereConditions[] = "s.is_self_published = 1";
                error_log("Adding is_self_published=1 filter");
            }
            
            // Add filter for AI-enhanced stories
            if (isset($_GET['is_ai_enhanced']) && $_GET['is_ai_enhanced'] == 1) {
                $whereConditions[] = "s.is_ai_enhanced = 1";
                error_log("Adding is_ai_enhanced=1 filter");
            }
            
            // Add filter for source_type
            if (isset($_GET['source_type']) && in_array($_GET['source_type'], ['child', 'parent', 'classic'])) {
                $whereConditions[] = "s.source_type = :source_type";
                $params[':source_type'] = $_GET['source_type'];
                error_log("Adding source_type={$_GET['source_type']} filter");
            }
            
            // Handle filter parameter (direct query string)
            if (isset($_GET['filter'])) {
                $filterParams = [];
                parse_str($_GET['filter'], $filterParams);
                
                foreach ($filterParams as $key => $value) {
                    if (in_array($key, ['featured', 'is_sponsored', 'is_self_published', 'is_ai_enhanced']) && $value == 1) {
                        $whereConditions[] = "s.$key = 1";
                    }
                }
            }
            
            // Combine all conditions
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count with filters
            $countSql = "SELECT COUNT(*) FROM stories s WHERE $whereClause";
            $total = (int)$db->query($countSql)->fetchColumn();
            
            // Build final query with filters
            $sql = "SELECT s.* FROM stories s
                    WHERE $whereClause
                    ORDER BY s.$sortColumn $sortDir
                    LIMIT :offset, :limit";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit',  $pageSize, PDO::PARAM_INT);
            $stmt->execute();

            $stories = [];
            while ($row = $stmt->fetch()) {
                // Get author information from story_authors table
                $authorStmt = $db->prepare("
                    SELECT a.id, a.name, a.slug, a.bio, a.avatar_url
                    FROM story_authors sa
                    JOIN authors a ON sa.author_id = a.id
                    WHERE sa.story_id = ?
                ");
                $authorStmt->execute([$row['id']]);
                $author = $authorStmt->fetch();
                
                // Get tags for the story
                $tagStmt = $db->prepare("
                    SELECT t.id, t.name, t.slug
                    FROM story_tags st
                    JOIN tags t ON st.tag_id = t.id
                    WHERE st.story_id = ?
                ");
                $tagStmt->execute([$row['id']]);
                $tags = $tagStmt->fetchAll();
                
                $stories[] = [
                    'id'              => $row['id'],
                    'title'           => $row['title'],
                    'slug'            => $row['slug'],
                    'excerpt'         => $row['excerpt'],
                    'content'         => $row['content'],
                    'cover_url'       => $row['cover_url'],
                    'publishedAt'     => date('c', strtotime($row['created_at'])),
                    'featured'        => (bool)$row['featured'],
                    'is_sponsored'    => (bool)$row['is_sponsored'],
                    'is_self_published' => (bool)$row['is_self_published'],
                    'is_ai_enhanced'  => (bool)$row['is_ai_enhanced'],
                    'average_rating'  => (float)$row['average_rating'],
                    'review_count'    => (int)$row['review_count'],
                    'source_type'     => $row['source_type'],
                    'allow_reviews'   => (bool)$row['allow_reviews'],
                    // Debug info
                    'debug_source_type' => 'Value: ' . $row['source_type'] . ' | Type: ' . gettype($row['source_type']),
                    'debug_allow_reviews' => 'Value: ' . $row['allow_reviews'] . ' | Type: ' . gettype($row['allow_reviews']),
                    'author'          => $author ? [
                        'id'          => $author['id'],
                        'name'        => $author['name'],
                        'slug'        => $author['slug'],
                        'bio'         => $author['bio'],
                        'avatar_url'  => $author['avatar_url']
                    ] : null,
                    'tags'            => array_map(function($tag) {
                        return [
                            'id'      => $tag['id'],
                            'name'    => $tag['name'],
                            'slug'    => $tag['slug']
                        ];
                    }, $tags)
                ];
            }

            echo json_encode($stories);
            break;

        /* -----------------------------------
           BLOG POSTS (flat array)
           ----------------------------------- */
        case 'blog-posts':
            $posts = $db
                ->query("SELECT * FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC")
                ->fetchAll();
            echo json_encode($posts);
            break;

        /* -----------------------------------
           AUTHORS (flat array)
           ----------------------------------- */
        case 'authors':
            // Debug log all GET parameters for authors endpoint
            error_log("Authors API GET parameters: " . json_encode($_GET));
            
            // Build WHERE clause with filters
            $whereConditions = ["is_published = 1"];
            $params = [];
            
            // Add filter for author_type if specified
            if (isset($_GET['author_type']) && in_array($_GET['author_type'], ['retail', 'parent', 'child', 'educator'])) {
                $whereConditions[] = "author_type = :author_type";
                $params[':author_type'] = $_GET['author_type'];
                error_log("Adding author_type={$_GET['author_type']} filter");
            }
            
            // Combine all conditions
            $whereClause = implode(' AND ', $whereConditions);
            
            // Prepare and execute the query
            $sql = "SELECT * FROM authors WHERE $whereClause ORDER BY name ASC";
            $stmt = $db->prepare($sql);
            
            // Bind parameters if any
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $authors = $stmt->fetchAll();
            
            echo json_encode($authors);
            break;

        /* -----------------------------------
           TAGS (flat array)
           ----------------------------------- */
        case 'tags':
            $tags = $db
                ->query("SELECT * FROM tags WHERE 1 ORDER BY name ASC")
                ->fetchAll();
            echo json_encode($tags);
            break;

        /* -----------------------------------
           GAMES (flat array)
           ----------------------------------- */
        case 'games':
            $games = $db
                ->query("SELECT * FROM games WHERE is_published = 1 ORDER BY title ASC")
                ->fetchAll();
            echo json_encode($games);
            break;

        /* -----------------------------------
           DIRECTORY ITEMS (flat array)
           ----------------------------------- */
        case 'directory-items':
            $items = $db
                ->query("SELECT * FROM directory_items WHERE is_published = 1 ORDER BY title ASC")
                ->fetchAll();
            echo json_encode($items);
            break;

        /* -----------------------------------
           AI TOOLS (flat array)
           ----------------------------------- */
        case 'ai-tools':
            $tools = $db
                ->query("SELECT * FROM ai_tools WHERE is_published = 1 ORDER BY title ASC")
                ->fetchAll();
            echo json_encode($tools);
            break;

        /* -----------------------------------
           FALLBACK = 404 Not Found
           ----------------------------------- */
        default:
            http_response_code(404);
            echo json_encode([
                'error' => [
                    'status'  => 404,
                    'message' => 'Endpoint not found',
                ]
            ]);
    }

} catch (Throwable $e) {
    error_log("API fatal: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => ['status' => 500, 'message' => 'Internal server error'],
    ]);
}
