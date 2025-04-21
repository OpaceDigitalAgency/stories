<?php
/**
 * Apply Database Changes
 */

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Apply Database Changes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .box {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success { color: green; }
        .error { color: red; }
        pre {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Apply Database Changes</h1>
    
    <div class="box">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
            try {
                // Load config
                $config = require __DIR__ . '/../api/v1/Config/config.php';
                
                // Create database connection
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s;port=%d',
                    $config['db']['host'],
                    $config['db']['name'],
                    $config['db']['charset'],
                    $config['db']['port']
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $pdo = new PDO(
                    $dsn,
                    $config['db']['user'],
                    $config['db']['password'],
                    $options
                );
                
                // Get SQL file content
                $sql = file_get_contents(__DIR__ . '/../api/v1/database.sql');
                
                // Split into individual statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) { return !empty($stmt); }
                );
                
                // Execute each statement
                echo "<h2>Applying Changes</h2>";
                foreach ($statements as $statement) {
                    try {
                        $pdo->exec($statement);
                        echo "<p class='success'>✓ Successfully executed:</p>";
                        echo "<pre>" . htmlspecialchars($statement) . "</pre>";
                    } catch (PDOException $e) {
                        if ($e->getCode() == '42S01') { // Table already exists
                            echo "<p class='success'>✓ Table already exists</p>";
                            echo "<pre>" . htmlspecialchars($statement) . "</pre>";
                        } else {
                            throw $e;
                        }
                    }
                }
                
                echo "<div class='box'>";
                echo "<h2>Success!</h2>";
                echo "<p class='success'>All database changes have been applied successfully.</p>";
                echo "<p>You can now test the API endpoints:</p>";
                echo "<ul>";
                echo "<li><a href='/api/v1/authors'>/api/v1/authors</a></li>";
                echo "<li><a href='/api/v1/games'>/api/v1/games</a></li>";
                echo "<li><a href='/api/v1/directory-items'>/api/v1/directory-items</a></li>";
                echo "</ul>";
                echo "</div>";
            } catch (Exception $e) {
                echo "<h2>Error</h2>";
                echo "<p class='error'>Failed to apply changes: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            ?>
            <h2>Database Changes</h2>
            <p>The following changes will be applied:</p>
            <pre><?php echo htmlspecialchars(file_get_contents(__DIR__ . '/../api/v1/database.sql')); ?></pre>
            
            <form method="post">
                <input type="hidden" name="apply" value="true">
                <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    Apply Changes
                </button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>