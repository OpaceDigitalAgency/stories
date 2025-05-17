<?php
/**
 * Test Goodreads Review Scraper
 *
 * This script tests the Goodreads review scraper directly, bypassing the admin interface.
 * It can be used to verify that the VPS-based scraper is working correctly.
 *
 * Usage:
 * php test-goodreads-scraper.php <isbn> <limit>
 *
 * Example:
 * php test-goodreads-scraper.php 9780007416851 50
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../services/ReviewFetcher/ReviewFetcherInterface.php';
require_once __DIR__ . '/../services/ReviewFetcher/AbstractReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/GoodreadsReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/ReviewFetcherFactory.php';

// Include database connection
$dbConfig = require_once __DIR__ . '/../config/database.php';

try {
    // Create PDO connection
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
    $db = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Get Goodreads source ID
    $stmt = $db->query("SELECT id FROM review_sources WHERE name = 'Goodreads' LIMIT 1");
    $sourceId = $stmt->fetchColumn();

    if (!$sourceId) {
        die("Error: Goodreads review source not found in the database.\n");
    }

    // Get ISBN from command line arguments
    $isbn = $argv[1] ?? null;
    $limit = isset($argv[2]) ? (int)$argv[2] : 50;

    if (!$isbn) {
        die("Usage: php test-goodreads-scraper.php <isbn> <limit>\n");
    }

    echo "Testing Goodreads Review Scraper\n";
    echo "-------------------------------\n";
    echo "ISBN: $isbn\n";
    echo "Limit: $limit\n";
    echo "Source ID: $sourceId\n\n";

    // Create Goodreads review fetcher
    $fetcher = new \Services\ReviewFetcher\GoodreadsReviewFetcher($db, $sourceId);

    // Force VPS Headless Browser to be used
    $reflectionClass = new ReflectionClass('\\Services\\ReviewFetcher\\GoodreadsReviewFetcher');
    $property = $reflectionClass->getProperty('useVpsHeadlessBrowser');
    $property->setAccessible(true);
    $property->setValue($fetcher, true);

    echo "Forced VPS Headless Browser to be used\n";

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

                // Check if we got more than 30 reviews (the limit of direct scraping)
                if (count($reviews) > 30) {
                    echo "   ✅ Got " . count($reviews) . " reviews, which is more than the 30 limit of direct scraping!\n";
                }
            } else {
                echo "❌ VPS Scraper was not used or failed.\n";

                // Check which methods were attempted
                if (strpos($logContent, '[VPS-Scraper-Goodreads]') !== false) {
                    echo "   VPS Scraper was attempted but may have failed\n";
                }
                if (strpos($logContent, 'Trying Puppeteer') !== false) {
                    echo "   Netlify Puppeteer API was attempted\n";
                }
                if (strpos($logContent, 'falling back to regex-based scraping') !== false) {
                    echo "   Direct regex-based scraping was attempted\n";
                }
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
