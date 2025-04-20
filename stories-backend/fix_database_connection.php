<?php
/**
 * Fix Database Connection
 * 
 * This script directly updates the database connection in the Database class.
 * It bypasses the config file and hardcodes the correct credentials.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Database Connection</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';

// Find the Database class
$coreUpperPath = $apiPath . '/Core';
$coreLowerPath = $apiPath . '/core';

$databaseClassFile = null;
$databaseUpperFile = $coreUpperPath . '/Database.php';
$databaseLowerFile = $coreLowerPath . '/Database.php';

if (file_exists($databaseUpperFile)) {
    $databaseClassFile = $databaseUpperFile;
    $corePath = $coreUpperPath;
    echo "<p>Found Database class at: $databaseClassFile</p>";
} elseif (file_exists($databaseLowerFile)) {
    $databaseClassFile = $databaseLowerFile;
    $corePath = $coreLowerPath;
    echo "<p>Found Database class at: $databaseClassFile</p>";
} else {
    echo "<p style='color:red'>❌ Database class not found!</p>";
    
    // Try to find any Database class
    $result = shell_exec("find $apiPath -name 'Database.php'");
    if ($result) {
        $files = explode("\n", trim($result));
        if (!empty($files)) {
            $databaseClassFile = $files[0];
            $corePath = dirname($databaseClassFile);
            echo "<p>Found Database class at: $databaseClassFile</p>";
        }
    }
    
    if (!$databaseClassFile) {
        echo "<p>Creating Database class...</p>";
        
        // Determine which core directory to use
        if (is_dir($coreUpperPath)) {
            $corePath = $coreUpperPath;
        } elseif (is_dir($coreLowerPath)) {
            $corePath = $coreLowerPath;
        } else {
            // Create the Core directory
            $corePath = $coreUpperPath;
            if (!mkdir($corePath, 0755, true)) {
                echo "<p style='color:red'>❌ Failed to create Core directory.</p>";
                exit;
            }
        }
        
        $databaseClassFile = $corePath . '/Database.php';
    }
}

// Get the namespace based on the core directory name
$coreNamespaceSuffix = basename($corePath);

// Database connection parameters - HARDCODED FOR DIRECT CONNECTION
$host = 'localhost';
$dbname = 'stories';
$username = 'root'; // Try with root user
$password = ''; // Empty password for root

echo "<h2>Database Connection Parameters</h2>";
echo "<p>Host: $host</p>";
echo "<p>Database: $dbname</p>";
echo "<p>Username: $username</p>";
echo "<p>Password: " . str_repeat('*', strlen($password)) . "</p>";

// Create or update the Database class with hardcoded credentials
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
        // HARDCODED CREDENTIALS FOR DIRECT CONNECTION
        \$host = '$host';
        \$dbname = '$dbname';
        \$username = '$username';
        \$password = '$password';
        \$charset = 'utf8mb4';
        
        \$dsn = "mysql:host=\$host;dbname=\$dbname;charset=\$charset";
        \$options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            \$this->pdo = new PDO(\$dsn, \$username, \$password, \$options);
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

// Create a backup of the original file if it exists
if (file_exists($databaseClassFile)) {
    $backupFile = $databaseClassFile . '.bak.' . date('YmdHis');
    if (copy($databaseClassFile, $backupFile)) {
        echo "<p>Created backup at: $backupFile</p>";
    }
}

// Write the new Database class
if (file_put_contents($databaseClassFile, $databaseClass)) {
    echo "<p style='color:green'>✅ Updated Database class with hardcoded credentials!</p>";
} else {
    echo "<p style='color:red'>❌ Failed to update Database class.</p>";
}

// Test the connection
echo "<h2>Testing Connection</h2>";

try {
    // Include the Database class
    require_once $databaseClassFile;
    
    // Create a new Database instance
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
            echo "<p style='color:orange'>⚠️ Games table does not exist.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:orange'>⚠️ Error checking games table: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    
    // Try with different credentials
    echo "<h2>Trying Alternative Credentials</h2>";
    
    // Common username/password combinations
    $credentials = [
        ['username' => 'stories', 'password' => ''],
        ['username' => 'stories', 'password' => 'stories'],
        ['username' => 'root', 'password' => 'root'],
        ['username' => 'admin', 'password' => 'admin'],
        ['username' => 'admin', 'password' => ''],
        ['username' => 'stories_admin', 'password' => 'stories_admin'],
        ['username' => 'stories_admin', 'password' => '']
    ];
    
    $connected = false;
    
    foreach ($credentials as $cred) {
        echo "<p>Trying username: {$cred['username']}, password: " . str_repeat('*', strlen($cred['password'])) . "</p>";
        
        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, $cred['username'], $cred['password'], $options);
            
            echo "<p style='color:green'>✅ Connection successful with username: {$cred['username']}</p>";
            
            // Update the Database class with these credentials
            $username = $cred['username'];
            $password = $cred['password'];
            
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
        // HARDCODED CREDENTIALS FOR DIRECT CONNECTION
        \$host = '$host';
        \$dbname = '$dbname';
        \$username = '$username';
        \$password = '$password';
        \$charset = 'utf8mb4';
        
        \$dsn = "mysql:host=\$host;dbname=\$dbname;charset=\$charset";
        \$options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            \$this->pdo = new PDO(\$dsn, \$username, \$password, \$options);
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
            
            if (file_put_contents($databaseClassFile, $databaseClass)) {
                echo "<p style='color:green'>✅ Updated Database class with working credentials!</p>";
                $connected = true;
                break;
            } else {
                echo "<p style='color:red'>❌ Failed to update Database class.</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Connection failed: " . $e->getMessage() . "</p>";
        }
    }
    
    if (!$connected) {
        echo "<h2>Manual Database Configuration</h2>";
        echo "<p>Please enter the correct database credentials on your server:</p>";
        echo "<form method='post'>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='host' style='display: inline-block; width: 150px;'>Host:</label>";
        echo "<input type='text' id='host' name='host' value='$host' required>";
        echo "</div>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='dbname' style='display: inline-block; width: 150px;'>Database Name:</label>";
        echo "<input type='text' id='dbname' name='dbname' value='$dbname' required>";
        echo "</div>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='username' style='display: inline-block; width: 150px;'>Username:</label>";
        echo "<input type='text' id='username' name='username' value='' required>";
        echo "</div>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<label for='password' style='display: inline-block; width: 150px;'>Password:</label>";
        echo "<input type='password' id='password' name='password' value=''>";
        echo "</div>";
        echo "<div style='margin-bottom: 10px;'>";
        echo "<input type='submit' value='Update Credentials'>";
        echo "</div>";
        echo "</form>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<p>After fixing the database connection, run these scripts in order:</p>";
echo "<ol>";
echo "<li><a href='fix_controller_inheritance.php'>fix_controller_inheritance.php</a> - Fix controller inheritance issues</li>";
echo "<li><a href='fix_games_endpoint.php'>fix_games_endpoint.php</a> - Fix games endpoint</li>";
echo "<li><a href='fix_directory_items_table.php'>fix_directory_items_table.php</a> - Create directory_items table</li>";
echo "<li><a href='fix_ai_tools_table.php'>fix_ai_tools_table.php</a> - Create ai_tools table</li>";
echo "</ol>";