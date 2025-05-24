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

            // Get book details including publisher and publication date
            $stmt = $db->prepare("
                SELECT di.id, di.title, b.isbn, b.isbn13, b.author, b.publisher, b.publication_date
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
            $suggestions = searchBooksByTitleAuthor($book['title'], $book['author'], 10); // Get more results for better matching

            if (empty($suggestions)) {
                echo json_encode(['status' => 'error', 'message' => 'No alternative ISBNs found for this book']);
                exit;
            }

            // Apply intelligent matching to find the best suggestions
            $matchedSuggestions = intelligentISBNMatching($suggestions, $book);

            // Return suggestions for user to choose from
            echo json_encode([
                'status' => 'success',
                'book_id' => $bookId,
                'suggestions' => $matchedSuggestions,
                'current_title' => $book['title'],
                'current_author' => $book['author'],
                'current_publisher' => $book['publisher'],
                'current_year' => $book['publication_date']
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

/**
 * Intelligent ISBN matching based on title, publisher, year, and format
 */
function intelligentISBNMatching($suggestions, $currentBook) {
    $currentTitle = strtolower(trim($currentBook['title']));
    $currentPublisher = strtolower(trim($currentBook['publisher'] ?? ''));
    $currentYear = intval($currentBook['publication_date'] ?? 0);

    // First, deduplicate suggestions by ISBN
    $uniqueSuggestions = [];
    $seenISBNs = [];

    foreach ($suggestions as $suggestion) {
        $isbn13 = $suggestion['isbn13'] ?? '';
        $isbn10 = $suggestion['isbn'] ?? '';
        $primaryISBN = $isbn13 ?: $isbn10;

        if (!empty($primaryISBN) && !in_array($primaryISBN, $seenISBNs)) {
            $seenISBNs[] = $primaryISBN;
            $uniqueSuggestions[] = $suggestion;
        }
    }

    $scoredSuggestions = [];

    foreach ($uniqueSuggestions as $suggestion) {
        $score = 0;
        $reasons = [];

        $suggestionTitle = strtolower(trim($suggestion['title'] ?? ''));
        $suggestionPublisher = strtolower(trim($suggestion['publisher'] ?? ''));
        $suggestionYear = intval($suggestion['publication_date'] ?? 0);

        // Skip if no valid ISBN
        if (empty($suggestion['isbn']) && empty($suggestion['isbn13'])) {
            continue;
        }

        // Title matching (most important)
        if ($suggestionTitle === $currentTitle) {
            $score += 200; // Increased for exact match
            $reasons[] = 'EXACT title match';
        } elseif (strpos($suggestionTitle, $currentTitle) !== false || strpos($currentTitle, $suggestionTitle) !== false) {
            $score += 80;
            $reasons[] = 'Partial title match';
        } else {
            // Penalize completely different titles
            $score -= 50;
            $reasons[] = 'Different title (penalty)';
        }

        // Publisher matching (very important) - handle variations
        if (!empty($currentPublisher) && !empty($suggestionPublisher)) {
            if ($suggestionPublisher === $currentPublisher) {
                $score += 50;
                $reasons[] = 'Exact publisher match';
            } elseif (publisherVariationMatch($currentPublisher, $suggestionPublisher)) {
                $score += 40;
                $reasons[] = 'Publisher variation match';
            } elseif (strpos($suggestionPublisher, $currentPublisher) !== false || strpos($currentPublisher, $suggestionPublisher) !== false) {
                $score += 30;
                $reasons[] = 'Partial publisher match';
            } else {
                // Only apply a small penalty for publisher mismatch if title is exact
                if ($suggestionTitle === $currentTitle) {
                    $score -= 5; // Small penalty for exact title but wrong publisher
                    $reasons[] = 'Publisher mismatch (small penalty for exact title)';
                } else {
                    $score -= 15; // Larger penalty for both title and publisher mismatch
                    $reasons[] = 'Publisher mismatch (penalty)';
                }
            }
        }

        // Year matching (important)
        if ($currentYear > 0 && $suggestionYear > 0) {
            $yearDiff = abs($currentYear - $suggestionYear);
            if ($yearDiff === 0) {
                $score += 30;
                $reasons[] = 'Exact year match';
            } elseif ($yearDiff <= 1) {
                $score += 20;
                $reasons[] = 'Close year match';
            } elseif ($yearDiff <= 3) {
                $score += 10;
                $reasons[] = 'Approximate year match';
            }
        }

        // Format preferences (exclude compilations and wrong formats)
        $titleLower = strtolower($suggestion['title'] ?? '');
        $suggestionFormat = strtolower($suggestion['format'] ?? '');

        // Penalize compilations
        if (strpos($titleLower, ' and ') !== false ||
            strpos($titleLower, 'includes') !== false ||
            strpos($titleLower, 'collection') !== false ||
            strpos($titleLower, 'box set') !== false) {
            $score -= 20;
            $reasons[] = 'Compilation/collection (penalty)';
        }

        // Penalize audiobooks if we're looking for print books (and vice versa)
        if (strpos($suggestionFormat, 'audio') !== false ||
            strpos($titleLower, 'audiobook') !== false ||
            strpos($titleLower, 'audio book') !== false) {
            $score -= 15;
            $reasons[] = 'Audiobook format (penalty)';
        }

        // Penalize ebooks if we're looking for print books
        if (strpos($suggestionFormat, 'ebook') !== false ||
            strpos($suggestionFormat, 'kindle') !== false ||
            strpos($titleLower, 'ebook') !== false) {
            $score -= 10;
            $reasons[] = 'Ebook format (penalty)';
        }

        // Prefer ISBN-13 over ISBN-10
        if (!empty($suggestion['isbn13'])) {
            $score += 5;
            $reasons[] = 'Has ISBN-13';
        }

        $scoredSuggestions[] = [
            'suggestion' => $suggestion,
            'score' => $score,
            'reasons' => $reasons
        ];
    }

    // Sort by score (highest first)
    usort($scoredSuggestions, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    // Return top suggestions with their scores and reasons
    $result = [];
    $maxResults = 5; // Limit to top 5 matches

    foreach (array_slice($scoredSuggestions, 0, $maxResults) as $scored) {
        $suggestion = $scored['suggestion'];
        $suggestion['match_score'] = $scored['score'];
        $suggestion['match_reasons'] = implode(', ', $scored['reasons']);
        $result[] = $suggestion;
    }

    // If we have a very high confidence match (exact title + good publisher), return just that one
    if (!empty($result) && $result[0]['match_score'] >= 240) {
        return [$result[0]]; // Return only the best match for automatic selection
    }

    return $result;
}

/**
 * Check if two publishers are variations of the same company
 */
function publisherVariationMatch($publisher1, $publisher2) {
    // Common publisher variations
    $variations = [
        ['scholastic', 'scholastic uk', 'scholastic ltd', 'marion lloyd books'],
        ['penguin', 'penguin random house', 'penguin books'],
        ['harpercollins', 'harper collins', 'harper', 'harpercollins uk', 'harpercollins children\'s books'],
        ['simon & schuster', 'simon and schuster', 'simon & schuster children\'s books', 'simon and schuster children\'s books'],
        ['macmillan', 'macmillan children\'s books'],
        ['orion', 'orion children\'s books', 'orion publishing'],
        ['bloomsbury', 'bloomsbury children\'s books'],
        ['walker', 'walker books'],
        ['usborne', 'usborne publishing']
    ];

    $pub1 = strtolower($publisher1);
    $pub2 = strtolower($publisher2);

    foreach ($variations as $group) {
        $inGroup1 = false;
        $inGroup2 = false;

        foreach ($group as $variant) {
            if (strpos($pub1, $variant) !== false) $inGroup1 = true;
            if (strpos($pub2, $variant) !== false) $inGroup2 = true;
        }

        if ($inGroup1 && $inGroup2) {
            return true;
        }
    }

    return false;
}
?>
