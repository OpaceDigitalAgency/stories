<?php
/**
 * Fix Base Controller
 * 
 * This script fixes the BaseController class to use the correct database instantiation.
 * The error "Call to undefined method StoriesAPI\Core\Database::getInstance()" indicates
 * that we're trying to use a singleton pattern but the Database class doesn't support it.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Base Controller</h1>";

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

// Find the BaseController file
$baseControllerFile = $corePath . '/BaseController.php';
if (!file_exists($baseControllerFile)) {
    echo "<p style='color:red'>❌ BaseController not found at: $baseControllerFile</p>";
    exit;
}

echo "<p>Found BaseController at: $baseControllerFile</p>";

// Create a backup
$backupFile = $baseControllerFile . '.bak.' . date('YmdHis');
if (copy($baseControllerFile, $backupFile)) {
    echo "<p>Created backup at: $backupFile</p>";
}

// Get the namespace based on the core directory name
$coreNamespaceSuffix = basename($corePath);

// Update the BaseController class
$baseControllerContent = <<<EOD
<?php
/**
 * Base Controller Class
 * 
 * This class serves as the base for all controllers in the API.
 */

namespace StoriesAPI\\$coreNamespaceSuffix;

use PDO;
use PDOException;

class BaseController {
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
        \StoriesAPI\Utils\Response::sendError(\$message, 404);
        exit;
    }
    
    /**
     * Send a bad request response
     * 
     * @param string \$message The error message
     */
    protected function badRequest(\$message = 'Bad request') {
        \StoriesAPI\Utils\Response::sendError(\$message, 400);
        exit;
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string \$message The error message
     */
    protected function unauthorized(\$message = 'Unauthorized') {
        \StoriesAPI\Utils\Response::sendError(\$message, 401);
        exit;
    }
    
    /**
     * Send a forbidden response
     * 
     * @param string \$message The error message
     */
    protected function forbidden(\$message = 'Forbidden') {
        \StoriesAPI\Utils\Response::sendError(\$message, 403);
        exit;
    }
    
    /**
     * Send a server error response
     * 
     * @param string \$message The error message
     */
    protected function serverError(\$message = 'Internal server error') {
        \StoriesAPI\Utils\Response::sendError(\$message, 500);
        exit;
    }
}
EOD;

if (file_put_contents($baseControllerFile, $baseControllerContent)) {
    echo "<p style='color:green'>✅ Updated BaseController successfully!</p>";
} else {
    echo "<p style='color:red'>❌ Failed to update BaseController.</p>";
}

// Check if any controllers extend BaseController instead of Controller
echo "<h2>Checking Controllers</h2>";

// Find the endpoints directory
$endpointsUpperPath = $apiPath . '/Endpoints';
$endpointsLowerPath = $apiPath . '/endpoints';

if (is_dir($endpointsUpperPath)) {
    $endpointsPath = $endpointsUpperPath;
} elseif (is_dir($endpointsLowerPath)) {
    $endpointsPath = $endpointsLowerPath;
} else {
    echo "<p style='color:red'>❌ Endpoints directory not found!</p>";
    exit;
}

echo "<p>Using endpoints directory: $endpointsPath</p>";

// Get all controller files
$controllerFiles = glob($endpointsPath . '/*.php');

foreach ($controllerFiles as $controllerFile) {
    $fileName = basename($controllerFile);
    echo "<h3>Checking $fileName</h3>";
    
    $content = file_get_contents($controllerFile);
    
    // Check if it extends BaseController
    if (strpos($content, 'extends BaseController') !== false) {
        echo "<p style='color:orange'>⚠️ $fileName extends BaseController. Creating backup and updating...</p>";
        
        // Create a backup
        $backupFile = $controllerFile . '.bak.' . date('YmdHis');
        if (copy($controllerFile, $backupFile)) {
            echo "<p>Created backup at: $backupFile</p>";
        }
        
        // Update to extend Controller instead
        $newContent = str_replace('extends BaseController', 'extends Controller', $content);
        
        if (file_put_contents($controllerFile, $newContent)) {
            echo "<p style='color:green'>✅ Updated $fileName to extend Controller.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update $fileName.</p>";
        }
    } else {
        echo "<p style='color:green'>✅ $fileName extends Controller.</p>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<p>Now run these scripts in order:</p>";
echo "<ol>";
echo "<li><a href='fix_database_manually.php'>fix_database_manually.php</a> - Update database credentials</li>";
echo "<li><a href='fix_games_endpoint.php'>fix_games_endpoint.php</a> - Fix games endpoint</li>";
echo "<li><a href='fix_directory_items_table.php'>fix_directory_items_table.php</a> - Create directory_items table</li>";
echo "<li><a href='fix_ai_tools_table.php'>fix_ai_tools_table.php</a> - Create ai_tools table</li>";
echo "</ol>";