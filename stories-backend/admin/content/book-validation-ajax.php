<?php
/**
 * AJAX endpoint for book validation
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header first
header('Content-Type: application/json');

// Add error logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    // Include auth check
    require_once '../includes/auth-check.php';

    // Include database connection
    require_once '../includes/db-connect.php';

    // Include validation functions
    require_once 'book-import-validate/functions/open-library-validation-functions.php';
    require_once 'book-import-validate/functions/google-books-validation-functions.php';

    // Get action
    $action = $_POST['action'] ?? '';

    // Debug: Log the request
    error_log("AJAX Request - Action: $action, POST data: " . json_encode($_POST));
    switch ($action) {
        case 'validate_isbn':
            $bookId = intval($_POST['book_id'] ?? 0);

            if (!$bookId) {
                echo json_encode(['status' => 'error', 'message' => 'No book ID provided']);
                exit;
            }

            // Get book details
            $stmt = $db->prepare("
                SELECT di.id, di.title, b.isbn, b.isbn13, b.author
                FROM directory_items di
                JOIN books b ON di.id = b.directory_item_id
                WHERE di.id = ?
            ");
            $stmt->execute([$bookId]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$book) {
                echo json_encode(['status' => 'error', 'message' => 'Book not found']);
                exit;
            }

            $isbn = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');

            // Debug: Log book details
            error_log("Book details - ID: $bookId, Title: {$book['title']}, ISBN: $isbn");

            // Validate ISBN against external APIs
            $validation = validateISBNAgainstAPIs($isbn, $book['title'], $book['author']);

            // Debug: Log validation result
            error_log("Validation result: " . json_encode($validation));

            echo json_encode([
                'status' => 'success',
                'book_id' => $bookId,
                'validation' => $validation
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Validate ISBN against external APIs
 */
function validateISBNAgainstAPIs($isbn, $title, $author) {
    error_log("validateISBNAgainstAPIs called with ISBN: '$isbn', Title: '$title', Author: '$author'");

    if (empty($isbn)) {
        error_log("No ISBN provided");
        return ['status' => 'missing', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'No ISBN'];
    }

    // Clean ISBN
    $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
    error_log("Cleaned ISBN: '$cleanIsbn', Length: " . strlen($cleanIsbn));

    // Basic format check first
    if (strlen($cleanIsbn) != 10 && strlen($cleanIsbn) != 13) {
        error_log("Invalid ISBN format");
        return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid format'];
    }

    // TEMPORARILY DISABLED: OpenLibrary check (403 errors causing timeouts)
    // if (validateIsbnWithOpenLibrary($cleanIsbn)) {
    //     return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (OpenLibrary)'];
    // }

    // Google Books check (primary validation)
    error_log("Calling validateIsbnWithGoogleBooks for ISBN: $cleanIsbn");
    $googleBooksResult = validateIsbnWithGoogleBooks($cleanIsbn);
    error_log("Google Books result: " . ($googleBooksResult ? 'true' : 'false'));

    if ($googleBooksResult) {
        error_log("ISBN validated successfully with Google Books");
        return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (Google Books)'];
    }

    // If ISBN validation fails, check if we can find the book by title/author using Google Books
    if (!empty($title)) {
        error_log("Searching by title/author: '$title' / '$author'");
        // Try to find suggestions by title/author using Google Books only
        $suggestions = searchBooksByTitleAuthor($title, $author, 1);
        error_log("Title/author search returned " . count($suggestions) . " suggestions");
        if (!empty($suggestions)) {
            return ['status' => 'mismatch', 'class' => 'warning', 'icon' => 'exclamation-triangle', 'message' => 'ISBN invalid, but book found by title'];
        }
    }

    error_log("ISBN not found anywhere");
    return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'ISBN not found'];
}
?>
