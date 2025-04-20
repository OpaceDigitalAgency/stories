<?php
/**
 * Fix AI Tools Controller
 * 
 * This script updates or creates the AiToolsController to properly format responses.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix AI Tools Controller</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$endpointsPath = $apiPath . '/endpoints';

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

// Check if AiToolsController exists
$aiToolsController = $endpointsPath . '/AiToolsController.php';

if (file_exists($aiToolsController)) {
    echo "<p style='color:green'>✅ AI Tools Controller found at: $aiToolsController</p>";
    
    // Create a backup
    $backupFile = $aiToolsController . '.bak.' . date('YmdHis');
    if (copy($aiToolsController, $backupFile)) {
        echo "<p>Created backup at: $backupFile</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ AI Tools Controller not found. Will create a new one.</p>";
}

// Get the namespace based on the endpoints directory name
$namespaceSuffix = basename($endpointsPath);

// Create or update the controller
$controllerContent = <<<EOD
<?php
/**
 * AI Tools Controller
 * 
 * Handles API requests for AI tools
 */

namespace StoriesAPI\\$namespaceSuffix;

use StoriesAPI\Core\Controller;
use StoriesAPI\Utils\Response;

class AiToolsController extends Controller {
    /**
     * Get a list of AI tools
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
            \$countQuery = "SELECT COUNT(*) as total FROM ai_tools \$whereClause";
            \$stmt = \$this->db->query(\$countQuery, \$params);
            \$total = \$stmt->fetch()['total'];
            
            // Get AI tools with pagination
            \$query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM ai_tools
                \$whereClause
                \$sortClause
                LIMIT \$offset, \$pageSize";
            
            \$stmt = \$this->db->query(\$query, \$params);
            \$tools = \$stmt->fetchAll();
            
            // Format tools with the expected structure
            \$formattedTools = Response::formatData(\$tools);
            
            // Send paginated response
            Response::sendPaginated(\$formattedTools, \$page, \$pageSize, \$total);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch AI tools: ' . \$e->getMessage());
        }
    }
    
    /**
     * Get a single AI tool by ID
     * 
     * @param int \$id The AI tool ID
     */
    public function show(\$id) {
        try {
            \$query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM ai_tools
                WHERE id = :id";
            
            \$stmt = \$this->db->query(\$query, ['id' => \$id]);
            \$tool = \$stmt->fetch();
            
            if (!\$tool) {
                \$this->notFound('AI tool not found');
                return;
            }
            
            // Format tool with the expected structure
            \$formattedTool = Response::formatData(\$tool);
            
            // Send success response
            Response::sendSuccess(\$formattedTool);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch AI tool: ' . \$e->getMessage());
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

if (file_put_contents($aiToolsController, $controllerContent)) {
    echo "<p style='color:green'>✅ AI Tools Controller updated successfully!</p>";
} else {
    echo "<p style='color:red'>❌ Failed to update AI Tools Controller.</p>";
}

// Check if routes file exists and contains ai-tools route
$routesFile = $apiPath . '/routes.php';

if (file_exists($routesFile)) {
    echo "<h2>Checking Routes Configuration</h2>";
    
    $routesContent = file_get_contents($routesFile);
    
    if (strpos($routesContent, 'ai-tools') === false) {
        echo "<p style='color:orange'>⚠️ AI tools route not found in routes file. Adding it...</p>";
        
        // Create a backup
        $backupFile = $routesFile . '.bak.' . date('YmdHis');
        if (copy($routesFile, $backupFile)) {
            echo "<p>Created backup of routes file at: $backupFile</p>";
        }
        
        // Add ai-tools route
        $routeToAdd = "\n// AI tools routes\n\$router->get('/ai-tools', 'StoriesAPI\\$namespaceSuffix\\AiToolsController@index');\n\$router->get('/ai-tools/{id}', 'StoriesAPI\\$namespaceSuffix\\AiToolsController@show');\n";
        
        // Find a good place to add the route
        $pos = strrpos($routesContent, '?>');
        if ($pos !== false) {
            $newContent = substr($routesContent, 0, $pos) . $routeToAdd . substr($routesContent, $pos);
        } else {
            $newContent = $routesContent . $routeToAdd;
        }
        
        if (file_put_contents($routesFile, $newContent)) {
            echo "<p style='color:green'>✅ Added ai-tools routes to routes file.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update routes file.</p>";
        }
    } else {
        echo "<p style='color:green'>✅ AI tools route already exists in routes file.</p>";
        
        // Check if the namespace is correct
        $pattern = "/ai-tools.*?StoriesAPI\\\\([^\\\\]+)\\\\AiToolsController/";
        if (preg_match($pattern, $routesContent, $matches)) {
            $routeNamespace = $matches[1];
            if ($routeNamespace !== $namespaceSuffix) {
                echo "<p style='color:orange'>⚠️ AI tools route is using a different namespace: $routeNamespace. Updating...</p>";
                
                // Create a backup
                $backupFile = $routesFile . '.bak.' . date('YmdHis');
                if (copy($routesFile, $backupFile)) {
                    echo "<p>Created backup of routes file at: $backupFile</p>";
                }
                
                // Update the namespace
                $newContent = preg_replace("/StoriesAPI\\\\$routeNamespace\\\\AiToolsController/", "StoriesAPI\\$namespaceSuffix\\AiToolsController", $routesContent);
                
                if (file_put_contents($routesFile, $newContent)) {
                    echo "<p style='color:green'>✅ Updated ai-tools route namespace in routes file.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to update routes file.</p>";
                }
            } else {
                echo "<p style='color:green'>✅ AI tools route is using the correct namespace.</p>";
            }
        }
    }
} else {
    echo "<p style='color:red'>❌ Routes file not found at: $routesFile</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>Now test the API endpoints using the <a href='test_api_format.php'>test_api_format.php</a> script.</p>";