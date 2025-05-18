<?php
/**
 * Book Import Scrape
 *
 * This script handles the scraping of reviews for books from various sources.
 * It can be called directly from the book-import-tool.php page or as a follow-up
 * to the book-import-process.php script.
 */

// Set page title and current page
$pageTitle = 'Book Review Scraping';
$currentPage = 'book-import-tool';
$pageDescription = 'Scrape reviews for books from various sources';

// Include the header
require_once '../includes/auth-check.php';
require_once '../includes/header.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include the review fetcher services
require_once '../../services/ReviewFetcher/ReviewFetcherInterface.php';
require_once '../../services/ReviewFetcher/AbstractReviewFetcher.php';
require_once '../../services/ReviewFetcher/GoogleBooksReviewFetcher.php';
require_once '../../services/ReviewFetcher/OpenLibraryReviewFetcher.php';
require_once '../../services/ReviewFetcher/GoodreadsReviewFetcher.php';
require_once '../../services/ReviewFetcher/AmazonReviewFetcher.php';
require_once '../../services/ReviewFetcher/KirkusReviewsFetcher.php';
require_once '../../services/ReviewFetcher/SLJReviewFetcher.php';
require_once '../../services/ReviewFetcher/StoriesReviewFetcher.php';
require_once '../../services/ReviewFetcher/ReviewFetcherFactory.php';

// Include the AI review analyzer
require_once '../../services/AI/ReviewAnalyzer.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer
function flushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Function to check if a review already exists
function reviewExists($db, $bookId, $sourceId, $reviewerName) {
    $stmt = $db->prepare("
        SELECT id FROM reviews
        WHERE book_id = ? AND source_id = ? AND
              LOWER(TRIM(REPLACE(reviewer_name, '**', ''))) = LOWER(TRIM(?))
    ");
    $stmt->execute([$bookId, $sourceId, $reviewerName]);
    return $stmt->fetch();
}

// Create the review fetcher factory
$reviewFetcherFactory = new \Services\ReviewFetcher\ReviewFetcherFactory($db);

// Get OpenAI API key from settings
$openaiApiKey = '';
try {
    $settingsStmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_name = 'openai_api_key'");
    $settingsStmt->execute();
    $openaiApiKey = $settingsStmt->fetchColumn();
} catch (Exception $e) {
    // If we can't get the API key, we'll just skip AI analysis
    error_log("Error getting OpenAI API key: " . $e->getMessage());
}

// Create the review analyzer
$reviewAnalyzer = new \Services\AI\ReviewAnalyzer($db);

// Function to update book aggregate values
function updateBookAggregateValues($db, $bookId) {
    // Get aggregate values
    $aggregateStmt = $db->prepare("
        SELECT
            COUNT(*) as review_count,
            AVG(rating_normalised) as average_rating,
            MAX(rating_normalised) as highest_rating,
            MIN(rating_normalised) as lowest_rating
        FROM reviews
        WHERE book_id = ? AND rating_normalised IS NOT NULL
    ");
    $aggregateStmt->execute([$bookId]);
    $aggregateValues = $aggregateStmt->fetch(PDO::FETCH_ASSOC);

    // Update the directory item
    if ($aggregateValues['review_count'] > 0) {
        $stmt = $db->prepare("
            UPDATE directory_items
            SET
                review_count = ?,
                average_rating = ?,
                highest_rating = ?,
                lowest_rating = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $aggregateValues['review_count'],
            $aggregateValues['average_rating'],
            $aggregateValues['highest_rating'],
            $aggregateValues['lowest_rating'],
            $bookId
        ]);

        return true;
    }

    return false;
}

// Main processing logic
// Note: We're not setting the content type header here because the header is already included
?>
<style>
    .progress-container {
        margin: 20px 0;
        background-color: #f1f1f1;
        border-radius: 5px;
        overflow: hidden;
        border: 2px solid #ddd;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .progress-bar {
        height: 40px;
        background-color: #4CAF50;
        background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent);
        background-size: 40px 40px;
        text-align: center;
        line-height: 40px;
        color: white;
        font-weight: bold;
        font-size: 16px;
        transition: width 0.5s;
        animation: progress-bar-stripes 2s linear infinite;
    }
    @keyframes progress-bar-stripes {
        from { background-position: 40px 0; }
        to { background-position: 0 0; }
    }
    .log-container {
        max-height: 400px;
        overflow-y: auto;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 20px;
    }
    .success { color: green; }
    .warning { color: orange; }
    .error { color: red; }
    .info { color: blue; }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Book Review Scraping</h5>
                <div class="btn-group">
                    <a href="book-import-tool.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Import Tool
                    </a>
                    <a href="debug-logs.php" class="btn btn-info">
                        <i class="fas fa-file-alt"></i> View Debug Logs
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
                </div>

                <div class="log-container" id="logContainer">
            <p class="info">Starting review scraping process...</p>
            <?php
            // Process the request
            try {
                // Get book IDs to process
                $bookIds = [];

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Single book from form submission
                    $bookId = $_POST['book_id'] ?? 0;
                    if ($bookId > 0) {
                        $bookIds[] = $bookId;
                    }

                    // Get selected sources
                    $sources = $_POST['sources'] ?? [];
                    $runAiAnalysis = isset($_POST['run_ai_analysis']) && $_POST['run_ai_analysis'] == 1;
                    $forceRefresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] == 1;
                    $continueFromLast = isset($_POST['continue_from_last']) && $_POST['continue_from_last'] == 1;
                    $reviewLimit = isset($_POST['review_limit']) ? intval($_POST['review_limit']) : 100;
                    $maxPages = isset($_POST['max_pages']) ? intval($_POST['max_pages']) : 20;
                    
                    // Validate limits
                    $reviewLimit = max(10, min(1000, $reviewLimit));
                    $maxPages = max(1, min(100, $maxPages));
                } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                    // Multiple books from URL parameter
                    if (isset($_GET['books'])) {
                        $bookIds = explode(',', $_GET['books']);
                        $bookIds = array_filter($bookIds, 'is_numeric');
                    }

                    // Default to all sources
                    $sourcesStmt = $db->prepare("
                        SELECT id FROM review_sources WHERE is_third_party = 1
                    ");
                    $sourcesStmt->execute();
                    $sources = $sourcesStmt->fetchAll(PDO::FETCH_COLUMN);

                    $runAiAnalysis = isset($_GET['ai']) && $_GET['ai'] == 1;
                    $forceRefresh = isset($_GET['force']) && $_GET['force'] == 1;
                    $continueFromLast = isset($_GET['continue']) && $_GET['continue'] == 1;
                    $reviewLimit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                    $maxPages = isset($_GET['pages']) ? intval($_GET['pages']) : 20;
                    
                    // Validate limits
                    $reviewLimit = max(10, min(1000, $reviewLimit));
                    $maxPages = max(1, min(100, $maxPages));
                }

                if (empty($bookIds)) {
                    echo "<p class='error'>No books specified for review scraping</p>";
                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                    exit;
                }

                if (empty($sources)) {
                    echo "<p class='error'>No sources specified for review scraping</p>";
                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                    exit;
                }

                // Get book details
                $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
                $booksStmt = $db->prepare("
                    SELECT di.id, di.title, b.isbn, b.isbn13
                    FROM directory_items di
                    JOIN books b ON di.id = b.directory_item_id
                    WHERE di.id IN ($placeholders)
                ");
                $booksStmt->execute($bookIds);
                $books = $booksStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($books)) {
                    echo "<p class='error'>No valid books found with the specified IDs</p>";
                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                    exit;
                }

                echo "<p class='info'>Found " . count($books) . " books to scrape reviews for</p>";
                echo "<p class='info'>Using " . count($sources) . " review sources</p>";
                flushOutput();

                // Process each book
                $totalBooks = count($books);
                $totalReviewsImported = 0;
                $totalReviewsSkipped = 0;

                foreach ($books as $index => $book) {
                    $progress = round(($index / $totalBooks) * 100);
                    echo "<script>
                        document.getElementById('progressBar').style.width = '$progress%';
                        document.getElementById('progressBar').innerText = '$progress%';
                    </script>";
                    flushOutput();

                    echo "<h3>Processing book " . ($index + 1) . " of $totalBooks: {$book['title']}</h3>";
                    flushOutput();

                    $bookReviewsImported = 0;
                    $bookReviewsSkipped = 0;

                    // Process each source
                    foreach ($sources as $sourceId) {
                        // Get source name
                        $sourceStmt = $db->prepare("SELECT name FROM review_sources WHERE id = ?");
                        $sourceStmt->execute([$sourceId]);
                        $sourceName = $sourceStmt->fetchColumn();

                        echo "<p class='info'>Scraping reviews from source: $sourceName</p>";
                        flushOutput();

                        // Get reviews using the review fetcher
                        $reviews = [];

                        // Set up error log capture
                        $logFile = __DIR__ . '/../../services/ReviewFetcher/debug/scrape-log.txt';
                        $debugDir = dirname($logFile);

                        // Create debug directory with proper permissions
                        if (!is_dir($debugDir)) {
                            mkdir($debugDir, 0777, true);
                        }

                        // Make sure the directory is readable and writable
                        chmod($debugDir, 0777);

                        // Clear the log file
                        file_put_contents($logFile, "Starting review scrape for source: {$sourceName}\n");

                        // Make sure the file is readable and writable
                        chmod($logFile, 0666);

                        // Get the appropriate fetcher for this source
                        echo "<div class='debug-log'>";
                        echo "<div class='d-flex justify-content-between align-items-center'>";
                        echo "<h3>Debug Log for {$sourceName}</h3>";
                        echo "<a href='debug-logs.php' class='btn btn-info btn-sm'><i class='fas fa-file-alt'></i> View All Debug Logs</a>";
                        echo "</div>";
                        echo "<pre id='debug-output-{$sourceId}' style='max-height: 300px; overflow-y: auto; background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
                        echo "Initializing fetcher for source: {$sourceName} (ID: {$sourceId})\n";
                        echo "</pre>";
                        echo "</div>";

                        // Add JavaScript to update the debug log
                        echo "<script>
                            function updateDebugLog_{$sourceId}() {
                                fetch('get-debug-log.php?source_id={$sourceId}')
                                    .then(response => response.text())
                                    .then(data => {
                                        document.getElementById('debug-output-{$sourceId}').innerHTML = data;
                                        document.getElementById('debug-output-{$sourceId}').scrollTop = document.getElementById('debug-output-{$sourceId}').scrollHeight;
                                    });
                            }

                            // Update every 2 seconds
                            const debugInterval_{$sourceId} = setInterval(updateDebugLog_{$sourceId}, 2000);

                            // Stop after 5 minutes to prevent infinite polling
                            setTimeout(() => clearInterval(debugInterval_{$sourceId}), 300000);
                        </script>";

                        flushOutput();

                        $fetcher = $reviewFetcherFactory->getFetcher($sourceId);

                        if (!$fetcher) {
                            echo "<p class='warning'>No fetcher available for source ID: $sourceId</p>";
                            flushOutput();
                            continue;
                        }

                        // Check if the fetcher is configured correctly
                        if (!$fetcher->isConfigured()) {
                            echo "<p class='warning'>Fetcher for {$sourceName} is not configured correctly</p>";
                            flushOutput();
                            continue;
                        }

                        // Use ISBN13 if available, otherwise use ISBN
                        $isbnToUse = !empty($book['isbn13']) ? $book['isbn13'] : $book['isbn'];

                        if (empty($isbnToUse)) {
                            echo "<p class='warning'>No ISBN available for book: {$book['title']}</p>";
                            flushOutput();
                            continue;
                        }

                        try {
                            // Set up options for the fetcher
                            $options = [
                                'maxPages' => $maxPages,
                                'continueFromLast' => $continueFromLast
                            ];
                            
                            // If we're continuing from last scrape, get the count of existing reviews
                            $existingReviewCount = 0;
                            if ($continueFromLast) {
                                $countStmt = $db->prepare("
                                    SELECT COUNT(*) FROM reviews
                                    WHERE book_id = ? AND source_id = ?
                                ");
                                $countStmt->execute([$book['id'], $sourceId]);
                                $existingReviewCount = (int)$countStmt->fetchColumn();
                                
                                echo "<p class='info'>Found {$existingReviewCount} existing reviews for this book from {$sourceName}</p>";
                                flushOutput();
                                
                                // If continuing, we need to fetch more than what we already have
                                $fetchLimit = $existingReviewCount + $reviewLimit;
                            } else {
                                $fetchLimit = $reviewLimit;
                            }
                            
                            // Fetch reviews from the source with the specified limit
                            $result = $fetcher->fetchReviewsByISBN($isbnToUse, $fetchLimit, $options);

                            // Check if we got a structured result (new format) or just an array of reviews (old format)
                            if (is_array($result) && isset($result['reviews'])) {
                                // New format with structured result
                                $reviews = $result['reviews'];

                                if (empty($reviews)) {
                                    echo "<p class='warning'>No reviews found from {$sourceName} for ISBN: $isbnToUse</p>";
                                    if (isset($result['errors'][$sourceName])) {
                                        echo "<p class='error'>Error: " . $result['errors'][$sourceName] . "</p>";
                                    } elseif ($fetcher->getLastError()) {
                                        echo "<p class='error'>Error: " . $fetcher->getLastError() . "</p>";
                                    }
                                    flushOutput();
                                    continue;
                                }
                            } else {
                                // Old format with just an array of reviews
                                $reviews = $result;

                                if (empty($reviews)) {
                                    echo "<p class='warning'>No reviews found from {$sourceName} for ISBN: $isbnToUse</p>";
                                    if ($fetcher->getLastError()) {
                                        echo "<p class='error'>Error: " . $fetcher->getLastError() . "</p>";
                                    }
                                    flushOutput();
                                    continue;
                                }
                            }

                            // Check if we got more than 30 reviews (indicating VPS scraper success)
                            if (count($reviews) > 30 && (strtolower($sourceName) === 'goodreads' || strtolower($sourceName) === 'amazon')) {
                                echo "<p class='success'><strong>🚀 [VPS-Scraper-Success]</strong> Found " . count($reviews) . " reviews using Puppeteer-based Headless Browser</p>";
                                flushOutput();
                            }
                        } catch (Exception $e) {
                            echo "<p class='error'>Error fetching reviews from {$sourceName}: " . $e->getMessage() . "</p>";
                            flushOutput();
                            continue;
                        }

                        echo "<p class='info'>Found " . count($reviews) . " reviews from $sourceName</p>";
                        flushOutput();

                        // Import reviews
                        foreach ($reviews as $review) {
                            // Check for duplicates, but allow force refresh or continue from last to override
                            if (!$forceRefresh && !$continueFromLast && reviewExists($db, $book['id'], $review['source_id'], $review['reviewer_name'])) {
                                echo "<p class='warning'>Skipping duplicate review by {$review['reviewer_name']}</p>";
                                flushOutput();
                                $bookReviewsSkipped++;
                                continue;
                            }

                            // If force refresh or continue from last is enabled and the review exists, delete the old one first
                            if (($forceRefresh || $continueFromLast) && ($existingReview = reviewExists($db, $book['id'], $review['source_id'], $review['reviewer_name']))) {
                                $deleteStmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
                                $deleteStmt->execute([$existingReview['id']]);
                                echo "<p class='info'>Replacing existing review by {$review['reviewer_name']}</p>";
                                flushOutput();
                            }

                            try {
                                // Insert the review
                                $stmt = $db->prepare("
                                    INSERT INTO reviews (
                                        book_id,
                                        source_id,
                                        reviewer_name,
                                        reviewer_age,
                                        review_date,
                                        original_rating,
                                        rating_value,
                                        rating_scale,
                                        rating_normalised,
                                        review_text,
                                        metadata,
                                        created_at,
                                        updated_at
                                    ) VALUES (
                                        :book_id,
                                        :source_id,
                                        :reviewer_name,
                                        :reviewer_age,
                                        :review_date,
                                        :original_rating,
                                        :rating_value,
                                        :rating_scale,
                                        :rating_normalised,
                                        :review_text,
                                        :metadata,
                                        NOW(),
                                        NOW()
                                    )
                                ");

                                // Ensure we have valid values for required fields
                                $ratingValue = $review['rating_value'] ?? 0;
                                $ratingScale = $review['rating_scale'] ?? 5;
                                $ratingNormalised = $review['rating_normalised'] ?? 0;

                                // If rating_value is null but we have a normalised rating, calculate a value
                                if (empty($ratingValue) && !empty($ratingNormalised) && !empty($ratingScale)) {
                                    $ratingValue = $ratingNormalised * $ratingScale;
                                }

                                $stmt->execute([
                                    ':book_id' => $book['id'],
                                    ':source_id' => $review['source_id'],
                                    ':reviewer_name' => $review['reviewer_name'],
                                    ':reviewer_age' => $review['reviewer_age'],
                                    ':review_date' => $review['review_date'],
                                    ':original_rating' => $review['original_rating'] ?? 'N/A',
                                    ':rating_value' => $ratingValue,
                                    ':rating_scale' => $ratingScale,
                                    ':rating_normalised' => $ratingNormalised,
                                    ':review_text' => $review['review_text'],
                                    ':metadata' => $review['metadata']
                                ]);

                                echo "<p class='success'>Imported review by {$review['reviewer_name']}</p>";
                                flushOutput();
                                $bookReviewsImported++;
                            } catch (Exception $e) {
                                echo "<p class='error'>Error importing review: " . $e->getMessage() . "</p>";
                                flushOutput();
                            }
                        }
                    }

                    // Update aggregate values
                    if ($bookReviewsImported > 0) {
                        if (updateBookAggregateValues($db, $book['id'])) {
                            echo "<p class='success'>Updated aggregate values for book: {$book['title']}</p>";
                        } else {
                            echo "<p class='warning'>Failed to update aggregate values for book: {$book['title']}</p>";
                        }
                        flushOutput();
                    }

                    echo "<p class='info'>Book summary: Imported $bookReviewsImported reviews, skipped $bookReviewsSkipped duplicates</p>";
                    flushOutput();

                    $totalReviewsImported += $bookReviewsImported;
                    $totalReviewsSkipped += $bookReviewsSkipped;
                }

                // Update progress to 100%
                echo "<script>
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('progressBar').innerText = '100%';
                </script>";
                flushOutput();

                // Summary
                echo "<h3>Scraping Summary</h3>";
                echo "<p>Total books processed: $totalBooks</p>";
                echo "<p>Total reviews imported: $totalReviewsImported</p>";
                echo "<p>Total duplicates skipped: $totalReviewsSkipped</p>";

                if ($runAiAnalysis && $totalReviewsImported > 0) {
                    echo "<p class='info'>Redirecting to AI analysis for imported reviews...</p>";
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'book-import-ai.php?books=" . implode(',', array_column($books, 'id')) . "';
                        }, 3000);
                    </script>";
                } else {
                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
                echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
            }
            ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the footer
require_once '../includes/footer.php';
?>

<script>
    // Auto-scroll to bottom of log container
    const logContainer = document.getElementById('logContainer');
    logContainer.scrollTop = logContainer.scrollHeight;

    // Set up interval to auto-scroll
    setInterval(function() {
        logContainer.scrollTop = logContainer.scrollHeight;
    }, 500);
</script>
