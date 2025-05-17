<?php
/**
 * Test All Review Scrapers
 * 
 * This script tests all review scrapers using the ReviewFetcherFactory,
 * similar to how the admin interface would use them.
 * 
 * Usage:
 * php test-all-scrapers.php <isbn> <limit>
 * 
 * Example:
 * php test-all-scrapers.php 9780007416851 50
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once __DIR__ . '/../services/ReviewFetcher/ReviewFetcherInterface.php';
require_once __DIR__ . '/../services/ReviewFetcher/AbstractReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/GoogleBooksReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/OpenLibraryReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/GoodreadsReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/AmazonReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/KirkusReviewsFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/SLJReviewFetcher.php';
require_once __DIR__ . '/../services/ReviewFetcher/StoriesReviewFetcher.php';
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
    
    // Get ISBN from command line arguments
    $isbn = $argv[1] ?? null;
    $limit = isset($argv[2]) ? (int)$argv[2] : 50;
    
    if (!$isbn) {
        die("Usage: php test-all-scrapers.php <isbn> <limit>\n");
    }
    
    echo "Testing All Review Scrapers\n";
    echo "-------------------------\n";
    echo "ISBN: $isbn\n";
    echo "Limit: $limit\n\n";
    
    // Create review fetcher factory
    $factory = new \Services\ReviewFetcher\ReviewFetcherFactory($db);
    
    // Get all available sources
    $sources = $factory->getSources();
    echo "Available sources:\n";
    foreach ($sources as $source) {
        echo "- {$source['name']} (ID: {$source['id']})\n";
    }
    echo "\n";
    
    // Get source IDs for Amazon and Goodreads
    $amazonId = null;
    $goodreadsId = null;
    
    foreach ($sources as $source) {
        if ($source['name'] === 'Amazon') {
            $amazonId = $source['id'];
        } elseif ($source['name'] === 'Goodreads') {
            $goodreadsId = $source['id'];
        }
    }
    
    if (!$amazonId) {
        echo "Warning: Amazon source not found in the database.\n";
    }
    
    if (!$goodreadsId) {
        echo "Warning: Goodreads source not found in the database.\n";
    }
    
    // Test with specific sources
    $sourceIds = [];
    if ($amazonId) $sourceIds[] = $amazonId;
    if ($goodreadsId) $sourceIds[] = $goodreadsId;
    
    if (empty($sourceIds)) {
        die("Error: No valid sources found.\n");
    }
    
    // Fetch reviews from all sources
    echo "Fetching reviews from all sources...\n";
    $startTime = microtime(true);
    $result = $factory->fetchReviewsFromAllSources($isbn, $sourceIds, $limit);
    $endTime = microtime(true);
    
    // Display results
    $duration = round($endTime - $startTime, 2);
    echo "\nResults:\n";
    echo "Duration: {$duration} seconds\n";
    echo "Sources attempted: {$result['sources_attempted']}\n";
    echo "Sources successful: {$result['sources_successful']}\n";
    echo "Reviews found: " . count($result['reviews']) . "\n\n";
    
    if (empty($result['reviews'])) {
        echo "No reviews found.\n";
        if (!empty($result['errors'])) {
            echo "Errors:\n";
            foreach ($result['errors'] as $source => $error) {
                echo "- {$source}: {$error}\n";
            }
        }
    } else {
        // Display review summary
        echo "Review Summary:\n";
        echo "---------------\n";
        
        foreach ($result['reviews'] as $index => $review) {
            $sourceName = '';
            foreach ($sources as $source) {
                if ($source['id'] == $review['source_id']) {
                    $sourceName = $source['name'];
                    break;
                }
            }
            
            $reviewerName = $review['reviewer_name'];
            $rating = $review['rating_value'];
            $date = $review['review_date'];
            $textSnippet = substr($review['review_text'], 0, 100) . (strlen($review['review_text']) > 100 ? '...' : '');
            
            echo ($index + 1) . ". [{$sourceName}] {$reviewerName} ({$rating}/5) - {$date}\n";
            echo "   {$textSnippet}\n\n";
        }
        
        // Check if we got reviews from the VPS scraper
        $debugDir = __DIR__ . '/../services/ReviewFetcher/debug';
        $goodreadsLogFile = "{$debugDir}/goodreads-log.txt";
        $amazonLogFile = "{$debugDir}/scrape-log.txt";
        
        echo "Log File Analysis:\n";
        echo "-----------------\n";
        
        if (file_exists($goodreadsLogFile)) {
            $logContent = file_get_contents($goodreadsLogFile);
            
            // Check for VPS scraper success message
            if (strpos($logContent, '[VPS-Scraper-Success]') !== false) {
                echo "✅ Goodreads VPS Scraper was used successfully!\n";
                
                // Extract the number of reviews found by the VPS scraper
                if (preg_match('/\[VPS-Scraper-Success\] Found (\d+) reviews/', $logContent, $matches)) {
                    echo "   Found {$matches[1]} reviews using Goodreads VPS Scraper\n";
                }
            } else {
                echo "❌ Goodreads VPS Scraper was not used or failed.\n";
            }
        }
        
        if (file_exists($amazonLogFile)) {
            $logContent = file_get_contents($amazonLogFile);
            
            // Check for VPS scraper success message
            if (strpos($logContent, '[VPS-Scraper-Success]') !== false) {
                echo "✅ Amazon VPS Scraper was used successfully!\n";
                
                // Extract the number of reviews found by the VPS scraper
                if (preg_match('/\[VPS-Scraper-Success\] Found (\d+) reviews/', $logContent, $matches)) {
                    echo "   Found {$matches[1]} reviews using Amazon VPS Scraper\n";
                }
            } else {
                echo "❌ Amazon VPS Scraper was not used or failed.\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
