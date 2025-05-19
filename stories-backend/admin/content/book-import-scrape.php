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

// Function to check if Git Auto Deploy webhook is running
function is_webhook_online() {
    // ONLY check the known working IP address: 37.27.31.107
    // Based on diagnostic results, this is the only address that works

    // Try direct socket connection first (most reliable)
    $fp = @fsockopen('37.27.31.107', 8080, $errno, $errstr, 1);
    if ($fp) {
        fclose($fp);
        return true;
    }

    // If socket fails, try cURL
    if (function_exists('curl_init')) {
        $ch = curl_init('http://37.27.31.107:8080');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false || ($httpCode > 0 && $httpCode < 500)) {
            return true;
        }
    }

    // Last resort: try file_get_contents
    $context = stream_context_create([
        'http' => [
            'method' => 'HEAD',
            'timeout' => 1,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents('http://37.27.31.107:8080', false, $context);
    if ($response !== false) {
        return true;
    }

    return false; // All checks failed
}

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
function reviewExists($db, $bookId, $sourceId, $reviewerName, $reviewText = null) {
    // If review text is provided, use it to check for duplicates more accurately
    if ($reviewText) {
        // Use the first 100 characters of the review text to check for duplicates
        $reviewTextStart = substr($reviewText, 0, 100);

        $stmt = $db->prepare("
            SELECT id FROM reviews
            WHERE book_id = ? AND source_id = ? AND
                  LOWER(TRIM(REPLACE(reviewer_name, '**', ''))) = LOWER(TRIM(?)) AND
                  LOWER(SUBSTRING(review_text, 1, 100)) = LOWER(?)
        ");
        $stmt->execute([$bookId, $sourceId, $reviewerName, $reviewTextStart]);
        $result = $stmt->fetch();

        if ($result) {
            return $result;
        }
    }

    // Fall back to just checking the reviewer name
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
    // Debug: Count all reviews for this book with detailed query
    $debugCountStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ?");
    $debugCountStmt->execute([$bookId]);
    $totalReviews = $debugCountStmt->fetchColumn();

    // Get current review count from directory_items
    $currentCountStmt = $db->prepare("SELECT review_count FROM directory_items WHERE id = ?");
    $currentCountStmt->execute([$bookId]);
    $currentReviewCount = $currentCountStmt->fetchColumn();

    // Get a list of all review IDs for this book to debug
    $reviewIdsStmt = $db->prepare("SELECT id, reviewer_name FROM reviews WHERE book_id = ? ORDER BY id DESC LIMIT 10");
    $reviewIdsStmt->execute([$bookId]);
    $recentReviews = $reviewIdsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p class='info'><strong>DEBUG:</strong> Current review count in directory_items: $currentReviewCount</p>";
    echo "<p class='info'><strong>DEBUG:</strong> Total reviews in database for book ID $bookId: $totalReviews</p>";
    echo "<p class='info'><strong>DEBUG:</strong> 10 most recent reviews:</p>";
    foreach ($recentReviews as $review) {
        echo "<p class='info'>- Review ID: {$review['id']}, Reviewer: {$review['reviewer_name']}</p>";
    }
    flushOutput();

    // First, count ALL reviews for this book
    $totalReviewCount = 0;
    $countAllStmt = $db->prepare("
        SELECT COUNT(*) as total_review_count
        FROM reviews
        WHERE book_id = ?
    ");
    $countAllStmt->execute([$bookId]);
    $totalReviewCount = $countAllStmt->fetchColumn();

    // Then get aggregate values for reviews with ratings
    $aggregateStmt = $db->prepare("
        SELECT
            AVG(rating_normalised) as average_rating,
            MAX(rating_normalised) as highest_rating,
            MIN(rating_normalised) as lowest_rating
        FROM reviews
        WHERE book_id = ? AND rating_normalised IS NOT NULL
    ");
    $aggregateStmt->execute([$bookId]);
    $aggregateValues = $aggregateStmt->fetch(PDO::FETCH_ASSOC);

    // Update the directory item
    if ($totalReviewCount > 0) {
        $stmt = $db->prepare("
            UPDATE directory_items
            SET
                review_count = ?,
                average_rating = ?,
                highest_rating = ?,
                lowest_rating = ?
            WHERE id = ?
        ");

        // Debug: Display the values being updated
        echo "<p class='info'><strong>DEBUG:</strong> Updating directory_items for book ID $bookId with review_count: {$totalReviewCount}</p>";
        flushOutput();

        $stmt->execute([
            $totalReviewCount,
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
                <?php
                // Get the last auto-pull timestamp
                function get_last_pull_timestamp() {
                    $logFile = '/var/log/git-auto-deploy.log';
                    if (file_exists($logFile)) {
                        $lastLine = exec("tail -n 1 " . escapeshellarg($logFile));
                        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lastLine, $matches)) {
                            return $matches[1];
                        }
                    }

                    // Fallback: check the git directory for last commit time
                    $gitDir = dirname(dirname(dirname(__DIR__))) . '/.git';
                    if (is_dir($gitDir)) {
                        $lastCommitTime = exec("git --git-dir=" . escapeshellarg($gitDir) . " log -1 --format=%cd --date=format:'%Y-%m-%d %H:%M:%S'");
                        if ($lastCommitTime) {
                            return $lastCommitTime;
                        }
                    }

                    return 'Unknown';
                }

                $webhookStatus = is_webhook_online();
                $statusClass = $webhookStatus ? 'alert-success' : 'alert-danger';
                $statusIcon = $webhookStatus ? 'check-circle' : 'exclamation-triangle';
                $statusText = $webhookStatus ? '🟢 Git Auto Deploy: Online' : '🔴 Git Auto Deploy: Not running!';
                $lastPullTime = get_last_pull_timestamp();
                $statusDesc = $webhookStatus ?
                    "Code changes will be automatically deployed to the server. Last auto-pull: {$lastPullTime}" :
                    "WARNING: Code changes will NOT be automatically deployed to the server! Please restart the Git Auto Deploy service.";
                ?>
                <div class="alert <?php echo $statusClass; ?> d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-<?php echo $statusIcon; ?> me-2"></i>
                    <div>
                        <strong><?php echo $statusText; ?></strong> - <?php echo $statusDesc; ?>
                    </div>
                </div>

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
                    // Use review_limit from POST but also check for limit for consistency with GET parameter
                    $reviewLimit = isset($_POST['limit']) ? intval($_POST['limit']) : (isset($_POST['review_limit']) ? intval($_POST['review_limit']) : 100);
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
                            // Initialize fetchLimit before using it
                            $fetchLimit = $reviewLimit;

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
                            }

                            // Set up options for the fetcher
                            $options = [
                                'limit' => (int)$fetchLimit, // Cast to integer and use the correct parameter name
                                'maxPages' => $maxPages,
                                'continueFromLast' => $continueFromLast,
                                'force' => $forceRefresh,
                                'book_id' => $book['id']
                            ];

                            // Debug the fetch limit
                            echo "<p class='info'><strong>DEBUG:</strong> Requesting {$fetchLimit} reviews from {$sourceName} (reviewLimit: {$reviewLimit}, existingReviewCount: {$existingReviewCount})</p>";

                            // Add more detailed debug information
                            echo "<p class='info'><strong>DEBUG PARAMS:</strong> Passing limit={$fetchLimit}, maxPages={$maxPages}, continueFromLast=" . ($continueFromLast ? "true" : "false") . ", force=" . ($forceRefresh ? "true" : "false") . "</p>";
                            flushOutput();

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

                        // Debug: Count reviews before processing
                        $beforeCountStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ?");
                        $beforeCountStmt->execute([$book['id']]);
                        $beforeCount = $beforeCountStmt->fetchColumn();
                        echo "<p class='info'><strong>DEBUG:</strong> Reviews count BEFORE processing: $beforeCount</p>";
                        flushOutput();

                        // Import reviews
                        foreach ($reviews as $review) {
                            // Check for duplicates
                            $existingReview = reviewExists($db, $book['id'], $review['source_id'], $review['reviewer_name'], $review['review_text'] ?? null);

                            // Handle different scenarios
                            if ($existingReview) {
                                if ($continueFromLast) {
                                    // If continuing from last, skip existing reviews
                                    echo "<p class='info'>Skipping existing review by {$review['reviewer_name']} (continuing from last)</p>";
                                    flushOutput();
                                    $bookReviewsSkipped++;
                                    continue;
                                } else if ($forceRefresh) {
                                    // If force refresh, delete and replace the review
                                    echo "<p class='info'><strong>DEBUG:</strong> Found existing review ID: {$existingReview['id']} for {$review['reviewer_name']}</p>";
                                    flushOutput();

                                    $deleteStmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
                                    $deleteStmt->execute([$existingReview['id']]);

                                    echo "<p class='info'>Successfully deleted review ID: {$existingReview['id']}</p>";
                                    echo "<p class='info'>Replacing existing review by {$review['reviewer_name']}</p>";
                                    flushOutput();
                                    $isReplacement = true;
                                } else {
                                    // Normal case - skip duplicate
                                    echo "<p class='warning'>Skipping duplicate review by {$review['reviewer_name']}</p>";
                                    flushOutput();
                                    $bookReviewsSkipped++;
                                    continue;
                                }
                            }

                            try {
                                // Debug the review data
                                echo "<p class='info'><strong>DEBUG:</strong> Inserting review for book ID: {$book['id']}, source ID: {$review['source_id']}, reviewer: {$review['reviewer_name']}</p>";
                                flushOutput();

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

                                try {
                                    $stmt->execute([
                                        ':book_id' => $book['id'],
                                        ':source_id' => $review['source_id'],
                                        ':reviewer_name' => $review['reviewer_name'],
                                        ':reviewer_age' => $review['reviewer_age'] ?? null,
                                        ':review_date' => $review['review_date'] ?? null,
                                        ':original_rating' => $review['original_rating'] ?? 'N/A',
                                        ':rating_value' => $ratingValue,
                                        ':rating_scale' => $ratingScale,
                                        ':rating_normalised' => $ratingNormalised,
                                        ':review_text' => $review['review_text'],
                                        ':metadata' => $review['metadata'] ?? null
                                    ]);

                                    echo "<p class='success'>SQL query executed successfully</p>";
                                    flushOutput();
                                } catch (PDOException $e) {
                                    echo "<p class='error'>SQL Error: " . $e->getMessage() . "</p>";
                                    flushOutput();
                                    throw $e; // Re-throw to be caught by the outer try-catch
                                }

                                // Get the ID of the newly inserted review
                                $newReviewId = $db->lastInsertId();
                                echo "<p class='success'>Imported review by {$review['reviewer_name']} (ID: $newReviewId)</p>";
                                flushOutput();
                                $bookReviewsImported++;
                            } catch (Exception $e) {
                                echo "<p class='error'>Error importing review: " . $e->getMessage() . "</p>";
                                flushOutput();
                            }
                        }
                    }

                    // Debug: Count reviews after processing
                    $afterCountStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ?");
                    $afterCountStmt->execute([$book['id']]);
                    $afterCount = $afterCountStmt->fetchColumn();
                    echo "<p class='info'><strong>DEBUG:</strong> Reviews count AFTER processing: $afterCount</p>";
                    echo "<p class='info'><strong>DEBUG:</strong> Change in review count: " . ($afterCount - $beforeCount) . "</p>";
                    flushOutput();

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
