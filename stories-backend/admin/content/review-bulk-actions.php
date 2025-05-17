<?php
/**
 * Review Bulk Actions
 *
 * This script handles bulk actions for reviews, such as delete and analyze.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

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

// Function to update book aggregate values
function updateBookAggregateValues($db, $bookId) {
    // Count the reviews for this book
    $countStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ?");
    $countStmt->execute([$bookId]);
    $reviewCount = $countStmt->fetchColumn();

    // If there are no reviews, reset all values to 0
    if ($reviewCount == 0) {
        error_log("Book ID {$bookId} - No reviews found, resetting all values to 0");

        $stmt = $db->prepare("
            UPDATE directory_items
            SET
                review_count = 0,
                average_rating = 0,
                highest_rating = 0,
                lowest_rating = 0
            WHERE id = ?
        ");

        $stmt->execute([$bookId]);
    } else {
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

        // Log the aggregate values for debugging
        error_log("Book ID {$bookId} - New aggregate values: " .
            "Count: {$aggregateValues['review_count']}, " .
            "Avg: {$aggregateValues['average_rating']}, " .
            "Max: {$aggregateValues['highest_rating']}, " .
            "Min: {$aggregateValues['lowest_rating']}");

        // Update the directory item
        $stmt = $db->prepare("
            UPDATE directory_items
            SET
                review_count = ?,
                average_rating = ?,
                highest_rating = ?,
                lowest_rating = ?
            WHERE id = ?
        ");

        // Handle null values properly
        $reviewCount = $aggregateValues['review_count'] ?? 0;
        $avgRating = $aggregateValues['average_rating'] !== null ? $aggregateValues['average_rating'] : 0;
        $highestRating = $aggregateValues['highest_rating'] !== null ? $aggregateValues['highest_rating'] : 0;
        $lowestRating = $aggregateValues['lowest_rating'] !== null ? $aggregateValues['lowest_rating'] : 0;

        $stmt->execute([
            $reviewCount,
            $avgRating,
            $highestRating,
            $lowestRating,
            $bookId
        ]);
    }

    // Verify the update
    $verifyStmt = $db->prepare("
        SELECT review_count, average_rating, highest_rating, lowest_rating
        FROM directory_items
        WHERE id = ?
    ");
    $verifyStmt->execute([$bookId]);
    $updatedValues = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    error_log("Book ID {$bookId} - Verified values after update: " .
        "Count: {$updatedValues['review_count']}, " .
        "Avg: {$updatedValues['average_rating']}, " .
        "Max: {$updatedValues['highest_rating']}, " .
        "Min: {$updatedValues['lowest_rating']}");

    return true;
}

// Main processing logic
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Bulk Actions</title>
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
        <h1>Review Bulk Actions</h1>

        <div class="progress-container">
            <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
        </div>

        <div class="log-container" id="logContainer">
            <p class="info">Starting bulk action process...</p>
            <?php
            // Process the request
            try {
                // Check if we have selected reviews and an action
                if (!isset($_POST['selected_reviews']) || !isset($_POST['bulk_action'])) {
                    throw new Exception("No reviews selected or no action specified");
                }

                $selectedIds = $_POST['selected_reviews'];
                $action = $_POST['bulk_action'];

                if (empty($selectedIds)) {
                    throw new Exception("No reviews selected");
                }

                echo "<p class='info'>Action: $action</p>";
                echo "<p class='info'>Selected reviews: " . count($selectedIds) . "</p>";
                flushOutput();

                // Process based on action
                switch ($action) {
                    case 'delete':
                        // Delete selected reviews
                        $db->beginTransaction();

                        // Get affected book IDs first
                        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                        $bookStmt = $db->prepare("SELECT DISTINCT book_id FROM reviews WHERE id IN ($placeholders)");
                        $bookStmt->execute($selectedIds);
                        $bookIds = $bookStmt->fetchAll(PDO::FETCH_COLUMN);

                        // Delete the reviews
                        $deleteStmt = $db->prepare("DELETE FROM reviews WHERE id IN ($placeholders)");
                        $deleteStmt->execute($selectedIds);
                        $deletedCount = $deleteStmt->rowCount();

                        // Log the deletion for debugging
                        error_log("Deleted {$deletedCount} reviews for books: " . implode(', ', $bookIds));

                        // Update book ratings
                        foreach ($bookIds as $index => $bookId) {
                            $progress = round(($index / count($bookIds)) * 100);
                            echo "<script>
                                document.getElementById('progressBar').style.width = '$progress%';
                                document.getElementById('progressBar').innerText = '$progress%';
                            </script>";
                            flushOutput();

                            // Get book title
                            $titleStmt = $db->prepare("SELECT title FROM directory_items WHERE id = ?");
                            $titleStmt->execute([$bookId]);
                            $bookTitle = $titleStmt->fetchColumn();

                            echo "<p class='info'>Updating aggregate values for book: $bookTitle</p>";
                            flushOutput();

                            updateBookAggregateValues($db, $bookId);
                        }

                        $db->commit();

                        echo "<p class='success'>Successfully deleted $deletedCount reviews</p>";
                        break;

                    case 'analyze':
                        // Analyze selected reviews with AI
                        $reviewAnalyzer = new \Services\AI\ReviewAnalyzer($db);

                        $totalReviews = count($selectedIds);
                        $analyzedCount = 0;

                        foreach ($selectedIds as $index => $reviewId) {
                            $progress = round(($index / $totalReviews) * 100);
                            echo "<script>
                                document.getElementById('progressBar').style.width = '$progress%';
                                document.getElementById('progressBar').innerText = '$progress%';
                            </script>";
                            flushOutput();

                            // Get review details
                            $reviewStmt = $db->prepare("
                                SELECT r.*, d.title as book_title
                                FROM reviews r
                                LEFT JOIN directory_items d ON r.book_id = d.id
                                WHERE r.id = ?
                            ");
                            $reviewStmt->execute([$reviewId]);
                            $review = $reviewStmt->fetch();

                            if (!$review) {
                                echo "<p class='warning'>Review ID $reviewId not found</p>";
                                flushOutput();
                                continue;
                            }

                            echo "<p class='info'>Analyzing review for: {$review['book_title']}</p>";
                            flushOutput();

                            if ($reviewAnalyzer->analyzeReview($reviewId)) {
                                echo "<p class='success'>Successfully analyzed review</p>";
                                $analyzedCount++;
                            } else {
                                echo "<p class='error'>Failed to analyze review: " . $reviewAnalyzer->getLastError() . "</p>";
                            }
                            flushOutput();
                        }

                        echo "<p class='success'>Successfully analyzed $analyzedCount out of $totalReviews reviews</p>";
                        break;

                    default:
                        throw new Exception("Unknown action: $action");
                }

                // Update progress to 100%
                echo "<script>
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('progressBar').innerText = '100%';
                </script>";
                flushOutput();

                echo "<p><a href='book-import-tool.php?tab=reviews' class='btn btn-primary'>Return to Reviews</a></p>";
            } catch (Exception $e) {
                echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
                echo "<p><a href='book-import-tool.php?tab=reviews' class='btn btn-primary'>Return to Reviews</a></p>";
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
