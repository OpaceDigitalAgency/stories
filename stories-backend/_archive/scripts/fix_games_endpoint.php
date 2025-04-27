<?php
/**
 * Fix Games Endpoint
 * 
 * This script fixes the games endpoint that is still returning 500 errors.
 * It will:
 * 1. Check if the games table exists and create it if needed
 * 2. Add sample data if the table is empty
 * 3. Fix the GamesController to properly format the response
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Games Endpoint</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$endpointsPath = $apiPath . '/endpoints';

// Database connection parameters - adjust these to match your configuration
$host = 'localhost';
$dbname = 'stories';
$username = 'stories_user';
$password = 'stories_password';

echo "<h2>Database Connection</h2>";
echo "<p>Attempting to connect to database: $dbname on $host</p>";

try {
    // Create PDO connection
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "<p style='color:green'>✅ Database connection successful!</p>";
    
    // Step 1: Check and fix games table
    echo "<h2>Checking Games Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'games'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<p style='color:orange'>⚠️ Games table does not exist. Creating it...</p>";
        
        $sql = "CREATE TABLE games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL,
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Games table created successfully!</p>";
        
        // Add sample data
        echo "<h3>Adding Sample Games</h3>";
        
        $sampleGames = [
            [
                'title' => 'Word Adventure',
                'description' => 'A fun word-finding game that challenges your vocabulary',
                'slug' => 'word-adventure',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Story Quest',
                'description' => 'Interactive storytelling game where your choices matter',
                'slug' => 'story-quest',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Puzzle Master',
                'description' => 'Collection of brain-teasing puzzles for all ages',
                'slug' => 'puzzle-master',
                'featured' => 0,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $sql = "INSERT INTO games (title, description, slug, featured, is_published, published_at) 
                VALUES (:title, :description, :slug, :featured, :is_published, :published_at)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($sampleGames as $game) {
            $stmt->execute($game);
            echo "<p>Added game: {$game['title']}</p>";
        }
    } else {
        echo "<p style='color:green'>✅ Games table exists.</p>";
        
        // Check if the table has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM games");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            echo "<p>Games table is empty. Adding sample data...</p>";
            
            // Add sample data
            $sampleGames = [
                [
                    'title' => 'Word Adventure',
                    'description' => 'A fun word-finding game that challenges your vocabulary',
                    'slug' => 'word-adventure',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Story Quest',
                    'description' => 'Interactive storytelling game where your choices matter',
                    'slug' => 'story-quest',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Puzzle Master',
                    'description' => 'Collection of brain-teasing puzzles for all ages',
                    'slug' => 'puzzle-master',
                    'featured' => 0,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $sql = "INSERT INTO games (title, description, slug, featured, is_published, published_at) 
                    VALUES (:title, :description, :slug, :featured, :is_published, :published_at)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($sampleGames as $game) {
                $stmt->execute($game);
                echo "<p>Added game: {$game['title']}</p>";
            }
        } else {
            echo "<p>Games table has $count records.</p>";
        }
    }
    
    // Check if endpoints directory exists
    if (!is_dir($endpointsPath)) {
        echo "<p style='color:orange'>⚠️ Endpoints directory not found at: $endpointsPath</p>";
        
        // Try uppercase version
        $endpointsPath = $apiPath . '/Endpoints';
        if (!is_dir($endpointsPath)) {
            echo "<p style='color:red'>❌ Endpoints directory not found at: $endpointsPath</p>";
            echo "<p>Please run the fix_controller_loading.php script first to consolidate controller folders.</p>";
            exit;
        } else {
            echo "<p>Using uppercase Endpoints directory: $endpointsPath</p>";
        }
    } else {
        echo "<p>Using lowercase endpoints directory: $endpointsPath</p>";
    }
    
    // Step 2: Fix the GamesController
    echo "<h2>Fixing Games Controller</h2>";
    
    $gamesController = $endpointsPath . '/GamesController.php';
    
    if (file_exists($gamesController)) {
        echo "<p style='color:green'>✅ Games Controller found at: $gamesController</p>";
        
        // Create a backup
        $backupFile = $gamesController . '.bak.' . date('YmdHis');
        if (copy($gamesController, $backupFile)) {
            echo "<p>Created backup at: $backupFile</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ Games Controller not found. Will create a new one.</p>";
    }
    
    // Get the namespace based on the endpoints directory name
    $namespaceSuffix = basename($endpointsPath);
    
    // Get the core namespace based on the Core directory name
    $corePath = $apiPath . '/Core';
    if (!is_dir($corePath)) {
        $corePath = $apiPath . '/core';
    }
    $coreNamespaceSuffix = basename($corePath);
    
    // Create or update the controller
    $controllerContent = <<<EOD
<?php
/**
 * Games Controller
 * 
 * Handles API requests for games
 */

namespace StoriesAPI\\$namespaceSuffix;

use StoriesAPI\\$coreNamespaceSuffix\\Controller;
use StoriesAPI\Utils\Response;

class GamesController extends Controller {
    /**
     * Get a list of games
     */
    public function index() {
        // Get pagination parameters
        \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;
        \$pageSize = isset(\$_GET['pageSize']) ? (int)\$_GET['pageSize'] : 25;
        
        // Ensure valid pagination values
        \$page = max(1, \$page);
        \$pageSize = max(1, min(100, \$pageSize));
        
        // Calculate offset
        \$offset = (\$page - 1) * \$pageSize;
        
        // Get filter parameters
        \$filters = [];
        if (isset(\$_GET['featured'])) {
            \$filters['featured'] = \$_GET['featured'] === 'true' ? 1 : 0;
        }
        if (isset(\$_GET['isPublished'])) {
            \$filters['is_published'] = \$_GET['isPublished'] === 'true' ? 1 : 0;
        }
        
        // Get sort parameter
        \$sortField = isset(\$_GET['sort']) ? \$_GET['sort'] : 'id';
        \$sortDirection = 'ASC';
        
        // Check if sort field has a direction prefix
        if (strpos(\$sortField, '-') === 0) {
            \$sortField = substr(\$sortField, 1);
            \$sortDirection = 'DESC';
        }
        
        // Map frontend field names to database column names
        \$fieldMap = [
            'id' => 'id',
            'title' => 'title',
            'publishedAt' => 'published_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at'
        ];
        
        // Ensure the sort field is valid
        if (!isset(\$fieldMap[\$sortField])) {
            \$sortField = 'id';
        }
        
        // Get the database column name
        \$sortColumn = \$fieldMap[\$sortField];
        
        // Build the sort clause
        \$sortClause = "ORDER BY \$sortColumn \$sortDirection";
        
        try {
            // Build the WHERE clause
            \$whereData = \$this->buildWhereClause(\$filters);
            \$whereClause = \$whereData['clause'];
            \$params = \$whereData['params'];
            
            // Count total records
            \$countQuery = "SELECT COUNT(*) as total FROM games \$whereClause";
            \$stmt = \$this->db->query(\$countQuery, \$params);
            \$total = \$stmt->fetch()['total'];
            
            // Get games with pagination
            \$query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                \$whereClause
                \$sortClause
                LIMIT \$offset, \$pageSize";
            
            \$stmt = \$this->db->query(\$query, \$params);
            \$games = \$stmt->fetchAll();
            
            // Format games with the expected structure
            \$formattedGames = Response::formatData(\$games);
            
            // Send paginated response
            Response::sendPaginated(\$formattedGames, \$page, \$pageSize, \$total);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch games: ' . \$e->getMessage());
        }
    }
    
    /**
     * Get a single game by ID
     * 
     * @param int \$id The game ID
     */
    public function show(\$id) {
        try {
            \$query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                WHERE id = :id";
            
            \$stmt = \$this->db->query(\$query, ['id' => \$id]);
            \$game = \$stmt->fetch();
            
            if (!\$game) {
                \$this->notFound('Game not found');
                return;
            }
            
            // Format game with the expected structure
            \$formattedGame = Response::formatData(\$game);
            
            // Send success response
            Response::sendSuccess(\$formattedGame);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch game: ' . \$e->getMessage());
        }
    }
    
    /**
     * Build a WHERE clause based on filters
     * 
     * @param array \$filters The filters to apply
     * @return array The WHERE clause and parameters
     */
    private function buildWhereClause(\$filters) {
        \$where = [];
        \$params = [];
        
        foreach (\$filters as \$key => \$value) {
            \$where[] = "\$key = :\$key";
            \$params[\$key] = \$value;
        }
        
        \$whereClause = empty(\$where) ? '' : 'WHERE ' . implode(' AND ', \$where);
        
        return [
            'clause' => \$whereClause,
            'params' => \$params
        ];
    }
}
EOD;
    
    if (file_put_contents($gamesController, $controllerContent)) {
        echo "<p style='color:green'>✅ Games Controller updated successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to update Games Controller.</p>";
    }
    
    // Step 3: Check routes file
    echo "<h2>Checking Routes Configuration</h2>";
    
    $routesFile = $apiPath . '/routes.php';
    
    if (file_exists($routesFile)) {
        echo "<p style='color:green'>✅ Routes file found at: $routesFile</p>";
        
        $routesContent = file_get_contents($routesFile);
        
        if (strpos($routesContent, 'games') === false) {
            echo "<p style='color:orange'>⚠️ Games route not found in routes file. Adding it...</p>";
            
            // Create a backup
            $backupFile = $routesFile . '.bak.' . date('YmdHis');
            if (copy($routesFile, $backupFile)) {
                echo "<p>Created backup of routes file at: $backupFile</p>";
            }
            
            // Add games route
            $routeToAdd = "\n// Games routes\n\$router->get('/games', 'StoriesAPI\\$namespaceSuffix\\GamesController@index');\n\$router->get('/games/{id}', 'StoriesAPI\\$namespaceSuffix\\GamesController@show');\n";
            
            // Find a good place to add the route
            $pos = strrpos($routesContent, '?>');
            if ($pos !== false) {
                $newContent = substr($routesContent, 0, $pos) . $routeToAdd . substr($routesContent, $pos);
            } else {
                $newContent = $routesContent . $routeToAdd;
            }
            
            if (file_put_contents($routesFile, $newContent)) {
                echo "<p style='color:green'>✅ Added games routes to routes file.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to update routes file.</p>";
            }
        } else {
            echo "<p style='color:green'>✅ Games route already exists in routes file.</p>";
            
            // Check if the namespace is correct
            $pattern = "/games.*?StoriesAPI\\\\([^\\\\]+)\\\\GamesController/";
            if (preg_match($pattern, $routesContent, $matches)) {
                $routeNamespace = $matches[1];
                if ($routeNamespace !== $namespaceSuffix) {
                    echo "<p style='color:orange'>⚠️ Games route is using a different namespace: $routeNamespace. Updating...</p>";
                    
                    // Create a backup
                    $backupFile = $routesFile . '.bak.' . date('YmdHis');
                    if (copy($routesFile, $backupFile)) {
                        echo "<p>Created backup of routes file at: $backupFile</p>";
                    }
                    
                    // Update the namespace
                    $newContent = preg_replace("/StoriesAPI\\\\$routeNamespace\\\\GamesController/", "StoriesAPI\\$namespaceSuffix\\GamesController", $routesContent);
                    
                    if (file_put_contents($routesFile, $newContent)) {
                        echo "<p style='color:green'>✅ Updated games route namespace in routes file.</p>";
                    } else {
                        echo "<p style='color:red'>❌ Failed to update routes file.</p>";
                    }
                } else {
                    echo "<p style='color:green'>✅ Games route is using the correct namespace.</p>";
                }
            }
        }
    } else {
        echo "<p style='color:red'>❌ Routes file not found at: $routesFile</p>";
    }
    
    // Step 4: Test the games endpoint
    echo "<h2>Testing Games Endpoint</h2>";
    
    // Get the base URL
    $baseUrl = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl = "$protocol://$baseUrl";
    
    // Build the API URL
    $apiUrl = "$baseUrl/api/v1/games";
    
    echo "<p>Testing games endpoint at: $apiUrl</p>";
    echo "<p>You can manually test the endpoint by visiting: <a href='$apiUrl' target='_blank'>$apiUrl</a></p>";
    
    echo "<h2>Next Steps</h2>";
    echo "<p>Now test the API endpoints using the <a href='test_api_format.php'>test_api_format.php</a> script.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
    
    echo "<h2>Troubleshooting</h2>";
    echo "<ol>";
    echo "<li>Check that the database credentials are correct. Run the <a href='fix_database_credentials.php'>fix_database_credentials.php</a> script to update them.</li>";
    echo "<li>Make sure the database exists and is accessible.</li>";
    echo "<li>Verify that the user has permission to create tables.</li>";
    echo "</ol>";
}