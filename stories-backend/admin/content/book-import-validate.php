<?php
/**
 * Book Import Validation
 *
 * This script handles the validation and enrichment of book data, including:
 * 1. ISBN validation against external sources
 * 2. Data enrichment for missing fields
 * 3. Batch processing of book data updates
 */

// Set page title and current page
$pageTitle = 'Book Data Validation';
$currentPage = 'book-import-tool';
$pageDescription = 'Validate and enrich book data from external sources';

// Include the header
require_once '../includes/auth-check.php';
require_once '../includes/header.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include the review fetcher services (we'll use these for API access)
require_once '../../services/ReviewFetcher/ReviewFetcherInterface.php';
require_once '../../services/ReviewFetcher/AbstractReviewFetcher.php';
require_once '../../services/ReviewFetcher/GoogleBooksReviewFetcher.php';
require_once '../../services/ReviewFetcher/OpenLibraryReviewFetcher.php';
require_once '../../services/ReviewFetcher/GoodreadsReviewFetcher.php';
require_once '../../services/ReviewFetcher/ReviewFetcherFactory.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Function to validate ISBN format and checksum
function validateISBNFormat($isbn) {
    // Remove hyphens and spaces
    $isbn = preg_replace('/[^0-9X]/i', '', $isbn);

    // Check if it's ISBN-10
    if (strlen($isbn) == 10) {
        // Validate ISBN-10 checksum
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$isbn[$i] * (10 - $i);
        }
        $checkDigit = ($isbn[9] == 'X') ? 10 : (int)$isbn[9];
        $calculatedChecksum = (11 - ($sum % 11)) % 11;
        if ($calculatedChecksum == 10) $calculatedChecksum = 'X';

        return $checkDigit == $calculatedChecksum;
    }

    // Check if it's ISBN-13
    if (strlen($isbn) == 13) {
        // Validate ISBN-13 checksum
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$isbn[$i] * (($i % 2 == 0) ? 1 : 3);
        }
        $calculatedChecksum = (10 - ($sum % 10)) % 10;

        return (int)$isbn[12] == $calculatedChecksum;
    }

    // Not a valid ISBN length
    return false;
}

// Function to check ISBN against external APIs
function checkISBNAgainstAPIs($isbn, $title, $db) {
    $results = [
        'status' => 'unknown',
        'message' => '',
        'suggestions' => [],
        'data' => []
    ];

    // Clean ISBN
    $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

    // Check format validity first
    if (empty($cleanIsbn)) {
        $results['status'] = 'error';
        $results['message'] = 'ISBN is empty';
        return $results;
    }

    if (!validateISBNFormat($cleanIsbn)) {
        $results['status'] = 'error';
        $results['message'] = 'ISBN format or checksum is invalid';
    }

    // Initialize the review fetcher factory
    $reviewFetcherFactory = new \Services\ReviewFetcher\ReviewFetcherFactory($db);

    // Get sources
    $sources = $reviewFetcherFactory->getSources();
    $googleBooksSourceId = null;
    $openLibrarySourceId = null;
    $goodreadsSourceId = null;

    // Find source IDs
    foreach ($sources as $source) {
        $sourceName = strtolower($source['name']);
        if ($sourceName === 'google books') {
            $googleBooksSourceId = $source['id'];
        } else if ($sourceName === 'open library') {
            $openLibrarySourceId = $source['id'];
        } else if ($sourceName === 'goodreads') {
            $goodreadsSourceId = $source['id'];
        }
    }

    $foundInSources = [];
    $bookData = [];

    // Check Google Books
    if ($googleBooksSourceId) {
        $googleBooksFetcher = $reviewFetcherFactory->getFetcher($googleBooksSourceId);
        if ($googleBooksFetcher && $googleBooksFetcher->isConfigured()) {
            try {
                // Use the fetcher to check if the ISBN exists
                $response = $googleBooksFetcher->fetchReviewsByISBN($cleanIsbn, 1);
                if (!empty($response)) {
                    $foundInSources[] = 'Google Books';
                    $bookData['google_books'] = [
                        'title' => $response[0]['book_title'] ?? '',
                        'author' => $response[0]['book_author'] ?? '',
                        'publisher' => $response[0]['book_publisher'] ?? '',
                        'publication_date' => $response[0]['book_publication_date'] ?? '',
                        'page_count' => $response[0]['book_page_count'] ?? '',
                        'isbn' => $response[0]['book_isbn'] ?? '',
                        'isbn13' => $response[0]['book_isbn13'] ?? '',
                        'categories' => $response[0]['book_categories'] ?? [],
                        'description' => $response[0]['book_description'] ?? '',
                        'cover_url' => $response[0]['book_cover_url'] ?? '',
                        'source' => 'Google Books'
                    ];
                }
            } catch (Exception $e) {
                // Log error but continue
                error_log("Google Books API error: " . $e->getMessage());
            }
        }
    }

    // Check Open Library
    if ($openLibrarySourceId) {
        $openLibraryFetcher = $reviewFetcherFactory->getFetcher($openLibrarySourceId);
        if ($openLibraryFetcher && $openLibraryFetcher->isConfigured()) {
            try {
                // Use the fetcher to check if the ISBN exists
                $response = $openLibraryFetcher->fetchReviewsByISBN($cleanIsbn, 1);
                if (!empty($response)) {
                    $foundInSources[] = 'Open Library';
                    $bookData['open_library'] = [
                        'title' => $response[0]['book_title'] ?? '',
                        'author' => $response[0]['book_author'] ?? '',
                        'publisher' => $response[0]['book_publisher'] ?? '',
                        'publication_date' => $response[0]['book_publication_date'] ?? '',
                        'page_count' => $response[0]['book_page_count'] ?? '',
                        'isbn' => $response[0]['book_isbn'] ?? '',
                        'isbn13' => $response[0]['book_isbn13'] ?? '',
                        'categories' => $response[0]['book_categories'] ?? [],
                        'description' => $response[0]['book_description'] ?? '',
                        'cover_url' => $response[0]['book_cover_url'] ?? '',
                        'source' => 'Open Library'
                    ];
                }
            } catch (Exception $e) {
                // Log error but continue
                error_log("Open Library API error: " . $e->getMessage());
            }
        }
    }

    // Check Goodreads
    if ($goodreadsSourceId) {
        $goodreadsFetcher = $reviewFetcherFactory->getFetcher($goodreadsSourceId);
        if ($goodreadsFetcher && $goodreadsFetcher->isConfigured()) {
            try {
                // Use the fetcher to check if the ISBN exists
                $response = $goodreadsFetcher->fetchReviewsByISBN($cleanIsbn, 1);
                if (!empty($response)) {
                    $foundInSources[] = 'Goodreads';
                    $bookData['goodreads'] = [
                        'title' => $response[0]['book_title'] ?? '',
                        'author' => $response[0]['book_author'] ?? '',
                        'publisher' => $response[0]['book_publisher'] ?? '',
                        'publication_date' => $response[0]['book_publication_date'] ?? '',
                        'page_count' => $response[0]['book_page_count'] ?? '',
                        'isbn' => $response[0]['book_isbn'] ?? '',
                        'isbn13' => $response[0]['book_isbn13'] ?? '',
                        'categories' => $response[0]['book_categories'] ?? [],
                        'description' => $response[0]['book_description'] ?? '',
                        'cover_url' => $response[0]['book_cover_url'] ?? '',
                        'source' => 'Goodreads'
                    ];
                }
            } catch (Exception $e) {
                // Log error but continue
                error_log("Goodreads API error: " . $e->getMessage());
            }
        }
    }

    // If we didn't find the ISBN in any source, try to search by title
    if (empty($foundInSources) && !empty($title)) {
        $titleSuggestions = searchBooksByTitle($title, $db, $reviewFetcherFactory);
        if (!empty($titleSuggestions)) {
            $results['suggestions'] = $titleSuggestions;
        }
    }

    // Determine status based on results
    if (empty($foundInSources)) {
        if ($results['status'] !== 'error') {
            $results['status'] = 'warning';
            $results['message'] = 'ISBN not found in any external sources';
        }
    } else {
        $results['status'] = 'success';
        $results['message'] = 'ISBN found in: ' . implode(', ', $foundInSources);
        $results['data'] = $bookData;
    }

    return $results;
}

// Function to search for books by title
function searchBooksByTitle($title, $db, $reviewFetcherFactory) {
    $suggestions = [];

    // Get Google Books source ID
    $sources = $reviewFetcherFactory->getSources();
    $googleBooksSourceId = null;

    foreach ($sources as $source) {
        if (strtolower($source['name']) === 'google books') {
            $googleBooksSourceId = $source['id'];
            break;
        }
    }

    if ($googleBooksSourceId) {
        $googleBooksFetcher = $reviewFetcherFactory->getFetcher($googleBooksSourceId);
        if ($googleBooksFetcher && $googleBooksFetcher->isConfigured()) {
            try {
                // Use Google Books API to search by title
                $url = "https://www.googleapis.com/books/v1/volumes?q=intitle:" . urlencode($title) . "&maxResults=5";

                // Make the request
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);

                if ($response) {
                    $data = json_decode($response, true);
                    if (!empty($data['items'])) {
                        foreach ($data['items'] as $item) {
                            $volumeInfo = $item['volumeInfo'] ?? [];
                            $industryIdentifiers = $volumeInfo['industryIdentifiers'] ?? [];

                            $isbn = '';
                            $isbn13 = '';

                            foreach ($industryIdentifiers as $identifier) {
                                if ($identifier['type'] === 'ISBN_10') {
                                    $isbn = $identifier['identifier'];
                                } else if ($identifier['type'] === 'ISBN_13') {
                                    $isbn13 = $identifier['identifier'];
                                }
                            }

                            if (!empty($isbn) || !empty($isbn13)) {
                                $suggestions[] = [
                                    'title' => $volumeInfo['title'] ?? '',
                                    'author' => implode(', ', $volumeInfo['authors'] ?? []),
                                    'publisher' => $volumeInfo['publisher'] ?? '',
                                    'isbn' => $isbn,
                                    'isbn13' => $isbn13,
                                    'confidence' => calculateTitleSimilarity($title, $volumeInfo['title'] ?? ''),
                                    'source' => 'Google Books'
                                ];
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error searching books by title: " . $e->getMessage());
            }
        }
    }

    // Sort suggestions by confidence score
    usort($suggestions, function($a, $b) {
        return $b['confidence'] <=> $a['confidence'];
    });

    return $suggestions;
}

// Function to calculate similarity between two titles
function calculateTitleSimilarity($title1, $title2) {
    // Normalize titles
    $title1 = strtolower(trim($title1));
    $title2 = strtolower(trim($title2));

    // If titles are identical, return 100%
    if ($title1 === $title2) {
        return 1.0;
    }

    // Calculate Levenshtein distance
    $levenshtein = levenshtein($title1, $title2);
    $maxLength = max(strlen($title1), strlen($title2));

    // Convert to similarity score (0-1)
    $similarity = 1 - ($levenshtein / $maxLength);

    return $similarity;
}

// Function to enrich book data from external sources
function enrichBookData($bookId, $db) {
    $results = [
        'status' => 'unknown',
        'message' => '',
        'updated_fields' => []
    ];

    try {
        // Get book details
        $stmt = $db->prepare("
            SELECT di.id, di.title, b.isbn, b.isbn13, b.author, b.publisher, b.publication_date,
                   b.page_count, b.age_range, b.reading_level, b.genre, b.series
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE di.id = ?
        ");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $results['status'] = 'error';
            $results['message'] = 'Book not found';
            return $results;
        }

        // Get ISBN to use for API calls
        $isbn = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');

        if (empty($isbn)) {
            $results['status'] = 'error';
            $results['message'] = 'No ISBN available for this book';
            return $results;
        }

        // Initialize the review fetcher factory
        $reviewFetcherFactory = new \Services\ReviewFetcher\ReviewFetcherFactory($db);

        // Get sources
        $sources = $reviewFetcherFactory->getSources();
        $googleBooksSourceId = null;
        $openLibrarySourceId = null;

        // Find source IDs
        foreach ($sources as $source) {
            $sourceName = strtolower($source['name']);
            if ($sourceName === 'google books') {
                $googleBooksSourceId = $source['id'];
            } else if ($sourceName === 'open library') {
                $openLibrarySourceId = $source['id'];
            }
        }

        $bookData = [];
        $fieldsToUpdate = [];

        // Check Google Books
        if ($googleBooksSourceId) {
            $googleBooksFetcher = $reviewFetcherFactory->getFetcher($googleBooksSourceId);
            if ($googleBooksFetcher && $googleBooksFetcher->isConfigured()) {
                try {
                    // Use the fetcher to get book data
                    $response = $googleBooksFetcher->fetchReviewsByISBN($isbn, 1);
                    if (!empty($response)) {
                        $bookData['google_books'] = [
                            'title' => $response[0]['book_title'] ?? '',
                            'author' => $response[0]['book_author'] ?? '',
                            'publisher' => $response[0]['book_publisher'] ?? '',
                            'publication_date' => $response[0]['book_publication_date'] ?? '',
                            'page_count' => $response[0]['book_page_count'] ?? '',
                            'categories' => $response[0]['book_categories'] ?? [],
                            'description' => $response[0]['book_description'] ?? '',
                            'source' => 'Google Books'
                        ];
                    }
                } catch (Exception $e) {
                    // Log error but continue
                    error_log("Google Books API error: " . $e->getMessage());
                }
            }
        }

        // Check Open Library
        if ($openLibrarySourceId) {
            $openLibraryFetcher = $reviewFetcherFactory->getFetcher($openLibrarySourceId);
            if ($openLibraryFetcher && $openLibraryFetcher->isConfigured()) {
                try {
                    // Use the fetcher to get book data
                    $response = $openLibraryFetcher->fetchReviewsByISBN($isbn, 1);
                    if (!empty($response)) {
                        $bookData['open_library'] = [
                            'title' => $response[0]['book_title'] ?? '',
                            'author' => $response[0]['book_author'] ?? '',
                            'publisher' => $response[0]['book_publisher'] ?? '',
                            'publication_date' => $response[0]['book_publication_date'] ?? '',
                            'page_count' => $response[0]['book_page_count'] ?? '',
                            'categories' => $response[0]['book_categories'] ?? [],
                            'description' => $response[0]['book_description'] ?? '',
                            'source' => 'Open Library'
                        ];
                    }
                } catch (Exception $e) {
                    // Log error but continue
                    error_log("Open Library API error: " . $e->getMessage());
                }
            }
        }

        // If we have data from external sources, update missing fields
        if (!empty($bookData)) {
            // Merge data from all sources
            $mergedData = [];

            foreach ($bookData as $source => $data) {
                foreach ($data as $field => $value) {
                    if (!empty($value) && $field !== 'source') {
                        if (!isset($mergedData[$field]) || empty($mergedData[$field])) {
                            $mergedData[$field] = $value;
                        }
                    }
                }
            }

            // Check which fields need to be updated
            if (empty($book['series']) && !empty($mergedData['categories'])) {
                // Try to extract series from categories
                $series = '';
                foreach ($mergedData['categories'] as $category) {
                    if (stripos($category, 'series') !== false) {
                        $series = $category;
                        break;
                    }
                }

                if (!empty($series)) {
                    $fieldsToUpdate['series'] = $series;
                    $results['updated_fields'][] = 'series';
                }
            }

            if ((empty($book['page_count']) || $book['page_count'] == 0) && !empty($mergedData['page_count'])) {
                $fieldsToUpdate['page_count'] = $mergedData['page_count'];
                $results['updated_fields'][] = 'page_count';
            }

            if (empty($book['genre']) && !empty($mergedData['categories'])) {
                // Use the first category as genre
                $genre = $mergedData['categories'][0] ?? '';
                if (!empty($genre)) {
                    $fieldsToUpdate['genre'] = $genre;
                    $results['updated_fields'][] = 'genre';
                }
            }

            if (empty($book['publisher']) && !empty($mergedData['publisher'])) {
                $fieldsToUpdate['publisher'] = $mergedData['publisher'];
                $results['updated_fields'][] = 'publisher';
            }

            if (empty($book['age_range']) && !empty($mergedData['categories'])) {
                // Try to extract age range from categories
                $ageRange = '';
                foreach ($mergedData['categories'] as $category) {
                    if (preg_match('/(\d+)-(\d+)/', $category, $matches) ||
                        stripos($category, 'children') !== false ||
                        stripos($category, 'young adult') !== false) {
                        $ageRange = $category;
                        break;
                    }
                }

                if (!empty($ageRange)) {
                    $fieldsToUpdate['age_range'] = $ageRange;
                    $results['updated_fields'][] = 'age_range';
                }
            }

            // Update the book if we have fields to update
            if (!empty($fieldsToUpdate)) {
                $updateFields = [];
                $updateParams = [];

                foreach ($fieldsToUpdate as $field => $value) {
                    $updateFields[] = "$field = ?";
                    $updateParams[] = $value;
                }

                $updateParams[] = $bookId;

                $updateQuery = "
                    UPDATE books
                    SET " . implode(', ', $updateFields) . ", updated_at = NOW()
                    WHERE directory_item_id = ?
                ";

                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute($updateParams);

                $results['status'] = 'success';
                $results['message'] = 'Book data enriched successfully. Updated fields: ' . implode(', ', $results['updated_fields']);
            } else {
                $results['status'] = 'warning';
                $results['message'] = 'No fields needed updating or no data available from external sources';
            }
        } else {
            $results['status'] = 'warning';
            $results['message'] = 'No data available from external sources';
        }
    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['message'] = 'Error enriching book data: ' . $e->getMessage();
    }

    return $results;
}

// Function to update book ISBN
function updateBookISBN($bookId, $isbn, $isbn13, $db) {
    try {
        $stmt = $db->prepare("
            UPDATE books
            SET isbn = ?, isbn13 = ?, updated_at = NOW()
            WHERE directory_item_id = ?
        ");
        $stmt->execute([$isbn, $isbn13, $bookId]);

        return [
            'status' => 'success',
            'message' => 'ISBN updated successfully'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Error updating ISBN: ' . $e->getMessage()
        ];
    }
}

// Main processing logic
$validationResults = null;
$bookDetails = null;
$suggestions = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'validate_isbn') {
    // Process single ISBN validation
    try {
        $bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
        $isbn = isset($_GET['isbn']) ? trim($_GET['isbn']) : '';

        if ($bookId > 0) {
            // Get book details
            $stmt = $db->prepare("
                SELECT di.id, di.title, b.isbn, b.isbn13, b.author, b.publisher, b.publication_date,
                       b.page_count, b.age_range, b.reading_level, b.genre, b.series
                FROM directory_items di
                JOIN books b ON di.id = b.directory_item_id
                WHERE di.id = ?
            ");
            $stmt->execute([$bookId]);
            $bookDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($bookDetails) {
                // Validate ISBN
                $validationResults = checkISBNAgainstAPIs($isbn, $bookDetails['title'], $db);
                $suggestions = $validationResults['suggestions'] ?? [];
            } else {
                echo "<p class='error'>Book not found</p>";
            }
        } else {
            echo "<p class='error'>Invalid book ID</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form submission
    try {
        // Get parameters
        $action = $_POST['action'] ?? '';
        $bookIds = isset($_POST['book_ids']) ? (array)$_POST['book_ids'] : [];

        if ($action === 'validate_isbns') {
            // Validate ISBNs for selected books
            if (empty($bookIds)) {
                echo "<p class='error'>No books selected</p>";
            } else {
                echo "<p class='info'>Processing " . count($bookIds) . " books...</p>";
                flushOutput();

                $validationResults = [];

                foreach ($bookIds as $bookId) {
                    // Get book details
                    $stmt = $db->prepare("
                        SELECT di.id, di.title, b.isbn, b.isbn13
                        FROM directory_items di
                        JOIN books b ON di.id = b.directory_item_id
                        WHERE di.id = ?
                    ");
                    $stmt->execute([$bookId]);
                    $book = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($book) {
                        $isbn = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');

                        echo "<p class='info'>Validating ISBN for: " . htmlspecialchars($book['title']) . "</p>";
                        flushOutput();

                        $result = checkISBNAgainstAPIs($isbn, $book['title'], $db);

                        $validationResults[$bookId] = [
                            'title' => $book['title'],
                            'isbn' => $isbn,
                            'result' => $result
                        ];

                        echo "<p class='" . ($result['status'] === 'success' ? 'success' : 'warning') . "'>" .
                             htmlspecialchars($result['message']) . "</p>";
                        flushOutput();
                    }
                }
            }
        } else if ($action === 'enrich_data') {
            // Enrich data for selected books
            $bookSelection = $_POST['enrich_book_selection'] ?? 'all';
            $specificBooks = isset($_POST['enrich_specific_books']) ? (array)$_POST['enrich_specific_books'] : [];
            $fields = isset($_POST['enrich_fields']) ? (array)$_POST['enrich_fields'] : [];

            if (empty($fields)) {
                echo "<p class='error'>No fields selected for enrichment</p>";
            } else {
                // Build query to get books
                $query = "
                    SELECT di.id, di.title, b.isbn, b.isbn13, b.author, b.publisher, b.publication_date,
                           b.page_count, b.age_range, b.reading_level, b.genre, b.series
                    FROM directory_items di
                    JOIN books b ON di.id = b.directory_item_id
                    WHERE di.type = 'book'
                ";

                $params = [];

                if ($bookSelection === 'specific' && !empty($specificBooks)) {
                    $placeholders = implode(',', array_fill(0, count($specificBooks), '?'));
                    $query .= " AND di.id IN ($placeholders)";
                    $params = $specificBooks;
                } else if ($bookSelection === 'missing') {
                    $conditions = [];

                    foreach ($fields as $field) {
                        if ($field === 'series') {
                            $conditions[] = "(b.series IS NULL OR b.series = '')";
                        } else if ($field === 'reading_age') {
                            $conditions[] = "(b.age_range IS NULL OR b.age_range = '')";
                        } else if ($field === 'page_count') {
                            $conditions[] = "(b.page_count IS NULL OR b.page_count = 0)";
                        } else if ($field === 'genre') {
                            $conditions[] = "(b.genre IS NULL OR b.genre = '')";
                        } else if ($field === 'publisher') {
                            $conditions[] = "(b.publisher IS NULL OR b.publisher = '')";
                        }
                    }

                    if (!empty($conditions)) {
                        $query .= " AND (" . implode(" OR ", $conditions) . ")";
                    }
                }

                $query .= " ORDER BY di.title ASC LIMIT 50";

                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($books)) {
                    echo "<p class='warning'>No books found matching the criteria</p>";
                } else {
                    echo "<p class='info'>Processing " . count($books) . " books for data enrichment...</p>";
                    flushOutput();

                    foreach ($books as $book) {
                        echo "<p class='info'>Enriching data for: " . htmlspecialchars($book['title']) . "</p>";
                        flushOutput();

                        $isbn = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');

                        if (empty($isbn)) {
                            echo "<p class='warning'>No ISBN available for this book, skipping</p>";
                            flushOutput();
                            continue;
                        }

                        $result = enrichBookData($book['id'], $db);

                        echo "<p class='" . ($result['status'] === 'success' ? 'success' : 'warning') . "'>" .
                             htmlspecialchars($result['message']) . "</p>";
                        flushOutput();
                    }
                }
            }
        } else if ($action === 'update_isbn') {
            // Update ISBN for a book
            $bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
            $isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
            $isbn13 = isset($_POST['isbn13']) ? trim($_POST['isbn13']) : '';

            if ($bookId > 0) {
                $result = updateBookISBN($bookId, $isbn, $isbn13, $db);

                echo "<p class='" . ($result['status'] === 'success' ? 'success' : 'error') . "'>" .
                     htmlspecialchars($result['message']) . "</p>";

                // Redirect back to the validation page
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'book-import-validate.php?action=validate_isbn&book_id=$bookId&isbn=" .
                        ($isbn13 ? $isbn13 : $isbn) . "';
                    }, 2000);
                </script>";
            } else {
                echo "<p class='error'>Invalid book ID</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Book Data Validation & Enrichment</h5>
                    <div class="btn-group">
                        <a href="book-import-tool.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Import Tool
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="logContainer" class="log-container mb-4">
                        <?php if (isset($_GET['action']) && $_GET['action'] === 'validate_isbn' && $bookDetails): ?>
                            <h4>ISBN Validation for: <?php echo htmlspecialchars($bookDetails['title']); ?></h4>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Current Book Data</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Title:</strong> <?php echo htmlspecialchars($bookDetails['title']); ?></p>
                                            <p><strong>Author:</strong> <?php echo htmlspecialchars($bookDetails['author']); ?></p>
                                            <p><strong>ISBN-10:</strong> <?php echo !empty($bookDetails['isbn']) ? htmlspecialchars($bookDetails['isbn']) : '<span class="text-muted">Not available</span>'; ?></p>
                                            <p><strong>ISBN-13:</strong> <?php echo !empty($bookDetails['isbn13']) ? htmlspecialchars($bookDetails['isbn13']) : '<span class="text-muted">Not available</span>'; ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Publisher:</strong> <?php echo !empty($bookDetails['publisher']) ? htmlspecialchars($bookDetails['publisher']) : '<span class="text-muted">Not available</span>'; ?></p>
                                            <p><strong>Genre:</strong> <?php echo !empty($bookDetails['genre']) ? htmlspecialchars($bookDetails['genre']) : '<span class="text-muted">Not available</span>'; ?></p>
                                            <p><strong>Series:</strong> <?php echo !empty($bookDetails['series']) ? htmlspecialchars($bookDetails['series']) : '<span class="text-muted">Not available</span>'; ?></p>
                                            <p><strong>Page Count:</strong> <?php echo !empty($bookDetails['page_count']) ? htmlspecialchars($bookDetails['page_count']) : '<span class="text-muted">Not available</span>'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($validationResults): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5>Validation Results</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-<?php
                                            echo $validationResults['status'] === 'success' ? 'success' :
                                                ($validationResults['status'] === 'warning' ? 'warning' : 'danger');
                                        ?>">
                                            <strong><?php echo ucfirst($validationResults['status']); ?>:</strong>
                                            <?php echo htmlspecialchars($validationResults['message']); ?>
                                        </div>

                                        <?php if (!empty($validationResults['data'])): ?>
                                            <h6>Book Data from External Sources:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Source</th>
                                                            <th>Title</th>
                                                            <th>Author</th>
                                                            <th>ISBN-10</th>
                                                            <th>ISBN-13</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($validationResults['data'] as $source => $data): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($data['source']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['title']); ?></td>
                                                                <td><?php echo htmlspecialchars($data['author']); ?></td>
                                                                <td><?php echo !empty($data['isbn']) ? htmlspecialchars($data['isbn']) : '<span class="text-muted">N/A</span>'; ?></td>
                                                                <td><?php echo !empty($data['isbn13']) ? htmlspecialchars($data['isbn13']) : '<span class="text-muted">N/A</span>'; ?></td>
                                                                <td>
                                                                    <form method="post" action="book-import-validate.php" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_isbn">
                                                                        <input type="hidden" name="book_id" value="<?php echo $bookDetails['id']; ?>">
                                                                        <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($data['isbn']); ?>">
                                                                        <input type="hidden" name="isbn13" value="<?php echo htmlspecialchars($data['isbn13']); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-success">
                                                                            <i class="fas fa-check"></i> Use This ISBN
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($suggestions)): ?>
                                            <h6 class="mt-4">Suggested Books Based on Title:</h6>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Title</th>
                                                            <th>Author</th>
                                                            <th>ISBN-10</th>
                                                            <th>ISBN-13</th>
                                                            <th>Confidence</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($suggestions as $suggestion): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($suggestion['title']); ?></td>
                                                                <td><?php echo htmlspecialchars($suggestion['author']); ?></td>
                                                                <td><?php echo !empty($suggestion['isbn']) ? htmlspecialchars($suggestion['isbn']) : '<span class="text-muted">N/A</span>'; ?></td>
                                                                <td><?php echo !empty($suggestion['isbn13']) ? htmlspecialchars($suggestion['isbn13']) : '<span class="text-muted">N/A</span>'; ?></td>
                                                                <td><?php echo number_format($suggestion['confidence'] * 100, 1) . '%'; ?></td>
                                                                <td>
                                                                    <form method="post" action="book-import-validate.php" class="d-inline">
                                                                        <input type="hidden" name="action" value="update_isbn">
                                                                        <input type="hidden" name="book_id" value="<?php echo $bookDetails['id']; ?>">
                                                                        <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($suggestion['isbn']); ?>">
                                                                        <input type="hidden" name="isbn13" value="<?php echo htmlspecialchars($suggestion['isbn13']); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-success">
                                                                            <i class="fas fa-check"></i> Use This ISBN
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mt-4">
                                            <h6>Manually Update ISBN:</h6>
                                            <form method="post" action="book-import-validate.php" class="row g-3">
                                                <input type="hidden" name="action" value="update_isbn">
                                                <input type="hidden" name="book_id" value="<?php echo $bookDetails['id']; ?>">

                                                <div class="col-md-5">
                                                    <label for="manual_isbn" class="form-label">ISBN-10</label>
                                                    <input type="text" class="form-control" id="manual_isbn" name="isbn"
                                                           value="<?php echo htmlspecialchars($bookDetails['isbn']); ?>"
                                                           placeholder="Enter ISBN-10">
                                                </div>

                                                <div class="col-md-5">
                                                    <label for="manual_isbn13" class="form-label">ISBN-13</label>
                                                    <input type="text" class="form-control" id="manual_isbn13" name="isbn13"
                                                           value="<?php echo htmlspecialchars($bookDetails['isbn13']); ?>"
                                                           placeholder="Enter ISBN-13">
                                                </div>

                                                <div class="col-md-2 d-flex align-items-end">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Update
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="info">Select books to validate or enrich data from the ISBN & Data Validation tab.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once '../includes/footer.php';
?>
