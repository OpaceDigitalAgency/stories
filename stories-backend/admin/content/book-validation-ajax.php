<?php
/**
 * AJAX endpoint for book validation
 */

// Set JSON header first
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Include auth check
    require_once '../includes/auth-check.php';

    // Include database connection
    require_once '../includes/db-connect.php';

    // Include validation functions
    require_once 'book-import-validate/functions/open-library-validation-functions.php';
    require_once 'book-import-validate/functions/google-books-validation-functions.php';

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'test':
            echo json_encode(['status' => 'success', 'message' => 'AJAX working', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

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

            // Validate ISBN against external APIs
            $validation = validateISBNAgainstAPIs($isbn, $book['title'], $book['author']);

            echo json_encode([
                'status' => 'success',
                'book_id' => $bookId,
                'validation' => $validation
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Validate ISBN against external APIs
 */
function validateISBNAgainstAPIs($isbn, $title, $author) {
    if (empty($isbn)) {
        return ['status' => 'missing', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'No ISBN'];
    }

    // Clean ISBN
    $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

    // Basic format check first
    if (strlen($cleanIsbn) != 10 && strlen($cleanIsbn) != 13) {
        return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid format'];
    }

    // OpenLibrary check (primary validation - faster response)
    if (validateIsbnWithOpenLibrary($cleanIsbn)) {
        return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (OpenLibrary)'];
    }

    // Google Books check (secondary validation)
    if (validateIsbnWithGoogleBooks($cleanIsbn)) {
        return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (Google Books)'];
    }

    // If ISBN validation fails, check if we can find the book by title/author
    if (!empty($title)) {
        // Try OpenLibrary search first (faster)
        $openLibrarySuggestions = searchOpenLibraryByTitleAuthor($title, $author, 1);
        if (!empty($openLibrarySuggestions)) {
            return ['status' => 'mismatch', 'class' => 'warning', 'icon' => 'exclamation-triangle', 'message' => 'ISBN invalid, but book found by title (OpenLibrary)'];
        }

        // Try Google Books search as fallback
        $googleBooksSuggestions = searchBooksByTitleAuthor($title, $author, 1);
        if (!empty($googleBooksSuggestions)) {
            return ['status' => 'mismatch', 'class' => 'warning', 'icon' => 'exclamation-triangle', 'message' => 'ISBN invalid, but book found by title (Google Books)'];
        }
    }

    return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'ISBN not found'];
}
?>
