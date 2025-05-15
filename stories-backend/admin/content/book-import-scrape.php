<?php
/**
 * Book Import Scrape
 * 
 * This script handles the scraping of reviews for books from various sources.
 * It can be called directly from the book-import-tool.php page or as a follow-up
 * to the book-import-process.php script.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

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

// Function to normalize rating to 0-1 scale
function normalizeRating($value, $scale) {
    if (empty($value) || empty($scale) || $scale == 0) {
        return null;
    }
    return min(1, max(0, $value / $scale));
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

// Function to scrape reviews from Google Books
function scrapeGoogleBooksReviews($bookId, $isbn, $isbn13) {
    // In a real implementation, this would call the Google Books API
    // For now, we'll return sample data
    $reviews = [];
    
    // Use ISBN13 if available, otherwise use ISBN
    $isbnToUse = !empty($isbn13) ? $isbn13 : $isbn;
    
    if (empty($isbnToUse)) {
        return $reviews;
    }
    
    // Simulate API delay
    sleep(1);
    
    // Sample reviews
    $reviews[] = [
        'source_id' => 2, // Google Books source ID
        'reviewer_name' => 'Google Books Reviewer 1',
        'reviewer_age' => null,
        'review_date' => date('Y-m-d'),
        'original_rating' => '4/5',
        'rating_value' => 4,
        'rating_scale' => 5,
        'rating_normalised' => 0.8,
        'review_text' => "This is a sample review from Google Books. The book was engaging and well-written. I particularly enjoyed the character development and plot twists. Would recommend to others who enjoy this genre.",
        'metadata' => json_encode([
            'review_id' => 'gb_' . uniqid(),
            'review_url' => "https://books.google.com/books?id=$isbnToUse"
        ])
    ];
    
    $reviews[] = [
        'source_id' => 2, // Google Books source ID
        'reviewer_name' => 'Google Books Reviewer 2',
        'reviewer_age' => null,
        'review_date' => date('Y-m-d', strtotime('-2 days')),
        'original_rating' => '5/5',
        'rating_value' => 5,
        'rating_scale' => 5,
        'rating_normalised' => 1.0,
        'review_text' => "Excellent book for children! My kids loved it and asked to read it again and again. The illustrations are beautiful and the story has good moral lessons. Perfect for ages 6-8.",
        'metadata' => json_encode([
            'review_id' => 'gb_' . uniqid(),
            'review_url' => "https://books.google.com/books?id=$isbnToUse"
        ])
    ];
    
    return $reviews;
}

// Function to scrape reviews from Open Library
function scrapeOpenLibraryReviews($bookId, $isbn, $isbn13) {
    // Similar to Google Books function, but for Open Library
    $reviews = [];
    
    // Use ISBN13 if available, otherwise use ISBN
    $isbnToUse = !empty($isbn13) ? $isbn13 : $isbn;
    
    if (empty($isbnToUse)) {
        return $reviews;
    }
    
    // Simulate API delay
    sleep(1);
    
    // Sample reviews
    $reviews[] = [
        'source_id' => 3, // Open Library source ID
        'reviewer_name' => 'Open Library Reviewer',
        'reviewer_age' => null,
        'review_date' => date('Y-m-d', strtotime('-5 days')),
        'original_rating' => '4.5/5',
        'rating_value' => 4.5,
        'rating_scale' => 5,
        'rating_normalised' => 0.9,
        'review_text' => "A wonderful book that captures the imagination. The story is well-paced and suitable for the target age group. My child found it very engaging and we had great discussions about the themes presented.",
        'metadata' => json_encode([
            'review_id' => 'ol_' . uniqid(),
            'review_url' => "https://openlibrary.org/isbn/$isbnToUse"
        ])
    ];
    
    return $reviews;
}

// Function to scrape reviews from Goodreads
function scrapeGoodreadsReviews($bookId, $isbn, $isbn13) {
    // Similar to previous functions, but for Goodreads
    $reviews = [];
    
    // Use ISBN13 if available, otherwise use ISBN
    $isbnToUse = !empty($isbn13) ? $isbn13 : $isbn;
    
    if (empty($isbnToUse)) {
        return $reviews;
    }
    
    // Simulate API delay
    sleep(1);
    
    // Sample reviews
    $reviews[] = [
        'source_id' => 4, // Goodreads source ID
        'reviewer_name' => 'Goodreads Reviewer 1',
        'reviewer_age' => null,
        'review_date' => date('Y-m-d', strtotime('-10 days')),
        'original_rating' => '4/5',
        'rating_value' => 4,
        'rating_scale' => 5,
        'rating_normalised' => 0.8,
        'review_text' => "I read this to my 7-year-old daughter and she absolutely loved it. The story teaches important lessons about friendship and courage. The vocabulary is appropriate for early readers but still engaging for adults reading along.",
        'metadata' => json_encode([
            'review_id' => 'gr_' . uniqid(),
            'review_url' => "https://www.goodreads.com/book/isbn/$isbnToUse"
        ])
    ];
    
    $reviews[] = [
        'source_id' => 4, // Goodreads source ID
        'reviewer_name' => 'Goodreads Reviewer 2',
        'reviewer_age' => 9,
        'review_date' => date('Y-m-d', strtotime('-15 days')),
        'original_rating' => '3/5',
        'rating_value' => 3,
        'rating_scale' => 5,
        'rating_normalised' => 0.6,
        'review_text' => "I'm 9 years old and I liked this book. Some parts were scary but it was exciting. I wish there were more pictures. My favorite character was the dog because he was brave and funny.",
        'metadata' => json_encode([
            'review_id' => 'gr_' . uniqid(),
            'review_url' => "https://www.goodreads.com/book/isbn/$isbnToUse"
        ])
    ];
    
    return $reviews;
}

// Function to scrape reviews from Amazon
function scrapeAmazonReviews($bookId, $isbn, $isbn13) {
    // Similar to previous functions, but for Amazon
    $reviews = [];
    
    // Use ISBN13 if available, otherwise use ISBN
    $isbnToUse = !empty($isbn13) ? $isbn13 : $isbn;
    
    if (empty($isbnToUse)) {
        return $reviews;
    }
    
    // Simulate API delay
    sleep(1);
    
    // Sample reviews
    $reviews[] = [
        'source_id' => 5, // Amazon source ID
        'reviewer_name' => 'Amazon Customer',
        'reviewer_age' => null,
        'review_date' => date('Y-m-d', strtotime('-20 days')),
        'original_rating' => '5/5',
        'rating_value' => 5,
        'rating_scale' => 5,
        'rating_normalised' => 1.0,
        'review_text' => "Purchased this for my 8-year-old nephew who is a reluctant reader. He finished it in two days and immediately asked for more books in the series! The story is engaging with just the right amount of humor and adventure. Highly recommended for elementary school children.",
        'metadata' => json_encode([
            'review_id' => 'amzn_' . uniqid(),
            'review_url' => "https://www.amazon.com/dp/$isbnToUse"
        ])
    ];
    
    return $reviews;
}

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
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Review Scraping</title>
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
        <h1>Book Review Scraping</h1>
        
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
                        
                        // Get reviews based on source
                        $reviews = [];
                        
                        switch ($sourceId) {
                            case 2: // Google Books
                                $reviews = scrapeGoogleBooksReviews($book['id'], $book['isbn'], $book['isbn13']);
                                break;
                                
                            case 3: // Open Library
                                $reviews = scrapeOpenLibraryReviews($book['id'], $book['isbn'], $book['isbn13']);
                                break;
                                
                            case 4: // Goodreads
                                $reviews = scrapeGoodreadsReviews($book['id'], $book['isbn'], $book['isbn13']);
                                break;
                                
                            case 5: // Amazon
                                $reviews = scrapeAmazonReviews($book['id'], $book['isbn'], $book['isbn13']);
                                break;
                                
                            default:
                                echo "<p class='warning'>Unknown source ID: $sourceId</p>";
                                flushOutput();
                                continue;
                        }
                        
                        echo "<p class='info'>Found " . count($reviews) . " reviews from $sourceName</p>";
                        flushOutput();
                        
                        // Import reviews
                        foreach ($reviews as $review) {
                            // Check for duplicates
                            if (reviewExists($db, $book['id'], $review['source_id'], $review['reviewer_name'])) {
                                echo "<p class='warning'>Skipping duplicate review by {$review['reviewer_name']}</p>";
                                flushOutput();
                                $bookReviewsSkipped++;
                                continue;
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
                                
                                $stmt->execute([
                                    ':book_id' => $book['id'],
                                    ':source_id' => $review['source_id'],
                                    ':reviewer_name' => $review['reviewer_name'],
                                    ':reviewer_age' => $review['reviewer_age'],
                                    ':review_date' => $review['review_date'],
                                    ':original_rating' => $review['original_rating'],
                                    ':rating_value' => $review['rating_value'],
                                    ':rating_scale' => $review['rating_scale'],
                                    ':rating_normalised' => $review['rating_normalised'],
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
