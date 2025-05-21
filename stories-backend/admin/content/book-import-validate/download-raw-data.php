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
require_once '../../includes/db_connect.php';

// Include validation functions
$validationFunctionsPath = __DIR__ . '/functions/validation-functions.php';
if (!file_exists($validationFunctionsPath)) {
    die("Error: Validation functions file not found at $validationFunctionsPath");
}
require_once $validationFunctionsPath;

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

    // Log what we're about to do
    error_log("About to get validation data for book ID: $bookId, ISBN: $isbn");

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

    // Add debug information
    error_log("Downloading raw data for book ID: $bookId, ISBN: $isbn");

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
    error_log("Error in download-raw-data.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'status' => 'error',
        'message' => 'Error retrieving book data: ' . $e->getMessage()
    ]);
}
