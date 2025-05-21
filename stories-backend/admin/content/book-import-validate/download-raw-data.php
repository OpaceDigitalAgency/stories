<?php
/**
 * Download Raw Book Data
 *
 * This script downloads the raw data for a book in JSON format.
 * It includes all data from all sources (database, Goodreads, Open Library, etc.)
 */

// Include database connection
require_once __DIR__ . '/../../../../db-connect.php';

// Include validation functions
require_once __DIR__ . '/functions/validation-functions.php';

// Check if book ID is provided
if (!isset($_GET['book_id']) || !is_numeric($_GET['book_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'status' => 'error',
        'message' => 'Book ID is required'
    ]);
    exit;
}

$bookId = (int)$_GET['book_id'];

// Get book data from database
try {
    $stmt = $db->prepare("
        SELECT * FROM books
        WHERE id = :book_id
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
    $isbn = $bookData['isbn'] ?? '';

    // Get validation data
    $validationData = validateBookData($bookId, $isbn, $bookData['title'], $db, false);

    // Add debug information
    error_log("Downloading raw data for book ID: $bookId, ISBN: $isbn");
    error_log("Book data: " . json_encode($bookData, JSON_PRETTY_PRINT));
    error_log("Validation data status: " . ($validationData['status'] ?? 'unknown'));

    // Prepare the full data object
    $fullData = [
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

    // Output the JSON data
    echo json_encode($fullData, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving book data: ' . $e->getMessage()
    ]);
}
