<?php
/**
 * Book Import Batch Processing
 *
 * This script handles batch processing of book imports and review scraping.
 * It can be run manually from the admin interface or as a cron job.
 */

// Check if running as CLI
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    // Include auth check for web requests
    require_once '../includes/auth-check.php';

    // Include database connection
    require_once '../includes/db-connect.php';

    // Set up error handling for web
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    set_time_limit(300); // 5 minutes
    ini_set('output_buffering', 'off');
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);
} else {
    // CLI mode - include database connection from a different path
    require_once __DIR__ . '/../includes/db-connect.php';
}

// Function to flush output buffer
function flushOutput() {
    if (!isCli() && ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

// Function to check if running as CLI
function isCli() {
    return (php_sapi_name() === 'cli');
}

// Function to log message
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] [$type] $message";

    if (isCli()) {
        echo $formattedMessage . PHP_EOL;
    } else {
        echo "<p class='$type'>$formattedMessage</p>";
        flushOutput();
    }
}

// Function to get books for batch processing
function getBooksForBatch($db, $batchType, $batchSize) {
    switch ($batchType) {
        case 'import_books':
            // For import_books, we would typically query an external API
            // For now, we'll return a sample list of ISBNs
            return [
                '9780545162074',
                '9780439023481',
                '9780439023498',
                '9780439023511',
                '9780545663267'
            ];

        case 'scrape_reviews':
            // Get books that have few or no reviews
            $stmt = $db->prepare("
                SELECT di.id
                FROM directory_items di
                LEFT JOIN reviews r ON di.id = r.book_id
                WHERE di.type = 'book'
                GROUP BY di.id
                HAVING COUNT(r.id) < 3
                ORDER BY COUNT(r.id) ASC
                LIMIT ?
            ");
            $stmt->execute([$batchSize]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        case 'update_metadata':
            // Get books with missing metadata
            $stmt = $db->prepare("
                SELECT di.id
                FROM directory_items di
                JOIN books b ON di.id = b.directory_item_id
                WHERE di.type = 'book' AND (
                    /* Genre is now stored as tags */
                    b.age_range IS NULL OR b.age_range = '' OR
                    b.reading_level IS NULL OR b.reading_level = '' OR
                    b.purchase_links IS NULL OR b.purchase_links = '{}'
                )
                LIMIT ?
            ");
            $stmt->execute([$batchSize]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        case 'fetch_images':
            // Get authors/publishers without images
            $stmt = $db->prepare("
                SELECT id
                FROM authors
                WHERE (avatar_url IS NULL OR avatar_url = '')
                LIMIT ?
            ");
            $stmt->execute([$batchSize]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        default:
            return [];
    }
}

// Function to process a batch
function processBatch($db, $batchType, $batchItems) {
    $results = [
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'details' => []
    ];

    switch ($batchType) {
        case 'import_books':
            // Import books by ISBN
            foreach ($batchItems as $isbn) {
                logMessage("Processing ISBN: $isbn");

                // Check if book already exists
                $stmt = $db->prepare("
                    SELECT di.id
                    FROM directory_items di
                    JOIN books b ON di.id = b.directory_item_id
                    WHERE (b.isbn = ? OR b.isbn13 = ?)
                ");
                $stmt->execute([$isbn, $isbn]);
                $existingBook = $stmt->fetch();

                if ($existingBook) {
                    logMessage("Book with ISBN $isbn already exists (ID: {$existingBook['id']})", 'warning');
                    $results['skipped']++;
                    $results['details'][] = [
                        'item' => $isbn,
                        'status' => 'skipped',
                        'message' => 'Book already exists'
                    ];
                    continue;
                }

                // In a real implementation, this would call an external API
                // For now, we'll create a sample book
                try {
                    // Begin transaction
                    $db->beginTransaction();

                    // Generate a title based on ISBN
                    $title = "Sample Book ($isbn)";
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
                    $slug = trim($slug, '-');

                    // Insert into directory_items
                    $stmt = $db->prepare("
                        INSERT INTO directory_items (
                            title, description, website_url, category_id, type, slug,
                            is_published, published_at, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, 'book', ?, 1, NOW(), NOW(), NOW())
                    ");

                    $stmt->execute([
                        $title,
                        "This is a sample book imported during batch processing.",
                        '',
                        1,
                        $slug
                    ]);

                    $directoryItemId = $db->lastInsertId();

                    // Insert into books
                    $stmt = $db->prepare("
                        INSERT INTO books (
                            directory_item_id, isbn, isbn13, author, publisher,
                            publication_date, page_count, age_range, reading_level
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $directoryItemId,
                        strlen($isbn) <= 10 ? $isbn : '',
                        strlen($isbn) > 10 ? $isbn : '',
                        'Sample Author',
                        'Sample Publisher',
                        date('Y-m-d'),
                        200,
                        '9-12',
                        'middle-grade'
                    ]);

                    // Commit transaction
                    $db->commit();

                    logMessage("Imported book: $title (ID: $directoryItemId)", 'success');
                    $results['success']++;
                    $results['details'][] = [
                        'item' => $isbn,
                        'status' => 'success',
                        'message' => "Imported as '$title' (ID: $directoryItemId)"
                    ];
                } catch (Exception $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }

                    logMessage("Error importing book: " . $e->getMessage(), 'error');
                    $results['failed']++;
                    $results['details'][] = [
                        'item' => $isbn,
                        'status' => 'failed',
                        'message' => $e->getMessage()
                    ];
                }
            }
            break;

        case 'scrape_reviews':
            // Scrape reviews for books
            foreach ($batchItems as $bookId) {
                // Get book details
                $stmt = $db->prepare("
                    SELECT di.title, b.isbn, b.isbn13
                    FROM directory_items di
                    JOIN books b ON di.id = b.directory_item_id
                    WHERE di.id = ?
                ");
                $stmt->execute([$bookId]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$book) {
                    logMessage("Book with ID $bookId not found", 'warning');
                    $results['skipped']++;
                    $results['details'][] = [
                        'item' => $bookId,
                        'status' => 'skipped',
                        'message' => 'Book not found'
                    ];
                    continue;
                }

                logMessage("Processing book: {$book['title']} (ID: $bookId)");

                // Get review sources
                $sourcesStmt = $db->prepare("
                    SELECT id FROM review_sources WHERE is_third_party = 1
                ");
                $sourcesStmt->execute();
                $sources = $sourcesStmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($sources)) {
                    logMessage("No review sources found", 'warning');
                    $results['skipped']++;
                    $results['details'][] = [
                        'item' => $bookId,
                        'status' => 'skipped',
                        'message' => 'No review sources found'
                    ];
                    continue;
                }

                // In a real implementation, this would call external APIs
                // For now, we'll create sample reviews
                try {
                    $reviewsAdded = 0;

                    foreach ($sources as $sourceId) {
                        // Get source name
                        $sourceStmt = $db->prepare("SELECT name FROM review_sources WHERE id = ?");
                        $sourceStmt->execute([$sourceId]);
                        $sourceName = $sourceStmt->fetchColumn();

                        logMessage("Scraping reviews from source: $sourceName");

                        // Create a sample review
                        $reviewerName = "Batch Reviewer ($sourceName)";

                        // Check if review already exists
                        $checkStmt = $db->prepare("
                            SELECT id FROM reviews
                            WHERE book_id = ? AND source_id = ? AND reviewer_name = ?
                        ");
                        $checkStmt->execute([$bookId, $sourceId, $reviewerName]);

                        if ($checkStmt->fetch()) {
                            logMessage("Review already exists for this book and source", 'warning');
                            continue;
                        }

                        // Insert the review
                        $stmt = $db->prepare("
                            INSERT INTO reviews (
                                book_id,
                                source_id,
                                reviewer_name,
                                review_date,
                                original_rating,
                                rating_value,
                                rating_scale,
                                rating_normalised,
                                review_text,
                                created_at,
                                updated_at
                            ) VALUES (
                                :book_id,
                                :source_id,
                                :reviewer_name,
                                :review_date,
                                :original_rating,
                                :rating_value,
                                :rating_scale,
                                :rating_normalised,
                                :review_text,
                                NOW(),
                                NOW()
                            )
                        ");

                        $stmt->execute([
                            ':book_id' => $bookId,
                            ':source_id' => $sourceId,
                            ':reviewer_name' => $reviewerName,
                            ':review_date' => date('Y-m-d'),
                            ':original_rating' => '4/5',
                            ':rating_value' => 4,
                            ':rating_scale' => 5,
                            ':rating_normalised' => 0.8,
                            ':review_text' => "This is a sample review created during batch processing. The book was engaging and well-written."
                        ]);

                        logMessage("Added review from $sourceName", 'success');
                        $reviewsAdded++;
                    }

                    if ($reviewsAdded > 0) {
                        // Update aggregate values
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

                        if ($aggregateValues['review_count'] > 0) {
                            $updateStmt = $db->prepare("
                                UPDATE directory_items
                                SET
                                    review_count = ?,
                                    average_rating = ?,
                                    highest_rating = ?,
                                    lowest_rating = ?
                                WHERE id = ?
                            ");

                            $updateStmt->execute([
                                $aggregateValues['review_count'],
                                $aggregateValues['average_rating'],
                                $aggregateValues['highest_rating'],
                                $aggregateValues['lowest_rating'],
                                $bookId
                            ]);

                            logMessage("Updated aggregate values for book", 'success');
                        }

                        $results['success']++;
                        $results['details'][] = [
                            'item' => $bookId,
                            'status' => 'success',
                            'message' => "Added $reviewsAdded reviews"
                        ];
                    } else {
                        logMessage("No new reviews added for book", 'warning');
                        $results['skipped']++;
                        $results['details'][] = [
                            'item' => $bookId,
                            'status' => 'skipped',
                            'message' => 'No new reviews added'
                        ];
                    }
                } catch (Exception $e) {
                    logMessage("Error scraping reviews: " . $e->getMessage(), 'error');
                    $results['failed']++;
                    $results['details'][] = [
                        'item' => $bookId,
                        'status' => 'failed',
                        'message' => $e->getMessage()
                    ];
                }
            }
            break;

        case 'update_metadata':
        case 'fetch_images':
            // These would be implemented similarly to the above cases
            // For now, we'll just log a message
            logMessage("Batch type '$batchType' not fully implemented yet", 'warning');
            $results['skipped'] = count($batchItems);
            foreach ($batchItems as $item) {
                $results['details'][] = [
                    'item' => $item,
                    'status' => 'skipped',
                    'message' => 'Batch type not fully implemented'
                ];
            }
            break;
    }

    return $results;
}

// Main processing logic
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Book Import Batch Processing</title>
        <link rel="stylesheet" href="../assets/css/enhanced-admin.css">
        <style>
            .progress-container {
                margin: 20px 0;
                background-color: #f1f1f1;
                border-radius: 5px;
                overflow: hidden;
            }
            .progress-bar {
                height: 30px;
                background-color: #4CAF50;
                text-align: center;
                line-height: 30px;
                color: white;
                transition: width 0.3s;
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
    </head>
    <body>
        <div class="container mt-4">
            <h1>Book Import Batch Processing</h1>

            <div class="progress-container">
                <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
            </div>

            <div class="log-container" id="logContainer">
                <p class="info">Starting batch processing...</p>
                <?php
                // Process the form submission
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $batchType = $_POST['batch_type'] ?? '';
                    $batchSize = (int)($_POST['batch_size'] ?? 10);
                    $batchDelay = (int)($_POST['batch_delay'] ?? 5);
                    $runAsCron = isset($_POST['run_as_cron']) && $_POST['run_as_cron'] == 1;

                    logMessage("Batch type: $batchType, Size: $batchSize, Delay: $batchDelay seconds");

                    if ($runAsCron) {
                        // Generate cron command
                        $scriptPath = realpath(__FILE__);
                        $cronCommand = "php $scriptPath $batchType $batchSize $batchDelay";

                        echo "<div class='alert alert-info'>";
                        echo "<h4>Cron Job Command</h4>";
                        echo "<p>Use the following command to set up a cron job:</p>";
                        echo "<pre>0 0 * * * $cronCommand</pre>";
                        echo "<p>This will run the batch process daily at midnight.</p>";
                        echo "</div>";
                    } else {
                        // Run the batch process
                        $batchItems = getBooksForBatch($db, $batchType, $batchSize);

                        if (empty($batchItems)) {
                            logMessage("No items found for batch processing", 'warning');
                        } else {
                            logMessage("Found " . count($batchItems) . " items for batch processing");

                            // Process the batch
                            $results = processBatch($db, $batchType, $batchItems);

                            // Update progress to 100%
                            echo "<script>
                                document.getElementById('progressBar').style.width = '100%';
                                document.getElementById('progressBar').innerText = '100%';
                            </script>";
                            flushOutput();

                            // Summary
                            echo "<h3>Batch Processing Summary</h3>";
                            echo "<p>Total items processed: " . count($batchItems) . "</p>";
                            echo "<p>Successfully processed: {$results['success']}</p>";
                            echo "<p>Failed to process: {$results['failed']}</p>";
                            echo "<p>Skipped items: {$results['skipped']}</p>";
                        }
                    }

                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                } else {
                    logMessage("Invalid request method. Please submit the form from the Book Import Tool page.", 'error');
                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                }
                ?>
            </div>
        </div>

        <script>
            // Auto-scroll to bottom of log container
            const logContainer = document.getElementById('logContainer');
            logContainer.scrollTop = logContainer.scrollHeight;

            // Set up interval to auto-scroll
            setInterval(function() {
                logContainer.scrollTop = logContainer.scrollHeight;
            }, 500);
        </script>
    </body>
    </html>
    <?php
} else {
    // CLI mode
    logMessage("Running in CLI mode");

    // Get command line arguments
    $batchType = $argv[1] ?? 'import_books';
    $batchSize = isset($argv[2]) ? (int)$argv[2] : 10;
    $batchDelay = isset($argv[3]) ? (int)$argv[3] : 5;

    logMessage("Batch type: $batchType, Size: $batchSize, Delay: $batchDelay seconds");

    // Run the batch process
    $batchItems = getBooksForBatch($db, $batchType, $batchSize);

    if (empty($batchItems)) {
        logMessage("No items found for batch processing", 'warning');
    } else {
        logMessage("Found " . count($batchItems) . " items for batch processing");

        // Process the batch
        $results = processBatch($db, $batchType, $batchItems);

        // Summary
        logMessage("Batch Processing Summary");
        logMessage("Total items processed: " . count($batchItems));
        logMessage("Successfully processed: {$results['success']}");
        logMessage("Failed to process: {$results['failed']}");
        logMessage("Skipped items: {$results['skipped']}");
    }
}
