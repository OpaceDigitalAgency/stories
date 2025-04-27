<?php
/**
 * All-in-One Fix Script
 * 
 * This script combines all the fixes into a single script that can be run to fix all issues at once.
 * It will:
 * 1. Fix case sensitivity issues with folders and files
 * 2. Fix the Response class to make formatData method public
 * 3. Fix controller loading issues
 * 4. Fix database table structure
 * 5. Test the API endpoints
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>All-in-One Fix Script</h1>";

// Base paths
$apiPath = __DIR__ . '/api/v1';
$utilsPath = $apiPath . '/Utils';
$configPath = $apiPath . '/config';

// Database connection parameters - adjust these to match your configuration
$host = 'localhost';
$dbname = 'stories';
$username = 'stories_user';
$password = 'stories_password';

// Step 1: Fix case sensitivity issues
echo "<h2>Step 1: Fix Case Sensitivity Issues</h2>";

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
    echo "<h3>Consolidating Controller Files</h3>";
    
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

// Step 2: Fix the Response class
echo "<h2>Step 2: Fix Response Class</h2>";

// Check if Utils directory exists
if (!is_dir($utilsPath)) {
    echo "<p>Utils directory does not exist. Creating it...</p>";
    if (mkdir($utilsPath, 0755, true)) {
        echo "<p style='color:green'>✅ Created Utils directory.</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create Utils directory.</p>";
    }
}

// Path to the Response class
$responseFile = $utilsPath . '/Response.php';

if (file_exists($responseFile)) {
    echo "<p style='color:green'>✅ Response file found at: $responseFile</p>";
    
    // Read the file content
    $content = file_get_contents($responseFile);
    
    // Check if formatData method is private
    $isPrivate = strpos($content, 'private static function formatData') !== false;
    $isPublic = strpos($content, 'public static function formatData') !== false;
    
    if ($isPrivate) {
        echo "<p style='color:orange'>⚠️ The formatData method is private. Changing to public...</p>";
        
        // Create a backup of the original file
        $backupFile = $responseFile . '.bak.' . date('YmdHis');
        if (copy($responseFile, $backupFile)) {
            echo "<p>Created a backup of the original Response class at: $backupFile</p>";
        }
        
        // Make formatData public
        $newContent = str_replace('private static function formatData', 'public static function formatData', $content);
        
        if (file_put_contents($responseFile, $newContent)) {
            echo "<p style='color:green'>✅ Successfully made formatData method public!</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update the Response class.</p>";
        }
    } elseif ($isPublic) {
        echo "<p style='color:green'>✅ The formatData method is already public.</p>";
    } else {
        echo "<p style='color:red'>❌ Could not find the formatData method in the Response class.</p>";
        
        // Add the formatData method if it doesn't exist
        echo "<h3>Adding formatData Method</h3>";
        
        // Find the class closing brace
        $classEnd = strrpos($content, '}');
        
        if ($classEnd !== false) {
            $formatDataMethod = <<<'EOD'

    /**
     * Format data to ensure it has the correct structure with attributes
     * 
     * @param array $data The data to format
     * @return array The formatted data
     */
    public static function formatData($data) {
        // If data is already in the correct format, check if attributes needs fixing
        if (isset($data['id']) && isset($data['attributes'])) {
            // Check for nested attributes
            if (isset($data['attributes']['attributes'])) {
                $data['attributes'] = $data['attributes']['attributes'];
            }
            return $data;
        }
        
        // If data is an array of items, format each item
        if (is_array($data) && !isset($data['id']) && !isset($data['attributes']) && !empty($data)) {
            $formattedData = [];
            foreach ($data as $item) {
                if (is_array($item) && isset($item['id'])) {
                    // Format each item
                    $attributes = [];
                    foreach ($item as $key => $value) {
                        if ($key !== 'id') {
                            $attributes[$key] = $value;
                        }
                    }
                    
                    $formattedData[] = [
                        'id' => $item['id'],
                        'attributes' => $attributes
                    ];
                } else {
                    // If item doesn't have an ID, keep it as is
                    $formattedData[] = $item;
                }
            }
            return $formattedData;
        }
        
        // Format a single item
        $id = $data['id'] ?? null;
        if ($id === null) {
            // If no ID, return data as is
            return $data;
        }
        
        // Create attributes array
        $attributes = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $attributes[$key] = $value;
            }
        }
        
        // Return formatted data
        return [
            'id' => $id,
            'attributes' => $attributes
        ];
    }
EOD;
            
            // Insert the method before the class closing brace
            $newContent = substr($content, 0, $classEnd) . $formatDataMethod . "\n" . substr($content, $classEnd);
            
            if (file_put_contents($responseFile, $newContent)) {
                echo "<p style='color:green'>✅ Successfully added formatData method to the Response class!</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to update the Response class.</p>";
            }
        } else {
            echo "<p style='color:red'>❌ Could not find the end of the Response class.</p>";
        }
    }
} else {
    echo "<p style='color:red'>❌ Response file not found at: $responseFile</p>";
    
    // Create a new Response class
    echo "<h3>Creating Response Class</h3>";
    
    $responseClass = <<<'EOD'
<?php
/**
 * API Response Utility Class
 * 
 * This class handles formatting API responses to match the expected format
 * by the Astro frontend.
 * 
 * @package Stories API
 * @version 1.0.0
 */

namespace StoriesAPI\Utils;

class Response {
    /**
     * @var bool Debug mode flag
     */
    public static $debugMode = false;
    
    /**
     * Format a successful response
     * 
     * @param array $data The data to include in the response
     * @param array $meta Additional metadata
     * @param int $statusCode HTTP status code
     * @return array The formatted response
     */
    public static function success($data, $meta = [], $statusCode = 200) {
        // Set the HTTP response code
        http_response_code($statusCode);
        
        // Format the response to match Strapi format expected by the frontend
        $response = [
            'data' => $data,
            'meta' => $meta
        ];
        
        // If meta doesn't include pagination and data is an array, add default pagination
        if (!isset($meta['pagination']) && is_array($data)) {
            $response['meta']['pagination'] = [
                'page' => 1,
                'pageSize' => count($data),
                'pageCount' => 1,
                'total' => count($data)
            ];
        }
        
        return $response;
    }
    
    /**
     * Format a paginated response
     * 
     * @param array $data The data to include in the response
     * @param int $page Current page number
     * @param int $pageSize Items per page
     * @param int $total Total number of items
     * @param array $additionalMeta Additional metadata
     * @param int $statusCode HTTP status code
     * @return array The formatted response
     */
    public static function paginated($data, $page, $pageSize, $total, $additionalMeta = [], $statusCode = 200) {
        // Set the HTTP response code
        http_response_code($statusCode);
        
        // Calculate page count
        $pageCount = ceil($total / $pageSize);
        
        // Format pagination metadata
        $pagination = [
            'page' => (int)$page,
            'pageSize' => (int)$pageSize,
            'pageCount' => (int)$pageCount,
            'total' => (int)$total
        ];
        
        // Add pagination headers for frontend consumption
        header('X-Total-Count: ' . $total);
        header('X-Pagination-Page: ' . $page);
        header('X-Pagination-Page-Size: ' . $pageSize);
        header('X-Pagination-Page-Count: ' . $pageCount);
        
        // Merge additional metadata with pagination
        $meta = array_merge(['pagination' => $pagination], $additionalMeta);
        
        // Return the formatted response
        return self::success($data, $meta, $statusCode);
    }
    
    /**
     * Format an error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Detailed error information
     * @return array The formatted error response
     */
    public static function error($message, $statusCode = 400, $errors = []) {
        // Set the HTTP response code
        http_response_code($statusCode);
        
        // Format the error response
        $response = [
            'error' => true,
            'message' => $message,
            'statusCode' => $statusCode
        ];
        
        // Add detailed errors if provided
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $response;
    }
    
    /**
     * Send the response as JSON
     * 
     * @param array $data The response data
     */
    public static function json($data) {
        // Set content type header
        header('Content-Type: application/json; charset=UTF-8');
        
        // Make sure there's no output before JSON
        if (ob_get_length() > 0) {
            ob_clean();
        }
        
        // Encode the data
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
        // Check for JSON encoding errors
        if ($json === false) {
            error_log("JSON encoding error: " . json_last_error_msg());
            
            // Try to sanitize data
            $cleanData = self::sanitizeDataForJson($data);
            $json = json_encode($cleanData);
            
            if ($json === false) {
                // If still failing, return a simple error response
                $errorJson = '{"error":true,"message":"Internal server error: Unable to encode response","statusCode":500}';
                echo $errorJson;
                exit;
            }
        }
        
        // Output the JSON response
        echo $json;
        exit;
    }
    
    /**
     * Sanitize data for JSON encoding
     *
     * @param mixed $data The data to sanitize
     * @return mixed Sanitized data
     */
    private static function sanitizeDataForJson($data) {
        if (is_array($data)) {
            $clean = [];
            foreach ($data as $key => $value) {
                $clean[$key] = self::sanitizeDataForJson($value);
            }
            return $clean;
        } elseif (is_object($data)) {
            $clean = new \stdClass();
            foreach (get_object_vars($data) as $key => $value) {
                $clean->$key = self::sanitizeDataForJson($value);
            }
            return $clean;
        } elseif (is_string($data)) {
            // Remove invalid UTF-8 characters
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        } else {
            return $data;
        }
    }
    
    /**
     * Format data to ensure it has the correct structure with attributes
     * 
     * @param array $data The data to format
     * @return array The formatted data
     */
    public static function formatData($data) {
        // If data is already in the correct format, check if attributes needs fixing
        if (isset($data['id']) && isset($data['attributes'])) {
            // Check for nested attributes
            if (isset($data['attributes']['attributes'])) {
                $data['attributes'] = $data['attributes']['attributes'];
            }
            return $data;
        }
        
        // If data is an array of items, format each item
        if (is_array($data) && !isset($data['id']) && !isset($data['attributes']) && !empty($data)) {
            $formattedData = [];
            foreach ($data as $item) {
                if (is_array($item) && isset($item['id'])) {
                    // Format each item
                    $attributes = [];
                    foreach ($item as $key => $value) {
                        if ($key !== 'id') {
                            $attributes[$key] = $value;
                        }
                    }
                    
                    $formattedData[] = [
                        'id' => $item['id'],
                        'attributes' => $attributes
                    ];
                } else {
                    // If item doesn't have an ID, keep it as is
                    $formattedData[] = $item;
                }
            }
            return $formattedData;
        }
        
        // Format a single item
        $id = $data['id'] ?? null;
        if ($id === null) {
            // If no ID, return data as is
            return $data;
        }
        
        // Create attributes array
        $attributes = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $attributes[$key] = $value;
            }
        }
        
        // Return formatted data
        return [
            'id' => $id,
            'attributes' => $attributes
        ];
    }
    
    /**
     * Send a success response as JSON
     * 
     * @param array $data The data to include in the response
     * @param array $meta Additional metadata
     * @param int $statusCode HTTP status code
     */
    public static function sendSuccess($data, $meta = [], $statusCode = 200) {
        // Format data if needed
        $formatted = self::formatData($data);
        
        // Send the response
        self::json(self::success($formatted, $meta, $statusCode));
    }
    
    /**
     * Send a paginated response as JSON
     * 
     * @param array $data The data to include in the response
     * @param int $page Current page number
     * @param int $pageSize Items per page
     * @param int $total Total number of items
     * @param array $additionalMeta Additional metadata
     * @param int $statusCode HTTP status code
     */
    public static function sendPaginated($data, $page, $pageSize, $total, $additionalMeta = [], $statusCode = 200) {
        // Check if data is already formatted
        $isFormatted = true;
        if (is_array($data)) {
            foreach ($data as $item) {
                if (!isset($item['id']) || !isset($item['attributes'])) {
                    $isFormatted = false;
                    break;
                }
            }
        }
        
        $formattedData = $isFormatted ? $data : self::formatData($data);
        self::json(self::paginated($formattedData, $page, $pageSize, $total, $additionalMeta, $statusCode));
    }
    
    /**
     * Send an error response as JSON
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Detailed error information
     */
    public static function sendError($message, $statusCode = 400, $errors = []) {
        self::json(self::error($message, $statusCode, $errors));
    }
}
EOD;
    
    if (file_put_contents($responseFile, $responseClass)) {
        echo "<p style='color:green'>✅ Successfully created Response class at: $responseFile</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to create Response class.</p>";
    }
}

// Step 3: Fix controller loading issues
echo "<h2>Step 3: Fix Controller Loading Issues</h2>";

// Check for GamesController in the target folder
$gamesController = $targetFolder . '/GamesController.php';
if (file_exists($gamesController)) {
    echo "<p style='color:green'>✅ GamesController found at: $gamesController</p>";
    
    // Check the namespace
    $content = file_get_contents($gamesController);
    $targetFolderName = basename($targetFolder);
    $expectedNamespace = "namespace StoriesAPI\\$targetFolderName;";
    
    if (strpos($content, $expectedNamespace) === false) {
        echo "<p style='color:orange'>⚠️ GamesController may have the wrong namespace. Fixing...</p>";
        
        // Create a backup of the original file
        $backupFile = $gamesController . '.bak.' . date('YmdHis');
        if (copy($gamesController, $backupFile)) {
            echo "<p>Created a backup of the original GamesController at: $backupFile</p>";
        }
        
        // Try to find the actual namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $actualNamespace = $matches[1];
            echo "<p>Actual namespace: " . htmlspecialchars($actualNamespace) . "</p>";
            echo "<p>Expected namespace: " . htmlspecialchars("StoriesAPI\\$targetFolderName") . "</p>";
            
            // Update the namespace
            $newContent = str_replace($actualNamespace, "StoriesAPI\\$targetFolderName", $content);
            
            if (file_put_contents($gamesController, $newContent)) {
                echo "<p style='color:green'>✅ Updated namespace in GamesController.</p>";
            } else {
                echo "<p style='color:red'>❌ Failed to update namespace in GamesController.</p>";
            }
        } else {
            echo "<p style='color:red'>❌ Could not find namespace in GamesController.</p>";
        }
    } else {
        echo "<p style='color:green'>✅ GamesController has the correct namespace.</p>";
    }
} else {
    echo "<p style='color:red'>❌ GamesController not found in the target folder!</p>";
    
    // Create a basic GamesController
    echo "<h3>Creating GamesController</h3>";
    
    $targetFolderName = basename($targetFolder);
    $controllerContent = <<<EOD
<?php
/**
 * Games Controller
 * 
 * Handles API requests for games
 */

namespace StoriesAPI\\$targetFolderName;

use StoriesAPI\Core\Controller;
use StoriesAPI\Utils\Response;

class GamesController extends Controller {
    /**
     * Get a list of games
     */
    public function index() {
        // Get pagination parameters
        \$page = isset(\$_GET['page']) ? (int)\$_GET['page'] : 1;
        \$pageSize = isset(\$_GET['pageSize']) ? (int)\$_GET['pageSize'] : 25;
        
        // Ensure valid pagination values
        \$page = max(1, \$page);
        \$pageSize = max(1, min(100, \$pageSize));
        
        // Calculate offset
        \$offset = (\$page - 1) * \$pageSize;
        
        // Get filter parameters
        \$filters = [];
        if (isset(\$_GET['featured'])) {
            \$filters['featured'] = \$_GET['featured'] === 'true' ? 1 : 0;
        }
        if (isset(\$_GET['isPublished'])) {
            \$filters['is_published'] = \$_GET['isPublished'] === 'true' ? 1 : 0;
        }
        
        // Get sort parameter
        \$sortField = isset(\$_GET['sort']) ? \$_GET['sort'] : 'id';
        \$sortDirection = 'ASC';
        
        // Check if sort field has a direction prefix
        if (strpos(\$sortField, '-') === 0) {
            \$sortField = substr(\$sortField, 1);
            \$sortDirection = 'DESC';
        }
        
        // Map frontend field names to database column names
        \$fieldMap = [
            'id' => 'id',
            'title' => 'title',
            'publishedAt' => 'published_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at'
        ];
        
        // Ensure the sort field is valid
        if (!isset(\$fieldMap[\$sortField])) {
            \$sortField = 'id';
        }
        
        // Get the database column name
        \$sortColumn = \$fieldMap[\$sortField];
        
        // Build the sort clause
        \$sortClause = "ORDER BY \$sortColumn \$sortDirection";
        
        try {
            // Build the WHERE clause
            \$whereData = \$this->buildWhereClause(\$filters);
            \$whereClause = \$whereData['clause'];
            \$params = \$whereData['params'];
            
            // Count total records
            \$countQuery = "SELECT COUNT(*) as total FROM games \$whereClause";
            \$stmt = \$this->db->query(\$countQuery, \$params);
            \$total = \$stmt->fetch()['total'];
            
            // Get games with pagination
            \$query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                \$whereClause
                \$sortClause
                LIMIT \$offset, \$pageSize";
            
            \$stmt = \$this->db->query(\$query, \$params);
            \$games = \$stmt->fetchAll();
            
            // Format games with the expected structure
            \$formattedGames = Response::formatData(\$games);
            
            // Send paginated response
            Response::sendPaginated(\$formattedGames, \$page, \$pageSize, \$total);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch games: ' . \$e->getMessage());
        }
    }
    
    /**
     * Get a single game by ID
     * 
     * @param int \$id The game ID
     */
    public function show(\$id) {
        try {
            \$query = "SELECT
                id, title, description, slug, featured, is_published,
                published_at AS publishedAt, created_at AS createdAt, updated_at AS updatedAt
                FROM games
                WHERE id = :id";
            
            \$stmt = \$this->db->query(\$query, ['id' => \$id]);
            \$game = \$stmt->fetch();
            
            if (!\$game) {
                \$this->notFound('Game not found');
                return;
            }
            
            // Format game with the expected structure
            \$formattedGame = Response::formatData(\$game);
            
            // Send success response
            Response::sendSuccess(\$formattedGame);
        } catch (\Exception \$e) {
            \$this->serverError('Failed to fetch game: ' . \$e->getMessage());
        }
    }
    
    /**
     * Build a WHERE clause based on filters
     * 
     * @param array \$filters The filters to apply
     * @return array The WHERE clause and parameters
     */
    private function buildWhereClause(\$filters) {
        \$where = [];
        \$params = [];
        
        foreach (\$filters as \$key => \$value) {
            \$where[] = "\$key = :\$key";
            \$params[\$key] = \$value;
        }
        
        \$whereClause = empty(\$where) ? '' : 'WHERE ' . implode(' AND ', \$where);
        
        return [
            'clause' => \$whereClause,
            'params' => \$params
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

// Check routes file
echo "<h3>Checking Routes Configuration</h3>";

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
            echo "<p style='color:orange'>⚠️ The routes file may be using the wrong namespace for GamesController. Fixing...</p>";
            
            // Create a backup of the original file
            $backupFile = $routesFile . '.bak.' . date('YmdHis');
