have y<?php
/**
 * Fix Games Endpoint Config
 * 
 * This script updates the games endpoint to use the correct database configuration
 * from config.php instead of hardcoded values.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Games Endpoint Config</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$endpointsPath = $apiPath . '/endpoints';

// Load the config
$config = require $apiPath . '/config/config.php';
$dbConfig = $config['db'];

echo "<h2>Database Configuration</h2>";
echo "<p>Using configuration from config.php:</p>";
echo "<ul>";
echo "<li>Host: {$dbConfig['host']}</li>";
echo "<li>Database: {$dbConfig['name']}</li>";
echo "<li>Username: {$dbConfig['user']}</li>";
echo "<li>Password: " . str_repeat('*', strlen($dbConfig['password'])) . "</li>";
echo "<li>Port: {$dbConfig['port']}</li>";
echo "</ul>";

// Find the GamesController
$gamesController = $endpointsPath . '/GamesController.php';
if (!file_exists($gamesController)) {
    $endpointsPath = $apiPath . '/Endpoints';
    $gamesController = $endpointsPath . '/GamesController.php';
    if (!file_exists($gamesController)) {
        echo "<p style='color:red'>❌ GamesController not found!</p>";
        exit;
    }
}

echo "<p>Found GamesController at: $gamesController</p>";

// Create a backup
$backupFile = $gamesController . '.bak.' . date('YmdHis');
if (copy($gamesController, $backupFile)) {
    echo "<p>Created backup at: $backupFile</p>";
}

// Get the namespace based on the endpoints directory name
$endpointsNamespaceSuffix = basename($endpointsPath);

// Update the GamesController
$controllerContent = <<<EOD
<?php
/**
 * Games Controller
 * 
 * Handles API requests for games
 */

namespace StoriesAPI\\$endpointsNamespaceSuffix;

use StoriesAPI\Core\Controller;
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
    echo "<p style='color:green'>✅ Updated GamesController successfully!</p>";
    
    // Test the connection
    echo "<h2>Testing Database Connection</h2>";
    
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$dbConfig['charset']};port={$dbConfig['port']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], $options);
        echo "<p style='color:green'>✅ Database connection successful!</p>";
        
        // Check if games table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'games'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            echo "<p style='color:green'>✅ Games table exists.</p>";
            
            // Check if the table has data
            $stmt = $pdo->query("SELECT COUNT(*) FROM games");
            $count = $stmt->fetchColumn();
            
            echo "<p>Games table has $count records.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ Games table does not exist. Creating it...</p>";
            
            // Create the games table
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
        }
        
        echo "<h2>Next Steps</h2>";
        echo "<p>Now run these scripts in order:</p>";
        echo "<ol>";
        echo "<li><a href='fix_directory_items_table.php'>fix_directory_items_table.php</a> - Create directory_items table</li>";
        echo "<li><a href='fix_ai_tools_table.php'>fix_ai_tools_table.php</a> - Create ai_tools table</li>";
        echo "</ol>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
        echo "<p>Please check that the database credentials in config.php are correct.</p>";
    }
} else {
    echo "<p style='color:red'>❌ Failed to update GamesController.</p>";
}