<?php
/**
 * Test script for controller loading
 * This script will:
 * 1. Check if controllers are correctly loaded
 * 2. Verify namespace and class names
 * 3. Test the router configuration
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Controller Loading Test</h1>";

// Base API path
$apiPath = __DIR__ . '/api/v1';

echo "<h2>Directory Structure</h2>";

// Check for endpoints folders
$lowerEndpointsPath = $apiPath . '/endpoints';
$upperEndpointsPath = $apiPath . '/Endpoints';

$lowerExists = is_dir($lowerEndpointsPath);
$upperExists = is_dir($upperEndpointsPath);

echo "<p>Lower case 'endpoints' folder exists: " . ($lowerExists ? 'Yes' : 'No') . "</p>";
echo "<p>Upper case 'Endpoints' folder exists: " . ($upperExists ? 'Yes' : 'No') . "</p>";

// Check which folder is being used
$activeFolder = null;
if ($lowerExists && $upperExists) {
    echo "<p style='color:orange'>⚠️ Both endpoints folders exist. This can cause case sensitivity issues.</p>";
    
    // Try to determine which one is being used based on the routes file
    $routesFile = $apiPath . '/routes.php';
    if (file_exists($routesFile)) {
        $routesContent = file_get_contents($routesFile);
        
        if (preg_match('/StoriesAPI\\\\endpoints\\\\/', $routesContent)) {
            echo "<p>Routes file is using the lower case 'endpoints' namespace.</p>";
            $activeFolder = $lowerEndpointsPath;
        } elseif (preg_match('/StoriesAPI\\\\Endpoints\\\\/', $routesContent)) {
            echo "<p>Routes file is using the upper case 'Endpoints' namespace.</p>";
            $activeFolder = $upperEndpointsPath;
        } else {
            echo "<p>Could not determine which namespace is being used in the routes file.</p>";
        }
    } else {
        echo "<p>Routes file not found. Cannot determine which namespace is being used.</p>";
    }
} elseif ($lowerExists) {
    $activeFolder = $lowerEndpointsPath;
} elseif ($upperExists) {
    $activeFolder = $upperEndpointsPath;
} else {
    echo "<p style='color:red'>❌ No endpoints folder found!</p>";
}

// List controllers in the active folder
if ($activeFolder) {
    echo "<h2>Controllers in Active Folder</h2>";
    
    $controllers = glob($activeFolder . '/*.php');
    
    if (count($controllers) > 0) {
        echo "<ul>";
        foreach ($controllers as $controller) {
            $fileName = basename($controller);
            echo "<li>$fileName</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No controllers found in the active folder.</p>";
    }
    
    // Check for GamesController
    $gamesController = $activeFolder . '/GamesController.php';
    if (file_exists($gamesController)) {
        echo "<p style='color:green'>✅ GamesController found in the active folder.</p>";
        
        // Check the namespace
        $content = file_get_contents($gamesController);
        $activeFolderName = basename($activeFolder);
        $expectedNamespace = "namespace StoriesAPI\\$activeFolderName;";
        
        if (strpos($content, $expectedNamespace) !== false) {
            echo "<p style='color:green'>✅ GamesController has the correct namespace.</p>";
        } else {
            echo "<p style='color:orange'>⚠️ GamesController may have the wrong namespace.</p>";
            
            // Try to find the actual namespace
            if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                echo "<p>Actual namespace: " . htmlspecialchars($matches[1]) . "</p>";
                echo "<p>Expected namespace: " . htmlspecialchars("StoriesAPI\\$activeFolderName") . "</p>";
            }
        }
    } else {
        echo "<p style='color:red'>❌ GamesController not found in the active folder!</p>";
    }
}

// Check routes file
echo "<h2>Routes Configuration</h2>";

$routesFile = $apiPath . '/routes.php';
if (file_exists($routesFile)) {
    echo "<p>Routes file found at: $routesFile</p>";
    
    // Read the routes file
    $routesContent = file_get_contents($routesFile);
    
    // Check for games endpoint configuration
    if (preg_match('/games.*?Controller/i', $routesContent, $matches)) {
        echo "<p>Games endpoint configuration found: " . htmlspecialchars($matches[0]) . "</p>";
        
        // Extract the full route configuration
        if (preg_match('/\$router->get\s*\(\s*[\'"]\/games[\'"].*?\);/s', $routesContent, $matches)) {
            echo "<p>Full route configuration: " . htmlspecialchars($matches[0]) . "</p>";
        }
    } else {
        echo "<p style='color:red'>❌ No games endpoint configuration found in routes file!</p>";
    }
} else {
    echo "<p style='color:red'>❌ Routes file not found at: $routesFile</p>";
}

// Test autoloading
echo "<h2>Autoloading Test</h2>";

// Check if the autoloader is available
$autoloaderFile = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloaderFile)) {
    echo "<p>Composer autoloader found. Attempting to load it...</p>";
    
    try {
        require_once $autoloaderFile;
        echo "<p style='color:green'>✅ Autoloader loaded successfully.</p>";
        
        // Try to load the GamesController class
        if ($activeFolder) {
            $activeFolderName = basename($activeFolder);
            $className = "StoriesAPI\\$activeFolderName\\GamesController";
            
            echo "<p>Attempting to load class: $className</p>";
            
            try {
                if (class_exists($className)) {
                    echo "<p style='color:green'>✅ Class $className loaded successfully.</p>";
                } else {
                    echo "<p style='color:red'>❌ Class $className not found!</p>";
                }
            } catch (\Exception $e) {
                echo "<p style='color:red'>❌ Error loading class: " . $e->getMessage() . "</p>";
            }
        }
    } catch (\Exception $e) {
        echo "<p style='color:red'>❌ Error loading autoloader: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>Composer autoloader not found. Checking for manual autoloader...</p>";
    
    // Check for a manual autoloader
    $manualAutoloader = $apiPath . '/autoload.php';
    if (file_exists($manualAutoloader)) {
        echo "<p>Manual autoloader found. Attempting to load it...</p>";
        
        try {
            require_once $manualAutoloader;
            echo "<p style='color:green'>✅ Manual autoloader loaded successfully.</p>";
        } catch (\Exception $e) {
            echo "<p style='color:red'>❌ Error loading manual autoloader: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ No autoloader found. Class loading may fail.</p>";
    }
}

// Test database connection
echo "<h2>Database Connection Test</h2>";

// Check if the database configuration file exists
$dbConfigFile = $apiPath . '/config/database.php';
if (file_exists($dbConfigFile)) {
    echo "<p>Database configuration file found. Attempting to load it...</p>";
    
    try {
        include_once $dbConfigFile;
        echo "<p style='color:green'>✅ Database configuration loaded successfully.</p>";
        
        // Try to connect to the database
        if (class_exists('Database')) {
            echo "<p>Attempting to connect to the database...</p>";
            
            try {
                $db = new Database();
                echo "<p style='color:green'>✅ Database connection successful.</p>";
                
                // Test a simple query
                try {
                    $result = $db->query("SELECT 1");
                    echo "<p style='color:green'>✅ Test query executed successfully.</p>";
                } catch (\Exception $e) {
                    echo "<p style='color:red'>❌ Error executing test query: " . $e->getMessage() . "</p>";
                }
            } catch (\Exception $e) {
                echo "<p style='color:red'>❌ Error connecting to database: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color:red'>❌ Database class not found!</p>";
        }
    } catch (\Exception $e) {
        echo "<p style='color:red'>❌ Error loading database configuration: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:orange'>⚠️ Database configuration file not found at: $dbConfigFile</p>";
}

// Test Response class
echo "<h2>Response Class Test</h2>";

// Check if the Response class file exists
$responseFile = $apiPath . '/Utils/Response.php';
if (file_exists($responseFile)) {
    echo "<p>Response class file found. Checking for formatData method...</p>";
    
    $content = file_get_contents($responseFile);
    
    // Check if formatData method is public
    if (strpos($content, 'public static function formatData') !== false) {
        echo "<p style='color:green'>✅ formatData method is public.</p>";
    } elseif (strpos($content, 'private static function formatData') !== false) {
        echo "<p style='color:orange'>⚠️ formatData method is private. This may cause issues.</p>";
    } else {
        echo "<p style='color:red'>❌ formatData method not found in Response class!</p>";
    }
} else {
    echo "<p style='color:red'>❌ Response class file not found at: $responseFile</p>";
}

echo "<h2>Recommendations</h2>";
echo "<ol>";

if ($lowerExists && $upperExists) {
    echo "<li style='color:orange'>Consolidate the duplicate endpoints folders. Use the <a href='fix_controller_loading.php'>fix_controller_loading.php</a> script.</li>";
}

if (!file_exists($gamesController)) {
    echo "<li style='color:red'>Create the GamesController. Use the <a href='fix_controller_loading.php'>fix_controller_loading.php</a> script.</li>";
}

if (file_exists($responseFile) && strpos(file_get_contents($responseFile), 'private static function formatData') !== false) {
    echo "<li style='color:orange'>Make the formatData method in Response class public. Use the <a href='fix_response_class.php'>fix_response_class.php</a> script.</li>";
}

echo "<li>Check that the database connection is working correctly.</li>";
echo "<li>Verify that the routes are correctly configured.</li>";
echo "<li>Test the API endpoints to ensure they are working as expected.</li>";
echo "</ol>";

echo "<h2>Next Steps</h2>";
echo "<p>After fixing any issues, test the API endpoints using the <a href='test_api_format.php'>test_api_format.php</a> script.</p>";