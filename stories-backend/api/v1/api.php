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
                $stmt = $db->prepare("INSERT INTO media (filename, file_path, file_type, file_size, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([basename($filename), $url, $filetype, strlen($input)]);
                
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

                    // Generate slug from name
                    if (isset($input['name'])) {
                        $name = trim($input['name']);
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                        $input['slug'] = $slug;
                    }

                    // Set default values if not provided
                    if (!isset($input['age'])) {
                        $input['age'] = null;
                    }
                    if (!isset($input['location'])) {
                        $input['location'] = null;
                    }
                    if (!isset($input['author_type'])) {
                        $input['author_type'] = 'parent';
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
                    echo json_encode(['id' => $newId, 'slug' => $input['slug']]);
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

                    // Generate slug from title
                    if (isset($input['title'])) {
                        $title = trim($input['title']);
                        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                        $input['slug'] = $slug;
                    }

                    // Calculate reading time
                    if (isset($input['content'])) {
                        $wordCount = str_word_count(strip_tags($input['content']));
                        $input['estimated_reading_time'] = ceil($wordCount / 200) . ' minutes';
                    }

                    // Start transaction
                    $db->beginTransaction();

                    // Insert story
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

                    // Analyze content and assign tags
                    if (isset($input['content'])) {
                        $content = strtolower($input['content']);
                        
                        // Define tag patterns
                        $tagPatterns = [
                            'adventure' => '/adventure|journey|quest|explore|discover/i',
                            'fantasy' => '/magic|wizard|dragon|fairy|enchanted/i',
                            'mystery' => '/mystery|clue|detective|solve|secret/i',
                            'animals' => '/dog|cat|pet|animal|bird|horse/i',
                            'family' => '/family|parent|mother|father|sister|brother/i',
                            'friendship' => '/friend|friendship|together|share|help/i',
                            'school' => '/school|teacher|student|class|learn/i',
                            'nature' => '/nature|tree|flower|garden|forest|river/i'
                        ];

                        // Check content against patterns and assign tags
                        foreach ($tagPatterns as $tag => $pattern) {
                            if (preg_match($pattern, $content)) {
                                // Get or create tag
                                $tagStmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                                $tagStmt->execute([$tag]);
                                $tagId = $tagStmt->fetchColumn();
                                
                                if (!$tagId) {
                                    $tagStmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                                    $tagStmt->execute([$tag, strtolower($tag)]);
                                    $tagId = $db->lastInsertId();
                                }
                                
                                // Associate tag with story
                                $storyTagStmt = $db->prepare("INSERT INTO story_tags (story_id, tag_id) VALUES (?, ?)");
                                $storyTagStmt->execute([$newId, $tagId]);
                            }
                        }
                    }

                    $db->commit();
                    http_response_code(201);
                    echo json_encode(['id' => $newId, 'slug' => $input['slug']]);
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

            // Check if user is admin (from Authorization header)
            $isAdmin = false;
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
                $token = substr($authHeader, 7);
                $stmt = $db->prepare("SELECT u.role FROM auth_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ? AND t.expires_at > NOW()");
                $stmt->execute([$token]);
                $userRole = $stmt->fetchColumn();
                $isAdmin = $userRole === 'admin';
            }

            $stories = [];
            while ($row = $stmt->fetch()) {
                // Calculate reading time based on content length
                $wordCount = str_word_count(strip_tags($row['content']));
                $readingTime = ceil($wordCount / 200) . ' minutes'; // Average reading speed

                // Get author information from story_authors table
                $authorStmt = $db->prepare("
                    SELECT a.id, a.name, a.slug, a.bio, a.avatar_url, a.age, a.location, a.author_type
                    FROM story_authors sa
                    JOIN authors a ON sa.author_id = a.id
                    WHERE sa.story_id = ?
                ");
                $authorStmt->execute([$row['id']]);
                $author = $authorStmt->fetch();

                // Determine age group based on author age if available
                $ageGroup = '12+'; // default
                if ($author && $author['age'] !== null) {
                    if ($author['age'] <= 5) $ageGroup = '3-5';
                    else if ($author['age'] <= 8) $ageGroup = '6-8';
                    else if ($author['age'] <= 12) $ageGroup = '9-12';
                    else $ageGroup = '12+';
                }
                
                // Get tags for the story
                $tagStmt = $db->prepare("
                    SELECT t.id, t.name, t.slug
                    FROM story_tags st
                    JOIN tags t ON st.tag_id = t.id
                    WHERE st.story_id = ?
                ");
                $tagStmt->execute([$row['id']]);
                $tags = $tagStmt->fetchAll();
                
                // Get media information for cover image
                $mediaStmt = null;
                $media = null;
                
                // Try to find media record by URL or filename
                if (!empty($row['cover_url'])) {
                    // Extract filename from URL
                    $coverFilename = basename($row['cover_url']);
                    
                    $mediaStmt = $db->prepare("
                        SELECT * FROM media
                        WHERE file_path = ?
                        OR thumbnail_url = ?
                        OR small_url = ?
                        OR medium_url = ?
                        OR large_url = ?
                        OR filename = ?
                    ");
                    $mediaStmt->execute([
                        $row['cover_url'], $row['cover_url'], $row['cover_url'],
                        $row['cover_url'], $row['cover_url'], $coverFilename
                    ]);
                    $media = $mediaStmt->fetch();
                }
                
                // Prepare cover image URLs
                $coverUrls = [
                    'default' => $row['cover_url'],
                    'thumbnail' => null,
                    'small' => null,
                    'medium' => null,
                    'large' => null
                ];
                
                // If we found a media record, use its URLs
                if ($media) {
                    $coverUrls['thumbnail'] = $media['thumbnail_url'] ?: $row['cover_url'];
                    $coverUrls['small'] = $media['small_url'] ?: $row['cover_url'];
                    $coverUrls['medium'] = $media['medium_url'] ?: $row['cover_url'];
                    $coverUrls['large'] = $media['large_url'] ?: $row['cover_url'];
                }
                
                // Determine if reviews should be shown (not for child authors)
                $showReviews = !($author && $author['author_type'] === 'child');

                $stories[] = [
                    'id'              => $row['id'],
                    'title'           => $row['title'],
                    'slug'            => $row['slug'],
                    'excerpt'         => $row['excerpt'],
                    'content'         => $row['content'],
                    'cover_url'       => $row['cover_url'],
                    'cover_urls'      => $coverUrls,
                    'publishedAt'     => date('c', strtotime($row['created_at'])),
                    'featured'        => (bool)$row['featured'],
                    'is_sponsored'    => (bool)$row['is_sponsored'],
                    'is_self_published' => (bool)$row['is_self_published'],
                    'is_ai_enhanced'  => (bool)$row['is_ai_enhanced'],
                    'average_rating'  => $showReviews ? (float)$row['average_rating'] : null,
                    'review_count'    => $showReviews ? (int)$row['review_count'] : 0,
                    'source_type'     => $row['source_type'],
                    'allow_reviews'   => $showReviews && (bool)$row['allow_reviews'],
                    'show_reviews'    => $showReviews,
                    'needs_moderation' => (bool)$row['needs_moderation'],
                    'show_moderation' => $isAdmin && (bool)$row['needs_moderation'],
                    'estimated_reading_time' => $readingTime,
                    'age_group'       => $ageGroup,
                    'author'          => $author ? [
                        'id'          => $author['id'],
                        'name'        => $author['name'],
                        'slug'        => $author['slug'],
                        'bio'         => $author['bio'],
                        'avatar_url'  => $author['avatar_url'],
                        'age'         => $author['age'],
                        'location'    => $author['location'],
                        'author_type' => $author['author_type']
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
            if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['id'])) {
                try {
                    $authorId = (int)$_GET['id'];
                    
                    // Check if author has any stories
                    $stmt = $db->prepare("SELECT COUNT(*) FROM story_authors WHERE author_id = ?");
                    $stmt->execute([$authorId]);
                    $storyCount = (int)$stmt->fetchColumn();
                    
                    if ($storyCount > 0) {
                        http_response_code(400);
                        echo json_encode(['error' => [
                            'status' => 400,
                            'message' => 'Cannot delete author with existing stories. Please remove story associations first.',
                            'story_count' => $storyCount
                        ]]);
                        break;
                    }
                    
                    // Start transaction
                    $db->beginTransaction();
                    
                    // Delete the author (no need to delete from story_authors since we checked there are none)
                    $stmt = $db->prepare("DELETE FROM authors WHERE id = ?");
                    $stmt->execute([$authorId]);
                    
                    $db->commit();
                    echo json_encode(['success' => true]);
                    break;
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                    error_log("Author deletion error: " . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => ['status' => 500, 'message' => 'Failed to delete author']]);
                    break;
                }
            }
            
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
            
            // Add filter for slug if specified
            if (isset($_GET['slug'])) {
                $whereConditions[] = "slug = :slug";
                $params[':slug'] = $_GET['slug'];
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
            // Build WHERE clause with filters
            $whereConditions = ["1"];
            $params = [];
            
            // Filter by slug if provided
            if (isset($_GET['slug'])) {
                $whereConditions[] = "t.slug = :slug";
                $params[':slug'] = $_GET['slug'];
            }
            
            // Combine all conditions
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get tag information with story count
            $sql = "SELECT t.*,
                    (SELECT COUNT(*) FROM story_tags st
                     JOIN stories s ON st.story_id = s.id
                     WHERE st.tag_id = t.id AND s.is_published = 1) as story_count
                    FROM tags t
                    WHERE $whereClause
                    ORDER BY t.name ASC";
            
            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $tags = $stmt->fetchAll();

            // If slug is provided, also get associated stories
            if (isset($_GET['slug'])) {
                foreach ($tags as &$tag) {
                    $storyStmt = $db->prepare("
                        SELECT s.*, a.name as author_name, a.slug as author_slug,
                               a.age as author_age, a.author_type,
                               (SELECT GROUP_CONCAT(t2.name)
                                FROM story_tags st2
                                JOIN tags t2 ON st2.tag_id = t2.id
                                WHERE st2.story_id = s.id) as all_tags
                        FROM stories s
                        JOIN story_tags st ON s.id = st.story_id
                        LEFT JOIN story_authors sa ON s.id = sa.story_id
                        LEFT JOIN authors a ON sa.author_id = a.id
                        WHERE st.tag_id = ? AND s.is_published = 1
                        ORDER BY s.created_at DESC
                    ");
                    $storyStmt->execute([$tag['id']]);
                    $tag['stories'] = array_map(function($story) {
                        // Calculate reading time
                        $wordCount = str_word_count(strip_tags($story['content']));
                        $readingTime = ceil($wordCount / 200) . ' minutes';
                        
                        // Determine age group based on author age
                        $ageGroup = '12+';
                        if ($story['author_age'] !== null) {
                            if ($story['author_age'] <= 5) $ageGroup = '3-5';
                            else if ($story['author_age'] <= 8) $ageGroup = '6-8';
                            else if ($story['author_age'] <= 12) $ageGroup = '9-12';
                        }
                        
                        // Determine if reviews should be shown
                        $showReviews = !($story['author_type'] === 'child');
                        
                        return [
                            'id' => $story['id'],
                            'title' => $story['title'],
                            'slug' => $story['slug'],
                            'excerpt' => $story['excerpt'],
                            'cover_url' => $story['cover_url'],
                            'publishedAt' => date('c', strtotime($story['created_at'])),
                            'author_name' => $story['author_name'],
                            'author_slug' => $story['author_slug'],
                            'estimated_reading_time' => $readingTime,
                            'age_group' => $ageGroup,
                            'show_reviews' => $showReviews,
                            'all_tags' => $story['all_tags'] ? explode(',', $story['all_tags']) : [],
                            'source_type' => $story['source_type']
                        ];
                    }, $storyStmt->fetchAll());
                }
            }
            
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
