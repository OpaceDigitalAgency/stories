<?php
/**
 * Book Validation Functions
 *
 * This file contains the main functions for validating and enriching book data.
 * It includes all the modular components for different sources and functionality.
 */

// Include the review fetcher services (we'll use these for API access)
require_once __DIR__ . '/../../../../services/ReviewFetcher/ReviewFetcherInterface.php';
require_once __DIR__ . '/../../../../services/ReviewFetcher/AbstractReviewFetcher.php';
require_once __DIR__ . '/../../../../services/ReviewFetcher/GoogleBooksReviewFetcher.php';
require_once __DIR__ . '/../../../../services/ReviewFetcher/OpenLibraryReviewFetcher.php';
require_once __DIR__ . '/../../../../services/ReviewFetcher/GoodreadsReviewFetcher.php';
require_once __DIR__ . '/../../../../services/ReviewFetcher/StoriesReviewFetcher.php';
require_once __DIR__ . '/../../../../services/ReviewFetcher/ReviewFetcherFactory.php';

// Include source-specific functions
require_once __DIR__ . '/google-books-validation-functions.php';
require_once __DIR__ . '/open-library-validation-functions.php';
require_once __DIR__ . '/goodreads-validation-functions.php';

// Include cache and history functions
require_once __DIR__ . '/cache-functions.php';

// Include book update functions
require_once __DIR__ . '/book-update-functions.php';

// Include search functions
require_once __DIR__ . '/search-functions.php';

/**
 * Validate book data against external sources
 *
 * @param int $bookId The book ID
 * @param string $isbn The ISBN to validate
 * @param string $title The book title
 * @param PDO $db Database connection
 * @param bool $forceRefresh Whether to force a refresh of cached data
 * @return array Validation results
 */
function validateBookData($bookId, $isbn, $title, $db, $forceRefresh = false) {
    // Initialize results
    $results = [
        'status' => 'unknown',
        'message' => '',
        'sourceData' => [],
        'history' => []
    ];

    try {
        // Log validation attempt for debugging
        error_log("Validating book ID: $bookId, ISBN: $isbn, Title: $title, Force refresh: " . ($forceRefresh ? 'Yes' : 'No'));

        // Clean ISBN
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

        if (empty($cleanIsbn)) {
            error_log("Warning: Empty ISBN for book ID: $bookId, Title: $title");
        }

        // Check cache first to improve performance (unless force refresh is requested)
        if (!$forceRefresh) {
            $cacheKey = md5("book_validation_{$bookId}_{$cleanIsbn}");
            $cachedResults = getValidationCacheNew($cacheKey, $db);

            if ($cachedResults) {
                error_log("Using cached validation results for book ID: $bookId, ISBN: $cleanIsbn");
                return $cachedResults;
            }
        }

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
            $results['status'] = 'error';
            $results['message'] = 'Book not found';
            return $results;
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

        // Initialize source data and timing
        $sourceData = [];
        $sourceTiming = [];
        $totalStartTime = microtime(true);

        // Check Goodreads first since that's what we're debugging
        if ($goodreadsSourceId) {
            $goodreadsStartTime = microtime(true);
            error_log("Starting Goodreads fetch at " . date('H:i:s'));
            
            $goodreadsFetcher = $reviewFetcherFactory->getFetcher($goodreadsSourceId);
            if ($goodreadsFetcher && $goodreadsFetcher->isConfigured()) {
                // Pass force parameter through
                $options = ['force' => $forceRefresh];
                $goodreadsData = fetchGoodreadsDataNew($cleanIsbn, $title, $book['author'] ?? '', $db, $options);
                
                $goodreadsEndTime = microtime(true);
                $goodreadsDuration = round($goodreadsEndTime - $goodreadsStartTime, 2);
                error_log("Completed Goodreads fetch at " . date('H:i:s') . " (took {$goodreadsDuration}s)");
                $sourceTiming['goodreads'] = $goodreadsDuration;
                
                if ($goodreadsData) {
                    $status = 'success';
                    $message = 'Successfully fetched data from Goodreads';
                    $processingTime = null;
                    $method = 'unknown';
                    $steps = [];

                    if (isset($goodreadsData['_status'])) {
                        $statusInfo = $goodreadsData['_status'];
                        $status = $statusInfo['status'] ?? 'success';
                        $message = $statusInfo['message'] ?? 'Successfully fetched data from Goodreads';
                        $processingTime = $statusInfo['processing_time'] ?? null;
                        $method = $statusInfo['method'] ?? 'unknown';
                        $steps = $statusInfo['steps'] ?? [];

                        unset($goodreadsData['_status']);
                    }

                    // Clean up GraphQL response data
                    if (isset($goodreadsData['data'])) {
                        $cleanData = $goodreadsData['data'];
                        // Remove GraphQL-specific fields
                        unset($cleanData['__typename']);
                        unset($cleanData['Work']);
                        unset($cleanData['links']);
                        unset($cleanData['reviewEditUrl']);
                        unset($cleanData['featureFlags']);
                        unset($cleanData['params']);
                        unset($cleanData['query']);
                        unset($cleanData['jwtToken']);
                        unset($cleanData['dataSource']);
                        unset($cleanData['authContextParams']);
                        unset($cleanData['userAgentContextParams']);
                        unset($cleanData['userAgent']);
                        unset($cleanData['__N_SSP']);
                        unset($cleanData['page']);
                        unset($cleanData['buildId']);
                        unset($cleanData['isFallback']);
                        unset($cleanData['isExperimentalCompile']);
                        unset($cleanData['gssp']);
                        unset($cleanData['locales']);
                        unset($cleanData['scriptLoader']);
                    } else {
                        $cleanData = $goodreadsData;
                    }

                    $sourceData['goodreads'] = [
                        'status' => $status,
                        'message' => $message,
                        'method' => $method,
                        'processing_time' => $processingTime,
                        'steps' => $steps,
                        'data' => $cleanData
                    ];
                }
            }
        }

        // Start Google Books and Open Library fetches in parallel
        $googleBooksPromise = null;
        $openLibraryPromise = null;

        // Check Google Books
        if ($googleBooksSourceId) {
            $googleBooksStartTime = microtime(true);
            error_log("Starting Google Books fetch at " . date('H:i:s'));
            $googleBooksFetcher = $reviewFetcherFactory->getFetcher($googleBooksSourceId);
            if ($googleBooksFetcher && $googleBooksFetcher->isConfigured()) {
                $googleBooksData = fetchGoogleBooksDataNew($cleanIsbn, $title, $book['author'] ?? '');
                
                $googleBooksEndTime = microtime(true);
                $googleBooksDuration = round($googleBooksEndTime - $googleBooksStartTime, 2);
                error_log("Completed Google Books fetch at " . date('H:i:s') . " (took {$googleBooksDuration}s)");
                $sourceTiming['google_books'] = $googleBooksDuration;
                if ($googleBooksData) {
                    // Check if we have status information
                    $status = 'success';
                    $message = 'Successfully fetched data from Google Books';
                    $processingTime = null;

                    if (isset($googleBooksData['_status'])) {
                        $statusInfo = $googleBooksData['_status'];
                        $status = $statusInfo['status'] ?? 'success';
                        $message = $statusInfo['message'] ?? 'Successfully fetched data from Google Books';
                        $processingTime = $statusInfo['processing_time'] ?? null;
                        $steps = $statusInfo['steps'] ?? [];

                        // Remove status info from the data
                        unset($googleBooksData['_status']);
                    }

                    $sourceData['google_books'] = [
                        'status' => $status,
                        'message' => $message,
                        'processing_time' => $processingTime,
                        'method' => $statusInfo['method'] ?? 'api',
                        'steps' => $steps ?? [],
                        'data' => $googleBooksData
                    ];
                } else {
                    $sourceData['google_books'] = [
                        'status' => 'error',
                        'message' => 'Failed to fetch data from Google Books'
                    ];
                }
            }
        }

        // Check Open Library
        if ($openLibrarySourceId) {
            $openLibraryStartTime = microtime(true);
            error_log("Starting Open Library fetch at " . date('H:i:s'));
            $openLibraryFetcher = $reviewFetcherFactory->getFetcher($openLibrarySourceId);
            if ($openLibraryFetcher && $openLibraryFetcher->isConfigured()) {
                $openLibraryData = fetchOpenLibraryDataNew($cleanIsbn, $title, $book['author'] ?? '');
                
                $openLibraryEndTime = microtime(true);
                $openLibraryDuration = round($openLibraryEndTime - $openLibraryStartTime, 2);
                error_log("Completed Open Library fetch at " . date('H:i:s') . " (took {$openLibraryDuration}s)");
                $sourceTiming['open_library'] = $openLibraryDuration;
                if ($openLibraryData) {
                    // Check if we have status information
                    $status = 'success';
                    $message = 'Successfully fetched data from Open Library';
                    $processingTime = null;
                    $method = 'api';
                    $steps = [];

                    if (isset($openLibraryData['_status'])) {
                        $statusInfo = $openLibraryData['_status'];
                        $status = $statusInfo['status'] ?? 'success';
                        $message = $statusInfo['message'] ?? 'Successfully fetched data from Open Library';
                        $processingTime = $statusInfo['processing_time'] ?? null;
                        $method = $statusInfo['method'] ?? 'api';
                        $steps = $statusInfo['steps'] ?? [];

                        // Remove status info from the data
                        unset($openLibraryData['_status']);
                    }

                    $sourceData['open_library'] = [
                        'status' => $status,
                        'message' => $message,
                        'processing_time' => $processingTime,
                        'method' => $method,
                        'steps' => $steps,
                        'data' => $openLibraryData
                    ];
                } else {
                    $sourceData['open_library'] = [
                        'status' => 'error',
                        'message' => 'Failed to fetch data from Open Library',
                        'method' => 'api'
                    ];
                }
            }
        }


        // Get validation history
        $history = getValidationHistory($bookId, $db);

        // Update validation status in the database
        $validationStatus = 'pending';
        $successfulSources = 0;

        // Log total execution time and timing breakdown
        $totalEndTime = microtime(true);
        $totalDuration = round($totalEndTime - $totalStartTime, 2);
        error_log("Total validation time: {$totalDuration}s");
        error_log("Source timing breakdown: " . json_encode($sourceTiming));

        foreach ($sourceData as $source => $data) {
            if ($data['status'] === 'success') {
                $successfulSources++;
            }
        }

        if ($successfulSources > 0) {
            $validationStatus = $successfulSources === count($sourceData) ? 'valid' : 'partial';
        } else {
            $validationStatus = 'invalid';
        }

        // Update book validation status
        $stmt = $db->prepare("
            UPDATE books
            SET validation_status = ?, last_validated = NOW()
            WHERE directory_item_id = ?
        ");
        $stmt->execute([$validationStatus, $bookId]);

        // Set results
        $results['status'] = $successfulSources > 0 ? 'success' : 'error';
        $results['message'] = $successfulSources > 0
            ? "Book data found in $successfulSources source(s)"
            : "Book data not found in any sources";
        $results['sourceData'] = $sourceData;
        $results['history'] = $history;

        // Save results to cache
        $cacheKey = md5("book_validation_{$bookId}_{$cleanIsbn}");
        saveValidationCacheNew($cacheKey, $results, $db);

        return $results;
    } catch (Exception $e) {
        error_log("Error validating book data: " . $e->getMessage());
        $results['status'] = 'error';
        $results['message'] = 'Error validating book data: ' . $e->getMessage();
        return $results;
    }
}

/**
 * Fetch data from Google Books
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @return array|null Book data or null if not found
 */
function fetchGoogleBooksData($isbn, $title, $author) {
    try {
        // Try ISBN search first
        $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn);

        // Make the request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        // If no results, try title and author search
        if (empty($data['items']) && (!empty($title) || !empty($author))) {
            $query = '';
            if (!empty($title)) {
                $query .= "intitle:" . urlencode($title);
            }
            if (!empty($author)) {
                if (!empty($query)) {
                    $query .= "+";
                }
                $query .= "inauthor:" . urlencode($author);
            }

            $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);
        }

        if (!empty($data['items'])) {
            $volumeInfo = $data['items'][0]['volumeInfo'] ?? [];

            // Extract ISBNs
            $isbn10 = '';
            $isbn13 = '';
            if (!empty($volumeInfo['industryIdentifiers'])) {
                foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                    if ($identifier['type'] === 'ISBN_10') {
                        $isbn10 = $identifier['identifier'];
                    } else if ($identifier['type'] === 'ISBN_13') {
                        $isbn13 = $identifier['identifier'];
                    }
                }
            }

            return [
                'title' => $volumeInfo['title'] ?? '',
                'author' => implode(', ', $volumeInfo['authors'] ?? []),
                'publisher' => $volumeInfo['publisher'] ?? '',
                'publication_date' => $volumeInfo['publishedDate'] ?? '',
                'page_count' => $volumeInfo['pageCount'] ?? '',
                'isbn' => $isbn10,
                'isbn13' => $isbn13,
                'language' => $volumeInfo['language'] ?? '',
                'format' => '',
                'series' => '',
                'awards' => '',
                'characters' => '',
                'settings' => '',
                'preview_link' => $volumeInfo['previewLink'] ?? '',
                'cover_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
                'rating' => $volumeInfo['averageRating'] ?? '',
                'rating_count' => $volumeInfo['ratingsCount'] ?? '',
                'review_count' => '',
                'maturity_rating' => $volumeInfo['maturityRating'] ?? ''
            ];
        }

        return null;
    } catch (Exception $e) {
        error_log("Error fetching Google Books data: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch data from Open Library
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @return array|null Book data or null if not found
 */
function fetchOpenLibraryData($isbn, $title, $author) {
    try {
        // Try ISBN search first
        $url = "https://openlibrary.org/api/books?bibkeys=ISBN:" . urlencode($isbn) . "&format=json&jscmd=data";

        // Make the request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        $key = "ISBN:$isbn";

        // If no results, try title and author search
        if (empty($data[$key]) && (!empty($title) || !empty($author))) {
            $query = '';
            if (!empty($title)) {
                $query .= "title=" . urlencode($title);
            }
            if (!empty($author)) {
                if (!empty($query)) {
                    $query .= "&";
                }
                $query .= "author=" . urlencode($author);
            }

            $url = "https://openlibrary.org/search.json?" . $query;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);

            $searchData = json_decode($response, true);

            if (!empty($searchData['docs'])) {
                $bookInfo = $searchData['docs'][0];

                // Extract ISBNs
                $isbn10 = '';
                $isbn13 = '';
                if (!empty($bookInfo['isbn'])) {
                    foreach ($bookInfo['isbn'] as $isbnValue) {
                        if (strlen($isbnValue) == 10) {
                            $isbn10 = $isbnValue;
                        } else if (strlen($isbnValue) == 13) {
                            $isbn13 = $isbnValue;
                        }
                    }
                }

                // Extract authors
                $authors = [];
                if (!empty($bookInfo['author_name'])) {
                    $authors = $bookInfo['author_name'];
                }

                $coverUrl = '';
                if (!empty($bookInfo['cover_i'])) {
                    $coverUrl = "https://covers.openlibrary.org/b/id/" . $bookInfo['cover_i'] . "-M.jpg";
                }

                return [
                    'title' => $bookInfo['title'] ?? '',
                    'author' => implode(', ', $authors),
                    'publisher' => !empty($bookInfo['publisher']) ? implode(', ', $bookInfo['publisher']) : '',
                    'publication_date' => !empty($bookInfo['publish_date']) ? $bookInfo['publish_date'][0] : '',
                    'page_count' => $bookInfo['number_of_pages_median'] ?? '',
                    'isbn' => $isbn10,
                    'isbn13' => $isbn13,
                    'language' => !empty($bookInfo['language']) ? implode(', ', $bookInfo['language']) : '',
                    'format' => '',
                    'series' => '',
                    'awards' => '',
                    'characters' => '',
                    'settings' => '',
                    'preview_link' => '',
                    'cover_url' => $coverUrl,
                    'rating' => '',
                    'rating_count' => '',
                    'review_count' => '',
                    'maturity_rating' => ''
                ];
            }

            return null;
        }

        if (!empty($data[$key])) {
            $bookInfo = $data[$key];

            // Extract ISBNs
            $isbn10 = '';
            $isbn13 = '';
            if (!empty($bookInfo['identifiers']['isbn_10'])) {
                $isbn10 = $bookInfo['identifiers']['isbn_10'][0];
            }
            if (!empty($bookInfo['identifiers']['isbn_13'])) {
                $isbn13 = $bookInfo['identifiers']['isbn_13'][0];
            }

            // Extract authors
            $authors = [];
            if (!empty($bookInfo['authors'])) {
                foreach ($bookInfo['authors'] as $author) {
                    $authors[] = $author['name'];
                }
            }

            return [
                'title' => $bookInfo['title'] ?? '',
                'author' => implode(', ', $authors),
                'publisher' => !empty($bookInfo['publishers']) ? $bookInfo['publishers'][0]['name'] : '',
                'publication_date' => $bookInfo['publish_date'] ?? '',
                'page_count' => $bookInfo['number_of_pages'] ?? '',
                'isbn' => $isbn10,
                'isbn13' => $isbn13,
                'language' => '',
                'format' => '',
                'series' => '',
                'awards' => '',
                'characters' => '',
                'settings' => '',
                'preview_link' => '',
                'cover_url' => !empty($bookInfo['cover']) ? $bookInfo['cover']['medium'] : '',
                'rating' => '',
                'rating_count' => '',
                'review_count' => '',
                'maturity_rating' => ''
            ];
        }

        return null;
    } catch (Exception $e) {
        error_log("Error fetching Open Library data: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate multiple books in batch
 *
 * @param array $bookIds Array of book IDs to validate
 * @param PDO $db Database connection
 * @return array Batch validation results
 */
function validateBooksBatch($bookIds, $db) {
    $results = [
        'total' => count($bookIds),
        'success' => 0,
        'error' => 0,
        'details' => []
    ];

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

        if (!$book) {
            $results['error']++;
            $results['details'][$bookId] = [
                'status' => 'error',
                'message' => 'Book not found'
            ];
            continue;
        }

        $isbnToUse = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');

        if (empty($isbnToUse)) {
            $results['error']++;
            $results['details'][$bookId] = [
                'status' => 'error',
                'message' => 'No ISBN available'
            ];
            continue;
        }

        // Validate the book
        $validationResult = validateBookData($bookId, $isbnToUse, $book['title'], $db);

        if ($validationResult['status'] === 'success') {
            $results['success']++;
        } else {
            $results['error']++;
        }

        $results['details'][$bookId] = [
            'status' => $validationResult['status'],
            'message' => $validationResult['message'],
            'title' => $book['title'],
            'isbn' => $isbnToUse
        ];
    }

    return $results;
}

/**
 * Get validation status for a book
 *
 * @param int $bookId The book ID
 * @param PDO $db Database connection
 * @return array Validation status
 */
function getBookValidationStatus($bookId, $db) {
    try {
        // Get book details with validation status
        $stmt = $db->prepare("
            SELECT di.id, di.title, b.isbn, b.isbn13, b.validation_status, b.last_validated
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE di.id = ?
        ");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            return [
                'status' => 'error',
                'message' => 'Book not found'
            ];
        }

        // Get missing fields
        $missingFields = getMissingFields($book);

        return [
            'status' => 'success',
            'book' => $book,
            'validation_status' => $book['validation_status'],
            'last_validated' => $book['last_validated'],
            'missing_fields' => $missingFields,
            'missing_count' => count($missingFields)
        ];
    } catch (Exception $e) {
        error_log("Error getting book validation status: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Error getting book validation status: ' . $e->getMessage()
        ];
    }
}
