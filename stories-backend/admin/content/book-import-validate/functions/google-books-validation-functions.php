<?php
/**
 * Google Books Validation Functions
 *
 * This file contains functions for fetching and validating book data from Google Books.
 */

/**
 * Fetch data from Google Books
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @return array|null Book data or null if not found
 */
function fetchGoogleBooksDataNew($isbn, $title, $author) {
    try {
        // Start timer for performance tracking
        $startTime = microtime(true);

        // Initialize status tracking
        $status = [
            'status' => 'initializing',
            'message' => 'Starting Google Books data fetch',
            'processing_time' => 0,
            'steps' => []
        ];

        // Add a unique request ID to prevent caching issues
        $requestId = uniqid();

        // Try ISBN search first if we have an ISBN
        $isbnResults = null;
        if (!empty($isbn)) {
            $status['steps'][] = [
                'name' => 'isbn_search',
                'status' => 'in_progress',
                'message' => "Searching by ISBN: $isbn"
            ];

            $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($isbn) . "&random=" . $requestId;

            // Make the request
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $status['steps'][] = [
                    'name' => 'isbn_search',
                    'status' => 'error',
                    'message' => "cURL error: $error"
                ];
            } else {
                $isbnResults = json_decode($response, true);

                if (isset($isbnResults['items']) && !empty($isbnResults['items'])) {
                    $status['steps'][] = [
                        'name' => 'isbn_search',
                        'status' => 'success',
                        'message' => "Found " . count($isbnResults['items']) . " results by ISBN"
                    ];
                } else {
                    $status['steps'][] = [
                        'name' => 'isbn_search',
                        'status' => 'warning',
                        'message' => "No results found by ISBN"
                    ];
                }
            }
        }

        // If no results from ISBN search, try title and author search
        $titleAuthorResults = null;
        if (empty($isbnResults['items']) && (!empty($title) || !empty($author))) {
            $status['steps'][] = [
                'name' => 'title_author_search',
                'status' => 'in_progress',
                'message' => "Searching by title/author: $title / $author"
            ];

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

            $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&random=" . $requestId;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $status['steps'][] = [
                    'name' => 'title_author_search',
                    'status' => 'error',
                    'message' => "cURL error: $error"
                ];
            } else {
                $titleAuthorResults = json_decode($response, true);

                if (isset($titleAuthorResults['items']) && !empty($titleAuthorResults['items'])) {
                    $status['steps'][] = [
                        'name' => 'title_author_search',
                        'status' => 'success',
                        'message' => "Found " . count($titleAuthorResults['items']) . " results by title/author"
                    ];
                } else {
                    $status['steps'][] = [
                        'name' => 'title_author_search',
                        'status' => 'warning',
                        'message' => "No results found by title/author"
                    ];
                }
            }
        }

        // Use the results from ISBN search if available, otherwise use title/author search results
        $data = !empty($isbnResults['items']) ? $isbnResults : $titleAuthorResults;

        if (!empty($data['items'])) {
            $status['steps'][] = [
                'name' => 'extract_data',
                'status' => 'in_progress',
                'message' => "Extracting data from first result"
            ];

            $volumeInfo = $data['items'][0]['volumeInfo'] ?? [];

            // Verify we have the correct book by checking title/author match
            $foundTitle = $volumeInfo['title'] ?? '';
            $foundAuthors = $volumeInfo['authors'] ?? [];
            $foundAuthor = implode(', ', $foundAuthors);

            $titleMatch = false;
            $authorMatch = false;

            // Check title match (case-insensitive, partial match)
            if (!empty($title) && !empty($foundTitle)) {
                if (stripos($foundTitle, $title) !== false || stripos($title, $foundTitle) !== false) {
                    $titleMatch = true;
                    $status['steps'][] = [
                        'name' => 'verify_title',
                        'status' => 'success',
                        'message' => "Title match: '$title' ≈ '$foundTitle'"
                    ];
                } else {
                    $status['steps'][] = [
                        'name' => 'verify_title',
                        'status' => 'warning',
                        'message' => "Title mismatch: '$title' ≠ '$foundTitle'"
                    ];
                }
            }

            // Check author match (case-insensitive, partial match)
            if (!empty($author) && !empty($foundAuthor)) {
                if (stripos($foundAuthor, $author) !== false || stripos($author, $foundAuthor) !== false) {
                    $authorMatch = true;
                    $status['steps'][] = [
                        'name' => 'verify_author',
                        'status' => 'success',
                        'message' => "Author match: '$author' ≈ '$foundAuthor'"
                    ];
                } else {
                    $status['steps'][] = [
                        'name' => 'verify_author',
                        'status' => 'warning',
                        'message' => "Author mismatch: '$author' ≠ '$foundAuthor'"
                    ];
                }
            }

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

                $status['steps'][] = [
                    'name' => 'extract_isbn',
                    'status' => 'success',
                    'message' => "Found ISBNs: " . ($isbn10 ? "ISBN-10: $isbn10" : "") .
                                 ($isbn10 && $isbn13 ? ", " : "") .
                                 ($isbn13 ? "ISBN-13: $isbn13" : "")
                ];
            } else {
                $status['steps'][] = [
                    'name' => 'extract_isbn',
                    'status' => 'warning',
                    'message' => "No ISBNs found in the result"
                ];
            }

            // Extract categories/genres
            $categories = $volumeInfo['categories'] ?? [];

            // Build book data
            $bookData = [
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
                'cover_url' => isset($volumeInfo['imageLinks']['thumbnail']) ? str_replace('http://', 'https://', $volumeInfo['imageLinks']['thumbnail']) : '',
                'rating' => $volumeInfo['averageRating'] ?? '',
                'rating_count' => $volumeInfo['ratingsCount'] ?? '',
                'review_count' => '',
                'maturity_rating' => $volumeInfo['maturityRating'] ?? ''
            ];

            // Calculate processing time
            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            // Update status
            $status['status'] = 'success';
            $status['message'] = "Successfully extracted Google Books data";
            $status['processing_time'] = $totalTime;

            // Add status information to the book data
            $bookData['_status'] = $status;

            return $bookData;
        }

        // No results found
        $endTime = microtime(true);
        $totalTime = round($endTime - $startTime, 2);

        $status['status'] = 'error';
        $status['message'] = "No book found on Google Books";
        $status['processing_time'] = $totalTime;

        return null;
    } catch (Exception $e) {
        error_log("Error fetching Google Books data: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate ISBN using Google Books
 *
 * @param string $isbn The ISBN to validate
 * @return bool True if valid, false otherwise
 */
function validateIsbnWithGoogleBooks($isbn) {
    try {
        // Clean ISBN
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

        // Check if ISBN is valid format
        if (strlen($cleanIsbn) !== 10 && strlen($cleanIsbn) !== 13) {
            return false;
        }

        // Try ISBN search
        $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . urlencode($cleanIsbn);

        // Make the request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return false;
        }

        $data = json_decode($response, true);

        // Check if we found any books
        return !empty($data['items']);
    } catch (Exception $e) {
        error_log("Error validating ISBN with Google Books: " . $e->getMessage());
        return false;
    }
}

/**
 * Search for books by title and author using Google Books
 *
 * @param string $title The book title
 * @param string $author The book author
 * @param int $limit Maximum number of results to return
 * @return array List of book suggestions
 */
function searchBooksByTitleAuthor($title, $author = '', $limit = 5) {
    try {
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

        $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&maxResults=" . $limit;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        $suggestions = [];
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $volumeInfo = $item['volumeInfo'] ?? [];

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

                $suggestions[] = [
                    'title' => $volumeInfo['title'] ?? '',
                    'author' => implode(', ', $volumeInfo['authors'] ?? []),
                    'publisher' => $volumeInfo['publisher'] ?? '',
                    'publication_date' => $volumeInfo['publishedDate'] ?? '',
                    'isbn' => $isbn10,
                    'isbn13' => $isbn13,
                    'cover_url' => isset($volumeInfo['imageLinks']['thumbnail']) ? str_replace('http://', 'https://', $volumeInfo['imageLinks']['thumbnail']) : '',
                    'preview_link' => $volumeInfo['previewLink'] ?? ''
                ];

                if (count($suggestions) >= $limit) {
                    break;
                }
            }
        }

        return $suggestions;
    } catch (Exception $e) {
        error_log("Error searching books by title/author: " . $e->getMessage());
        return [];
    }
}
