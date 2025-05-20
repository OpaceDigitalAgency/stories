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
 * @return array|null Book data or null if not found
 */
function fetchGoodreadsDataNew($isbn, $title, $author) {
    try {
        // Include the PHP-based Goodreads scraper
        require_once __DIR__ . '/goodreads/goodreads_scraper.php';

        // Start timer for performance tracking
        $startTime = microtime(true);

        // Initialize detailed status tracking
        $detailedStatus = [
            'status' => 'initializing',
            'message' => 'Starting Goodreads data fetch',
            'method' => 'php_scraper',
            'processing_time' => 0,
            'steps' => []
        ];

        // Add initialization step with detailed parameters
        $detailedStatus['steps'][] = [
            'name' => 'initialization',
            'status' => 'success',
            'message' => "Parameters: ISBN: '$isbn', Title: '$title', Author: '$author'"
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

        // Use the PHP-based Goodreads scraper
        $detailedStatus['steps'][] = [
            'name' => 'php_scraper',
            'status' => 'in_progress',
            'message' => "Using PHP-based Goodreads scraper"
        ];

        // Call the PHP scraper function
        $goodreadsStatus = getGoodreadsBookInfo($searchUrl);

        // Calculate processing time
        $processingTime = microtime(true) - $startTime;
        $goodreadsStatus['processing_time'] = round($processingTime, 2);

        // Update method
        $goodreadsStatus['method'] = 'php_scraper';

        // Merge steps from the scraper into our detailed status
        if (isset($goodreadsStatus['steps']) && is_array($goodreadsStatus['steps'])) {
            foreach ($goodreadsStatus['steps'] as $step) {
                $detailedStatus['steps'][] = $step;
            }
        }

        // Check if we have book data
        if (isset($goodreadsStatus['data']) && !empty($goodreadsStatus['data'])) {
            $jsonData = $goodreadsStatus['data'];

            // Log the full JSON data for debugging
            error_log("Goodreads JSON data: " . json_encode($jsonData));

            // Extract book details from JSON data
            // Make sure to clean any HTML tags from all fields
            $bookData = [
                'title' => strip_tags($jsonData['title'] ?? ''),
                'author' => strip_tags($jsonData['author'] ?? ''),
                'publisher' => strip_tags($jsonData['publisher'] ?? ''),
                'publication_date' => strip_tags($jsonData['published_date'] ?? ($jsonData['publication_date'] ?? '')),
                'page_count' => strip_tags($jsonData['pages'] ?? ($jsonData['page_count'] ?? '')),
                'isbn' => strip_tags($jsonData['isbn'] ?? ''),
                'isbn13' => strip_tags($jsonData['isbn13'] ?? ''),
                'language' => strip_tags($jsonData['language'] ?? ''),
                'format' => strip_tags($jsonData['format'] ?? ''),
                'series' => strip_tags($jsonData['series'] ?? ''),
                'awards' => strip_tags($jsonData['awards'] ?? ''),
                'characters' => is_array($jsonData['characters'] ?? null) ? array_map('strip_tags', $jsonData['characters']) : [],
                'settings' => is_array($jsonData['settings'] ?? null) ? array_map('strip_tags', $jsonData['settings']) : [],
                'preview_link' => $jsonData['preview_link'] ?? '',
                'cover_url' => $jsonData['cover_image'] ?? '',
                'rating' => strip_tags($jsonData['rating'] ?? ''),
                'rating_count' => strip_tags($jsonData['rating_count'] ?? ''),
                'review_count' => strip_tags($jsonData['review_count'] ?? ''),
                'maturity_rating' => strip_tags($jsonData['maturity_rating'] ?? '')
            ];

            // Log success for debugging
            $endTime = microtime(true);
            $totalTime = round($endTime - $startTime, 2);

            // Update detailed status with final information
            $detailedStatus['status'] = $goodreadsStatus['status'] ?? 'success';
            $detailedStatus['message'] = $goodreadsStatus['message'] ?? 'Successfully extracted data from Goodreads via PHP scraper';
            $detailedStatus['processing_time'] = $totalTime;

            // Add detailed status to book data
            $bookData['_status'] = $detailedStatus;

            return $bookData;
        } else {
            $detailedStatus['steps'][] = [
                'name' => 'php_scraper',
                'status' => 'error',
                'message' => "PHP scraper failed to extract book information"
            ];
        }

        // Return null with detailed status on Python script failure
        $detailedStatus['status'] = 'error';
        $detailedStatus['message'] = 'Failed to extract data from Goodreads';
        $detailedStatus['method'] = 'python_script';

        // Create empty book data with status
        $bookData = [
            '_status' => $detailedStatus
        ];

        return $bookData;
    } catch (Exception $e) {
        error_log("Error fetching Goodreads data: " . $e->getMessage());
        return [
            '_status' => [
                'status' => 'error',
                'message' => 'Exception while fetching Goodreads data: ' . $e->getMessage(),
                'method' => 'python_script'
            ]
        ];
    }
}
