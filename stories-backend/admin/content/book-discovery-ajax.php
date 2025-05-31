<?php
/**
 * AJAX Book Discovery Endpoint
 * 
 * Handles individual book discovery and enrichment requests
 */

// Disable output buffering for real-time updates
if (ob_get_level()) {
    ob_end_clean();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
require_once '../../admin/includes/auth-check.php';

// Include database connection
require_once '../../admin/includes/db-connect.php';

// Include discovery engine and enrichment functions
require_once 'book-discovery/BookDiscoveryEngine.php';
require_once 'book-import-validate/functions/data-enrichment-functions.php';

// Set JSON response header and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    error_log("AJAX Discovery: Starting request processing");
    error_log("POST data: " . print_r($_POST, true));
    
    $action = $_POST['action'] ?? '';
    error_log("Action: " . $action);
    
    switch ($action) {
        case 'discover_all':
            error_log("Processing discover_all action");
            $url = filter_var($_POST['url'], FILTER_VALIDATE_URL);
            if (!$url) {
                error_log("Invalid URL provided: " . ($_POST['url'] ?? 'null'));
                throw new Exception('Invalid URL provided');
            }
            
            error_log("Valid URL: " . $url);
            error_log("Creating BookDiscoveryEngine");
            $discoveryEngine = new BookDiscoveryEngine($db);
            error_log("Calling discoverFromURL");
            
            // Add progress updates during discovery
            echo json_encode(['progress' => 5, 'message' => 'Connecting to website...']) . "\n";
            flush();
            
            // Add a small delay to show the progress update
            usleep(500000); // 0.5 seconds
            
            echo json_encode(['progress' => 15, 'message' => 'Downloading page content...']) . "\n";
            flush();
            
            $books = $discoveryEngine->discoverFromURL($url);
            error_log("Discovery completed. Found " . count($books) . " books");
            
            echo json_encode(['progress' => 25, 'message' => 'Parsing book data...']) . "\n";
            flush();
            
            // Add another small delay
            usleep(300000); // 0.3 seconds
            
            echo json_encode(['progress' => 30, 'message' => 'Processing discovered books...']) . "\n";
            flush();
            
            // Filter by age if specified
            $ageFilter = $_POST['age_filter'] ?? '';
            if ($ageFilter && !empty($books)) {
                error_log("Applying age filter: " . $ageFilter);
                $books = array_filter($books, function($book) use ($ageFilter) {
                    $bookAge = strtolower($book['age_range'] ?? '');
                    $filterAge = strtolower($ageFilter);
                    return strpos($bookAge, $filterAge) !== false ||
                           strpos($bookAge, str_replace('-', ' to ', $filterAge)) !== false;
                });
                error_log("After filtering: " . count($books) . " books");
            }
            
            $response = [
                'success' => true,
                'books' => array_values($books),
                'total' => count($books)
            ];
            error_log("Sending response: " . json_encode($response));
            echo json_encode($response);
            break;
            
        case 'enrich_book':
            $bookJson = $_POST['book'] ?? '';
            if (empty($bookJson)) {
                throw new Exception('No book data provided');
            }
            
            // Decode JSON book data
            $bookData = json_decode($bookJson, true);
            if (!$bookData) {
                throw new Exception('Invalid book data format');
            }
            
            error_log("Enriching book: " . ($bookData['title'] ?? 'Unknown'));
            
            // Get enriched data from APIs
            $enrichedData = getEnrichedBookData(
                $bookData['title'] ?? '',
                $bookData['author'] ?? '',
                $bookData['isbn'] ?? $bookData['isbn13'] ?? ''
            );
            
            error_log("Enriched data received: " . print_r($enrichedData, true));
            
            // Merge enriched data with original book data
            if (!empty($enrichedData['fields'])) {
                foreach ($enrichedData['fields'] as $field => $data) {
                    if (!empty($data['value'])) {
                        // Always add enriched data, don't check if field is empty
                        $bookData[$field] = $data['value'];
                        error_log("Added enriched field $field: " . $data['value']);
                    }
                }
            }
            
            error_log("Final book data: " . print_r($bookData, true));
            
            echo json_encode([
                'success' => true,
                'book' => $bookData
            ]);
            break;
            
        case 'import_book':
            $bookJson = $_POST['book'] ?? '';
            if (empty($bookJson)) {
                throw new Exception('No book data provided');
            }
            
            // Decode JSON book data
            $bookData = json_decode($bookJson, true);
            if (!$bookData) {
                throw new Exception('Invalid book data format');
            }
            
            // Import book function
            $result = importBook($db, $bookData);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("AJAX Discovery Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Import book function for discovered books
function importBook($db, $book) {
    try {
        // Check if book already exists
        $checkStmt = $db->prepare("
            SELECT id FROM directory_items 
            WHERE title = ? AND type = 'book'
        ");
        $checkStmt->execute([$book['title']]);
        
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'Book already exists'];
        }
        
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $book['title'])));
        
        // Insert directory item
        $dirStmt = $db->prepare("
            INSERT INTO directory_items (title, slug, type, description, created_at, updated_at)
            VALUES (?, ?, 'book', ?, NOW(), NOW())
        ");
        
        $description = $book['description'] ?? '';
        $dirStmt->execute([$book['title'], $slug, $description]);
        $directoryItemId = $db->lastInsertId();
        
        // Insert book details
        $bookStmt = $db->prepare("
            INSERT INTO books (
                directory_item_id, isbn, isbn13, author, publisher, 
                page_count, series, price_range, age_range, tags
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $tags = is_array($book['tags']) ? implode(',', $book['tags']) : ($book['tags'] ?? '');
        
        $bookStmt->execute([
            $directoryItemId,
            $book['isbn'] ?? '',
            $book['isbn13'] ?? '',
            $book['author'] ?? '',
            $book['publisher'] ?? '',
            $book['page_count'] ?? null,
            $book['series'] ?? '',
            $book['price_range'] ?? '',
            $book['age_range'] ?? '',
            $tags
        ]);
        
        return ['success' => true, 'id' => $directoryItemId];
        
    } catch (Exception $e) {
        error_log("Import error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>