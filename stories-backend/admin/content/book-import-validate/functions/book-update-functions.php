<?php
/**
 * Book Update Functions
 *
 * This file contains functions for updating book data.
 */

// Include cache functions
require_once __DIR__ . '/cache-functions.php';

/**
 * Update a single field in a book
 *
 * @param int $bookId The book ID
 * @param string $field The field to update
 * @param string $value The new value
 * @param string $source The source of the update
 * @param PDO $db Database connection
 * @return array Result of the update
 */
function updateBookField($bookId, $field, $value, $source, $db) {
    $results = [
        'status' => 'error',
        'message' => '',
        'updated_fields' => []
    ];

    try {
        // Get current book data
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

        // Get current value
        $currentValue = $book[$field] ?? '';

        // Skip if the value is the same
        if ($currentValue == $value) {
            $results['status'] = 'success';
            $results['message'] = 'No change needed, values are identical';
            return $results;
        }

        // Update the field
        if ($field === 'title') {
            // Title is in directory_items table
            $stmt = $db->prepare("
                UPDATE directory_items
                SET title = ?
                WHERE id = ?
            ");
            $stmt->execute([$value, $bookId]);
        } else {
            // Check if the field exists in the books table
            $stmt = $db->prepare("
                SELECT COUNT(*) as field_exists
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'books'
                AND COLUMN_NAME = ?
            ");
            $stmt->execute([$field]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['field_exists'] == 0) {
                $results['message'] = "Field '$field' does not exist in the books table";
                return $results;
            }

            // Update the field in the books table
            $stmt = $db->prepare("
                UPDATE books
                SET $field = ?
                WHERE directory_item_id = ?
            ");
            $stmt->execute([$value, $bookId]);
        }

        // Add to validation history
        addValidationHistoryEntry($bookId, $field, $currentValue, $value, $source, $db);

        // Update validation status
        $stmt = $db->prepare("
            UPDATE books
            SET validation_status = 'valid', last_validated = NOW()
            WHERE directory_item_id = ?
        ");
        $stmt->execute([$bookId]);

        // Clear cache
        $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
        $title = $book['title'] ?? '';
        clearValidationCache($bookId, $isbn, $title, $db);

        $results['status'] = 'success';
        $results['message'] = "Successfully updated $field from $source";
        $results['updated_fields'] = [$field];

        return $results;
    } catch (Exception $e) {
        error_log("Error updating book field: " . $e->getMessage());
        $results['message'] = 'Error updating book field: ' . $e->getMessage();
        return $results;
    }
}

/**
 * Apply all valid values from all sources
 *
 * @param int $bookId The book ID
 * @param PDO $db Database connection
 * @return array Result of the update
 */
function applyAllValidValues($bookId, $db) {
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

        // Get validation data
        $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
        $cacheKey = md5("book_validation_{$bookId}_{$isbn}");
        $validationData = getValidationCache($cacheKey, $db);

        if (!$validationData || empty($validationData['sourceData'])) {
            $results['message'] = 'No validation data available';
            return $results;
        }

        $sourceData = $validationData['sourceData'];
        $updatedFields = [];

        // Define fields to update
        $fields = [
            'title', 'author', 'publisher', 'publication_date', 'page_count',
            'isbn', 'isbn13', 'language', 'format', 'series', 'awards',
            'characters', 'settings', 'preview_link', 'cover_url', 'rating',
            'rating_count', 'review_count', 'maturity_rating'
        ];

        // Start transaction
        $db->beginTransaction();

        // Process each field
        foreach ($fields as $field) {
            // Skip title for now (special case)
            if ($field === 'title') {
                continue;
            }

            $currentValue = $book[$field] ?? '';
            $bestValue = '';
            $bestSource = '';
            $bestPriority = -1;

            // Source priority: Google Books (1), Open Library (2), Goodreads (3)
            $sourcePriorities = [
                'google_books' => 1,
                'open_library' => 2,
                'goodreads' => 3
            ];

            // Find the best value from all sources
            foreach ($sourceData as $source => $data) {
                if ($data['status'] === 'success' && !empty($data['data'][$field])) {
                    $sourceValue = $data['data'][$field];
                    $sourcePriority = $sourcePriorities[$source] ?? 999;

                    // If current value is empty or this source has higher priority
                    if (empty($currentValue) || empty($bestValue) || $sourcePriority < $bestPriority) {
                        $bestValue = $sourceValue;
                        $bestSource = $source;
                        $bestPriority = $sourcePriority;
                    }
                }
            }

            // Update if we found a better value
            if (!empty($bestValue) && $bestValue !== $currentValue) {
                $updateResult = updateBookField($bookId, $field, $bestValue, $bestSource, $db);
                if ($updateResult['status'] === 'success') {
                    $updatedFields[] = $field;
                }
            }
        }

        // Special case for title (in directory_items table)
        $currentTitle = $book['title'] ?? '';
        $bestTitle = '';
        $bestSource = '';
        $bestPriority = -1;

        foreach ($sourceData as $source => $data) {
            if ($data['status'] === 'success' && !empty($data['data']['title'])) {
                $sourceTitle = $data['data']['title'];
                $sourcePriority = $sourcePriorities[$source] ?? 999;

                if (empty($bestTitle) || $sourcePriority < $bestPriority) {
                    $bestTitle = $sourceTitle;
                    $bestSource = $source;
                    $bestPriority = $sourcePriority;
                }
            }
        }

        if (!empty($bestTitle) && $bestTitle !== $currentTitle) {
            $updateResult = updateBookField($bookId, 'title', $bestTitle, $bestSource, $db);
            if ($updateResult['status'] === 'success') {
                $updatedFields[] = 'title';
            }
        }

        // Commit transaction
        $db->commit();

        // Update results
        $results['status'] = 'success';
        $results['message'] = count($updatedFields) > 0 
            ? 'Successfully updated ' . count($updatedFields) . ' fields' 
            : 'No fields needed updating';
        $results['updated_fields'] = $updatedFields;

        return $results;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log("Error applying all valid values: " . $e->getMessage());
        $results['message'] = 'Error applying all valid values: ' . $e->getMessage();
        return $results;
    }
}

/**
 * Apply all values from a specific source
 *
 * @param int $bookId The book ID
 * @param string $source The source to use
 * @param PDO $db Database connection
 * @return array Result of the update
 */
function applyAllFromSource($bookId, $source, $db) {
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

        // Get validation data
        $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
        $cacheKey = md5("book_validation_{$bookId}_{$isbn}");
        $validationData = getValidationCache($cacheKey, $db);

        if (!$validationData || empty($validationData['sourceData'][$source])) {
            $results['message'] = "No validation data available for source: $source";
            return $results;
        }

        $sourceData = $validationData['sourceData'][$source]['data'] ?? [];
        if (empty($sourceData)) {
            $results['message'] = "No data available from source: $source";
            return $results;
        }

        $updatedFields = [];

        // Define fields to update
        $fields = [
            'title', 'author', 'publisher', 'publication_date', 'page_count',
            'isbn', 'isbn13', 'language', 'format', 'series', 'awards',
            'characters', 'settings', 'preview_link', 'cover_url', 'rating',
            'rating_count', 'review_count', 'maturity_rating'
        ];

        // Start transaction
        $db->beginTransaction();

        // Process each field
        foreach ($fields as $field) {
            // Skip title for now (special case)
            if ($field === 'title') {
                continue;
            }

            $currentValue = $book[$field] ?? '';
            $sourceValue = $sourceData[$field] ?? '';

            // Update if source has a value and it's different from current
            if (!empty($sourceValue) && $sourceValue !== $currentValue) {
                $updateResult = updateBookField($bookId, $field, $sourceValue, $source, $db);
                if ($updateResult['status'] === 'success') {
                    $updatedFields[] = $field;
                }
            }
        }

        // Special case for title (in directory_items table)
        $currentTitle = $book['title'] ?? '';
        $sourceTitle = $sourceData['title'] ?? '';

        if (!empty($sourceTitle) && $sourceTitle !== $currentTitle) {
            $updateResult = updateBookField($bookId, 'title', $sourceTitle, $source, $db);
            if ($updateResult['status'] === 'success') {
                $updatedFields[] = 'title';
            }
        }

        // Commit transaction
        $db->commit();

        // Update results
        $results['status'] = 'success';
        $results['message'] = count($updatedFields) > 0 
            ? "Successfully updated " . count($updatedFields) . " fields from $source" 
            : "No fields needed updating from $source";
        $results['updated_fields'] = $updatedFields;

        return $results;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log("Error applying values from source: " . $e->getMessage());
        $results['message'] = "Error applying values from $source: " . $e->getMessage();
        return $results;
    }
}
