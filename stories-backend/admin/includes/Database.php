<?php
/**
 * Database Connection Class
 * 
 * This class handles the database connection and provides methods for
 * executing queries with prepared statements for security.
 * 
 * @package Stories Admin
 * @version 1.0.0
 */

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
                throw new Exception('Database configuration is required for the first initialization');
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
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['name']};charset={$this->config['charset']};port={$this->config['port']}";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => true,
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->config['user'], $this->config['password'], $options);
        } catch (PDOException $e) {
            // Log detailed error information for debugging
            $errorMessage = "Database connection failed: " . $e->getMessage();
            $errorCode = $e->getCode();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            error_log("[ADMIN DB ERROR] Code: $errorCode | Message: $errorMessage | File: $errorFile | Line: $errorLine");
            
            // Check for specific error conditions to provide more helpful messages
            if (strpos($e->getMessage(), "Access denied") !== false) {
                throw new Exception("Database authentication failed. Please check credentials.");
            } elseif (strpos($e->getMessage(), "Unknown database") !== false) {
                throw new Exception("Database not found. Please check database name.");
            } elseif (strpos($e->getMessage(), "Connection refused") !== false) {
                throw new Exception("Database server connection refused. Please check host and port.");
            } else {
                throw new Exception("Database connection failed. Please contact support with error code: " . date('YmdHis'));
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
            $sanitizedQuery = preg_replace('/password\s*=\s*[^\s,)]+/i', 'password=***', $query);
            
            error_log("[ADMIN QUERY ERROR] Code: $errorCode | Message: $errorMessage | Query: $sanitizedQuery | File: $errorFile | Line: $errorLine");
            
            // Check for specific error conditions
            if ($e->getCode() == '23000') {
                // Integrity constraint violation
                if (strpos($errorMessage, "Duplicate entry") !== false) {
                    throw new Exception("Record already exists with this information.");
                } else {
                    throw new Exception("Data integrity error. Please check your input.");
                }
            } elseif ($e->getCode() == '42S02') {
                // Table not found
                throw new Exception("Database schema error. Please contact support.");
            } elseif ($e->getCode() == '42000') {
                // Syntax error
                throw new Exception("Database query syntax error. Please contact support.");
            } else {
                // Generic error with timestamp for log correlation
                $errorId = date('YmdHis');
                error_log("[ADMIN ERROR ID: $errorId] " . $errorMessage);
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
}