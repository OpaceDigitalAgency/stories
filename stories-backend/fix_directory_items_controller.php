<?php
/**
 * Fix Directory Items Controller
 * 
 * This script updates or creates the DirectoryItemsController to properly format responses.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Directory Items Controller</h1>";

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

// Check if DirectoryItemsController exists
$directoryController = $endpointsPath . '/DirectoryItemsController.php';

if (file_exists($directoryController)) {
    echo "<p style='color:green'>✅ Directory Items Controller found at: $directoryController</p>";
    
    // Create a backup
    $backupFile = $directoryController . '.bak.' . date('YmdHis');
    if (copy($directoryController, $backupFile)) {
        echo "<p>Created backup at: $backupFile</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ Directory Items Controller not found. Will create a new one.</p>";
}

// Get the namespace based on the endpoints directory name
$namespaceSuffix = basename($endpointsPath);

// Create or update the controller
$controllerContent = <<<EOD
<?php
/**
 * Directory Items Controller
 * 
 * Handles API requests for directory items
 */

namespace StoriesAPI\\$namespaceSuffix;

use StoriesAPI\Core\Controller;
use StoriesAPI\Utils\Response;

class DirectoryItemsController extends Controller {
    /**
     * Get a list of directory items
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
            \$countQuery = "SELECT COUNT(*) as total FROM directory_items \$whereClause";
            \$stmt = \$this->db->query(\$countQuery, \$params);
            \$total = \$stmt->fetch()['total'];
            
            // Get directory items with pagination
            \$query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM directory_items
                \$whereClause
                \$sortClause
                LIMIT \$offset, \$pageSize";
            
            \$stmt = \$this->db->query(\$query, \$params);
            \$items = \$stmt->fetchAll();
            
            // Format items with the expected structure
            \$formattedItems = Response::formatData(\$items);
            
            // Send paginated response
            Response::sendPaginated(\$formattedItems, \$page, \$pageSize, \$total);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch directory items: ' . \$e->getMessage());
        }
    }
    
    /**
     * Get a single directory item by ID
     * 
     * @param int \$id The directory item ID
     */
    public function show(\$id) {
        try {
            \$query = "SELECT
                id, title, description, slug, url, category, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM directory_items
                WHERE id = :id";
            
            \$stmt = \$this->db->query(\$query, ['id' => \$id]);
            \$item = \$stmt->fetch();
            
            if (!\$item) {
                \$this->notFound('Directory item not found');
                return;
            }
            
            // Format item with the expected structure
            \$formattedItem = Response::formatData(\$item);
            
            // Send success response
            Response::sendSuccess(\$formattedItem);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch directory item: ' . \$e->getMessage());
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

if (file_put_contents($directoryController, $controllerContent)) {
    echo "<p style='color:green'>✅ Directory Items Controller updated successfully!</p>";
} else {
    echo "<p style='color:red'>❌ Failed to update Directory Items Controller.</p>";
}

// Check if routes file exists and contains directory-items route
$routesFile = $apiPath . '/routes.php';

if (file_exists($routesFile)) {
    echo "<h2>Checking Routes Configuration</h2>";
    
    $routesContent = file_get_contents($routesFile);
    
    if (strpos($routesContent, 'directory-items') === false) {
        echo "<p style='color:orange'>⚠️ Directory items route not found in routes file. Adding it...</p>";
        
        // Create a backup
        $backupFile = $routesFile . '.bak.' . date('YmdHis');
        if (copy($routesFile, $backupFile)) {
            echo "<p>Created backup of routes file at: $backupFile</p>";
        }
        
        // Add directory-items route
        $routeToAdd = "\n// Directory items routes\n\$router->get('/directory-items', 'StoriesAPI\\$namespaceSuffix\\DirectoryItemsController@index');\n\$router->get('/directory-items/{id}', 'StoriesAPI\\$namespaceSuffix\\DirectoryItemsController@show');\n";
        
        // Find a good place to add the route
        $pos = strrpos($routesContent, '?>');
        if ($pos !== false) {
            $newContent = substr($routesContent, 0, $pos) . $routeToAdd . substr($routesContent, $pos);
        } else {
            $newContent = $routesContent . $routeToAdd;
        }
        
        if (file_put_contents($routesFile, $newContent)) {
            echo "<p style='color:green'>✅ Added directory-items routes to routes file.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update routes file.</p>";
        }
    } else {
        echo "<p style='color:green'>✅ Directory items route already exists in routes file.</p>";
        
        // Check if the namespace is correct
        $pattern = "/directory-items.*?StoriesAPI\\\\([^\\\\]+)\\\\DirectoryItemsController/";
        if (preg_match($pattern, $routesContent, $matches)) {
            $routeNamespace = $matches[1];
            if ($routeNamespace !== $namespaceSuffix) {
                echo "<p style='color:orange'>⚠️ Directory items route is using a different namespace: $routeNamespace. Updating...</p>";
                
                // Create a backup
                $backupFile = $routesFile . '.bak.' . date('YmdHis');
                if (copy($routesFile, $backupFile)) {
                    echo "<p>Created backup of routes file at: $backupFile</p>";
                }
                
                // Update the namespace
                $newContent = preg_replace("/StoriesAPI\\\\$routeNamespace\\\\DirectoryItemsController/", "StoriesAPI\\$namespaceSuffix\\DirectoryItemsController", $routesContent);
                
                if (file_put_contents($routesFile, $newContent)) {
                    echo "<p style='color:green'>✅ Updated directory-items route namespace in routes file.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to update routes file.</p>";
                }
            } else {
                echo "<p style='color:green'>✅ Directory items route is using the correct namespace.</p>";
            }
        }
    }
} else {
    echo "<p style='color:red'>❌ Routes file not found at: $routesFile</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>Now run the <a href='fix_ai_tools_table.php'>fix_ai_tools_table.php</a> script to create or fix the AI tools table.</p>";