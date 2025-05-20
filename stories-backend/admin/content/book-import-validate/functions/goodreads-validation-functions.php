<?php
/**
 * Goodreads Validation Functions
 *
 * This file contains functions for fetching and validating book data from Goodreads.
 */

/**
 * Fetch data from Goodreads
 *
 * @param string $isbn The ISBN to search for
 * @param string $title The book title
 * @param string $author The book author
 * @param PDO|null $db Database connection (optional)
 * @return array|null Book data or null if not found
 */
function fetchGoodreadsDataNew($isbn, $title, $author, $db = null) {
    try {
        // Start timer for performance tracking
        $startTime = microtime(true);

        // Initialize detailed status tracking
        $detailedStatus = [
            'status' => 'initializing',
            'message' => 'Starting Goodreads data fetch',
            'method' => 'review_fetcher',
            'processing_time' => 0,
            'steps' => []
        ];

        // Add initialization step with detailed parameters
        $detailedStatus['steps'][] = [
            'name' => 'initialization',
            'status' => 'success',
            'message' => "Parameters: ISBN: '$isbn', Title: '$title', Author: '$author'"
        ];

        // Get a database connection
        if (!$db) {
            // Try multiple possible paths for db-connect.php
            $possiblePaths = [
                __DIR__ . '/../../../../db-connect.php',
                __DIR__ . '/../../../../includes/db-connect.php',
                __DIR__ . '/../../../../admin/includes/db-connect.php',
                __DIR__ . '/../../../includes/db-connect.php',
                __DIR__ . '/../../../db-connect.php'
            ];

            $dbConnected = false;
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    global $db;
                    if (isset($db) && $db instanceof PDO) {
                        $dbConnected = true;
                        break;
                    }
                }
            }

            // If we couldn't connect to the database, try to create a direct connection
            if (!$dbConnected) {
                try {
                    // Try to create a direct database connection
                    $db = new PDO(
                        'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                        'stories_user',
                        '$tw1cac3*sOt',
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]
                    );
                } catch (PDOException $e) {
                    // If direct connection fails, log the error and create a minimal PDO object
                    error_log("Database connection error in Goodreads validation: " . $e->getMessage());
                    $db = new PDO('sqlite::memory:');
                }
            }
        }

        // Make sure we have the required classes
        require_once __DIR__ . '/../../../../services/ReviewFetcher/ReviewFetcherInterface.php';
        require_once __DIR__ . '/../../../../services/ReviewFetcher/AbstractReviewFetcher.php';
        require_once __DIR__ . '/../../../../services/ReviewFetcher/GoodreadsReviewFetcher.php';

        // Create a GoodreadsReviewFetcher instance directly
        $goodreadsFetcher = new \Services\ReviewFetcher\GoodreadsReviewFetcher($db, 1); // 1 is the Goodreads source ID

        // Check if we got the correct fetcher type
        if (!$goodreadsFetcher || !$goodreadsFetcher->isConfigured()) {
            $detailedStatus['steps'][] = [
                'name' => 'fetcher_initialization',
                'status' => 'error',
                'message' => "Failed to initialize Goodreads review fetcher"
            ];

            $detailedStatus['status'] = 'error';
            $detailedStatus['message'] = 'Failed to initialize Goodreads review fetcher';

            return [
                '_status' => $detailedStatus
            ];
        }

        // We're creating a GoodreadsReviewFetcher directly, so no need to check the class type

        $detailedStatus['steps'][] = [
            'name' => 'fetcher_initialization',
            'status' => 'success',
            'message' => "Successfully initialized Goodreads review fetcher"
        ];

        // Try to build a direct book URL if possible
        $searchUrl = "";
        if (!empty($title) && !empty($author)) {
            // Format the URL with title and author for better results
            $titleSlug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($title));
            $titleSlug = trim($titleSlug, '_');
            $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($title . " " . $author);

            $detailedStatus['steps'][] = [
                'name' => 'url_generation',
                'status' => 'success',
                'message' => "Generated URL from title/author: $searchUrl"
            ];
        } else {
            // Fallback to search URL with ISBN
            $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($isbn);

            $detailedStatus['steps'][] = [
                'name' => 'url_generation',
                'status' => 'info',
                'message' => "Generated URL from ISBN: $searchUrl"
            ];
        }

        // Use the GoodreadsReviewFetcher to find the book
        $detailedStatus['steps'][] = [
            'name' => 'review_fetcher',
            'status' => 'in_progress',
            'message' => "Using GoodreadsReviewFetcher to find book"
        ];

        // Make sure we're using the correct API key
        putenv('HEADLESS_BROWSER_API_KEY=stories-scraper-api-key-2023');

        // Set options for the fetcher
        $options = [
            'timeout' => 30, // Longer timeout for validation
            'maxPages' => 1,
            'limit' => 1,
            'validation_mode' => true, // Flag to indicate we're just validating, not fetching reviews
            'skip_db_check' => true, // Skip database check for reviews
            'force' => true, // Force refresh to avoid caching issues
            'cache_ttl' => 0 // Don't cache results
        ];

        try {
            // Determine whether to use ISBN or title/author for search
            if (!empty($isbn)) {
                // Use the fetchReviewsByISBN method to get book data
                $detailedStatus['steps'][] = [
                    'name' => 'search_method',
                    'status' => 'info',
                    'message' => "Searching by ISBN: $isbn"
                ];
                $response = $goodreadsFetcher->fetchReviewsByISBN($isbn, 1, $options);
            } else if (!empty($title) && !empty($author)) {
                // For validation without ISBN, we need to search by title and author
                $detailedStatus['steps'][] = [
                    'name' => 'search_method',
                    'status' => 'info',
                    'message' => "Searching by title and author: $title by $author"
                ];

                // Create a search URL for Goodreads
                $searchUrl = "https://www.goodreads.com/search?q=" . urlencode($title . " " . $author);

                // Make a request to the search URL
                $ch = curl_init($searchUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                $searchResponse = curl_exec($ch);
                curl_close($ch);

                // Extract the first book URL from the search results
                $bookUrl = null;
                if (preg_match('/<a[^>]+href="(\/book\/show\/[^"]+)"[^>]*>/i', $searchResponse, $matches)) {
                    $bookUrl = 'https://www.goodreads.com' . html_entity_decode($matches[1]);
                    $detailedStatus['steps'][] = [
                        'name' => 'book_url',
                        'status' => 'success',
                        'message' => "Found book URL: $bookUrl"
                    ];

                    // Make sure we're not using a reviews URL
                    if (strpos($bookUrl, '/reviews') !== false) {
                        $originalUrl = $bookUrl;
                        $bookUrl = str_replace('/reviews', '', $bookUrl);
                        error_log("⚠️ Found reviews URL, converting to main book URL: {$bookUrl}");
                    }

                    // Now get the book details using the URL
                    // Check if the method exists (it should, since we checked the class type earlier)
                    if (method_exists($goodreadsFetcher, 'getBookDetails')) {
                        $bookDetails = $goodreadsFetcher->getBookDetails($bookUrl);

                        if ($bookDetails) {
                            // Create a response similar to fetchReviewsByISBN
                            $response = [
                                [
                                    'source_id' => 1,
                                    'reviewer_name' => 'Goodreads Aggregate',
                                    'book_metadata' => $bookDetails
                                ]
                            ];
                        } else {
                            throw new Exception("Failed to get book details from URL: $bookUrl");
                        }
                    } else {
                        // Fallback if the method doesn't exist
                        throw new Exception("The getBookDetails method is not available in " . get_class($goodreadsFetcher));
                    }
                } else {
                    throw new Exception("No book found for title: $title by author: $author");
                }
            } else {
                throw new Exception("No ISBN, title, or author provided for search");
            }

            // Calculate processing time
            $processingTime = microtime(true) - $startTime;

            // Debug the response
            error_log("Goodreads response: " . print_r($response, true));

            if (empty($response) || !is_array($response)) {
                $error = $goodreadsFetcher->getLastError();
                $detailedStatus['steps'][] = [
                    'name' => 'review_fetcher',
                    'status' => 'error',
                    'message' => "Failed to fetch book data: " . ($error ?: "Unknown error")
                ];

                $detailedStatus['status'] = 'error';
                $detailedStatus['message'] = 'Failed to fetch book data from Goodreads';
                $detailedStatus['processing_time'] = round($processingTime, 2);

                return [
                    '_status' => $detailedStatus
                ];
            }

            // Check if we have a book_metadata object directly in the response
            if (isset($response['book_metadata'])) {
                $bookMetadata = $response['book_metadata'];
                $detailedStatus['steps'][] = [
                    'name' => 'data_extraction',
                    'status' => 'success',
                    'message' => "Found book metadata directly in response"
                ];
            }

            // Success - we have book data
            $detailedStatus['steps'][] = [
                'name' => 'review_fetcher',
                'status' => 'success',
                'message' => "Successfully fetched book data from Goodreads"
            ];

            // Get the book metadata from the response
            if (!isset($bookMetadata)) {
                // If we didn't find book_metadata directly in the response, check if it's in the first review
                if (isset($response[0]) && isset($response[0]['book_metadata'])) {
                    $bookMetadata = $response[0]['book_metadata'] ?? [];
                    $detailedStatus['steps'][] = [
                        'name' => 'data_extraction',
                        'status' => 'success',
                        'message' => "Found book metadata in first review"
                    ];
                } else {
                    // Try to extract book metadata from the response structure
                    $bookMetadata = [];

                    // Check if this is an error response
                    if (isset($response[0]['reviewer_name']) && $response[0]['reviewer_name'] === 'Error') {
                        $errorMessage = $response[0]['review_text'] ?? 'Unknown error';
                        $detailedStatus['steps'][] = [
                            'name' => 'data_extraction',
                            'status' => 'error',
                            'message' => "Error from Goodreads: $errorMessage"
                        ];

                        $detailedStatus['status'] = 'error';
                        $detailedStatus['message'] = "Error from Goodreads: $errorMessage";
                        $detailedStatus['processing_time'] = round($processingTime, 2);

                        // Return a minimal set of data with the error status
                        return [
                            'title' => $bookMetadata['title'] ?? 'Unknown',
                            'author' => $bookMetadata['author'] ?? 'Unknown',
                            'isbn' => $bookMetadata['isbn'] ?? $isbn,
                            'error' => $errorMessage,
                            '_status' => $detailedStatus
                        ];
                    }

                    // If we have a book object in the response
                    if (isset($response['book'])) {
                        $bookMetadata = $response['book'];
                        $detailedStatus['steps'][] = [
                            'name' => 'data_extraction',
                            'status' => 'success',
                            'message' => "Found book data in 'book' property"
                        ];
                    }
                    // If we have reviews array with book info
                    else if (isset($response['reviews']) && !empty($response['reviews'][0]['book_metadata'])) {
                        $bookMetadata = $response['reviews'][0]['book_metadata'];
                        $detailedStatus['steps'][] = [
                            'name' => 'data_extraction',
                            'status' => 'success',
                            'message' => "Found book metadata in reviews array"
                        ];
                    }
                }
            }

            // If we still don't have book metadata, log an error
            if (empty($bookMetadata)) {
                $detailedStatus['steps'][] = [
                    'name' => 'data_extraction',
                    'status' => 'error',
                    'message' => "No book metadata found in response"
                ];

                $detailedStatus['status'] = 'error';
                $detailedStatus['message'] = 'No book metadata found in Goodreads response';
                $detailedStatus['processing_time'] = round($processingTime, 2);

                // Log the full response for debugging
                error_log("Full Goodreads response structure: " . json_encode($response));

                return [
                    '_status' => $detailedStatus
                ];
            }

            // Extract book details from metadata
            $bookData = [];

            // Helper function to clean HTML tags and trim whitespace
            $cleanField = function($value) {
                if (is_string($value)) {
                    return trim(strip_tags($value));
                }
                return $value;
            };

            // Map fields with proper cleaning
            $fieldMappings = [
                'title' => ['title', 'book_title'],
                'author' => ['author', 'authors', 'book_author'],
                'publisher' => ['publisher', 'book_publisher'],
                'publication_date' => ['publication_date', 'published_date', 'publish_date'],
                'page_count' => ['page_count', 'num_pages', 'pages'],
                'isbn' => ['isbn', 'isbn10'],
                'isbn13' => ['isbn13'],
                'language' => ['language'],
                'format' => ['format', 'book_format'],
                'series' => ['series'],
                'description' => ['description'],
                'cover_url' => ['cover_url', 'image_url', 'cover_image_url'],
                'rating' => ['average_rating', 'rating'],
                'rating_count' => ['ratings_count', 'rating_count'],
                'review_count' => ['reviews_count', 'review_count'],
                'awards' => ['awards'],
                'characters' => ['characters'],
                'settings' => ['settings'],
                'genres' => ['genres', 'genre', 'shelves']
            ];

            // Process each field with its possible mappings
            foreach ($fieldMappings as $outputField => $possibleFields) {
                foreach ($possibleFields as $field) {
                    if (isset($bookMetadata[$field]) && !empty($bookMetadata[$field])) {
                        $bookData[$outputField] = $outputField === 'cover_url' ?
                            $bookMetadata[$field] : $cleanField($bookMetadata[$field]);
                        break;
                    }
                }
            }

            // Ensure we have the ISBN if it wasn't found in the metadata
            if (empty($bookData['isbn']) && !empty($isbn)) {
                $bookData['isbn'] = $isbn;
            }

            // Set defaults for missing fields
            $defaults = [
                'title' => 'Unknown',
                'author' => 'Unknown',
                'publisher' => '',
                'publication_date' => '',
                'page_count' => '',
                'isbn' => $isbn,
                'isbn13' => '',
                'language' => '',
                'format' => '',
                'series' => '',
                'description' => '',
                'cover_url' => '',
                'rating' => '',
                'rating_count' => '',
                'review_count' => '',
                'awards' => [],
                'characters' => [],
                'settings' => [],
                'genres' => []
            ];

            // Fill in any missing fields with defaults
            foreach ($defaults as $field => $defaultValue) {
                if (!isset($bookData[$field])) {
                    $bookData[$field] = $defaultValue;
                }
            }

            // Log success for debugging
            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            // Update detailed status with final information
            $detailedStatus['status'] = 'success';
            $detailedStatus['message'] = 'Successfully extracted data from Goodreads via ReviewFetcher';
            $detailedStatus['processing_time'] = $totalTime;

            // Add detailed status to book data
            $bookData['_status'] = $detailedStatus;

            return $bookData;
        } catch (\Exception $e) {
            $detailedStatus['steps'][] = [
                'name' => 'review_fetcher',
                'status' => 'error',
                'message' => "Exception: " . $e->getMessage()
            ];

            $detailedStatus['status'] = 'error';
            $detailedStatus['message'] = 'Exception while fetching data from Goodreads: ' . $e->getMessage();
            $detailedStatus['processing_time'] = round(microtime(true) - $startTime, 2);

            return [
                '_status' => $detailedStatus
            ];
        }
    } catch (Exception $e) {
        error_log("Error fetching Goodreads data: " . $e->getMessage());
        return [
            '_status' => [
                'status' => 'error',
                'message' => 'Exception while fetching Goodreads data: ' . $e->getMessage(),
                'method' => 'review_fetcher'
            ]
        ];
    }
}
