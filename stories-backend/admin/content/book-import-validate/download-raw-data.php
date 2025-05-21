<?php
/**
 * Download Raw Book Data
 *
 * This script downloads the raw data for a book in JSON format.
 * It includes all data from all sources (database, Goodreads, Open Library, etc.)
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include auth check
require_once '../../includes/auth-check.php';

// Include database connection
require_once '../../includes/db-connect.php';

// Include validation functions
require_once __DIR__ . '/functions/validation-functions.php';

// Check if book ID is provided (from GET or POST)
$bookId = null;
if (isset($_GET['book_id']) && is_numeric($_GET['book_id'])) {
    $bookId = (int)$_GET['book_id'];
} elseif (isset($_POST['book_id']) && is_numeric($_POST['book_id'])) {
    $bookId = (int)$_POST['book_id'];
}

if ($bookId === null) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'status' => 'error',
        'message' => 'Book ID is required'
    ]);
    exit;
}

// Get book data from database
try {
    // Get book details
    $stmt = $db->prepare("
        SELECT di.id, di.title, di.slug, di.review_count, di.average_rating, b.*
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE di.id = :book_id
    ");
    $stmt->execute([':book_id' => $bookId]);
    $bookData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bookData) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode([
            'status' => 'error',
            'message' => 'Book not found'
        ]);
        exit;
    }

    // Get ISBN
    $isbn = $bookData['isbn'] ?? ($bookData['isbn13'] ?? '');
    $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

    // Get validation data - but handle errors
    try {
        $validationData = validateBookData($bookId, $isbn, $bookData['title'], $db, false);
    } catch (Exception $e) {
        error_log("Error getting validation data: " . $e->getMessage());
        $validationData = [
            'status' => 'error',
            'message' => 'Error getting validation data: ' . $e->getMessage()
        ];
    }

    // Get cache data
    $cacheKey = md5("book_validation_{$bookId}_{$cleanIsbn}");
    $stmt = $db->prepare("
        SELECT cache_data
        FROM validation_cache
        WHERE cache_key = ?
    ");
    $stmt->execute([$cacheKey]);
    $cache = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cache) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode([
            'status' => 'error',
            'message' => 'No validation data found'
        ]);
        exit;
    }

    // Prepare the processed data
    $processedData = [
        'book' => $bookData,
        'validation' => $validationData,
        'download_time' => date('Y-m-d H:i:s'),
        'download_info' => [
            'book_id' => $bookId,
            'isbn' => $isbn,
            'title' => $bookData['title'] ?? 'Unknown'
        ]
    ];

    // Set headers for file download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="book_' . $bookId . '_data.json"');

    // Output both processed and raw data with a separator
    echo "// PROCESSED DATA\n";
    echo json_encode($processedData, JSON_PRETTY_PRINT);
    echo "\n\n// RAW DATA\n";
    // Decode and re-encode the raw data to pretty print it
    $rawData = json_decode($cache['cache_data'], true);
    echo json_encode($rawData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Error in download-raw-data.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving book data: ' . $e->getMessage()
    ]);
}
