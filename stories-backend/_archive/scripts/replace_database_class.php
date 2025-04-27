<?php
/**
 * Replace Database Class Script
 * 
 * This script completely replaces the Database.php file with a new version
 * that doesn't use the DEBUG_MODE constant at all.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/replace-database.log');

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

echo "<h1>Replace Database Class</h1>";
echo "<p>Database.php file found at: $filePath</p>";

// Create a backup of the original file
$backupPath = $filePath . '.bak.' . date('YmdHis');
if (!copy($filePath, $backupPath)) {
    die("Failed to create backup of Database.php at $backupPath");
}

echo "<p>Created backup of original file at: $backupPath</p>";

// New content for Database.php
$newContent = '<?php
/**
 * Database Connection Class
 * 
 * This class handles the database connection and provides methods for
 * executing queries with prepared statements for security.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Core;

use PDO;
use PDOException;
use Exception;

class Database {
    /**
     * @var PDO The database connection
     */
    private $connection;
    
    /**
     * @var array The database configuration
     */
    private $config;
    
    /**
     * @var Database The singleton instance
     */
    private static $instance = null;
    
    /**
     * Constructor - Private to enforce singleton pattern
     * 
     * @param array $config Database configuration
     */
    private function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }
    
    /**
     * Get the singleton instance
     * 
     * @param array $config Database configuration
     * @return Database The database instance
     */
    public static function getInstance(array $config = null) {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception(\'Database configuration is required for the first initialization\');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Connect to the database
     * 
     * @throws PDOException If connection fails
     */
    private function connect() {
        $dsn = "mysql:host={$this->config[\'host\']};dbname={$this->config[\'name\']};charset={$this->config[\'charset\']};port={$this->config[\'port\']}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true,
        ];
        
        // Enhanced logging for connection debugging
        error_log("[DB CONNECTION ATTEMPT] Host: {$this->config[\'host\']} | DB: {$this->config[\'name\']} | User: {$this->config[\'user\']} | Port: {$this->config[\'port\']}");
        
        try {
            $this->connection = new PDO($dsn, $this->config[\'user\'], $this->config[\'password\'], $options);
            
            if (!$this->connection) {
                throw new \Exception(\'Database connection failed (no PDO handle)\');
            }
            
            error_log("[DB CONNECTION SUCCESS] Connected to database {$this->config[\'name\']} as user {$this->config[\'user\']}");
        } catch (PDOException $e) {
            // Log detailed error information for debugging
            $errorMessage = "Database connection failed: " . $e->getMessage();
            $errorCode = $e->getCode();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            error_log("[DB ERROR] Code: $errorCode | Message: $errorMessage | File: $errorFile | Line: $errorLine");
            error_log("[DB CONFIG USED] Host: {$this->config[\'host\']} | DB: {$this->config[\'name\']} | User: {$this->config[\'user\']} | Port: {$this->config[\'port\']}");
            
            // Check for specific error conditions to provide more helpful messages
            if (strpos($e->getMessage(), "Access denied") !== false) {
                throw new Exception("Database authentication failed. Please check credentials.");
            } elseif (strpos($e->getMessage(), "Unknown database") !== false) {
                throw new Exception("Database not found. Please check database name.");
            } elseif (strpos($e->getMessage(), "Connection refused") !== false) {
                throw new Exception("Database server connection refused. Please check host and port.");
            } else {
                throw new Exception("Database connection failed. Please contact support with error code: " . date(\'YmdHis\'));
            }
        }
    }
    
    /**
     * Get the database connection
     * 
     * @return PDO The database connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query with parameters
     * 
     * @param string $query The SQL query
     * @param array $params The parameters for the query
     * @return \PDOStatement The prepared statement
     * @throws Exception If query execution fails
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log detailed error information for debugging
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            // Create a sanitized version of the query for logging (remove sensitive data)
            $sanitizedQuery = preg_replace(\'/password\s*=\s*[^\s,)]+/i\', \'password=***\', $query);
            
            error_log("[QUERY ERROR] Code: $errorCode | Message: $errorMessage | Query: $sanitizedQuery | File: $errorFile | Line: $errorLine");
            
            // Check for specific error conditions
            if ($e->getCode() == \'23000\') {
                // Integrity constraint violation
                if (strpos($errorMessage, "Duplicate entry") !== false) {
                    throw new Exception("Record already exists with this information.");
                } else {
                    throw new Exception("Data integrity error. Please check your input.");
                }
            } elseif ($e->getCode() == \'42S02\') {
                // Table not found
                throw new Exception("Database schema error. Please contact support.");
            } elseif ($e->getCode() == \'42000\') {
                // Syntax error
                throw new Exception("Database query syntax error. Please contact support.");
            } else {
                // Generic error with timestamp for log correlation
                $errorId = date(\'YmdHis\');
                error_log("[ERROR ID: $errorId] " . $errorMessage);
                
                // Include more detailed error information for debugging
                throw new Exception("Database operation failed. Reference ID: $errorId");
            }
        }
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string The last inserted ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Close the database connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}';

// Write the new content to the file
if (file_put_contents($filePath, $newContent) === false) {
    die("Failed to write new content to file: $filePath");
}

echo "<p>Successfully replaced Database.php with new version that doesn't use DEBUG_MODE.</p>";

// Create a test script to verify the fix
$testScriptPath = __DIR__ . '/test_database_fix.php';
$testScriptContent = '<?php
// Include autoloader
require_once __DIR__ . \'/api/v1/autoload.php\';

// Use the Database class
use StoriesAPI\Core\Database;

// Test database connection
try {
    // Database configuration
    $config = [
        \'host\' => \'localhost\',
        \'name\' => \'stories_db\',
        \'user\' => \'stories_user\',
        \'password\' => \'$tw1cac3*sOt\',
        \'charset\' => \'utf8mb4\',
        \'port\' => 3306
    ];
    
    // Get database instance
    $db = Database::getInstance($config);
    
    // Test a simple query
    $stmt = $db->query("SELECT COUNT(*) as count FROM games");
    $count = $stmt->fetch()[\'count\'];
    
    echo "<h1>Database Test</h1>";
    echo "<p>Successfully connected to database.</p>";
    echo "<p>Games count: $count</p>";
    
    // Test API endpoints
    $endpoints = [
        \'games\',
        \'directory-items\',
        \'ai-tools\'
    ];
    
    echo "<h2>API Endpoints Test</h2>";
    echo "<ul>";
    
    foreach ($endpoints as $endpoint) {
        echo "<li><a href=\'/api/v1/$endpoint\' target=\'_blank\'>Test $endpoint Endpoint</a></li>";
    }
    
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h1>Database Test Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
';

// Write the test script
if (file_put_contents($testScriptPath, $testScriptContent) === false) {
    echo "<p>Failed to write test script to file: $testScriptPath</p>";
} else {
    echo "<p>Created a test script at: $testScriptPath</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Try accessing the API endpoints again to see if the fix worked.</li>";
echo "<li>If it didn't work, try running the test script: <a href='/test_database_fix.php'>/test_database_fix.php</a></li>";
echo "</ol>";

echo "<h2>Test API Endpoints</h2>";
echo "<ul>";
echo "<li><a href='/api/v1/games' target='_blank'>Test Games Endpoint</a></li>";
echo "<li><a href='/api/v1/directory-items' target='_blank'>Test Directory Items Endpoint</a></li>";
echo "<li><a href='/api/v1/ai-tools' target='_blank'>Test AI Tools Endpoint</a></li>";
echo "</ul>";

echo "<h2>Test API Format</h2>";
echo "<ul>";
echo "<li><a href='/test_api_format.php' target='_blank'>Test API Format</a></li>";
echo "</ul>";