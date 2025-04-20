<?php
/**
 * Fix Database Manually
 * 
 * This script provides a form to manually enter the correct database credentials
 * and updates the Database class with these credentials.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Database Manually</h1>";

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

// Default database connection parameters
$host = 'localhost';
$dbname = 'stories';
$username = '';
$password = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $dbname = $_POST['dbname'] ?? 'stories';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
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
        
        // Create a backup of the original file if it exists
        if (file_exists($databaseClassFile)) {
            $backupFile = $databaseClassFile . '.bak.' . date('YmdHis');
            if (copy($databaseClassFile, $backupFile)) {
                echo "<p>Created backup at: $backupFile</p>";
            }
        }
        
        // Update the Database class with these credentials
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
            
            // Check if games table exists
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'games'");
                $tableExists = $stmt->rowCount() > 0;
                
                if ($tableExists) {
                    echo "<p style='color:green'>✅ Games table exists.</p>";
                    
                    // Check if the table has data
                    $stmt = $pdo->query("SELECT COUNT(*) FROM games");
                    $count = $stmt->fetchColumn();
                    
                    echo "<p>Games table has $count records.</p>";
                } else {
                    echo "<p style='color:orange'>⚠️ Games table does not exist. You'll need to create it.</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color:orange'>⚠️ Error checking games table: " . $e->getMessage() . "</p>";
            }
            
            echo "<h2>Next Steps</h2>";
            echo "<p>Database connection fixed! Now run these scripts in order:</p>";
            echo "<ol>";
            echo "<li><a href='fix_controller_inheritance.php'>fix_controller_inheritance.php</a> - Fix controller inheritance issues</li>";
            echo "<li><a href='fix_games_endpoint.php'>fix_games_endpoint.php</a> - Fix games endpoint</li>";
            echo "<li><a href='fix_directory_items_table.php'>fix_directory_items_table.php</a> - Create directory_items table</li>";
            echo "<li><a href='fix_ai_tools_table.php'>fix_ai_tools_table.php</a> - Create ai_tools table</li>";
            echo "</ol>";
        } else {
            echo "<p style='color:red'>❌ Failed to update Database class.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        echo "<p>Please check your credentials and try again.</p>";
    }
}

// Display the form
echo "<h2>Enter Database Credentials</h2>";
echo "<p>Please enter the correct database credentials for your server:</p>";
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
echo "<input type='text' id='username' name='username' value='$username' required>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label for='password' style='display: inline-block; width: 150px;'>Password:</label>";
echo "<input type='password' id='password' name='password' value='$password'>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<input type='submit' value='Test and Update'>";
echo "</div>";
echo "</form>";

echo "<h2>Common Database Credentials</h2>";
echo "<p>Here are some common database credentials you can try:</p>";
echo "<ul>";
echo "<li>Username: <strong>stories_user</strong>, Password: <strong>stories_password</strong></li>";
echo "<li>Username: <strong>root</strong>, Password: <em>(empty)</em></li>";
echo "<li>Username: <strong>root</strong>, Password: <strong>root</strong></li>";
echo "<li>Username: <strong>admin</strong>, Password: <strong>admin</strong></li>";
echo "<li>Username: <strong>stories</strong>, Password: <strong>stories</strong></li>";
echo "<li>Username: <strong>stories_admin</strong>, Password: <strong>stories_admin</strong></li>";
echo "</ul>";

echo "<p>You can also check the database credentials in your hosting control panel or ask your hosting provider.</p>";