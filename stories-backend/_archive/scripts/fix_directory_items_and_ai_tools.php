<?php
/**
 * Fix Directory Items and AI Tools Endpoints
 * 
 * This script fixes the directory-items and ai-tools endpoints that are still returning 500 errors.
 * It will:
 * 1. Check if the tables exist and create them if needed
 * 2. Add sample data if the tables are empty
 * 3. Fix the controllers to properly format the response
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Directory Items and AI Tools Endpoints</h1>";

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
    
    // Step 1: Check and fix directory_items table
    echo "<h2>Checking Directory Items Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'directory_items'");
    $directoryTableExists = $stmt->rowCount() > 0;
    
    if (!$directoryTableExists) {
        echo "<p style='color:orange'>⚠️ Directory items table does not exist. Creating it...</p>";
        
        $sql = "CREATE TABLE directory_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL,
            url VARCHAR(255),
            category VARCHAR(100),
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ Directory items table created successfully!</p>";
        
        // Add sample data
        echo "<h3>Adding Sample Directory Items</h3>";
        
        $sampleItems = [
            [
                'title' => 'Story Writing Guide',
                'description' => 'A comprehensive guide to writing compelling stories',
                'slug' => 'story-writing-guide',
                'url' => 'https://example.com/guides/story-writing',
                'category' => 'guides',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Children\'s Book Publishers',
                'description' => 'Directory of publishers specializing in children\'s books',
                'slug' => 'childrens-book-publishers',
                'url' => 'https://example.com/publishers/childrens',
                'category' => 'publishers',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Writing Communities',
                'description' => 'Online communities for writers to share and get feedback',
                'slug' => 'writing-communities',
                'url' => 'https://example.com/communities',
                'category' => 'communities',
                'featured' => 0,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $sql = "INSERT INTO directory_items (title, description, slug, url, category, featured, is_published, published_at) 
                VALUES (:title, :description, :slug, :url, :category, :featured, :is_published, :published_at)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($sampleItems as $item) {
            $stmt->execute($item);
            echo "<p>Added directory item: {$item['title']}</p>";
        }
    } else {
        echo "<p style='color:green'>✅ Directory items table exists.</p>";
        
        // Check if the table has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM directory_items");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            echo "<p>Directory items table is empty. Adding sample data...</p>";
            
            // Add sample data
            $sampleItems = [
                [
                    'title' => 'Story Writing Guide',
                    'description' => 'A comprehensive guide to writing compelling stories',
                    'slug' => 'story-writing-guide',
                    'url' => 'https://example.com/guides/story-writing',
                    'category' => 'guides',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Children\'s Book Publishers',
                    'description' => 'Directory of publishers specializing in children\'s books',
                    'slug' => 'childrens-book-publishers',
                    'url' => 'https://example.com/publishers/childrens',
                    'category' => 'publishers',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Writing Communities',
                    'description' => 'Online communities for writers to share and get feedback',
                    'slug' => 'writing-communities',
                    'url' => 'https://example.com/communities',
                    'category' => 'communities',
                    'featured' => 0,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $sql = "INSERT INTO directory_items (title, description, slug, url, category, featured, is_published, published_at) 
                    VALUES (:title, :description, :slug, :url, :category, :featured, :is_published, :published_at)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($sampleItems as $item) {
                $stmt->execute($item);
                echo "<p>Added directory item: {$item['title']}</p>";
            }
        } else {
            echo "<p>Directory items table has $count records.</p>";
        }
    }
    
    // Step 2: Check and fix ai_tools table
    echo "<h2>Checking AI Tools Table</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'ai_tools'");
    $aiToolsTableExists = $stmt->rowCount() > 0;
    
    if (!$aiToolsTableExists) {
        echo "<p style='color:orange'>⚠️ AI tools table does not exist. Creating it...</p>";
        
        $sql = "CREATE TABLE ai_tools (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            slug VARCHAR(255) NOT NULL,
            url VARCHAR(255),
            category VARCHAR(100),
            featured TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p style='color:green'>✅ AI tools table created successfully!</p>";
        
        // Add sample data
        echo "<h3>Adding Sample AI Tools</h3>";
        
        $sampleTools = [
            [
                'title' => 'Story Generator',
                'description' => 'AI-powered tool to generate story ideas and outlines',
                'slug' => 'story-generator',
                'url' => 'https://example.com/tools/story-generator',
                'category' => 'writing',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Character Creator',
                'description' => 'Create detailed character profiles with AI assistance',
                'slug' => 'character-creator',
                'url' => 'https://example.com/tools/character-creator',
                'category' => 'writing',
                'featured' => 1,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Plot Analyzer',
                'description' => 'AI tool to analyze and improve your story\'s plot',
                'slug' => 'plot-analyzer',
                'url' => 'https://example.com/tools/plot-analyzer',
                'category' => 'analysis',
                'featured' => 0,
                'is_published' => 1,
                'published_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        $sql = "INSERT INTO ai_tools (title, description, slug, url, category, featured, is_published, published_at) 
                VALUES (:title, :description, :slug, :url, :category, :featured, :is_published, :published_at)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($sampleTools as $tool) {
            $stmt->execute($tool);
            echo "<p>Added AI tool: {$tool['title']}</p>";
        }
    } else {
        echo "<p style='color:green'>✅ AI tools table exists.</p>";
        
        // Check if the table has data
        $stmt = $pdo->query("SELECT COUNT(*) FROM ai_tools");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            echo "<p>AI tools table is empty. Adding sample data...</p>";
            
            // Add sample data
            $sampleTools = [
                [
                    'title' => 'Story Generator',
                    'description' => 'AI-powered tool to generate story ideas and outlines',
                    'slug' => 'story-generator',
                    'url' => 'https://example.com/tools/story-generator',
                    'category' => 'writing',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Character Creator',
                    'description' => 'Create detailed character profiles with AI assistance',
                    'slug' => 'character-creator',
                    'url' => 'https://example.com/tools/character-creator',
                    'category' => 'writing',
                    'featured' => 1,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Plot Analyzer',
                    'description' => 'AI tool to analyze and improve your story\'s plot',
                    'slug' => 'plot-analyzer',
                    'url' => 'https://example.com/tools/plot-analyzer',
                    'category' => 'analysis',
                    'featured' => 0,
                    'is_published' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ]
            ];
            
            $sql = "INSERT INTO ai_tools (title, description, slug, url, category, featured, is_published, published_at) 
                    VALUES (:title, :description, :slug, :url, :category, :featured, :is_published, :published_at)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($sampleTools as $tool) {
                $stmt->execute($tool);
                echo "<p>Added AI tool: {$tool['title']}</p>";
            }
        } else {
            echo "<p>AI tools table has $count records.</p>";
        }
    }
    
    // Step 3: Fix the DirectoryItemsController
    echo "<h2>Fixing Directory Items Controller</h2>";
    
    $directoryController = $endpointsPath . '/DirectoryItemsController.php';
    
    if (file_exists($directoryController)) {
        echo "<p>Directory Items Controller found. Creating backup...</p>";
        
        // Create a backup
        $backupFile = $directoryController . '.bak.' . date('YmdHis');
        if (copy($directoryController, $backupFile)) {
            echo "<p>Created backup at: $backupFile</p>";
        }
        
        // Update the controller
        $controllerContent = <<<'EOD'
<?php
/**
 * Directory Items Controller
 * 
 * Handles API requests for directory items
 */

namespace StoriesAPI\endpoints;

use StoriesAPI\Core\Controller;
use StoriesAPI\Utils\Response;

class DirectoryItemsController extends Controller {
    /**
     * Get a list of directory items
     */
    public function index() {
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 25;
        
        // Ensure valid pagination values
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        
        // Calculate offset
        $offset = ($page - 1) * $pageSize;
        
        // Get filter parameters
        $filters = [];
        if (isset($_GET['featured'])) {
            $filters['featured'] = $_GET['featured'] === 'true' ? 1 : 0;
        }
        if (isset($_GET['isPublished'])) {
            $filters['is_published'] = $_GET['isPublished'] === 'true' ? 1 : 0;
        }
        
        // Get sort parameter
        $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'id';
        $sortDirection = 'ASC';
        
        // Check if sort field has a direction prefix
        if (strpos($sortField, '-') === 0) {
            $sortField = substr($sortField, 1);
            $sortDirection = 'DESC';
        }
        
        // Map frontend field names to database column names
        $fieldMap = [
            'id' => 'id',
            'title' => 'title',
            'publishedAt' => 'published_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at'
        ];
        
        // Ensure the sort field is valid
        if (!isset($fieldMap[$sortField])) {
            $sortField = 'id';
        }
        
        // Get the database column name
        $sortColumn = $fieldMap[$sortField];
        
        // Build the sort clause
        $sortClause = "ORDER BY $sortColumn $sortDirection";
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM directory_items $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get directory items with pagination
            $query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM directory_items
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $items = $stmt->fetchAll();
            
            // Format items with the expected structure
            $formattedItems = Response::formatData($items);
            
            // Send paginated response
            Response::sendPaginated($formattedItems, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory items: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single directory item by ID
     * 
     * @param int $id The directory item ID
     */
    public function show($id) {
        try {
            $query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM directory_items
                WHERE id = :id";
            
            $stmt = $this->db->query($query, ['id' => $id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                $this->notFound('Directory item not found');
                return;
            }
            
            // Format item with the expected structure
            $formattedItem = Response::formatData($item);
            
            // Send success response
            Response::sendSuccess($formattedItem);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory item: ' . $e->getMessage());
        }
    }
    
    /**
     * Build a WHERE clause based on filters
     * 
     * @param array $filters The filters to apply
     * @return array The WHERE clause and parameters
     */
    private function buildWhereClause($filters) {
        $where = [];
        $params = [];
        
        foreach ($filters as $key => $value) {
            $where[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        return [
            'clause' => $whereClause,
            'params' => $params
        ];
    }
}
EOD;
        
        if (file_put_contents($directoryController, $controllerContent)) {
            echo "<p style='color:green'>✅ Updated Directory Items Controller successfully!</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update Directory Items Controller.</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ Directory Items Controller not found. Creating it...</p>";
        
        // Create the controller
        $controllerContent = <<<'EOD'
<?php
/**
 * Directory Items Controller
 * 
 * Handles API requests for directory items
 */

namespace StoriesAPI\endpoints;

use StoriesAPI\Core\Controller;
use StoriesAPI\Utils\Response;

class DirectoryItemsController extends Controller {
    /**
     * Get a list of directory items
     */
    public function index() {
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 25;
        
        // Ensure valid pagination values
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        
        // Calculate offset
        $offset = ($page - 1) * $pageSize;
        
        // Get filter parameters
        $filters = [];
        if (isset($_GET['featured'])) {
            $filters['featured'] = $_GET['featured'] === 'true' ? 1 : 0;
        }
        if (isset($_GET['isPublished'])) {
            $filters['is_published'] = $_GET['isPublished'] === 'true' ? 1 : 0;
        }
        
        // Get sort parameter
        $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'id';
        $sortDirection = 'ASC';
        
        // Check if sort field has a direction prefix
        if (strpos($sortField, '-') === 0) {
            $sortField = substr($sortField, 1);
            $sortDirection = 'DESC';
        }
        
        // Map frontend field names to database column names
        $fieldMap = [
            'id' => 'id',
            'title' => 'title',
            'publishedAt' => 'published_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at'
        ];
        
        // Ensure the sort field is valid
        if (!isset($fieldMap[$sortField])) {
            $sortField = 'id';
        }
        
        // Get the database column name
        $sortColumn = $fieldMap[$sortField];
        
        // Build the sort clause
        $sortClause = "ORDER BY $sortColumn $sortDirection";
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM directory_items $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get directory items with pagination
            $query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM directory_items
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $items = $stmt->fetchAll();
            
            // Format items with the expected structure
            $formattedItems = Response::formatData($items);
            
            // Send paginated response
            Response::sendPaginated($formattedItems, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory items: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single directory item by ID
     * 
     * @param int $id The directory item ID
     */
    public function show($id) {
        try {
            $query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM directory_items
                WHERE id = :id";
            
            $stmt = $this->db->query($query, ['id' => $id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                $this->notFound('Directory item not found');
                return;
            }
            
            // Format item with the expected structure
            $formattedItem = Response::formatData($item);
            
            // Send success response
            Response::sendSuccess($formattedItem);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch directory item: ' . $e->getMessage());
        }
    }
    
    /**
     * Build a WHERE clause based on filters
     * 
     * @param array $filters The filters to apply
     * @return array The WHERE clause and parameters
     */
    private function buildWhereClause($filters) {
        $where = [];
        $params = [];
        
        foreach ($filters as $key => $value) {
            $where[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        return [
            'clause' => $whereClause,
            'params' => $params
        ];
    }
}
EOD;
        
        if (file_put_contents($directoryController, $controllerContent)) {
            echo "<p style='color:green'>✅ Created Directory Items Controller successfully!</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to create Directory Items Controller.</p>";
        }
    }
    
    // Step 4: Fix the AIToolsController
    echo "<h2>Fixing AI Tools Controller</h2>";
    
    $aiToolsController = $endpointsPath . '/AiToolsController.php';
    
    if (file_exists($aiToolsController)) {
        echo "<p>AI Tools Controller found. Creating backup...</p>";
        
        // Create a backup
        $backupFile = $aiToolsController . '.bak.' . date('YmdHis');
        if (copy($aiToolsController, $backupFile)) {
            echo "<p>Created backup at: $backupFile</p>";
        }
        
        // Update the controller
        $controllerContent = <<<'EOD'
<?php
/**
 * AI Tools Controller
 * 
 * Handles API requests for AI tools
 */

namespace StoriesAPI\endpoints;

use StoriesAPI\Core\Controller;
use StoriesAPI\Utils\Response;

class AiToolsController extends Controller {
    /**
     * Get a list of AI tools
     */
    public function index() {
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 25;
        
        // Ensure valid pagination values
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        
        // Calculate offset
        $offset = ($page - 1) * $pageSize;
        
        // Get filter parameters
        $filters = [];
        if (isset($_GET['featured'])) {
            $filters['featured'] = $_GET['featured'] === 'true' ? 1 : 0;
        }
        if (isset($_GET['isPublished'])) {
            $filters['is_published'] = $_GET['isPublished'] === 'true' ? 1 : 0;
        }
        
        // Get sort parameter
        $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'id';
        $sortDirection = 'ASC';
        
        // Check if sort field has a direction prefix
        if (strpos($sortField, '-') === 0) {
            $sortField = substr($sortField, 1);
            $sortDirection = 'DESC';
        }
        
        // Map frontend field names to database column names
        $fieldMap = [
            'id' => 'id',
            'title' => 'title',
            'publishedAt' => 'published_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at'
        ];
        
        // Ensure the sort field is valid
        if (!isset($fieldMap[$sortField])) {
            $sortField = 'id';
        }
        
        // Get the database column name
        $sortColumn = $fieldMap[$sortField];
        
        // Build the sort clause
        $sortClause = "ORDER BY $sortColumn $sortDirection";
        
        try {
            // Build the WHERE clause
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM ai_tools $whereClause";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            
            // Get AI tools with pagination
            $query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM ai_tools
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            $stmt = $this->db->query($query, $params);
            $tools = $stmt->fetchAll();
            
            // Format tools with the expected structure
            $formattedTools = Response::formatData($tools);
            
            // Send paginated response
            Response::sendPaginated($formattedTools, $page, $pageSize, $total);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch AI tools: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single AI tool by ID
     * 
     * @param int $id The AI tool ID
     */
    public function show($id) {
        try {
            $query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM ai_tools
                WHERE id = :id";
            
            $stmt = $this->db->query($query, ['id' => $id]);
            $tool = $stmt->fetch();
            
            if (!$tool) {
                $this->notFound('AI tool not found');
                return;
            }
            
            // Format tool with the expected structure
            $formattedTool = Response::formatData($tool);
            
            // Send success response
            Response::sendSuccess($formattedTool);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch AI tool: ' . $e->getMessage());
        }
    }
    
    /**
     * Build a WHERE clause based on filters
     * 
     * @param array $filters The filters to apply
     * @return array The WHERE clause and parameters
     */
    private function buildWhereClause($filters) {
        $where = [];
        $params = [];
        
        foreach ($filters as $key => $value) {
            $where[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        return [
            'clause' => $whereClause,
            'params' => $params
        ];
    }
}
EOD;
        
        if (file_put_contents($aiToolsController, $controllerContent)) {
            echo "<p style='color:green'>✅ Updated AI Tools Controller successfully!</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update AI Tools Controller.</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ AI Tools Controller not found. Creating it...</p>";
        
        // Create the controller
        $controllerContent = <<<'EOD'
<?php
/**
 * AI Tools Controller
 * 
 * Handles API requests for AI tools
 */

