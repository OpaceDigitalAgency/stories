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
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

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
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'discover_all':
            $url = filter_var($_POST['url'], FILTER_VALIDATE_URL);
            if (!$url) {
                throw new Exception('Invalid URL provided');
            }
            
            $discoveryEngine = new BookDiscoveryEngine($db);
            $books = $discoveryEngine->discoverFromURL($url);
            
            // Filter by age if specified
            $ageFilter = $_POST['age_filter'] ?? '';
            if ($ageFilter && !empty($books)) {
                $books = array_filter($books, function($book) use ($ageFilter) {
                    $bookAge = strtolower($book['age_range'] ?? '');
                    $filterAge = strtolower($ageFilter);
                    return strpos($bookAge, $filterAge) !== false || 
                           strpos($bookAge, str_replace('-', ' to ', $filterAge)) !== false;
                });
            }
            
            echo json_encode([
                'success' => true,
                'books' => array_values($books),
                'total' => count($books)
            ]);
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
            
            // Get enriched data from APIs
            $enrichedData = getEnrichedBookData(
                $bookData['title'] ?? '',
                $bookData['author'] ?? '',
                $bookData['isbn'] ?? $bookData['isbn13'] ?? ''
            );
            
            // Merge enriched data with original book data
            if (!empty($enrichedData['fields'])) {
                foreach ($enrichedData['fields'] as $field => $data) {
                    if (!empty($data['new_data']['value']) && empty($bookData[$field])) {
                        $bookData[$field] = $data['new_data']['value'];
                    }
                }
            }
            
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