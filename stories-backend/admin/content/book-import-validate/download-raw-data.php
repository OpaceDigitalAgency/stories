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

    // Create a temporary directory
    $tempDir = sys_get_temp_dir() . '/book_data_' . uniqid();
    mkdir($tempDir);

    // Create the processed data file
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
    file_put_contents($tempDir . '/book_' . $bookId . '_processed_data.json', json_encode($processedData, JSON_PRETTY_PRINT));

    // Create the raw data file
    file_put_contents($tempDir . '/book_' . $bookId . '_raw_data.json', $cache['cache_data']);

    // Create ZIP archive
    $zipFile = $tempDir . '/book_' . $bookId . '_data.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($tempDir . '/book_' . $bookId . '_processed_data.json', 'book_' . $bookId . '_processed_data.json');
        $zip->addFile($tempDir . '/book_' . $bookId . '_raw_data.json', 'book_' . $bookId . '_raw_data.json');
        $zip->close();

        // Set headers for ZIP download
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="book_' . $bookId . '_data.zip"');
        header('Content-Length: ' . filesize($zipFile));

        // Output the ZIP file
        readfile($zipFile);

        // Clean up
        unlink($tempDir . '/book_' . $bookId . '_processed_data.json');
        unlink($tempDir . '/book_' . $bookId . '_raw_data.json');
        unlink($zipFile);
        rmdir($tempDir);
    } else {
        throw new Exception("Could not create ZIP file");
    }

} catch (Exception $e) {
    error_log("Error in download-raw-data.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving book data: ' . $e->getMessage()
    ]);
}
