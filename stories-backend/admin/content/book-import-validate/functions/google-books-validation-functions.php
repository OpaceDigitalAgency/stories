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
 * @param bool $isForEnrichment Whether this is for data enrichment (exact ISBN only) or validation (allow fallback)
 * @return array|null Book data or null if not found
 */
function fetchGoogleBooksDataNew($isbn, $title, $author, $isForEnrichment = false) {
    try {
        // Start timer for performance tracking
        $startTime = microtime(true);

        // Initialize status tracking
        $status = [
            'status' => 'initializing',
            'message' => 'Starting Google Books data fetch',
            'method' => 'api',
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

            // Add URL to steps
            $status['steps'][] = [
                'name' => 'url_generation',
                'status' => 'success',
                'message' => "Generated URL for ISBN search",
                'fetch_url' => $url
            ];

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

        // For data enrichment, ONLY use exact ISBN matches - do not fall back to title/author searches
        // This prevents returning data for different editions when enriching specific ISBNs
        if (empty($isbnResults['items']) && $isForEnrichment) {
            // Return null for enrichment if exact ISBN not found
            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            $status['status'] = 'error';
            $status['message'] = "No exact ISBN match found in Google Books for enrichment";
            $status['processing_time'] = $totalTime;
            $status['steps'][] = [
                'name' => 'enrichment_isbn_only',
                'status' => 'error',
                'message' => "Enrichment requires exact ISBN match - no title/author fallback"
            ];

            return null;
        }

        // For validation (not enrichment), allow title/author fallback
        $titleAuthorResults = null;
        if (empty($isbnResults['items']) && !$isForEnrichment && (!empty($title) || !empty($author))) {
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

            // Add URL to steps
            $status['steps'][] = [
                'name' => 'url_generation',
                'status' => 'success',
                'message' => "Generated URL for title/author search",
                'fetch_url' => $url
            ];

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
                'format' => $volumeInfo['printType'] ?? '',
                'series' => '',
                'awards' => '',
                'characters' => '',
                'settings' => '',
                'preview_link' => $volumeInfo['previewLink'] ?? '',
                'cover_url' => isset($volumeInfo['imageLinks']['thumbnail']) ? str_replace('http://', 'https://', $volumeInfo['imageLinks']['thumbnail']) : '',
                'rating' => $volumeInfo['averageRating'] ?? '',
                'rating_count' => $volumeInfo['ratingsCount'] ?? '',
                'review_count' => '',
                'maturity_rating' => $volumeInfo['maturityRating'] ?? '',
                'categories' => $categories, // ADD THE MISSING CATEGORIES!
                'source' => 'google_books'
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
function searchBooksByTitleAuthor($title, $author = '', $limit = 10) {
    try {
        $suggestions = [];
        $seenISBNs = [];

        // Try multiple search strategies to find more comprehensive results
        $searchStrategies = [];

        // Strategy 1: Exact intitle/inauthor search
        if (!empty($title) && !empty($author)) {
            $searchStrategies[] = "intitle:" . urlencode($title) . "+inauthor:" . urlencode($author);
        }

        // Strategy 2: General search with title and author
        if (!empty($title) && !empty($author)) {
            $searchStrategies[] = urlencode($title) . "+" . urlencode($author);
        }

        // Strategy 3: Title only search (more results)
        if (!empty($title)) {
            $searchStrategies[] = urlencode($title);
        }

        // Strategy 4: Try without common words
        if (!empty($title)) {
            $cleanTitle = preg_replace('/\b(the|a|an)\b/i', '', $title);
            $cleanTitle = trim(preg_replace('/\s+/', ' ', $cleanTitle));
            if (!empty($cleanTitle) && $cleanTitle !== $title) {
                $searchStrategies[] = urlencode($cleanTitle);
                if (!empty($author)) {
                    $searchStrategies[] = urlencode($cleanTitle) . "+" . urlencode($author);
                }
            }
        }

        foreach ($searchStrategies as $query) {
            if (count($suggestions) >= $limit) break;

            $url = "https://www.googleapis.com/books/v1/volumes?q=" . $query . "&maxResults=20"; // Get more results per strategy

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (count($suggestions) >= $limit) break;

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

                    // Skip if we've already seen this ISBN
                    $primaryISBN = $isbn13 ?: $isbn10;
                    if (!empty($primaryISBN) && isset($seenISBNs[$primaryISBN])) {
                        continue;
                    }
                    if (!empty($primaryISBN)) {
                        $seenISBNs[$primaryISBN] = true;
                    }

                    $suggestions[] = [
                        'title' => $volumeInfo['title'] ?? '',
                        'author' => implode(', ', $volumeInfo['authors'] ?? []),
                        'publisher' => $volumeInfo['publisher'] ?? '',
                        'publication_date' => $volumeInfo['publishedDate'] ?? '',
                        'isbn' => $isbn10,
                        'isbn13' => $isbn13,
                        'page_count' => $volumeInfo['pageCount'] ?? null,
                        'language' => $volumeInfo['language'] ?? '',
                        'format' => $volumeInfo['printType'] ?? '',
                        'cover_url' => isset($volumeInfo['imageLinks']['thumbnail']) ? str_replace('http://', 'https://', $volumeInfo['imageLinks']['thumbnail']) : '',
                        'preview_link' => $volumeInfo['previewLink'] ?? '',
                        'summary' => $volumeInfo['description'] ?? '',
                        'categories' => $volumeInfo['categories'] ?? [],
                        'maturity_rating' => $volumeInfo['maturityRating'] ?? '',
                        'reading_modes' => $volumeInfo['readingModes'] ?? [],
                        'content_version' => $volumeInfo['contentVersion'] ?? '',
                        'info_link' => $volumeInfo['infoLink'] ?? '',
                        'canonical_volume_link' => $volumeInfo['canonicalVolumeLink'] ?? '',
                        'web_reader_link' => $item['accessInfo']['webReaderLink'] ?? '',
                        'source' => 'google_books'
                    ];
                }
            }

            // Small delay between requests to be respectful
            usleep(100000); // 0.1 second
        }

        return $suggestions;
    } catch (Exception $e) {
        error_log("Error searching books by title/author: " . $e->getMessage());
        return [];
    }
}
