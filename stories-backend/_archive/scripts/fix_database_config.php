<?php
/**
 * Fix Database Config
 * 
 * This script updates the Database class to use the credentials from config.php
 * instead of trying to manually enter them.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Database Config</h1>";

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

// Find the Database class
$databaseFile = $corePath . '/Database.php';
if (!file_exists($databaseFile)) {
    echo "<p style='color:red'>❌ Database class not found at: $databaseFile</p>";
    exit;
}

echo "<p>Found Database class at: $databaseFile</p>";

// Create a backup
$backupFile = $databaseFile . '.bak.' . date('YmdHis');
if (copy($databaseFile, $backupFile)) {
    echo "<p>Created backup at: $backupFile</p>";
}

// Get the namespace based on the core directory name
$coreNamespaceSuffix = basename($corePath);

// Update the Database class to use config.php
$databaseContent = <<<EOD
<?php
/**
 * Database Class
 * 
 * Handles database connections using credentials from config.php
 */

namespace StoriesAPI\\$coreNamespaceSuffix;

use PDO;
use PDOException;

class Database {
    /**
     * @var PDO The PDO instance
     */
    private \$pdo;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load configuration
        \$config = require __DIR__ . '/../config/config.php';
        \$dbConfig = \$config['db'];
        
        // Build DSN
        \$dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s;port=%d",
            \$dbConfig['host'],
            \$dbConfig['name'],
            \$dbConfig['charset'],
            \$dbConfig['port']
        );
        
        \$options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            \$this->pdo = new PDO(
                \$dsn,
                \$dbConfig['user'],
                \$dbConfig['password'],
                \$options
            );
        } catch (PDOException \$e) {
            throw new \Exception("Database connection failed: " . \$e->getMessage());
        }
    }
    
    /**
     * Execute a query
     * 
     * @param string \$sql The SQL query
     * @param array \$params The query parameters
     * @return \PDOStatement The PDO statement
     */
    public function query(\$sql, \$params = []) {
        \$stmt = \$this->pdo->prepare(\$sql);
        \$stmt->execute(\$params);
        return \$stmt;
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string The last inserted ID
     */
    public function lastInsertId() {
        return \$this->pdo->lastInsertId();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        \$this->pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        \$this->pdo->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback() {
        \$this->pdo->rollBack();
    }
}
EOD;

if (file_put_contents($databaseFile, $databaseContent)) {
    echo "<p style='color:green'>✅ Updated Database class to use config.php!</p>";
    
    // Test the connection
    echo "<h2>Testing Connection</h2>";
    
    try {
        require_once $databaseFile;
        $className = "StoriesAPI\\$coreNamespaceSuffix\\Database";
        $db = new $className();
        
        // Test a simple query
        $stmt = $db->query("SELECT 1");
        $result = $stmt->fetch();
        
        echo "<p style='color:green'>✅ Database connection successful!</p>";
        
        // Check if games table exists
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'games'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                echo "<p style='color:green'>✅ Games table exists.</p>";
                
                // Check if the table has data
                $stmt = $db->query("SELECT COUNT(*) FROM games");
                $count = $stmt->fetchColumn();
                
                echo "<p>Games table has $count records.</p>";
            } else {
                echo "<p style='color:orange'>⚠️ Games table does not exist. You'll need to create it.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:orange'>⚠️ Error checking games table: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>Next Steps</h2>";
        echo "<p>Now run these scripts in order:</p>";
        echo "<ol>";
        echo "<li><a href='fix_games_endpoint.php'>fix_games_endpoint.php</a> - Fix games endpoint</li>";
        echo "<li><a href='fix_directory_items_table.php'>fix_directory_items_table.php</a> - Create directory_items table</li>";
        echo "<li><a href='fix_ai_tools_table.php'>fix_ai_tools_table.php</a> - Create ai_tools table</li>";
        echo "</ol>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        echo "<p>Please check that config.php exists and has the correct database credentials.</p>";
    }
} else {
    echo "<p style='color:red'>❌ Failed to update Database class.</p>";
}