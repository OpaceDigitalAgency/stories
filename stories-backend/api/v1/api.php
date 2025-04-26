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
        case 'stories':
            $total = (int)$db
                ->query("SELECT COUNT(*) FROM stories WHERE is_published = 1")
                ->fetchColumn();

            $sql = "SELECT s.* FROM stories s
                    WHERE s.is_published = 1
                    ORDER BY s.$sortColumn $sortDir
                    LIMIT :offset, :limit";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit',  $pageSize, PDO::PARAM_INT);
            $stmt->execute();

            $stories = [];
            while ($row = $stmt->fetch()) {
                $stories[] = [
                    'id'      => $row['id'],
                    'title'   => $row['title'],
                    'slug'    => $row['slug'],
                    'excerpt' => $row['excerpt'],
                    'content' => $row['content'],
                    'publishedAt' => date('c', strtotime($row['created_at'])),
                    /* … other story fields … */
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
            $authors = $db
                ->query("SELECT * FROM authors WHERE is_published = 1 ORDER BY name ASC")
                ->fetchAll();
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
