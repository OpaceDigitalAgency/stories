<?php
/**
 * Script to fix the games endpoint by ensuring the correct controller is used
 * and addressing case sensitivity issues.
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Games Endpoint Fix</h1>";

// Check for duplicate endpoints folders
$apiPath = __DIR__ . '/api/v1';
echo "<h2>Checking for duplicate endpoints folders</h2>";

$lowerEndpointsPath = $apiPath . '/endpoints';
$upperEndpointsPath = $apiPath . '/Endpoints';

$lowerExists = is_dir($lowerEndpointsPath);
$upperExists = is_dir($upperEndpointsPath);

echo "<p>Lower case 'endpoints' folder exists: " . ($lowerExists ? 'Yes' : 'No') . "</p>";
echo "<p>Upper case 'Endpoints' folder exists: " . ($upperExists ? 'Yes' : 'No') . "</p>";

// Check for GamesController in both folders
$lowerGamesController = $lowerEndpointsPath . '/GamesController.php';
$upperGamesController = $upperEndpointsPath . '/GamesController.php';

$lowerControllerExists = file_exists($lowerGamesController);
$upperControllerExists = file_exists($upperGamesController);

echo "<p>GamesController in lower case folder: " . ($lowerControllerExists ? 'Yes' : 'No') . "</p>";
echo "<p>GamesController in upper case folder: " . ($upperControllerExists ? 'Yes' : 'No') . "</p>";

// Check which controller is being loaded by the router
echo "<h2>Checking router configuration</h2>";

$routesFile = $apiPath . '/routes.php';
if (file_exists($routesFile)) {
    $routesContent = file_get_contents($routesFile);
    echo "<p>Routes file exists. Checking for games endpoint configuration...</p>";
    
    // Look for games endpoint configuration
    if (preg_match('/games.*?Controller/i', $routesContent, $matches)) {
        echo "<p>Games endpoint configuration found: " . htmlspecialchars($matches[0]) . "</p>";
    } else {
        echo "<p>No games endpoint configuration found in routes file.</p>";
    }
} else {
    echo "<p>Routes file not found at: " . $routesFile . "</p>";
}

// Check database connection for games table
echo "<h2>Checking database connection and games table</h2>";

// Include database configuration
$dbConfigFile = $apiPath . '/config/database.php';
if (file_exists($dbConfigFile)) {
    include_once $dbConfigFile;
    echo "<p>Database configuration file found.</p>";
    
    // Try to connect to the database
    try {
        if (class_exists('Database')) {
            $db = new Database();
            echo "<p>Database class found and instantiated.</p>";
            
            // Check if games table exists
            $query = "SHOW TABLES LIKE 'games'";
            $result = $db->query($query);
            
            if ($result && $result->rowCount() > 0) {
                echo "<p>Games table exists. Checking structure...</p>";
                
                // Check table structure
                $query = "DESCRIBE games";
                $result = $db->query($query);
                
                if ($result) {
                    echo "<p>Games table structure:</p><ul>";
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>Failed to get games table structure.</p>";
                }
            } else {
                echo "<p>Games table does not exist!</p>";
            }
        } else {
            echo "<p>Database class not found.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Database connection error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Database configuration file not found at: " . $dbConfigFile . "</p>";
}

// Recommendations
echo "<h2>Recommendations</h2>";
echo "<ul>";

if ($lowerExists && $upperExists) {
    echo "<li>Consolidate the duplicate endpoints folders. Keep the one that matches your naming convention.</li>";
    
    if ($lowerControllerExists && $upperControllerExists) {
        echo "<li>Ensure only one copy of GamesController.php exists and is correctly referenced in the routes.</li>";
    } else if ($lowerControllerExists) {
        echo "<li>Update routes to use the lowercase endpoints folder.</li>";
    } else if ($upperControllerExists) {
        echo "<li>Update routes to use the uppercase Endpoints folder.</li>";
    }
}

echo "<li>Check that the database connection parameters are correct.</li>";
echo "<li>Verify that the games table exists and has the expected structure.</li>";
echo "<li>Ensure the Response class is correctly formatting the data.</li>";
echo "</ul>";

// Automatic fixes
echo "<h2>Automatic Fixes</h2>";

// Fix 1: Ensure Response class has formatData method public
$responseFile = $apiPath . '/Utils/Response.php';
if (file_exists($responseFile)) {
    $responseContent = file_get_contents($responseFile);
    
    // Check if formatData is private
    if (strpos($responseContent, 'private static function formatData') !== false) {
        // Make formatData public
        $newContent = str_replace('private static function formatData', 'public static function formatData', $responseContent);
        
        if (file_put_contents($responseFile, $newContent)) {
            echo "<p>✅ Made Response::formatData method public.</p>";
        } else {
            echo "<p>❌ Failed to update Response::formatData method.</p>";
        }
    } else if (strpos($responseContent, 'public static function formatData') !== false) {
        echo "<p>✅ Response::formatData method is already public.</p>";
    } else {
        echo "<p>❓ Could not find formatData method in Response class.</p>";
    }
} else {
    echo "<p>❌ Response file not found at: " . $responseFile . "</p>";
}

// Fix 2: Create a test script to directly query the games table
$testScript = __DIR__ . '/test_games_table.php';
$testContent = '<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

echo "<h1>Games Table Test</h1>";

// Include database configuration
include_once __DIR__ . "/api/v1/config/database.php";

try {
    $db = new Database();
    echo "<p>Database connected successfully.</p>";
    
    // Query games table
    $query = "SELECT * FROM games LIMIT 10";
    $stmt = $db->query($query);
    
    if ($stmt) {
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Query executed successfully. Found " . count($games) . " games.</p>";
        
        if (count($games) > 0) {
            echo "<h2>Games Data</h2>";
            echo "<pre>" . json_encode($games, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>No games found in the database.</p>";
        }
    } else {
        echo "<p>Failed to execute query.</p>";
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
';

if (file_put_contents($testScript, $testContent)) {
    echo "<p>✅ Created test script at: <a href='/test_games_table.php'>test_games_table.php</a></p>";
} else {
    echo "<p>❌ Failed to create test script.</p>";
}

echo "<p>Fix completed. Please check the recommendations and run the test script.</p>";