<?php
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
            // Log the error but don't expose details in the response
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
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
            // Log the error but don't expose details in the response
            error_log("Query execution failed: " . $e->getMessage() . " - Query: $query");
            throw new Exception("Database query failed. Please try again later.");
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
}