<?php
/**
 * Web-based Database Fix Script
 * 
 * This script applies the Database.php fix through a web interface.
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/web-fix.log');

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .success {
            color: #27ae60;
        }
        .error {
            color: #e74c3c;
        }
        .warning {
            color: #f39c12;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            max-height: 300px;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Fix</h1>';

// Check if the form has been submitted
if (isset($_POST['apply_fix'])) {
    echo '<div class="card">';
    echo '<h2>Applying Fix...</h2>';
    
    // Define the path to the Database.php file
    $databaseFilePath = __DIR__ . '/api/v1/Core/Database.php';
    $databaseFileLowerPath = __DIR__ . '/api/v1/core/Database.php';
    
    // Check if the file exists
    if (file_exists($databaseFilePath)) {
        $filePath = $databaseFilePath;
    } elseif (file_exists($databaseFileLowerPath)) {
        $filePath = $databaseFileLowerPath;
    } else {
        echo '<p class="error">Error: Database.php file not found at either ' . $databaseFilePath . ' or ' . $databaseFileLowerPath . '</p>';
        echo '</div>';
        showForm();
        echo '</div></body></html>';
        exit;
    }
    
    echo '<p>Database.php file found at: ' . $filePath . '</p>';
    
    // Create a backup of the original file
    $backupPath = $filePath . '.bak.' . date('YmdHis');
    if (!copy($filePath, $backupPath)) {
        echo '<p class="error">Error: Failed to create backup of Database.php at ' . $backupPath . '</p>';
        echo '</div>';
        showForm();
        echo '</div></body></html>';
        exit;
    }
    
    echo '<p class="success">Created backup of original file at: ' . $backupPath . '</p>';
    
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
        echo '<p class="error">Error: Failed to write new content to file: ' . $filePath . '</p>';
        echo '<p>Restoring backup...</p>';
        copy($backupPath, $filePath);
        echo '</div>';
        showForm();
        echo '</div></body></html>';
        exit;
    }
    
    echo '<p class="success">Database.php content replaced successfully!</p>';
    
    // Set proper permissions
    chmod($filePath, 0644);
    echo '<p>Set proper permissions (644) on the file.</p>';
    
    echo '<h2>Next Steps</h2>';
    echo '<p>The fix has been applied successfully. You can now test the API endpoints:</p>';
    echo '<ul>';
    echo '<li><a href="/api/v1/games" target="_blank">Test Games Endpoint</a></li>';
    echo '<li><a href="/api/v1/directory-items" target="_blank">Test Directory Items Endpoint</a></li>';
    echo '<li><a href="/api/v1/ai-tools" target="_blank">Test AI Tools Endpoint</a></li>';
    echo '</ul>';
    
    echo '<p>Then run the API format test:</p>';
    echo '<ul>';
    echo '<li><a href="/test_api_format.php" target="_blank">Test API Format</a></li>';
    echo '</ul>';
    
    echo '</div>';
} else {
    showForm();
}

echo '</div></body></html>';

/**
 * Show the form to apply the fix
 */
function showForm() {
    echo '<div class="card">';
    echo '<h2>Apply Database Fix</h2>';
    echo '<p>This script will fix the DEBUG_MODE constant issue in the Database.php file by replacing it with a version that doesn\'t use DEBUG_MODE.</p>';
    echo '<p>The script will:</p>';
    echo '<ul>';
    echo '<li>Create a backup of the original file</li>';
    echo '<li>Replace Database.php with a version that doesn\'t use DEBUG_MODE</li>';
    echo '<li>Set proper permissions</li>';
    echo '</ul>';
    echo '<form method="post">';
    echo '<input type="hidden" name="apply_fix" value="1">';
    echo '<button type="submit" class="btn">Apply Fix</button>';
    echo '</form>';
    echo '</div>';
}