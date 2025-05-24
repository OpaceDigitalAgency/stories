<?php
/**
 * Open Library Validation Functions
 *
 * This file contains functions for fetching and validating book data from Open Library.
 */

/**
 * Fetch data from Open Library
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @return array|null Book data or null if not found
 */
function fetchOpenLibraryDataNew($isbn, $title, $author) {
    try {
        // Start timer for performance tracking
        $startTime = microtime(true);

        // Initialize status tracking
        $status = [
            'status' => 'initializing',
            'message' => 'Starting Open Library data fetch',
            'method' => 'api',
            'processing_time' => 0,
            'steps' => []
        ];

        // Log that we're starting Open Library fetch
        error_log("Starting Open Library data fetch for ISBN: $isbn");

        // Try ISBN search first
        $url = "https://openlibrary.org/api/books?bibkeys=ISBN:" . urlencode($isbn) . "&format=json&jscmd=data";

        // Add step for URL generation
        $status['steps'][] = [
            'name' => 'url_generation',
            'status' => 'success',
            'message' => "Generated URL: $url",
            'fetch_url' => $url
        ];

        // Make the request
        $status['steps'][] = [
            'name' => 'api_request',
            'status' => 'in_progress',
            'message' => "Making request to Open Library API"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Update step status based on response
        if ($response === false) {
            $status['steps'][count($status['steps']) - 1] = [
                'name' => 'api_request',
                'status' => 'error',
                'message' => "cURL error: $curlError",
                'fetch_url' => $url
            ];

            // Return error status
            $status['status'] = 'error';
            $status['message'] = "Failed to connect to Open Library API: $curlError";
            $status['processing_time'] = round(microtime(true) - $startTime, 2);
            return ['_status' => $status];
        } else if ($httpCode !== 200) {
            $status['steps'][count($status['steps']) - 1] = [
                'name' => 'api_request',
                'status' => 'error',
                'message' => "HTTP error: $httpCode",
                'fetch_url' => $url
            ];

            // Return error status
            $status['status'] = 'error';
            $status['message'] = "Open Library API returned HTTP error: $httpCode";
            $status['processing_time'] = round(microtime(true) - $startTime, 2);
            return ['_status' => $status];
        } else {
            $status['steps'][count($status['steps']) - 1] = [
                'name' => 'api_request',
                'status' => 'success',
                'message' => "API request successful (HTTP 200)",
                'fetch_url' => $url
            ];
        }

        // Try to decode JSON response
        $data = json_decode($response, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $jsonError = json_last_error_msg();
            $status['steps'][] = [
                'name' => 'json_parsing',
                'status' => 'error',
                'message' => "JSON parsing error: $jsonError"
            ];

            // Return error status
            $status['status'] = 'error';
            $status['message'] = "Failed to parse Open Library API response: $jsonError";
            $status['processing_time'] = round(microtime(true) - $startTime, 2);
            return ['_status' => $status];
        } else {
            $status['steps'][] = [
                'name' => 'json_parsing',
                'status' => 'success',
                'message' => "JSON parsed successfully"
            ];
        }
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

            // Add step for fallback URL generation
            $status['steps'][] = [
                'name' => 'fallback_url_generation',
                'status' => 'success',
                'message' => "Generated fallback URL: $url",
                'fetch_url' => $url
            ];

            $status['steps'][] = [
                'name' => 'fallback_api_request',
                'status' => 'in_progress',
                'message' => "Making fallback request to Open Library search API"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Update step status based on response
            if ($response === false) {
                $status['steps'][count($status['steps']) - 1] = [
                    'name' => 'fallback_api_request',
                    'status' => 'error',
                    'message' => "cURL error: $curlError",
                    'fetch_url' => $url
                ];

                // Return error status
                $status['status'] = 'error';
                $status['message'] = "Failed to connect to Open Library search API: $curlError";
                $status['processing_time'] = round(microtime(true) - $startTime, 2);
                return ['_status' => $status];
            } else if ($httpCode !== 200) {
                $status['steps'][count($status['steps']) - 1] = [
                    'name' => 'fallback_api_request',
                    'status' => 'error',
                    'message' => "HTTP error: $httpCode",
                    'fetch_url' => $url
                ];

                // Return error status
                $status['status'] = 'error';
                $status['message'] = "Open Library search API returned HTTP error: $httpCode";
                $status['processing_time'] = round(microtime(true) - $startTime, 2);
                return ['_status' => $status];
            } else {
                $status['steps'][count($status['steps']) - 1] = [
                    'name' => 'fallback_api_request',
                    'status' => 'success',
                    'message' => "Search API request successful (HTTP 200)",
                    'fetch_url' => $url
                ];
            }

            // Try to decode JSON response
            $searchData = json_decode($response, true);
            if ($searchData === null && json_last_error() !== JSON_ERROR_NONE) {
                $jsonError = json_last_error_msg();
                $status['steps'][] = [
                    'name' => 'fallback_json_parsing',
                    'status' => 'error',
                    'message' => "JSON parsing error: $jsonError"
                ];

                // Return error status
                $status['status'] = 'error';
                $status['message'] = "Failed to parse Open Library search API response: $jsonError";
                $status['processing_time'] = round(microtime(true) - $startTime, 2);
                return ['_status' => $status];
            } else {
                $status['steps'][] = [
                    'name' => 'fallback_json_parsing',
                    'status' => 'success',
                    'message' => "Search JSON parsed successfully"
                ];
            }

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

                // Get Internet Archive ID if available
                $iaId = !empty($bookInfo['ia']) ? $bookInfo['ia'][0] : '';

                $bookData = [
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
                    'preview_link' => $iaId ? "https://archive.org/details/$iaId" : '',
                    'cover_url' => $coverUrl,
                    'rating' => '',
                    'rating_count' => '',
                    'review_count' => '',
                    'maturity_rating' => '',
                    'internet_archive_id' => $iaId
                ];

                // Add status information
                $endTime = microtime(true);
                $totalTime = round($endTime - $startTime, 2);

                $status['status'] = 'success';
                $status['message'] = 'Successfully extracted data from Open Library search';
                $status['processing_time'] = $totalTime;
                $status['steps'] = [
                    [
                        'name' => 'search_request',
                        'status' => 'success',
                        'message' => "Search request successful for title/author"
                    ],
                    [
                        'name' => 'data_extraction',
                        'status' => 'success',
                        'message' => "Successfully extracted book data from search results"
                    ]
                ];

                $bookData['_status'] = $status;
                return $bookData;
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

            // Get Internet Archive ID if available
            $iaId = '';
            if (!empty($bookInfo['ebooks']) && !empty($bookInfo['ebooks'][0]['preview_url'])) {
                $previewUrl = $bookInfo['ebooks'][0]['preview_url'];
                if (preg_match('/archive\.org\/details\/([^\/]+)/', $previewUrl, $matches)) {
                    $iaId = $matches[1];
                }
            }

            $bookData = [
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
                'preview_link' => !empty($bookInfo['ebooks']) ? $bookInfo['ebooks'][0]['preview_url'] : '',
                'cover_url' => !empty($bookInfo['cover']) ? $bookInfo['cover']['medium'] : '',
                'rating' => '',
                'rating_count' => '',
                'review_count' => '',
                'maturity_rating' => '',
                'internet_archive_id' => $iaId
            ];

            // Add status information
            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            $status['status'] = 'success';
            $status['message'] = 'Successfully extracted data from Open Library API';
            $status['processing_time'] = $totalTime;
            $status['steps'] = [
                [
                    'name' => 'api_request',
                    'status' => 'success',
                    'message' => "API request successful for ISBN: $isbn"
                ],
                [
                    'name' => 'data_extraction',
                    'status' => 'success',
                    'message' => "Successfully extracted book data from API response"
                ]
            ];

            $bookData['_status'] = $status;
            return $bookData;
        }

        // Log failure for debugging
        error_log("No book found on Open Library for ISBN: $isbn");

        // Return failure status
        $status['status'] = 'error';
        $status['message'] = 'No book found on Open Library';
        $status['processing_time'] = round(microtime(true) - $startTime, 2);
        $status['steps'] = [
            [
                'name' => 'search_attempt',
                'status' => 'error',
                'message' => "No results found for ISBN: $isbn or title/author search"
            ]
        ];

        return ['_status' => $status];
    } catch (Exception $e) {
        error_log("Error fetching Open Library data: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate ISBN using Open Library
 *
 * @param string $isbn The ISBN to validate
 * @return bool True if valid, false otherwise
 */
function validateIsbnWithOpenLibrary($isbn) {
    try {
        // Clean ISBN
        $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

        // Check if ISBN is valid format
        if (strlen($cleanIsbn) !== 10 && strlen($cleanIsbn) !== 13) {
            return false;
        }

        // Try ISBN search
        $url = "https://openlibrary.org/api/books?bibkeys=ISBN:" . urlencode($cleanIsbn) . "&format=json";

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
        $key = "ISBN:$cleanIsbn";

        // Check if we found the book
        return !empty($data[$key]);
    } catch (Exception $e) {
        error_log("Error validating ISBN with Open Library: " . $e->getMessage());
        return false;
    }
}

/**
 * Search for books by title and author using Open Library
 *
 * @param string $title The book title
 * @param string $author The book author
 * @param int $limit Maximum number of results to return
 * @return array List of book suggestions
 */
function searchOpenLibraryByTitleAuthor($title, $author = '', $limit = 10) {
    try {
        $suggestions = [];
        $seenISBNs = [];

        // Try multiple search strategies
        $searchStrategies = [];

        // Strategy 1: Title and author together
        if (!empty($title) && !empty($author)) {
            $searchStrategies[] = "title=" . urlencode($title) . "&author=" . urlencode($author);
        }

        // Strategy 2: Title only (broader search)
        if (!empty($title)) {
            $searchStrategies[] = "title=" . urlencode($title);
        }

        // Strategy 3: General search query
        if (!empty($title) && !empty($author)) {
            $searchStrategies[] = "q=" . urlencode($title . " " . $author);
        } elseif (!empty($title)) {
            $searchStrategies[] = "q=" . urlencode($title);
        }

        foreach ($searchStrategies as $query) {
            if (count($suggestions) >= $limit) break;

            $url = "https://openlibrary.org/search.json?" . $query . "&limit=20"; // Get more results per strategy

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            if (!empty($data['docs'])) {
                foreach ($data['docs'] as $doc) {
                    if (count($suggestions) >= $limit) break;

                    // Extract ISBNs
                    $isbn10 = '';
                    $isbn13 = '';
                    if (!empty($doc['isbn'])) {
                        foreach ($doc['isbn'] as $isbnValue) {
                            $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbnValue);
                            if (strlen($cleanIsbn) == 10) {
                                $isbn10 = $isbnValue;
                            } else if (strlen($cleanIsbn) == 13) {
                                $isbn13 = $isbnValue;
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

                    $coverUrl = '';
                    if (!empty($doc['cover_i'])) {
                        $coverUrl = "https://covers.openlibrary.org/b/id/" . $doc['cover_i'] . "-M.jpg";
                    }

                    $suggestions[] = [
                        'title' => $doc['title'] ?? '',
                        'author' => !empty($doc['author_name']) ? implode(', ', $doc['author_name']) : '',
                        'publisher' => !empty($doc['publisher']) ? implode(', ', $doc['publisher']) : '',
                        'publication_date' => !empty($doc['publish_date']) ? $doc['publish_date'][0] : '',
                        'isbn' => $isbn10,
                        'isbn13' => $isbn13,
                        'format' => !empty($doc['type']) ? $doc['type'] : '',
                        'cover_url' => $coverUrl,
                        'preview_link' => !empty($doc['ia']) ? "https://archive.org/details/" . $doc['ia'][0] : '',
                        'source' => 'open_library'
                    ];
                }
            }

            // Small delay between requests
            usleep(100000); // 0.1 second
        }

        return $suggestions;
    } catch (Exception $e) {
        error_log("Error searching Open Library by title/author: " . $e->getMessage());
        return [];
    }
}
