<?php
/**
 * AJAX endpoint for updating book fields
 * 
 * This file handles AJAX requests to update book fields without reloading the page.
 */

// Include necessary files
require_once __DIR__ . '/../../../../db-connect.php';
require_once __DIR__ . '/functions/book-update-functions.php';
require_once __DIR__ . '/functions/cache-functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if this is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    echo json_encode([
        'success' => false,
        'message' => 'This endpoint only accepts AJAX requests'
    ]);
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'This endpoint only accepts POST requests'
    ]);
    exit;
}

// Get the request data
$action = $_POST['action'] ?? '';
$bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';
$source = $_POST['source'] ?? '';

// Validate the request data
if (empty($action) || empty($bookId) || empty($field)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Connect to the database
    $db = $pdo;

    // Handle different actions
    switch ($action) {
        case 'update_field':
            // Update the field
            $result = updateBookField($bookId, $field, $value, $db);
            
            if ($result) {
                // Get the updated book data
                $stmt = $db->prepare("
                    SELECT di.id, di.title, b.*
                    FROM directory_items di
                    JOIN books b ON di.id = b.directory_item_id
                    WHERE di.id = ?
                ");
                $stmt->execute([$bookId]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Clear the validation cache
                $isbn = $book['isbn'] ?? '';
                $cacheKey = md5("book_validation_{$bookId}_{$isbn}");
                clearValidationCache($cacheKey, $db);
                
                // Add validation history entry
                addValidationHistoryEntry($bookId, "Updated field '{$field}' from source '{$source}'", $db);
                
                $response['success'] = true;
                $response['message'] = "Successfully updated field '{$field}'";
                $response['data'] = [
                    'book' => $book,
                    'field' => $field,
                    'value' => $value,
                    'formatted_value' => formatFieldValue($field, $value)
                ];
            } else {
                $response['message'] = "Failed to update field '{$field}'";
            }
            break;
            
        case 'apply_all_source':
            // Apply all fields from a source
            $result = applyAllFieldsFromSource($bookId, $source, $db);
            
            if ($result) {
                // Get the updated book data
                $stmt = $db->prepare("
                    SELECT di.id, di.title, b.*
                    FROM directory_items di
                    JOIN books b ON di.id = b.directory_item_id
                    WHERE di.id = ?
                ");
                $stmt->execute([$bookId]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Clear the validation cache
                $isbn = $book['isbn'] ?? '';
                $cacheKey = md5("book_validation_{$bookId}_{$isbn}");
                clearValidationCache($cacheKey, $db);
                
                // Add validation history entry
                addValidationHistoryEntry($bookId, "Applied all fields from source '{$source}'", $db);
                
                $response['success'] = true;
                $response['message'] = "Successfully applied all fields from source '{$source}'";
                $response['data'] = [
                    'book' => $book,
                    'source' => $source
                ];
            } else {
                $response['message'] = "Failed to apply fields from source '{$source}'";
            }
            break;
            
        default:
            $response['message'] = "Unknown action '{$action}'";
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return the response
echo json_encode($response);

/**
 * Format field value for display
 * 
 * @param string $field The field name
 * @param mixed $value The field value
 * @return string The formatted value
 */
function formatFieldValue($field, $value) {
    if (empty($value)) {
        return '<span class="text-muted">Not available</span>';
    }
    
    switch ($field) {
        case 'publication_date':
            return date('Y-m-d', strtotime($value));
        
        case 'cover_url':
            return '<img src="' . htmlspecialchars($value) . '" alt="Cover" class="img-thumbnail" style="max-height: 100px;">';
        
        case 'preview_link':
            return '<a href="' . htmlspecialchars($value) . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt"></i> View</a>';
        
        case 'rating':
            return number_format((float)$value, 2) . '/5';
        
        case 'rating_count':
        case 'review_count':
            return number_format((int)$value);
        
        case 'awards':
        case 'characters':
        case 'settings':
            // Handle array or JSON
            if (is_array($value)) {
                return implode(', ', $value);
            } elseif (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
                try {
                    $decoded = json_decode($value, true);
                    if (is_array($decoded)) {
                        return implode(', ', $decoded);
                    }
                } catch (Exception $e) {
                    // Fall through to default
                }
            }
            return htmlspecialchars($value);
            
        default:
            return htmlspecialchars($value);
    }
}
