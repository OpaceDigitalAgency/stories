<?php
/**
 * Fix Database Credentials
 * 
 * This script helps fix the database connection issues by updating the database credentials.
 * The error "Access denied for user 'stories_user'@'localhost'" indicates that the database
 * credentials are incorrect or the user doesn't have the necessary permissions.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Database Credentials</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$configPath = $apiPath . '/config';

// Check if config directory exists
if (!is_dir($configPath)) {
    echo "<p style='color:orange'>⚠️ Config directory not found at: $configPath</p>";
    
    // Try to create it
    if (mkdir($configPath, 0755, true)) {
        echo "<p style='color:green'>✅ Created config directory at: $configPath</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create config directory.</p>";
        exit;
    }
}

// Check if database config file exists
$dbConfigFile = $configPath . '/database.php';
if (file_exists($dbConfigFile)) {
    echo "<p style='color:green'>✅ Database config file found at: $dbConfigFile</p>";
    
    // Create a backup
    $backupFile = $dbConfigFile . '.bak.' . date('YmdHis');
    if (copy($dbConfigFile, $backupFile)) {
        echo "<p>Created backup at: $backupFile</p>";
    }
    
    // Read the current config
    $content = file_get_contents($dbConfigFile);
    
    // Extract current credentials
    $hostPattern = "/['\"](host|hostname)['\"]\\s*=>\\s*['\"](.*?)['\"]/i";
    $dbNamePattern = "/['\"](dbname|database|db)['\"]\\s*=>\\s*['\"](.*?)['\"]/i";
    $usernamePattern = "/['\"](username|user)['\"]\\s*=>\\s*['\"](.*?)['\"]/i";
    $passwordPattern = "/['\"](password|pass)['\"]\\s*=>\\s*['\"](.*?)['\"]/i";
    
    preg_match($hostPattern, $content, $hostMatches);
    preg_match($dbNamePattern, $content, $dbNameMatches);
    preg_match($usernamePattern, $content, $usernameMatches);
    preg_match($passwordPattern, $content, $passwordMatches);
    
    $currentHost = isset($hostMatches[2]) ? $hostMatches[2] : 'localhost';
    $currentDbName = isset($dbNameMatches[2]) ? $dbNameMatches[2] : 'stories';
    $currentUsername = isset($usernameMatches[2]) ? $usernameMatches[2] : 'stories_user';
    $currentPassword = isset($passwordMatches[2]) ? $passwordMatches[2] : 'stories_password';
} else {
    echo "<p style='color:orange'>⚠️ Database config file not found. Will create a new one.</p>";
    
    // Default values
    $currentHost = 'localhost';
    $currentDbName = 'stories';
    $currentUsername = 'stories_user';
    $currentPassword = 'stories_password';
}

// Display form to update credentials
echo "<h2>Update Database Credentials</h2>";
echo "<p>Current credentials:</p>";
echo "<ul>";
echo "<li>Host: $currentHost</li>";
echo "<li>Database Name: $currentDbName</li>";
echo "<li>Username: $currentUsername</li>";
echo "<li>Password: " . str_repeat('*', strlen($currentPassword)) . "</li>";
echo "</ul>";

echo "<p>Enter the correct database credentials:</p>";
echo "<form method='post'>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label for='host' style='display: inline-block; width: 150px;'>Host:</label>";
echo "<input type='text' id='host' name='host' value='$currentHost' required>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label for='dbname' style='display: inline-block; width: 150px;'>Database Name:</label>";
echo "<input type='text' id='dbname' name='dbname' value='$currentDbName' required>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label for='username' style='display: inline-block; width: 150px;'>Username:</label>";
echo "<input type='text' id='username' name='username' value='$currentUsername' required>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label for='password' style='display: inline-block; width: 150px;'>Password:</label>";
echo "<input type='password' id='password' name='password' value='$currentPassword' required>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<input type='submit' value='Update Credentials'>";
echo "</div>";
echo "</form>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? $currentHost;
    $dbname = $_POST['dbname'] ?? $currentDbName;
    $username = $_POST['username'] ?? $currentUsername;
    $password = $_POST['password'] ?? $currentPassword;
    
    echo "<h2>Testing Connection</h2>";
    
    try {
        // Test the connection
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        echo "<p style='color:green'>✅ Database connection successful!</p>";
        
        // Update the config file
        $dbConfig = <<<EOD
<?php
/**
 * Database Configuration
 */

return [
    'host' => '$host',
    'dbname' => '$dbname',
    'username' => '$username',
    'password' => '$password',
    'charset' => 'utf8mb4'
];
EOD;
        
        if (file_put_contents($dbConfigFile, $dbConfig)) {
            echo "<p style='color:green'>✅ Database config file updated successfully!</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update database config file.</p>";
        }
        
        // Check for Database class
        $databaseClassFile = $apiPath . '/Core/Database.php';
        if (!file_exists($databaseClassFile)) {
            $databaseClassFile = $apiPath . '/core/Database.php';
            if (!file_exists($databaseClassFile)) {
                echo "<p style='color:orange'>⚠️ Database class not found. Creating it...</p>";
                
                // Determine the namespace based on directory name
                $coreDir = is_dir($apiPath . '/Core') ? $apiPath . '/Core' : $apiPath . '/core';
                $coreNamespaceSuffix = basename($coreDir);
                
                // Create the Database class
                $databaseClass = <<<EOD
<?php
/**
 * Database Class
 * 
 * Handles database connections and queries.
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
        \$config = require __DIR__ . '/../config/database.php';
        
        \$dsn = "mysql:host={\$config['host']};dbname={\$config['dbname']};charset={\$config['charset']}";
        \$options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            \$this->pdo = new PDO(\$dsn, \$config['username'], \$config['password'], \$options);
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
                
                // Create the directory if it doesn't exist
                $coreDir = dirname($databaseClassFile);
                if (!is_dir($coreDir)) {
                    if (mkdir($coreDir, 0755, true)) {
                        echo "<p>Created directory: $coreDir</p>";
                    } else {
                        echo "<p style='color:red'>❌ Failed to create directory: $coreDir</p>";
                    }
                }
                
                if (file_put_contents($databaseClassFile, $databaseClass)) {
                    echo "<p style='color:green'>✅ Created Database class at: $databaseClassFile</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to create Database class.</p>";
                }
            } else {
                echo "<p style='color:green'>✅ Database class found at: $databaseClassFile</p>";
            }
        } else {
            echo "<p style='color:green'>✅ Database class found at: $databaseClassFile</p>";
        }
        
        echo "<h2>Next Steps</h2>";
        echo "<p>Now run the <a href='fix_directory_items_table.php'>fix_directory_items_table.php</a> and <a href='fix_ai_tools_table.php'>fix_ai_tools_table.php</a> scripts to create the necessary tables.</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        echo "<p>Please check your credentials and try again.</p>";
    }
}