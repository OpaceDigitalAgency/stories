 - <?php
/**
 * Fix Controller Class
 * 
 * This script creates the base Controller class that all endpoint controllers extend from.
 * The error "Class StoriesAPI\Core\Controller not found" indicates this class is missing.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Controller Class</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$corePath = $apiPath . '/Core';

// Check if Core directory exists
if (!is_dir($corePath)) {
    $corePath = $apiPath . '/core';
    if (!is_dir($corePath)) {
        echo "<p style='color:red'>❌ Core directory not found!</p>";
        exit;
    }
}

echo "<p>Using Core directory: $corePath</p>";

// Create Controller class file
$controllerFile = $corePath . '/Controller.php';
echo "<p>Creating Controller class at: $controllerFile</p>";

// Create a backup if file exists
if (file_exists($controllerFile)) {
    $backupFile = $controllerFile . '.bak.' . date('YmdHis');
    if (copy($controllerFile, $backupFile)) {
        echo "<p>Created backup at: $backupFile</p>";
    }
}

// Get the namespace based on the core directory name
$coreNamespaceSuffix = basename($corePath);

// Create the Controller class
$controllerContent = <<<EOD
<?php
/**
 * Base Controller Class
 * 
 * This class serves as the base for all controllers in the API.
 * It provides common functionality and database access.
 */

namespace StoriesAPI\\$coreNamespaceSuffix;

use PDO;
use PDOException;
use StoriesAPI\Utils\Response;

class Controller {
    /**
     * @var Database The database connection
     */
    protected \$db;
    
    /**
     * Constructor
     */
    public function __construct() {
        try {
            \$this->db = new Database();
        } catch (PDOException \$e) {
            \$this->serverError('Database connection failed: ' . \$e->getMessage());
        }
    }
    
    /**
     * Send a not found response
     * 
     * @param string \$message The error message
     */
    protected function notFound(\$message = 'Not found') {
        Response::sendError(\$message, 404);
        exit;
    }
    
    /**
     * Send a bad request response
     * 
     * @param string \$message The error message
     */
    protected function badRequest(\$message = 'Bad request') {
        Response::sendError(\$message, 400);
        exit;
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string \$message The error message
     */
    protected function unauthorized(\$message = 'Unauthorized') {
        Response::sendError(\$message, 401);
        exit;
    }
    
    /**
     * Send a forbidden response
     * 
     * @param string \$message The error message
     */
    protected function forbidden(\$message = 'Forbidden') {
        Response::sendError(\$message, 403);
        exit;
    }
    
    /**
     * Send a server error response
     * 
     * @param string \$message The error message
     */
    protected function serverError(\$message = 'Internal server error') {
        Response::sendError(\$message, 500);
        exit;
    }
    
    /**
     * Get pagination parameters from request
     * 
     * @return array The page and pageSize values
     */
    protected function getPaginationParams() {
        \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;
        \$pageSize = isset(\$_GET['pageSize']) ? (int)\$_GET['pageSize'] : 25;
        
        // Ensure valid values
        \$page = max(1, \$page);
        \$pageSize = max(1, min(100, \$pageSize));
        
        return [
            'page' => \$page,
            'pageSize' => \$pageSize,
            'offset' => (\$page - 1) * \$pageSize
        ];
    }
    
    /**
     * Get sort parameters from request
     * 
     * @param array \$allowedFields The allowed fields to sort by
     * @return array The sort field and direction
     */
    protected function getSortParams(\$allowedFields = ['id']) {
        \$sortField = isset(\$_GET['sort']) ? \$_GET['sort'] : 'id';
        \$sortDirection = 'ASC';
        
        // Check if sort field has a direction prefix
        if (strpos(\$sortField, '-') === 0) {
            \$sortField = substr(\$sortField, 1);
            \$sortDirection = 'DESC';
        }
        
        // Ensure the sort field is allowed
        if (!in_array(\$sortField, \$allowedFields)) {
            \$sortField = 'id';
        }
        
        return [
            'field' => \$sortField,
            'direction' => \$sortDirection
        ];
    }
    
    /**
     * Build a WHERE clause based on filters
     * 
     * @param array \$filters The filters to apply
     * @return array The WHERE clause and parameters
     */
    protected function buildWhereClause(\$filters) {
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

if (file_put_contents($controllerFile, $controllerContent)) {
    echo "<p style='color:green'>✅ Created Controller class successfully!</p>";
    
    // Update autoloader to ensure the class is loaded
    $autoloaderFile = $apiPath . '/autoload.php';
    if (file_exists($autoloaderFile)) {
        echo "<h2>Updating Autoloader</h2>";
        
        $autoloaderContent = file_get_contents($autoloaderFile);
        if (strpos($autoloaderContent, 'Controller.php') === false) {
            // Add Controller.php to the list of core files
            $autoloaderContent = str_replace(
                "'Database.php',",
                "'Database.php',\n        'Controller.php',",
                $autoloaderContent
            );
            
            if (file_put_contents($autoloaderFile, $autoloaderContent)) {
                echo "<p style='color:green'>✅ Updated autoloader to include Controller class</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to update autoloader</p>";
            }
        } else {
            echo "<p>Autoloader already includes Controller class</p>";
        }
    }
    
    echo "<h2>Next Steps</h2>";
    echo "<p>The Controller class has been created. Now test the endpoints:</p>";
    echo "<ul>";
    echo "<li><a href='/api/v1/stories'>/api/v1/stories</a></li>";
    echo "<li><a href='/api/v1/authors'>/api/v1/authors</a></li>";
    echo "<li><a href='/api/v1/games'>/api/v1/games</a></li>";
    echo "<li><a href='/api/v1/directory-items'>/api/v1/directory-items</a></li>";
    echo "<li><a href='/api/v1/ai-tools'>/api/v1/ai-tools</a></li>";
    echo "</ul>";
} else {
    echo "<p style='color:red'>❌ Failed to create Controller class.</p>";
}