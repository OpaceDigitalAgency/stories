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

/**
 * Clean author name by removing "illustrated by" text
 */
function cleanAuthorName($author) {
    if (empty($author)) return '';
    
    // Remove "illustrated by" variations
    $author = preg_replace('/,\s*illustrated\s+by\s+[^,]+/i', '', $author);
    $author = preg_replace('/,\s*illus\.\s+[^,]+/i', '', $author);
    $author = preg_replace('/,\s*illustrations\s+by\s+[^,]+/i', '', $author);
    
    return trim($author);
}

/**
 * Format age range to DB format (e.g., "4-5 years")
 */
function formatAgeRange($ageRange) {
    if (empty($ageRange)) return '';
    
    // Convert "4 to 5 years" to "4-5 years"
    $ageRange = preg_replace('/(\d+)\s+to\s+(\d+)\s+years?/i', '$1-$2 years', $ageRange);
    
    // Convert "4-5 year olds" to "4-5 years"
    $ageRange = preg_replace('/(\d+-\d+)\s+year\s+olds?/i', '$1 years', $ageRange);
    
    // Convert "4+ years" to "4-99 years"
    $ageRange = preg_replace('/(\d+)\+\s+years?/i', '$1-99 years', $ageRange);
    
    return trim($ageRange);
}

/**
 * Format publication date to DB format (DD/MM/YYYY)
 */
function formatPublicationDate($date) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    if ($timestamp === false) return '';
    
    return date('d/m/Y', $timestamp);
}

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
            
            // Format books for enhanced table display
            $formattedBooks = [];
            foreach ($books as $index => $book) {
                $formattedBooks[] = [
                    'id' => $index,
                    'title' => htmlspecialchars($book['title'] ?? ''),
                    'author' => htmlspecialchars(cleanAuthorName($book['author'] ?? '')),
                    'isbn10' => htmlspecialchars($book['isbn'] ?? ''),
                    'isbn13' => htmlspecialchars($book['isbn13'] ?? ''),
                    'age' => htmlspecialchars(formatAgeRange($book['age_range'] ?? '')),
                    'date' => htmlspecialchars($book['formatted_date'] ?? ($book['year'] ? "01/01/{$book['year']}" : '')),
                    'publisher' => htmlspecialchars($book['publisher'] ?? ''),
                    'language' => htmlspecialchars($book['language'] ?? ''),
                    'page_count' => htmlspecialchars($book['page_count'] ?? ''),
                    'reading_level' => htmlspecialchars($book['reading_level'] ?? ''),
                    'status' => '<span class="text-info">Ready</span>'
                ];
            }
            
            $response = [
                'success' => true,
                'books' => array_values($books),
                'formatted_books' => $formattedBooks,
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
                        
                        // Also store confidence for publisher matching
                        if ($field === 'publisher' && isset($data['confidence'])) {
                            $bookData['publisher_confidence'] = $data['confidence'];
                        }
                        
                        error_log("Added enriched field $field: " . $data['value']);
                    }
                }
            }
            
            // Clean author name - remove "illustrated by" text
            if (!empty($bookData['author'])) {
                $bookData['author'] = cleanAuthorName($bookData['author']);
            }
            
            // Format age range to DB format
            if (!empty($bookData['age_range'])) {
                $bookData['age_range'] = formatAgeRange($bookData['age_range']);
            }
            
            // Format publication date
            if (!empty($bookData['publication_date'])) {
                $bookData['formatted_date'] = formatPublicationDate($bookData['publication_date']);
            } elseif (!empty($bookData['year'])) {
                $bookData['formatted_date'] = "01/01/" . $bookData['year'];
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
            
        case 'render_table':
            $booksJson = $_POST['books'] ?? '';
            if (empty($booksJson)) {
                throw new Exception('No books data provided');
            }
            
            $books = json_decode($booksJson, true);
            if (!$books) {
                throw new Exception('Invalid books data format');
            }
            
            // Format books for enhanced table
            $tableData = [];
            foreach ($books as $index => $book) {
                $status = $book['processing_error'] ?? false ? 'Error' :
                         ($book['imported'] ?? false ? 'Imported' : 'Ready');
                $statusClass = $status === 'Error' ? 'text-danger' :
                              ($status === 'Imported' ? 'text-success' : 'text-info');
                
                // Calculate publisher match percentage if available
                $publisherDisplay = $book['publisher'] ?? '';
                if (!empty($book['publisher_confidence'])) {
                    $publisherDisplay .= ' (' . round($book['publisher_confidence']) . '% match)';
                }
                
                $tableData[] = [
                    'id' => $index,
                    'title' => $book['title'] ?? '',
                    'author' => cleanAuthorName($book['author'] ?? ''),
                    'isbn10' => $book['isbn'] ?? '',
                    'isbn13' => $book['isbn13'] ?? '',
                    'age' => formatAgeRange($book['age_range'] ?? ''),
                    'date' => $book['formatted_date'] ?? ($book['year'] ? "01/01/{$book['year']}" : ''),
                    'publisher' => $publisherDisplay,
                    'language' => $book['language'] ?? '',
                    'page_count' => $book['page_count'] ?? '',
                    'reading_level' => $book['reading_level'] ?? '',
                    'status' => '<span class="' . $statusClass . '">' . $status . '</span>'
                ];
            }
            
            // Define columns for enhanced table
            $columns = [
                'title' => 'Title',
                'author' => 'Author',
                'isbn10' => 'ISBN-10',
                'isbn13' => 'ISBN-13',
                'age' => 'Age Range',
                'date' => 'Publication Date',
                'publisher' => 'Publisher',
                'language' => 'Language',
                'page_count' => 'Pages',
                'reading_level' => 'Reading Level',
                'status' => 'Status'
            ];
            
            // Start output buffering to capture the table HTML
            ob_start();
            
            // Render enhanced table
            renderEnhancedTable(
                $tableData,
                $columns,
                'book',
                'discovery-books-table',
                [
                    'showCheckboxes' => true,
                    'showActions' => false,
                    'bulkActions' => ['import', 'export', 'delete'],
                    'htmlFields' => ['status'],
                    'showPagination' => true,
                    'itemsPerPage' => 25,
                    'thumbnailField' => false // No thumbnails for book discovery
                ]
            );
            
            $tableHtml = ob_get_clean();
            
            echo json_encode([
                'success' => true,
                'table_html' => $tableHtml
            ]);
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