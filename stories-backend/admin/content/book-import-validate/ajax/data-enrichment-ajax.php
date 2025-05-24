<?php
/**
 * AJAX handler for data enrichment operations
 */

// Set JSON header first
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, but log them

try {
    // Include necessary files
    require_once '../../../includes/db-connect.php';
    require_once '../functions/data-enrichment-functions.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

// Check if action is provided
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];

try {
    switch ($action) {
        case 'test':
            echo json_encode(['success' => true, 'message' => 'Data enrichment AJAX is working!', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        case 'get_enrichment_data':
            handleGetEnrichmentData();
            break;

        case 'apply_enrichment':
            handleApplyEnrichment();
            break;

        case 'check_goodreads_isbn':
            handleCheckGoodreadsISBN();
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log("Data enrichment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}

/**
 * Handle getting enrichment data for a book
 */
function handleGetEnrichmentData() {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $currentISBN = $_POST['current_isbn'] ?? '';

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Title is required']);
        return;
    }

    // Get enriched data
    $enrichedData = getEnrichedBookData($title, $author, $currentISBN);

    // Filter out fields that are empty or same as current
    $enrichedData['fields'] = filterRelevantFields($enrichedData['fields'], $currentISBN);

    echo json_encode([
        'success' => true,
        'data' => $enrichedData
    ]);
}

/**
 * Handle applying enrichment changes to a book
 */
function handleApplyEnrichment() {
    global $db;

    $bookId = $_POST['book_id'] ?? '';
    $fieldsJson = $_POST['fields'] ?? '';

    if (empty($bookId) || empty($fieldsJson)) {
        echo json_encode(['success' => false, 'message' => 'Book ID and fields are required']);
        return;
    }

    $fields = json_decode($fieldsJson, true);
    if (!$fields) {
        echo json_encode(['success' => false, 'message' => 'Invalid fields data']);
        return;
    }

    // Build update query
    $updateFields = [];
    $params = [];

    foreach ($fields as $fieldName => $fieldData) {
        $value = $fieldData['value'];

        // Handle special field mappings
        switch ($fieldName) {
            case 'publication_date':
                // Ensure date format is correct
                if (!empty($value)) {
                    $date = date('Y-m-d', strtotime($value));
                    if ($date !== '1970-01-01') {
                        $updateFields[] = "publication_date = ?";
                        $params[] = $date;
                    }
                }
                break;

            case 'page_count':
                // Ensure it's a number
                if (is_numeric($value)) {
                    $updateFields[] = "page_count = ?";
                    $params[] = intval($value);
                }
                break;

            default:
                // Standard string fields
                if (!empty($value)) {
                    $updateFields[] = "$fieldName = ?";
                    $params[] = $value;
                }
                break;
        }
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        return;
    }

    // Add validation status update
    $updateFields[] = "validation_status = 'partial'";
    $updateFields[] = "last_validated = NOW()";

    // Add book ID parameter
    $params[] = $bookId;

    // Execute update
    $sql = "UPDATE books SET " . implode(', ', $updateFields) . " WHERE directory_item_id = ?";

    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
        // Log the enrichment
        logEnrichmentActivity($bookId, array_keys($fields));

        echo json_encode([
            'success' => true,
            'message' => 'Book data updated successfully',
            'updated_fields' => array_keys($fields)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
}

/**
 * Handle checking if ISBN exists on Goodreads
 */
function handleCheckGoodreadsISBN() {
    $isbn = $_POST['isbn'] ?? '';

    if (empty($isbn)) {
        echo json_encode(['success' => false, 'message' => 'ISBN is required']);
        return;
    }

    $exists = validateISBNOnGoodreads($isbn);

    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'isbn' => $isbn
    ]);
}

/**
 * Filter out fields that aren't relevant for enrichment
 */
function filterRelevantFields($fields, $currentISBN) {
    $filtered = [];

    foreach ($fields as $fieldName => $fieldData) {
        $value = $fieldData['value'];

        // Skip empty values
        if (empty($value)) {
            continue;
        }

        // Skip if it's the same as current ISBN
        if (($fieldName === 'isbn' || $fieldName === 'isbn13') && $value === $currentISBN) {
            continue;
        }

        // Skip very low confidence fields
        if ($fieldData['confidence'] < 30) {
            continue;
        }

        $filtered[$fieldName] = $fieldData;
    }

    return $filtered;
}

/**
 * Log enrichment activity for audit purposes
 */
function logEnrichmentActivity($bookId, $updatedFields) {
    global $db;

    try {
        $logData = [
            'book_id' => $bookId,
            'action' => 'data_enrichment',
            'fields_updated' => $updatedFields,
            'timestamp' => date('Y-m-d H:i:s'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];

        // You could insert this into a log table if you have one
        error_log("Book enrichment: " . json_encode($logData));

    } catch (Exception $e) {
        error_log("Failed to log enrichment activity: " . $e->getMessage());
    }
}

/**
 * Validate that the book exists and user has permission
 */
function validateBookAccess($bookId) {
    global $db;

    $stmt = $db->prepare("SELECT directory_item_id FROM books WHERE directory_item_id = ?");
    $stmt->execute([$bookId]);

    return $stmt->fetch() !== false;
}
?>
