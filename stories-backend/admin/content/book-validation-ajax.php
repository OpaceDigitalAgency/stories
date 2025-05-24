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

            // Get the best available ISBN (prefer isbn13, fallback to isbn)
            $isbn = '';
            if (!empty($book['isbn13'])) {
                $isbn = $book['isbn13'];
            } elseif (!empty($book['isbn'])) {
                $isbn = $book['isbn'];
            }

            // If no ISBN found, return missing status
            if (empty($isbn)) {
                echo json_encode([
                    'status' => 'success',
                    'book_id' => $bookId,
                    'validation' => ['status' => 'missing', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'No ISBN']
                ]);
                exit;
            }

            // Validate ISBN against external APIs
            $validation = validateISBNAgainstAPIs($isbn, $book['title'], $book['author']);

            echo json_encode([
                'status' => 'success',
                'book_id' => $bookId,
                'validation' => $validation
            ]);
            break;

        case 'fix_isbn':
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

            // Try to find correct ISBN by searching with title and author
            $suggestions = searchBooksByTitleAuthor($book['title'], $book['author'], 3);

            if (empty($suggestions)) {
                echo json_encode(['status' => 'error', 'message' => 'No alternative ISBNs found for this book']);
                exit;
            }

            // Return suggestions for user to choose from
            echo json_encode([
                'status' => 'success',
                'book_id' => $bookId,
                'suggestions' => $suggestions,
                'current_title' => $book['title'],
                'current_author' => $book['author']
            ]);
            break;

        case 'update_isbn':
            $bookId = intval($_POST['book_id'] ?? 0);
            $newISBN = trim($_POST['isbn'] ?? '');

            if (!$bookId) {
                echo json_encode(['status' => 'error', 'message' => 'No book ID provided']);
                exit;
            }

            if (empty($newISBN)) {
                echo json_encode(['status' => 'error', 'message' => 'No ISBN provided']);
                exit;
            }

            // Clean the ISBN
            $cleanISBN = preg_replace('/[^0-9X]/i', '', $newISBN);

            // Determine if it's ISBN-10 or ISBN-13
            $isISBN13 = (strlen($cleanISBN) == 13);
            $isISBN10 = (strlen($cleanISBN) == 10);

            if (!$isISBN13 && !$isISBN10) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid ISBN format']);
                exit;
            }

            // Update the database
            try {
                if ($isISBN13) {
                    // Update ISBN-13 field, clear ISBN-10
                    $stmt = $db->prepare("UPDATE books SET isbn13 = ?, isbn = '' WHERE directory_item_id = ?");
                    $stmt->execute([$cleanISBN, $bookId]);
                } else {
                    // Update ISBN-10 field, clear ISBN-13
                    $stmt = $db->prepare("UPDATE books SET isbn = ?, isbn13 = '' WHERE directory_item_id = ?");
                    $stmt->execute([$cleanISBN, $bookId]);
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => 'ISBN updated successfully',
                    'book_id' => $bookId,
                    'new_isbn' => $cleanISBN
                ]);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            }
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

    // Clean ISBN (remove hyphens, spaces, etc.)
    $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

    // Basic format check
    if (strlen($cleanIsbn) != 10 && strlen($cleanIsbn) != 13) {
        return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid format (' . strlen($cleanIsbn) . ' digits)'];
    }

    // ISBN-13 checksum validation for 13-digit ISBNs
    if (strlen($cleanIsbn) == 13) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = intval($cleanIsbn[$i]);
            $sum += ($i % 2 == 0) ? $digit : $digit * 3;
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        $actualCheckDigit = intval($cleanIsbn[12]);

        if ($checkDigit != $actualCheckDigit) {
            return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid ISBN-13 checksum'];
        }
    }

    // ISBN-10 checksum validation for 10-digit ISBNs
    if (strlen($cleanIsbn) == 10) {
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $digit = intval($cleanIsbn[$i]);
            $sum += $digit * (10 - $i);
        }
        $checkDigit = (11 - ($sum % 11)) % 11;
        $actualCheckDigit = ($cleanIsbn[9] == 'X') ? 10 : intval($cleanIsbn[9]);

        if ($checkDigit != $actualCheckDigit) {
            return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid ISBN-10 checksum'];
        }
    }

    // If checksum is valid, verify against external APIs
    // OpenLibrary check (primary validation)
    if (validateIsbnWithOpenLibrary($cleanIsbn)) {
        return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (OpenLibrary)'];
    }

    // Google Books check (secondary validation)
    if (validateIsbnWithGoogleBooks($cleanIsbn)) {
        return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (Google Books)'];
    }

    // If ISBN validation fails but checksum is valid, check if we can find the book by title/author
    if (!empty($title)) {
        // Try OpenLibrary search first
        $openLibrarySuggestions = searchOpenLibraryByTitleAuthor($title, $author, 1);
        if (!empty($openLibrarySuggestions)) {
            return ['status' => 'mismatch', 'class' => 'warning', 'icon' => 'exclamation-triangle', 'message' => 'ISBN not found, but book exists by title'];
        }

        // Try Google Books search as fallback
        $googleBooksSuggestions = searchBooksByTitleAuthor($title, $author, 1);
        if (!empty($googleBooksSuggestions)) {
            return ['status' => 'mismatch', 'class' => 'warning', 'icon' => 'exclamation-triangle', 'message' => 'ISBN not found, but book exists by title'];
        }
    }

    // Valid checksum but ISBN doesn't exist in any database
    return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'ISBN not found in any database'];
}
?>
