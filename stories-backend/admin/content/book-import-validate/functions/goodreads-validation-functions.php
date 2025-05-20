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

        // Get the GoodreadsReviewFetcher instance from the global factory
        // The ReviewFetcherFactory is already included in validation-functions.php
        global $reviewFetcherFactory;
        if (!isset($reviewFetcherFactory)) {
            // If we have a database connection, use it
            if ($db) {
                $reviewFetcherFactory = new \Services\ReviewFetcher\ReviewFetcherFactory($db);
            } else {
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
                            $reviewFetcherFactory = new \Services\ReviewFetcher\ReviewFetcherFactory($db);
                            $dbConnected = true;
                            break;
                        }
                    }
                }

                // If we couldn't connect to the database, create a factory with a null DB connection
                // or try to create a new PDO connection directly
                if (!$dbConnected) {
                    try {
                        // Try to create a direct database connection
                        $directDb = new PDO(
                            'mysql:host=localhost;dbname=stories_db;charset=utf8mb4',
                            'stories_user',
                            '$tw1cac3*sOt',
                            [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            ]
                        );
                        $reviewFetcherFactory = new \Services\ReviewFetcher\ReviewFetcherFactory($directDb);
                    } catch (PDOException $e) {
                        // If direct connection fails, log the error and create a minimal PDO object
                        error_log("Database connection error in Goodreads validation: " . $e->getMessage());

                        // Create a minimal factory without trying to use the database
                        // We'll handle this case in the code below by checking if the fetcher is configured
                        $reviewFetcherFactory = new \Services\ReviewFetcher\ReviewFetcherFactory(new PDO('sqlite::memory:'));
                    }
                }
            }
        }
        $goodreadsFetcher = $reviewFetcherFactory->getFetcher(1); // 1 is the Goodreads source ID

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

        // Set options for the fetcher
        $options = [
            'timeout' => 30, // Longer timeout for validation
            'maxPages' => 1,
            'limit' => 1
        ];

        try {
            // Use the fetchReviewsByISBN method to get book data
            // This will automatically find the book URL and extract book details
            $response = $goodreadsFetcher->fetchReviewsByISBN($isbn, 1, $options);

            // Calculate processing time
            $processingTime = microtime(true) - $startTime;

            if (empty($response)) {
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

            // Success - we have book data
            $detailedStatus['steps'][] = [
                'name' => 'review_fetcher',
                'status' => 'success',
                'message' => "Successfully fetched book data from Goodreads"
            ];

            // Get the book metadata from the first review
            $bookMetadata = $response[0]['book_metadata'] ?? [];

            if (empty($bookMetadata)) {
                $detailedStatus['steps'][] = [
                    'name' => 'data_extraction',
                    'status' => 'error',
                    'message' => "No book metadata found in response"
                ];

                $detailedStatus['status'] = 'error';
                $detailedStatus['message'] = 'No book metadata found in Goodreads response';
                $detailedStatus['processing_time'] = round($processingTime, 2);

                return [
                    '_status' => $detailedStatus
                ];
            }

            // Extract book details from metadata
            $bookData = [
                'title' => strip_tags($bookMetadata['title'] ?? ''),
                'author' => strip_tags($bookMetadata['author'] ?? ''),
                'publisher' => strip_tags($bookMetadata['publisher'] ?? ''),
                'publication_date' => strip_tags($bookMetadata['publication_date'] ?? ''),
                'page_count' => strip_tags($bookMetadata['page_count'] ?? ''),
                'isbn' => strip_tags($bookMetadata['isbn'] ?? $isbn),
                'isbn13' => strip_tags($bookMetadata['isbn13'] ?? ''),
                'language' => strip_tags($bookMetadata['language'] ?? ''),
                'format' => strip_tags($bookMetadata['format'] ?? ''),
                'series' => strip_tags($bookMetadata['series'] ?? ''),
                'description' => strip_tags($bookMetadata['description'] ?? ''),
                'cover_url' => $bookMetadata['cover_url'] ?? '',
                'rating' => strip_tags($bookMetadata['average_rating'] ?? ''),
                'rating_count' => strip_tags($bookMetadata['ratings_count'] ?? ''),
                'review_count' => strip_tags($bookMetadata['reviews_count'] ?? ''),
                'awards' => $bookMetadata['awards'] ?? [],
                'characters' => $bookMetadata['characters'] ?? [],
                'settings' => $bookMetadata['settings'] ?? [],
                'genres' => $bookMetadata['genres'] ?? []
            ];

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
