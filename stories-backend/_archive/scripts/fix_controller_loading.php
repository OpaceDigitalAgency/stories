<?php
/**
 * Script to fix controller loading issues
 * This script will:
 * 1. Check for duplicate controller folders with different casing
 * 2. Consolidate controllers into a single folder with consistent casing
 * 3. Update routes to use the correct controller paths
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Controller Loading Fix</h1>";

// Base API path
$apiPath = __DIR__ . '/api/v1';

echo "<h2>Checking Directory Structure</h2>";

// Check for duplicate endpoints folders
$lowerEndpointsPath = $apiPath . '/endpoints';
$upperEndpointsPath = $apiPath . '/Endpoints';

$lowerExists = is_dir($lowerEndpointsPath);
$upperExists = is_dir($upperEndpointsPath);

echo "<p>Lower case 'endpoints' folder exists: " . ($lowerExists ? 'Yes' : 'No') . "</p>";
echo "<p>Upper case 'Endpoints' folder exists: " . ($upperExists ? 'Yes' : 'No') . "</p>";

// Decide which folder to keep
$targetFolder = null;
$sourceFolder = null;

if ($lowerExists && $upperExists) {
    echo "<p style='color:orange'>⚠️ Both endpoints folders exist. This can cause case sensitivity issues.</p>";
    
    // Count files in each folder to decide which to keep
    $lowerFiles = glob($lowerEndpointsPath . '/*.php');
    $upperFiles = glob($upperEndpointsPath . '/*.php');
    
    echo "<p>Files in lower case folder: " . count($lowerFiles) . "</p>";
    echo "<p>Files in upper case folder: " . count($upperFiles) . "</p>";
    
    // Keep the folder with more files, or the uppercase one if equal
    if (count($upperFiles) >= count($lowerFiles)) {
        $targetFolder = $upperEndpointsPath;
        $sourceFolder = $lowerEndpointsPath;
        echo "<p>Decision: Keep the upper case 'Endpoints' folder and move files from the lower case folder.</p>";
    } else {
        $targetFolder = $lowerEndpointsPath;
        $sourceFolder = $upperEndpointsPath;
        echo "<p>Decision: Keep the lower case 'endpoints' folder and move files from the upper case folder.</p>";
    }
} elseif ($lowerExists) {
    echo "<p>Only the lower case 'endpoints' folder exists. No consolidation needed.</p>";
    $targetFolder = $lowerEndpointsPath;
} elseif ($upperExists) {
    echo "<p>Only the upper case 'Endpoints' folder exists. No consolidation needed.</p>";
    $targetFolder = $upperEndpointsPath;
} else {
    echo "<p style='color:red'>❌ No endpoints folder found! Creating one...</p>";
    
    // Create the Endpoints folder (uppercase, as it's more conventional in PHP)
    $targetFolder = $upperEndpointsPath;
    if (mkdir($targetFolder, 0755, true)) {
        echo "<p style='color:green'>✅ Created Endpoints folder at: $targetFolder</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create Endpoints folder.</p>";
        exit;
    }
}

// Consolidate files if needed
if ($sourceFolder && $targetFolder) {
    echo "<h2>Consolidating Controller Files</h2>";
    
    $sourceFiles = glob($sourceFolder . '/*.php');
    
    if (count($sourceFiles) > 0) {
        foreach ($sourceFiles as $sourceFile) {
            $fileName = basename($sourceFile);
            $targetFile = $targetFolder . '/' . $fileName;
            
            // Check if the file already exists in the target folder
            if (file_exists($targetFile)) {
                echo "<p>File $fileName already exists in target folder. Comparing content...</p>";
                
                $sourceContent = file_get_contents($sourceFile);
                $targetContent = file_get_contents($targetFile);
                
                if ($sourceContent === $targetContent) {
                    echo "<p>Files are identical. Keeping the target file.</p>";
                } else {
                    echo "<p style='color:orange'>⚠️ Files have different content. Creating a backup of the target file.</p>";
                    $backupFile = $targetFile . '.bak.' . date('YmdHis');
                    if (copy($targetFile, $backupFile)) {
                        echo "<p>Created backup at: $backupFile</p>";
                    }
                    
                    // Use the newer file
                    if (filemtime($sourceFile) > filemtime($targetFile)) {
                        echo "<p>Source file is newer. Copying to target folder.</p>";
                        if (copy($sourceFile, $targetFile)) {
                            echo "<p style='color:green'>✅ Copied $fileName to target folder.</p>";
                        } else {
                            echo "<p style='color:red'>❌ Failed to copy $fileName to target folder.</p>";
                        }
                    } else {
                        echo "<p>Target file is newer. Keeping it.</p>";
                    }
                }
            } else {
                // File doesn't exist in target folder, copy it
                if (copy($sourceFile, $targetFile)) {
                    echo "<p style='color:green'>✅ Copied $fileName to target folder.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to copy $fileName to target folder.</p>";
                }
            }
        }
    } else {
        echo "<p>No files found in the source folder.</p>";
    }
}

// Check for GamesController in the target folder
$gamesController = $targetFolder . '/GamesController.php';
if (file_exists($gamesController)) {
    echo "<p style='color:green'>✅ GamesController found at: $gamesController</p>";
} else {
    echo "<p style='color:red'>❌ GamesController not found in the target folder!</p>";
    
    // Check if it exists in the source folder
    if ($sourceFolder && file_exists($sourceFolder . '/GamesController.php')) {
        echo "<p>GamesController found in the source folder. Copying to target folder...</p>";
        if (copy($sourceFolder . '/GamesController.php', $gamesController)) {
            echo "<p style='color:green'>✅ Copied GamesController to target folder.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to copy GamesController to target folder.</p>";
        }
    } else {
        echo "<p>Creating a basic GamesController...</p>";
        
        // Create a basic GamesController
        $controllerContent = <<<'EOD'
<?php
/**
 * Games Controller
 * 
 * Handles API requests for games
 */

namespace StoriesAPI\Endpoints;

use StoriesAPI\Core\Controller;
use StoriesAPI\Utils\Response;

class GamesController extends Controller {
    /**
     * Get a list of games
     */
    public function index() {
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : 25;
        
        // Ensure valid pagination values
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        
        // Calculate offset
        $offset = ($page - 1) * $pageSize;
        
        // Get filter parameters
        $filters = [];
        if (isset($_GET['featured'])) {
            $filters['featured'] = $_GET['featured'] === 'true' ? 1 : 0;
        }
        if (isset($_GET['isPublished'])) {
            $filters['is_published'] = $_GET['isPublished'] === 'true' ? 1 : 0;
        }
        
        // Get sort parameter
        $sortField = isset($_GET['sort']) ? $_GET['sort'] : 'id';
        $sortDirection = 'ASC';
        
        // Check if sort field has a direction prefix
        if (strpos($sortField, '-') === 0) {
            $sortField = substr($sortField, 1);
            $sortDirection = 'DESC';
        }
        
        // Map frontend field names to database column names
        $fieldMap = [
            'id' => 'id',
            'title' => 'title',
            'publishedAt' => 'published_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at'
        ];
        
        // Ensure the sort field is valid
        if (!isset($fieldMap[$sortField])) {
            $sortField = 'id';
        }
        
        // Get the database column name
        $sortColumn = $fieldMap[$sortField];
        
        // Build the sort clause
        $sortClause = "ORDER BY $sortColumn $sortDirection";
        
        try {
            // Connect to database
            echo "<p>Connecting to database...</p>";
            try {
                $this->db->query("SELECT 1");
                echo "<p>Database connection successful.</p>";
            } catch (\Exception $e) {
                echo "<p>Database connection failed: " . $e->getMessage() . "</p>";
                $this->serverError('Database connection failed: ' . $e->getMessage());
                return;
            }
            
            // Build the WHERE clause
            echo "<p>Building WHERE clause...</p>";
            $whereData = $this->buildWhereClause($filters);
            $whereClause = $whereData['clause'];
            $params = $whereData['params'];
            echo "<p>WHERE clause built: $whereClause with params: " . json_encode($params) . "</p>";
            
            // Count total records
            $countQuery = "SELECT COUNT(*) as total FROM games $whereClause";
            echo "<p>Executing count query: $countQuery with params: " . json_encode($params) . "</p>";
            $stmt = $this->db->query($countQuery, $params);
            $total = $stmt->fetch()['total'];
            echo "<p>Total records: $total</p>";
            
            // Get games with pagination
            $query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                $whereClause
                $sortClause
                LIMIT $offset, $pageSize";
            
            echo "<p>Executing data query: $query with params: " . json_encode($params) . "</p>";
            $stmt = $this->db->query($query, $params);
            $games = $stmt->fetchAll();
            echo "<p>Fetched games: " . json_encode($games) . "</p>";
            
            // Format games with the expected structure
            $formattedGames = Response::formatData($games);
            echo "<p>Formatted games: " . json_encode($formattedGames) . "</p>";
            
            // Send paginated response
            Response::sendPaginated($formattedGames, $page, $pageSize, $total);
        } catch (\Exception $e) {
            echo "<p>Error fetching games: " . $e->getMessage() . "</p>";
            $this->serverError('Failed to fetch games: ' . $e->getMessage());
        }
    }
    
    /**
     * Get a single game by ID
     * 
     * @param int $id The game ID
     */
    public function show($id) {
        try {
            $query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                WHERE id = :id";
            
            $stmt = $this->db->query($query, ['id' => $id]);
            $game = $stmt->fetch();
            
            if (!$game) {
                $this->notFound('Game not found');
                return;
            }
            
            // Format game with the expected structure
            $formattedGame = Response::formatData($game);
            
            // Send success response
            Response::sendSuccess($formattedGame);
        } catch (\Exception $e) {
            $this->serverError('Failed to fetch game: ' . $e->getMessage());
        }
    }
    
    /**
     * Build a WHERE clause based on filters
     * 
     * @param array $filters The filters to apply
     * @return array The WHERE clause and parameters
     */
    private function buildWhereClause($filters) {
        $where = [];
        $params = [];
        
        foreach ($filters as $key => $value) {
            $where[] = "$key = :$key";
            $params[$key] = $value;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        return [
            'clause' => $whereClause,
            'params' => $params
        ];
    }
}
EOD;
        
        if (file_put_contents($gamesController, $controllerContent)) {
            echo "<p style='color:green'>✅ Created GamesController at: $gamesController</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to create GamesController.</p>";
        }
    }
}

// Check routes file
echo "<h2>Checking Routes Configuration</h2>";

$routesFile = $apiPath . '/routes.php';
if (file_exists($routesFile)) {
    echo "<p>Routes file found at: $routesFile</p>";
    
    // Read the routes file
    $routesContent = file_get_contents($routesFile);
    
    // Check for games endpoint configuration
    if (preg_match('/games.*?Controller/i', $routesContent, $matches)) {
        echo "<p>Games endpoint configuration found: " . htmlspecialchars($matches[0]) . "</p>";
        
        // Check if the path is correct
        $targetFolderName = basename($targetFolder);
        $correctNamespace = "StoriesAPI\\$targetFolderName\\GamesController";
        $correctNamespaceEscaped = str_replace('\\', '\\\\', $correctNamespace);
        
        if (strpos($routesContent, $correctNamespace) === false && !preg_match("/$correctNamespaceEscaped/", $routesContent)) {
            echo "<p style='color:orange'>⚠️ The routes file may be using the wrong namespace for GamesController.</p>";
            
            echo "<h2>Updating Routes File</h2>";
            
            // Try to update the namespace
            $patterns = [
                "/StoriesAPI\\\\endpoints\\\\GamesController/i",
                "/StoriesAPI\\\\Endpoints\\\\GamesController/i"
            ];
            
            $newContent = $routesContent;
            foreach ($patterns as $pattern) {
                $newContent = preg_replace($pattern, $correctNamespace, $newContent);
            }
            
            if ($newContent !== $routesContent) {
                // Create a backup of the original file
                $backupFile = $routesFile . '.bak.' . date('YmdHis');
                if (copy($routesFile, $backupFile)) {
                    echo "<p>Created a backup of the original routes file at: $backupFile</p>";
                }
                
                // Write the updated content
                if (file_put_contents($routesFile, $newContent)) {
                    echo "<p style='color:green'>✅ Updated routes file with correct namespace.</p>";
                } else {
                    echo "<p style='color:red'>❌ Failed to update routes file.</p>";
                }
            } else {
                echo "<p>No namespace updates needed in the routes file.</p>";
            }
        } else {
            echo "<p style='color:green'>✅ The routes file is using the correct namespace.</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ No games endpoint configuration found in routes file.</p>";
        
        echo "<h2>Adding Games Route</h2>";
        
        // Get the correct namespace
        $targetFolderName = basename($targetFolder);
        $correctNamespace = "StoriesAPI\\$targetFolderName\\GamesController";
        
        // Add the games route
        $routeToAdd = "\n// Games routes\n\$router->get('/games', '$correctNamespace@index');\n\$router->get('/games/{id}', '$correctNamespace@show');\n";
        
        // Find a good place to add the route
        $pos = strrpos($routesContent, '?>');
        if ($pos !== false) {
            $newContent = substr($routesContent, 0, $pos) . $routeToAdd . substr($routesContent, $pos);
        } else {
            $newContent = $routesContent . $routeToAdd;
        }
        
        // Create a backup of the original file
        $backupFile = $routesFile . '.bak.' . date('YmdHis');
        if (copy($routesFile, $backupFile)) {
            echo "<p>Created a backup of the original routes file at: $backupFile</p>";
        }
        
        // Write the updated content
        if (file_put_contents($routesFile, $newContent)) {
            echo "<p style='color:green'>✅ Added games routes to the routes file.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update routes file.</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ Routes file not found at: $routesFile</p>";
    
    echo "<h2>Creating Routes File</h2>";
    
    // Get the correct namespace
    $targetFolderName = basename($targetFolder);
    $correctNamespace = "StoriesAPI\\$targetFolderName";
    
    // Create a basic routes file
    $routesContent = <<<EOD
<?php
/**
 * API Routes
 * 
 * This file defines all the API routes
 */

// Stories routes
\$router->get('/stories', '$correctNamespace\\StoriesController@index');
\$router->get('/stories/{id}', '$correctNamespace\\StoriesController@show');

// Authors routes
\$router->get('/authors', '$correctNamespace\\AuthorsController@index');
\$router->get('/authors/{id}', '$correctNamespace\\AuthorsController@show');

// Tags routes
\$router->get('/tags', '$correctNamespace\\TagsController@index');
\$router->get('/tags/{id}', '$correctNamespace\\TagsController@show');

// Games routes
\$router->get('/games', '$correctNamespace\\GamesController@index');
\$router->get('/games/{id}', '$correctNamespace\\GamesController@show');

// Directory items routes
\$router->get('/directory-items', '$correctNamespace\\DirectoryItemsController@index');
\$router->get('/directory-items/{id}', '$correctNamespace\\DirectoryItemsController@show');

// AI tools routes
\$router->get('/ai-tools', '$correctNamespace\\AIToolsController@index');
\$router->get('/ai-tools/{id}', '$correctNamespace\\AIToolsController@show');

// Auth routes
\$router->post('/auth/login', '$correctNamespace\\AuthController@login');
\$router->post('/auth/refresh', '$correctNamespace\\AuthController@refresh');
\$router->post('/auth/logout', '$correctNamespace\\AuthController@logout');
?>
EOD;
    
    // Create the directory if it doesn't exist
    $routesDir = dirname($routesFile);
    if (!is_dir($routesDir)) {
        if (mkdir($routesDir, 0755, true)) {
            echo "<p>Created directory: $routesDir</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to create directory: $routesDir</p>";
            exit;
        }
    }
    
    // Write the routes file
    if (file_put_contents($routesFile, $routesContent)) {
        echo "<p style='color:green'>✅ Created routes file at: $routesFile</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create routes file.</p>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Check that the controllers are correctly loaded by the router.</li>";
echo "<li>Verify that the Response class is being used correctly in the controllers.</li>";
echo "<li>Test the API endpoints to ensure they are working as expected.</li>";
echo "</ol>";

echo "<p>You can test the controller loading using the <a href='test_controller_loading.php'>test_controller_loading.php</a> script.</p>";