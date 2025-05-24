<?php
/**
 * Search Functions
 *
 * This file contains functions for searching books and handling suggestions.
 */

// Include source-specific functions
require_once __DIR__ . '/google-books-validation-functions.php';
require_once __DIR__ . '/open-library-validation-functions.php';
require_once __DIR__ . '/goodreads-validation-functions.php';

/**
 * Search for a book by title
 *
 * @param int $bookId The book ID
 * @param string $title The title to search for
 * @param PDO $db Database connection
 * @return array Search results
 */
function searchBookByTitle($bookId, $title, $db) {
    $results = [
        'status' => 'error',
        'message' => '',
        'suggestions' => []
    ];

    try {
        // Get book details
        $stmt = $db->prepare("
            SELECT di.id, di.title, b.author
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE di.id = ?
        ");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $results['message'] = 'Book not found';
            return $results;
        }

        // Use provided title or current title
        $searchTitle = !empty($title) ? $title : $book['title'];
        $author = $book['author'] ?? '';

        // Search in Google Books
        $googleSuggestions = searchBooksByTitleAuthor($searchTitle, $author, 3);

        // Search in Open Library
        $openLibrarySuggestions = searchOpenLibraryByTitleAuthor($searchTitle, $author, 3);

        // Combine suggestions
        $allSuggestions = array_merge($googleSuggestions, $openLibrarySuggestions);

        // Remove duplicates based on ISBN
        $uniqueSuggestions = [];
        $seenIsbns = [];

        foreach ($allSuggestions as $suggestion) {
            $isbn = !empty($suggestion['isbn13']) ? $suggestion['isbn13'] : $suggestion['isbn'];

            if (!empty($isbn) && !isset($seenIsbns[$isbn])) {
                $seenIsbns[$isbn] = true;
                $uniqueSuggestions[] = $suggestion;
            } else if (empty($isbn)) {
                // If no ISBN, just add it
                $uniqueSuggestions[] = $suggestion;
            }
        }

        // Sort suggestions by relevance (exact title match first)
        usort($uniqueSuggestions, function($a, $b) use ($searchTitle) {
            // Exact title match gets highest priority
            $aExactMatch = strtolower($a['title']) === strtolower($searchTitle);
            $bExactMatch = strtolower($b['title']) === strtolower($searchTitle);

            if ($aExactMatch && !$bExactMatch) {
                return -1;
            } else if (!$aExactMatch && $bExactMatch) {
                return 1;
            }

            // Then check if title contains search term
            $aContains = stripos($a['title'], $searchTitle) !== false;
            $bContains = stripos($b['title'], $searchTitle) !== false;

            if ($aContains && !$bContains) {
                return -1;
            } else if (!$aContains && $bContains) {
                return 1;
            }

            // Finally, sort by whether they have ISBNs
            $aHasIsbn = !empty($a['isbn']) || !empty($a['isbn13']);
            $bHasIsbn = !empty($b['isbn']) || !empty($b['isbn13']);

            if ($aHasIsbn && !$bHasIsbn) {
                return -1;
            } else if (!$aHasIsbn && $bHasIsbn) {
                return 1;
            }

            return 0;
        });

        // Limit to top 5 suggestions
        $uniqueSuggestions = array_slice($uniqueSuggestions, 0, 5);

        $results['status'] = 'success';
        $results['message'] = count($uniqueSuggestions) > 0
            ? 'Found ' . count($uniqueSuggestions) . ' suggestions'
            : 'No suggestions found';
        $results['suggestions'] = $uniqueSuggestions;

        return $results;
    } catch (Exception $e) {
        error_log("Error searching book by title: " . $e->getMessage());
        $results['message'] = 'Error searching book by title: ' . $e->getMessage();
        return $results;
    }
}

/**
 * Update book from suggestion
 *
 * @param int $bookId The book ID
 * @param array $suggestionData The suggestion data
 * @param PDO $db Database connection
 * @return array Result of the update
 */
function updateBookFromSuggestion($bookId, $suggestionData, $db) {
    $results = [
        'status' => 'error',
        'message' => '',
        'updated_fields' => []
    ];

    try {
        // Get book details
        $stmt = $db->prepare("
            SELECT di.id, di.title, b.*
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE di.id = ?
        ");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $results['message'] = 'Book not found';
            return $results;
        }

        // Start transaction
        $db->beginTransaction();

        $updatedFields = [];

        // Update title if different
        if (!empty($suggestionData['title']) && $suggestionData['title'] !== $book['title']) {
            $stmt = $db->prepare("
                UPDATE directory_items
                SET title = ?
                WHERE id = ?
            ");
            $stmt->execute([$suggestionData['title'], $bookId]);
            $updatedFields[] = 'title';
        }

        // Update other fields
        $fieldsToUpdate = [
            'author', 'publisher', 'publication_date', 'isbn', 'isbn13', 'cover_url', 'preview_link'
        ];

        foreach ($fieldsToUpdate as $field) {
            if (!empty($suggestionData[$field]) && $suggestionData[$field] !== ($book[$field] ?? '')) {
                $stmt = $db->prepare("
                    UPDATE books
                    SET $field = ?
                    WHERE directory_item_id = ?
                ");
                $stmt->execute([$suggestionData[$field], $bookId]);
                $updatedFields[] = $field;
            }
        }

        // Update validation status
        $stmt = $db->prepare("
            UPDATE books
            SET validation_status = 'valid', last_validated = NOW()
            WHERE directory_item_id = ?
        ");
        $stmt->execute([$bookId]);

        // Add to validation history
        foreach ($updatedFields as $field) {
            $oldValue = $field === 'title' ? $book['title'] : ($book[$field] ?? '');
            $newValue = $suggestionData[$field];
            addValidationHistoryEntry($bookId, $field, $oldValue, $newValue, 'suggestion', $db);
        }

        // Clear cache
        $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
        $title = $book['title'] ?? '';
        clearValidationCacheNew($bookId, $isbn, $title, $db);

        // Commit transaction
        $db->commit();

        $results['status'] = 'success';
        $results['message'] = count($updatedFields) > 0
            ? 'Successfully updated ' . count($updatedFields) . ' fields from suggestion'
            : 'No fields needed updating';
        $results['updated_fields'] = $updatedFields;

        return $results;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log("Error updating book from suggestion: " . $e->getMessage());
        $results['message'] = 'Error updating book from suggestion: ' . $e->getMessage();
        return $results;
    }
}

/**
 * Get missing fields for a book
 *
 * @param array $book The book data
 * @return array List of missing fields
 */
function getMissingFields($book) {
    $missingFields = [];

    // Define required fields based on actual database schema
    $requiredFields = [
        'title' => 'Title',
        'author' => 'Author',
        'isbn' => 'ISBN-10',
        'isbn13' => 'ISBN-13',
        'publisher' => 'Publisher',
        'publication_date' => 'Publication Date',
        'page_count' => 'Page Count',
        'price_range' => 'Price Range',
        'age_range' => 'Age Range',
        'reading_level' => 'Reading Level',
        'language' => 'Language',
        'format' => 'Format',
        'cover_url' => 'Cover Image',
        'preview_link' => 'Preview Link',
        'series' => 'Series',
        'awards' => 'Awards',
        'characters' => 'Characters',
        'settings' => 'Settings'
    ];

    // Check each field
    foreach ($requiredFields as $field => $label) {
        if (isEmpty($book[$field])) {
            $missingFields[] = $label;
        }
    }

    return $missingFields;
}

/**
 * Check if a field is empty or contains placeholder values
 *
 * @param mixed $value The field value to check
 * @return bool True if the field is considered empty
 */
function isEmpty($value) {
    // Check for null, empty string, or zero
    if (is_null($value) || $value === '' || $value === 0 || $value === '0') {
        return true;
    }

    // Check for common placeholder values
    $placeholders = ['unknown', 'Unknown', 'UNKNOWN', 'N/A', 'n/a', 'Not available', 'not available'];
    if (is_string($value) && in_array(trim($value), $placeholders)) {
        return true;
    }

    return false;
}
