<?php
/**
 * Fix Controller Inheritance
 * 
 * This script fixes the inheritance issue with the controllers.
 * The error "Class 'StoriesAPI\Core\Controller' not found" indicates that
 * the Controller class is not being found or loaded correctly.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Controller Inheritance</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$corePath = $apiPath . '/Core';
$endpointsPath = $apiPath . '/endpoints';

// Check if Core directory exists
if (!is_dir($corePath)) {
    echo "<p style='color:orange'>⚠️ Core directory not found at: $corePath</p>";
    
    // Try lowercase version
    $corePath = $apiPath . '/core';
    if (!is_dir($corePath)) {
        echo "<p style='color:red'>❌ Core directory not found at: $corePath</p>";
        echo "<p>Please run the fix_case_sensitivity.php script first to consolidate core folders.</p>";
        exit;
    } else {
        echo "<p>Using lowercase core directory: $corePath</p>";
    }
} else {
    echo "<p>Using uppercase Core directory: $corePath</p>";
}

// Check if Controller class exists
$controllerFile = $corePath . '/Controller.php';
$baseControllerFile = $corePath . '/BaseController.php';

if (file_exists($controllerFile)) {
    echo "<p style='color:green'>✅ Controller class found at: $controllerFile</p>";
    $controllerClassFile = $controllerFile;
} elseif (file_exists($baseControllerFile)) {
    echo "<p style='color:green'>✅ BaseController class found at: $baseControllerFile</p>";
    $controllerClassFile = $baseControllerFile;
} else {
    echo "<p style='color:red'>❌ Controller class not found!</p>";
    
    // Create the Controller class
    echo "<h2>Creating Controller Class</h2>";
    
    // Get the namespace based on the core directory name
    $coreNamespaceSuffix = basename($corePath);
    
    $controllerContent = <<<EOD
<?php
/**
 * Base Controller Class
 * 
 * This class serves as the base for all controllers in the API.
 */

namespace StoriesAPI\\$coreNamespaceSuffix;

class Controller {
    /**
     * @var Database The database connection
     */
    protected \$db;
    
    /**
     * Constructor
     */
    public function __construct() {
        \$this->db = new Database();
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
    
    $controllerClassFile = $corePath . '/Controller.php';
    
    if (file_put_contents($controllerClassFile, $controllerContent)) {
        echo "<p style='color:green'>✅ Created Controller class at: $controllerClassFile</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create Controller class.</p>";
        exit;
    }
}

// Check the namespace in the Controller class
$controllerContent = file_get_contents($controllerClassFile);
$coreNamespaceSuffix = basename($corePath);

// Extract the namespace
if (preg_match('/namespace\s+([^;]+);/', $controllerContent, $matches)) {
    $actualNamespace = $matches[1];
    $expectedNamespace = "StoriesAPI\\$coreNamespaceSuffix";
    
    if ($actualNamespace !== $expectedNamespace) {
        echo "<p style='color:orange'>⚠️ Controller class has incorrect namespace: $actualNamespace. Expected: $expectedNamespace. Fixing...</p>";
        
        // Create a backup
        $backupFile = $controllerClassFile . '.bak.' . date('YmdHis');
        if (copy($controllerClassFile, $backupFile)) {
            echo "<p>Created backup at: $backupFile</p>";
        }
        
        // Update the namespace
        $newContent = str_replace($actualNamespace, $expectedNamespace, $controllerContent);
        
        if (file_put_contents($controllerClassFile, $newContent)) {
            echo "<p style='color:green'>✅ Updated namespace in Controller class.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update namespace in Controller class.</p>";
        }
    } else {
        echo "<p style='color:green'>✅ Controller class has the correct namespace.</p>";
    }
} else {
    echo "<p style='color:red'>❌ Could not find namespace in Controller class.</p>";
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

// Get the namespace based on the endpoints directory name
$endpointsNamespaceSuffix = basename($endpointsPath);

// Fix DirectoryItemsController
$directoryController = $endpointsPath . '/DirectoryItemsController.php';
if (file_exists($directoryController)) {
    echo "<h2>Fixing DirectoryItemsController</h2>";
    
    $content = file_get_contents($directoryController);
    
    // Check the use statement
    $usePattern = '/use\s+StoriesAPI\\\\([^\\\\]+)\\\\Controller;/';
    if (preg_match($usePattern, $content, $matches)) {
        $useNamespace = $matches[1];
        if ($useNamespace !== $coreNamespaceSuffix) {
            echo "<p style='color:orange'>⚠️ DirectoryItemsController is using incorrect namespace for Controller: $useNamespace. Expected: $coreNamespaceSuffix. Fixing...</p>";
            
            // Create a backup
            $backupFile = $directoryController . '.bak.' . date('YmdHis');
            if (copy($directoryController, $backupFile)) {
                echo "<p>Created backup at: $backupFile</p>";
            }
            
            // Update the use statement
            $newContent = preg_replace($usePattern, "use StoriesAPI\\$coreNamespaceSuffix\\Controller;", $content);
            
            if (file_put_contents($directoryController, $newContent)) {
                echo "<p style='color:green'>✅ Updated use statement in DirectoryItemsController.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to update use statement in DirectoryItemsController.</p>";
            }
        } else {
            echo "<p style='color:green'>✅ DirectoryItemsController is using the correct namespace for Controller.</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Could not find use statement for Controller in DirectoryItemsController.</p>";
    }
} else {
    echo "<p style='color:red'>❌ DirectoryItemsController not found at: $directoryController</p>";
}

// Fix AiToolsController
$aiToolsController = $endpointsPath . '/AiToolsController.php';
if (file_exists($aiToolsController)) {
    echo "<h2>Fixing AiToolsController</h2>";
    
    $content = file_get_contents($aiToolsController);
    
    // Check the use statement
    $usePattern = '/use\s+StoriesAPI\\\\([^\\\\]+)\\\\Controller;/';
    if (preg_match($usePattern, $content, $matches)) {
        $useNamespace = $matches[1];
        if ($useNamespace !== $coreNamespaceSuffix) {
            echo "<p style='color:orange'>⚠️ AiToolsController is using incorrect namespace for Controller: $useNamespace. Expected: $coreNamespaceSuffix. Fixing...</p>";
            
            // Create a backup
            $backupFile = $aiToolsController . '.bak.' . date('YmdHis');
            if (copy($aiToolsController, $backupFile)) {
                echo "<p>Created backup at: $backupFile</p>";
            }
            
            // Update the use statement
            $newContent = preg_replace($usePattern, "use StoriesAPI\\$coreNamespaceSuffix\\Controller;", $content);
            
            if (file_put_contents($aiToolsController, $newContent)) {
                echo "<p style='color:green'>✅ Updated use statement in AiToolsController.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to update use statement in AiToolsController.</p>";
            }
        } else {
            echo "<p style='color:green'>✅ AiToolsController is using the correct namespace for Controller.</p>";
        }
    } else {
        echo "<p style='color:red'>❌ Could not find use statement for Controller in AiToolsController.</p>";
    }
} else {
    echo "<p style='color:red'>❌ AiToolsController not found at: $aiToolsController</p>";
}

// Check autoload.php
$autoloadFile = $apiPath . '/autoload.php';
if (file_exists($autoloadFile)) {
    echo "<h2>Checking Autoload Configuration</h2>";
    
    $content = file_get_contents($autoloadFile);
    
    // Check if it's using the correct case for directories
    if (strpos($content, "'/Core/") !== false && $coreNamespaceSuffix !== 'Core') {
        echo "<p style='color:orange'>⚠️ Autoload is using uppercase 'Core' but the actual directory is '$coreNamespaceSuffix'. Fixing...</p>";
        
        // Create a backup
        $backupFile = $autoloadFile . '.bak.' . date('YmdHis');
        if (copy($autoloadFile, $backupFile)) {
            echo "<p>Created backup at: $backupFile</p>";
        }
        
        // Update the autoload
        $newContent = str_replace("'/Core/", "'/$coreNamespaceSuffix/", $content);
        
        if (file_put_contents($autoloadFile, $newContent)) {
            echo "<p style='color:green'>✅ Updated Core path in autoload.php.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update Core path in autoload.php.</p>";
        }
    }
    
    if (strpos($content, "'/Endpoints/") !== false && $endpointsNamespaceSuffix !== 'Endpoints') {
        echo "<p style='color:orange'>⚠️ Autoload is using uppercase 'Endpoints' but the actual directory is '$endpointsNamespaceSuffix'. Fixing...</p>";
        
        // Create a backup if not already done
        if (!isset($backupFile) || !file_exists($backupFile)) {
            $backupFile = $autoloadFile . '.bak.' . date('YmdHis');
            if (copy($autoloadFile, $backupFile)) {
                echo "<p>Created backup at: $backupFile</p>";
            }
        }
        
        // Update the autoload
        $content = file_get_contents($autoloadFile); // Re-read in case it was updated above
        $newContent = str_replace("'/Endpoints/", "'/$endpointsNamespaceSuffix/", $content);
        
        if (file_put_contents($autoloadFile, $newContent)) {
            echo "<p style='color:green'>✅ Updated Endpoints path in autoload.php.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update Endpoints path in autoload.php.</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ Autoload file not found at: $autoloadFile</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>Now test the API endpoints using the <a href='test_api_format.php'>test_api_format.php</a> script.</p>";