<?php
/**
 * Fix DEBUG_MODE Constant Script
 * 
 * This script fixes the DEBUG_MODE constant issue in the Database.php file.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/fix-debug-mode.log');

// Define the path to the Database.php file
$databaseFilePath = __DIR__ . '/api/v1/Core/Database.php';
$databaseFileLowerPath = __DIR__ . '/api/v1/core/Database.php';

// Check if the file exists
if (file_exists($databaseFilePath)) {
    $filePath = $databaseFilePath;
} elseif (file_exists($databaseFileLowerPath)) {
    $filePath = $databaseFileLowerPath;
} else {
    die("Database.php file not found at either $databaseFilePath or $databaseFileLowerPath");
}

echo "<h1>Fix DEBUG_MODE Constant</h1>";
echo "<p>Database.php file found at: $filePath</p>";

// Read the file content
$content = file_get_contents($filePath);
if ($content === false) {
    die("Failed to read file: $filePath");
}

echo "<h2>Original File Content</h2>";
echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "...</pre>";

// Check if DEBUG_MODE is already defined
if (strpos($content, "define('StoriesAPI\\Core\\DEBUG_MODE'") !== false) {
    echo "<p>DEBUG_MODE constant is already defined in the file.</p>";
    
    // Check if it's being used correctly
    if (strpos($content, "if (DEBUG_MODE)") !== false) {
        echo "<p>But it's being used incorrectly. Fixing...</p>";
        
        // Replace the incorrect usage
        $content = str_replace("if (DEBUG_MODE)", "if (\\StoriesAPI\\Core\\DEBUG_MODE)", $content);
        
        // Write the modified content back to the file
        if (file_put_contents($filePath, $content) === false) {
            die("Failed to write to file: $filePath");
        }
        
        echo "<p>Fixed DEBUG_MODE usage in the file.</p>";
    } else {
        echo "<p>DEBUG_MODE is being used correctly.</p>";
    }
} else {
    echo "<p>DEBUG_MODE constant is not defined in the file. Adding it...</p>";
    
    // Add the constant definition after the namespace declaration
    $pattern = "/namespace StoriesAPI\\\\Core;/";
    $replacement = "namespace StoriesAPI\\Core;\n\n// Define DEBUG_MODE constant\nif (!defined('StoriesAPI\\Core\\DEBUG_MODE')) {\n    define('StoriesAPI\\Core\\DEBUG_MODE', false);\n}";
    
    $content = preg_replace($pattern, $replacement, $content);
    
    // Also fix the usage of DEBUG_MODE
    $content = str_replace("if (DEBUG_MODE)", "if (\\StoriesAPI\\Core\\DEBUG_MODE)", $content);
    
    // Write the modified content back to the file
    if (file_put_contents($filePath, $content) === false) {
        die("Failed to write to file: $filePath");
    }
    
    echo "<p>Added DEBUG_MODE constant definition to the file.</p>";
}

// Create a simpler version of the Database.php file
$simpleFixPath = __DIR__ . '/api/v1/Core/Database.simple.php';
$simpleFixLowerPath = __DIR__ . '/api/v1/core/Database.simple.php';

// Determine the correct path
if (file_exists(dirname($databaseFilePath))) {
    $simpleFixPath = $simpleFixPath;
} elseif (file_exists(dirname($databaseFileLowerPath))) {
    $simpleFixPath = $simpleFixLowerPath;
}

// Create a simple version of the Database.php file
$simpleContent = '<?php
/**
 * Database Connection Class - Simplified Version
 */

namespace StoriesAPI\Core;

use PDO;
use PDOException;
use Exception;

// Define DEBUG_MODE constant
if (!defined(\'StoriesAPI\\Core\\DEBUG_MODE\')) {
    define(\'StoriesAPI\\Core\\DEBUG_MODE\', false);
}

class Database {
    private $connection;
    private $config;
    private static $instance = null;
    
    private function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }
    
    public static function getInstance(array $config = null) {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception(\'Database configuration is required for the first initialization\');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    private function connect() {
        $dsn = "mysql:host={$this->config[\'host\']};dbname={$this->config[\'name\']};charset={$this->config[\'charset\']};port={$this->config[\'port\']}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->config[\'user\'], $this->config[\'password\'], $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Simple error handling without DEBUG_MODE check
            $errorId = date(\'YmdHis\');
            error_log("[ERROR ID: $errorId] " . $e->getMessage());
            throw new Exception("Database operation failed. Reference ID: $errorId");
        }
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function close() {
        $this->connection = null;
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}';

// Write the simple version to the file
if (file_put_contents($simpleFixPath, $simpleContent) === false) {
    echo "<p>Failed to write simple version to file: $simpleFixPath</p>";
} else {
    echo "<p>Created a simplified version of Database.php at: $simpleFixPath</p>";
    echo "<p>You can replace the original Database.php with this simplified version if needed.</p>";
}

// Create a direct fix script
$directFixPath = __DIR__ . '/direct_fix_debug_mode.php';
$directFixContent = '<?php
// Define the constant directly in the global scope
define("StoriesAPI\\Core\\DEBUG_MODE", false);

// Redirect to the API endpoint
header("Location: /api/v1/games");
';

// Write the direct fix script
if (file_put_contents($directFixPath, $directFixContent) === false) {
    echo "<p>Failed to write direct fix script to file: $directFixPath</p>";
} else {
    echo "<p>Created a direct fix script at: $directFixPath</p>";
    echo "<p>You can run this script to define the DEBUG_MODE constant globally.</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Try accessing the API endpoints again to see if the fix worked.</li>";
echo "<li>If it didn't work, try running the direct fix script: <a href='/direct_fix_debug_mode.php'>/direct_fix_debug_mode.php</a></li>";
echo "<li>If that still doesn't work, replace the original Database.php with the simplified version.</li>";
echo "</ol>";

echo "<h2>Test API Endpoints</h2>";
echo "<ul>";
echo "<li><a href='/api/v1/games' target='_blank'>Test Games Endpoint</a></li>";
echo "<li><a href='/api/v1/directory-items' target='_blank'>Test Directory Items Endpoint</a></li>";
echo "<li><a href='/api/v1/ai-tools' target='_blank'>Test AI Tools Endpoint</a></li>";
echo "</ul>";