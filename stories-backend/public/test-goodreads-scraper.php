<?php
/**
 * Test Goodreads Review Scraper (Web Version)
 *
 * This script tests the Goodreads review scraper directly, bypassing the admin interface.
 * It can be used to verify that the VPS-based scraper is working correctly.
 *
 * Usage:
 * /test-goodreads-scraper.php?isbn=9780007416851&limit=20
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to plain text for better readability
header('Content-Type: text/plain');

// Include required files
require_once __DIR__ . '/../services/ReviewFetcher/ReviewFetcherInterface.php';
require_once __DIR__ . '/../services/ReviewFetcher/AbstractReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/GoodreadsReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/ReviewFetcherFactory.php';

// Include database connection
require_once __DIR__ . '/../admin/includes/db-connect.php';

try {
    // Get Goodreads source ID
    $stmt = $db->query("SELECT id FROM review_sources WHERE name = 'Goodreads' LIMIT 1");
    $sourceId = $stmt->fetchColumn();

    if (!$sourceId) {
        die("Error: Goodreads review source not found in the database.\n");
    }

    // Get ISBN from query parameters
    $isbn = $_GET['isbn'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

    if (!$isbn) {
        die("Usage: /test-goodreads-scraper.php?isbn=9780007416851&limit=20\n");
    }

    echo "Testing Goodreads Review Scraper\n";
    echo "-------------------------------\n";
    echo "ISBN: $isbn\n";
    echo "Limit: $limit\n";
    echo "Source ID: $sourceId\n\n";

    // Create Goodreads review fetcher
    $fetcher = new \Services\ReviewFetcher\GoodreadsReviewFetcher($db, $sourceId);

    // Fetch reviews
    echo "Fetching reviews...\n";
    $startTime = microtime(true);
    $reviews = $fetcher->fetchReviewsByISBN($isbn, $limit);
    $endTime = microtime(true);

    // Display results
    $duration = round($endTime - $startTime, 2);
    echo "\nResults:\n";
    echo "Duration: {$duration} seconds\n";
    echo "Reviews found: " . count($reviews) . "\n\n";

    if (empty($reviews)) {
        echo "No reviews found. Error: " . $fetcher->getLastError() . "\n";
    } else {
        // Display review summary
        echo "Review Summary:\n";
        echo "---------------\n";

        foreach ($reviews as $index => $review) {
            $reviewerName = $review['reviewer_name'];
            $rating = $review['rating_value'];
            $date = $review['review_date'];
            $textSnippet = substr($review['review_text'], 0, 100) . (strlen($review['review_text']) > 100 ? '...' : '');

            echo ($index + 1) . ". {$reviewerName} ({$rating}/5) - {$date}\n";
            echo "   {$textSnippet}\n\n";
        }

        // Check if we got reviews from the VPS scraper
        $debugDir = __DIR__ . '/../services/ReviewFetcher/debug';
        $logFile = "{$debugDir}/goodreads-log.txt";

        if (file_exists($logFile)) {
            echo "Log File Analysis:\n";
            echo "-----------------\n";

            $logContent = file_get_contents($logFile);

            // Check for VPS scraper success message
            if (strpos($logContent, '[VPS-Scraper-Success]') !== false) {
                echo "✅ VPS Scraper was used successfully!\n";

                // Extract the number of reviews found by the VPS scraper
                if (preg_match('/\[VPS-Scraper-Success\] Found (\d+) reviews/', $logContent, $matches)) {
                    echo "   Found {$matches[1]} reviews using VPS Scraper\n";
                }
            } else {
                echo "❌ VPS Scraper was not used or failed.\n";

                // Check which methods were attempted
                if (strpos($logContent, '[VPS-Scraper-Goodreads]') !== false) {
                    echo "   VPS Scraper was attempted but may have failed\n";
                }
                if (strpos($logContent, 'Trying Puppeteer') !== false) {
                    echo "   Puppeteer was attempted\n";
                }
                if (strpos($logContent, 'Falling back to regex-based scraping') !== false) {
                    echo "   Direct scraping was attempted\n";
                }
            }

            // Check for health check results
            if (strpos($logContent, 'VPS Headless Browser API is reachable') !== false) {
                echo "✅ VPS Headless Browser API is reachable\n";
            } else if (strpos($logContent, 'VPS Headless Browser API is not reachable') !== false) {
                echo "❌ VPS Headless Browser API is NOT reachable\n";
                
                // Extract HTTP code if available
                if (preg_match('/VPS Headless Browser API is not reachable: HTTP (\d+)/', $logContent, $matches)) {
                    echo "   HTTP Status Code: {$matches[1]}\n";
                }
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
