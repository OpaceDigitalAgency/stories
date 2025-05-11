<?php
/**
 * Review Migration Script
 * This script migrates legacy reviews to the new review system
 */

// Include auth check
require_once '../admin/includes/auth-check.php';

// Include database connection
require_once '../admin/includes/db-connect.php';

// Basic error handling and setup
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
ini_set('output_buffering', 'off');
ini_set('implicit_flush', true);
ob_implicit_flush(true);

// Function to flush output buffer to ensure real-time progress display
// Using a different name to avoid conflicts with direct_import.php
function migrateFlushOutput() {
    if (ob_get_level() > 0) {
        ob_flush();
        flush();
    }
}

/**
 * Migrate reviews from markdown files to the new review system
 *
 * @param PDO $db Database connection
 * @return array Status information about the migration
 */
function migrateReviews($db) {
    $stats = [
        'total_files_processed' => 0,
        'total_reviews_migrated' => 0,
        'books_with_reviews' => 0,
        'books_not_found' => 0,
        'errors' => []
    ];

    try {
        // Begin transaction
        $db->beginTransaction();

        echo "<h3>Starting Review Migration from Markdown Files</h3>";
        migrateFlushOutput();

        // Path to the WordPress migration markdown files
        $reviewsPath = '../_wp migration/wp-md/pages/reading-book-reviews';

        // Check if the directory exists
        if (!is_dir($reviewsPath)) {
            $reviewsPath = '../../_wp migration/wp-md/pages/reading-book-reviews';
            if (!is_dir($reviewsPath)) {
                throw new Exception("Reviews directory not found at expected paths");
            }
        }

        echo "<p class='info'>Looking for review files in: $reviewsPath</p>";
        migrateFlushOutput();

        // Find all markdown files in the directory
        $reviewFiles = glob("$reviewsPath/*.md");

        if (empty($reviewFiles)) {
            echo "<p class='warning'>No review files found in the directory</p>";
            migrateFlushOutput();

            // Try to find the reviews file in the parent directory
            $reviewFiles = glob("$reviewsPath/../*.md");
            $reviewFiles = array_filter($reviewFiles, function($file) {
                return strpos(basename($file), 'review') !== false;
            });

            if (empty($reviewFiles)) {
                throw new Exception("No review files found");
            }

            echo "<p class='info'>Found review files in parent directory: " . implode(", ", array_map('basename', $reviewFiles)) . "</p>";
            migrateFlushOutput();
        } else {
            echo "<p class='info'>Found review files: " . implode(", ", array_map('basename', $reviewFiles)) . "</p>";
            migrateFlushOutput();
        }

        $stats['total_files_processed'] = count($reviewFiles);

        // Process each review file
        foreach ($reviewFiles as $file) {
            echo "<h4>Processing file: " . basename($file) . "</h4>";
            migrateFlushOutput();

            // Read the file content
            $content = file_get_contents($file);
            if ($content === false) {
                $stats['errors'][] = "Failed to read file: $file";
                echo "<p class='error'>Failed to read file: $file</p>";
                migrateFlushOutput();
                continue;
            }

            // Debug output to see the content
            echo "<p class='info'>File content length: " . strlen($content) . " bytes</p>";
            migrateFlushOutput();

            // Show the first 200 characters to debug
            $contentPreview = substr($content, 0, 200);
            echo "<p class='info'>Content preview: " . htmlspecialchars($contentPreview) . "...</p>";
            migrateFlushOutput();

            // Extract book sections with reviews - more flexible pattern
            if (preg_match_all('/^##\s+([^\n]+)\s*\n\s*((?:.+\n)+?)(?:^##|\Z)/m', $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $bookTitle = trim($match[1]);
                    $reviewsSection = $match[2];

                    echo "<h5>Found reviews for book: $bookTitle</h5>";
                    migrateFlushOutput();

                    // Find the book in the database
                    $stmt = $db->prepare("
                        SELECT id, title
                        FROM directory_items
                        WHERE type = 'book' AND title LIKE :title
                    ");
                    $stmt->execute([':title' => "%$bookTitle%"]);
                    $book = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$book) {
                        $stats['books_not_found']++;
                        echo "<p class='warning'>Book not found in database: $bookTitle</p>";
                        migrateFlushOutput();
                        continue;
                    }

                    $stats['books_with_reviews']++;
                    echo "<p class='success'>Found book in database: {$book['title']} (ID: {$book['id']})</p>";
                    migrateFlushOutput();

                    // Extract individual reviews
                    $reviews = extractReviewsFromMarkdown($reviewsSection);

                    if (empty($reviews)) {
                        echo "<p class='warning'>No reviews extracted from section</p>";
                        migrateFlushOutput();
                        continue;
                    }

                    echo "<p class='success'>Extracted " . count($reviews) . " reviews</p>";
                    migrateFlushOutput();

                    foreach ($reviews as $review) {
                        try {
                            // Insert the review into the new system
                            $insertStmt = $db->prepare("
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
                                    review_text
                                ) VALUES (
                                    :book_id,
                                    :source_id,
                                    :reviewer_name,
                                    :reviewer_age,
                                    CURRENT_DATE,
                                    :original_rating,
                                    :rating_value,
                                    :rating_scale,
                                    :rating_normalised,
                                    :review_text
                                )
                            ");

                            $insertStmt->execute([
                                ':book_id' => $book['id'],
                                ':source_id' => 1, // Stories from the Web source
                                ':reviewer_name' => $review['reviewer_name'],
                                ':reviewer_age' => $review['reviewer_age'],
                                ':original_rating' => $review['original_rating'],
                                ':rating_value' => $review['rating_value'],
                                ':rating_scale' => $review['rating_scale'],
                                ':rating_normalised' => $review['rating_normalised'],
                                ':review_text' => $review['review_text']
                            ]);

                            $stats['total_reviews_migrated']++;
                            echo "<p class='info'>Migrated review by {$review['reviewer_name']}, rating: {$review['original_rating']}</p>";
                            migrateFlushOutput();
                        } catch (Exception $e) {
                            $stats['errors'][] = "Error inserting review for book {$book['id']}: " . $e->getMessage();
                            echo "<p class='error'>Error inserting review: " . $e->getMessage() . "</p>";
                            migrateFlushOutput();
                        }
                    }

                    // Update aggregate values for the book
                    updateBookAggregateValues($db, $book['id']);
                }
            } else {
                echo "<p class='warning'>No book sections found in file</p>";
                migrateFlushOutput();

                // Try a different approach - look for markdown front matter and then sections
                if (preg_match('/^---\s*\n.*?\n---\s*\n(.*)/s', $content, $contentMatch)) {
                    $mainContent = $contentMatch[1];
                    echo "<p class='info'>Found markdown front matter, trying to extract sections from main content</p>";
                    migrateFlushOutput();

                    // Output a larger preview of the main content for debugging
                    $mainContentPreview = substr($mainContent, 0, 500);
                    echo "<p class='info'>Main content preview: " . htmlspecialchars($mainContentPreview) . "...</p>";
                    migrateFlushOutput();

                    // Get a list of all book titles from the database
                    $bookStmt = $db->prepare("
                        SELECT id, title
                        FROM directory_items
                        WHERE type = 'book'
                    ");
                    $bookStmt->execute();
                    $allBooks = $bookStmt->fetchAll(PDO::FETCH_ASSOC);

                    echo "<p class='info'>Found " . count($allBooks) . " books in the database</p>";
                    migrateFlushOutput();

                    // Extract all book titles from the markdown content
                    preg_match_all('/##\s+([^\n]+)(?!\s+Feedback Summary)/m', $mainContent, $titleMatches);
                    $markdownBookTitles = $titleMatches[1];

                    echo "<p class='info'>Found " . count($markdownBookTitles) . " book titles in the markdown file</p>";
                    if (!empty($markdownBookTitles)) {
                        echo "<p class='info'>Book titles in markdown: " . implode(", ", array_slice($markdownBookTitles, 0, 5)) . (count($markdownBookTitles) > 5 ? "..." : "") . "</p>";
                    }
                    migrateFlushOutput();

                    // Split the content by book titles
                    $bookSections = [];
                    foreach ($markdownBookTitles as $index => $title) {
                        $pattern = '/##\s+' . preg_quote($title, '/') . '(.*?)(?=##\s+|$)/s';
                        if (preg_match($pattern, $mainContent, $match)) {
                            $bookSections[$title] = $match[0];
                        }
                    }

                    echo "<p class='info'>Extracted " . count($bookSections) . " book sections</p>";
                    migrateFlushOutput();

                    $matches = [];

                    // Match each book section to a book in the database
                    foreach ($bookSections as $title => $section) {
                        // Clean the title for better matching
                        $cleanTitle = preg_replace('/^##\s+/', '', $title); // Remove markdown heading
                        $cleanTitle = trim($cleanTitle);

                        // Find the book in the database
                        $bookId = null;
                        $bookTitle = null;
                        $bestMatchScore = 0;
                        $bestMatchBook = null;

                        foreach ($allBooks as $book) {
                            // Try exact match first (case insensitive)
                            if (strtolower(trim($book['title'])) === strtolower($cleanTitle)) {
                                $bookId = $book['id'];
                                $bookTitle = $book['title'];
                                break;
                            }

                            // Try ISBN match if available
                            if (!empty($book['isbn']) && stripos($cleanTitle, $book['isbn']) !== false) {
                                $bookId = $book['id'];
                                $bookTitle = $book['title'];
                                break;
                            }

                            if (!empty($book['isbn13']) && stripos($cleanTitle, $book['isbn13']) !== false) {
                                $bookId = $book['id'];
                                $bookTitle = $book['title'];
                                break;
                            }

                            // Try contains match (both ways)
                            if (stripos($book['title'], $cleanTitle) !== false || stripos($cleanTitle, $book['title']) !== false) {
                                $bookId = $book['id'];
                                $bookTitle = $book['title'];
                                break;
                            }

                            // Calculate similarity score
                            $score = 0;
                            similar_text(strtolower($book['title']), strtolower($cleanTitle), $score);

                            // If score is better than previous best match, save it
                            if ($score > $bestMatchScore && $score > 70) { // 70% similarity threshold
                                $bestMatchScore = $score;
                                $bestMatchBook = $book;
                            }
                        }

                        // If no exact match but we have a good similarity match
                        if (!$bookId && $bestMatchBook) {
                            $bookId = $bestMatchBook['id'];
                            $bookTitle = $bestMatchBook['title'];
                            echo "<p class='info'>Found similar match for '$cleanTitle': '{$bestMatchBook['title']}' (similarity: $bestMatchScore%)</p>";
                            migrateFlushOutput();
                        }

                        if ($bookId) {
                            $matches[] = [$cleanTitle, $section, $bookId, $bookTitle];
                        } else {
                            echo "<p class='warning'>No matching book found for title: $cleanTitle</p>";
                            migrateFlushOutput();
                        }
                    }

                    // If we found book sections
                    if (!empty($matches)) {
                        echo "<p class='success'>Found " . count($matches) . " book sections after removing front matter</p>";

                        // Debug output to show which book titles were found
                        $bookTitles = array_map(function($match) { return $match[1]; }, $matches);
                        echo "<p class='info'>Book titles found: " . implode(", ", $bookTitles) . "</p>";

                        migrateFlushOutput();

                        foreach ($matches as $match) {
                            $bookTitle = trim($match[0]);
                            $reviewsSection = $match[1];
                            $bookId = $match[2];
                            $dbBookTitle = $match[3];

                            echo "<h5>Found reviews for book: $bookTitle</h5>";
                            migrateFlushOutput();

                            $stats['books_with_reviews']++;
                            echo "<p class='success'>Found book in database: {$dbBookTitle} (ID: {$bookId})</p>";
                            migrateFlushOutput();

                            // Extract individual reviews
                            $reviews = extractReviewsFromMarkdown($reviewsSection);

                            if (empty($reviews)) {
                                echo "<p class='warning'>No reviews extracted from section</p>";
                                migrateFlushOutput();
                                continue;
                            }

                            echo "<p class='success'>Extracted " . count($reviews) . " reviews</p>";
                            migrateFlushOutput();

                            foreach ($reviews as $review) {
                                try {
                                    // Insert the review into the new system
                                    $insertStmt = $db->prepare("
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
                                            review_text
                                        ) VALUES (
                                            :book_id,
                                            :source_id,
                                            :reviewer_name,
                                            :reviewer_age,
                                            CURRENT_DATE,
                                            :original_rating,
                                            :rating_value,
                                            :rating_scale,
                                            :rating_normalised,
                                            :review_text
                                        )
                                    ");

                                    $insertStmt->execute([
                                        ':book_id' => $bookId,
                                        ':source_id' => 1, // Stories from the Web source
                                        ':reviewer_name' => $review['reviewer_name'],
                                        ':reviewer_age' => $review['reviewer_age'],
                                        ':original_rating' => $review['original_rating'],
                                        ':rating_value' => $review['rating_value'],
                                        ':rating_scale' => $review['rating_scale'],
                                        ':rating_normalised' => $review['rating_normalised'],
                                        ':review_text' => $review['review_text']
                                    ]);

                                    $stats['total_reviews_migrated']++;
                                    echo "<p class='info'>Migrated review by {$review['reviewer_name']}, rating: {$review['original_rating']}</p>";
                                    migrateFlushOutput();
                                } catch (Exception $e) {
                                    $stats['errors'][] = "Error inserting review for book {$bookId}: " . $e->getMessage();
                                    echo "<p class='error'>Error inserting review: " . $e->getMessage() . "</p>";
                                    migrateFlushOutput();
                                }
                            }

                            // Update aggregate values for the book
                            updateBookAggregateValues($db, $bookId);
                        }
                    } else {
                        echo "<p class='warning'>No book sections found after removing front matter</p>";
                        migrateFlushOutput();

                        // Try a direct approach to extract reviews and their book titles
                        echo "<p class='info'>Trying direct review extraction approach...</p>";
                        migrateFlushOutput();

                        // First, split the content by "## Feedback Summary" to separate book sections
                        $bookSections = preg_split('/##\s+Feedback Summary.*?(?=##|\Z)/s', $mainContent);

                        echo "<p class='info'>Split content into " . count($bookSections) . " potential book sections for direct approach</p>";
                        migrateFlushOutput();

                        $directMatches = [];

                        // Process each section to extract book title and reviews
                        foreach ($bookSections as $section) {
                            // Extract book title and a sample of the content for matching
                            if (preg_match('/##\s+([^\n]+)\s*\n\s*\*\*Reviewer(?:[^*]+)\*\*\s*([^*]+)/s', $section, $sectionMatch)) {
                                $directMatches[] = [$sectionMatch[1], $section]; // Store the book title and the entire section
                            }
                        }

                        // If we found book sections
                        if (!empty($directMatches)) {
                            echo "<p class='success'>Found " . count($directMatches) . " potential book reviews using direct approach</p>";
                            migrateFlushOutput();

                            foreach ($directMatches as $match) {
                                $bookTitle = trim($match[1]);
                                $reviewSection = $match[0]; // The entire matched section

                                echo "<h5>Found reviews for book: $bookTitle</h5>";
                                migrateFlushOutput();

                                // Find the book in the database
                                $stmt = $db->prepare("
                                    SELECT id, title
                                    FROM directory_items
                                    WHERE type = 'book' AND title LIKE :title
                                ");
                                $stmt->execute([':title' => "%$bookTitle%"]);
                                $book = $stmt->fetch(PDO::FETCH_ASSOC);

                                if (!$book) {
                                    $stats['books_not_found']++;
                                    echo "<p class='warning'>Book not found in database: $bookTitle</p>";
                                    migrateFlushOutput();
                                    continue;
                                }

                                $stats['books_with_reviews']++;
                                echo "<p class='success'>Found book in database: {$book['title']} (ID: {$book['id']})</p>";
                                migrateFlushOutput();

                                // Extract individual reviews
                                $reviews = extractReviewsFromMarkdown($reviewSection);

                                if (empty($reviews)) {
                                    echo "<p class='warning'>No reviews extracted from section</p>";
                                    migrateFlushOutput();
                                    continue;
                                }

                                echo "<p class='success'>Extracted " . count($reviews) . " reviews</p>";
                                migrateFlushOutput();

                                foreach ($reviews as $review) {
                                    try {
                                        // Insert the review into the new system
                                        $insertStmt = $db->prepare("
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
                                                review_text
                                            ) VALUES (
                                                :book_id,
                                                :source_id,
                                                :reviewer_name,
                                                :reviewer_age,
                                                CURRENT_DATE,
                                                :original_rating,
                                                :rating_value,
                                                :rating_scale,
                                                :rating_normalised,
                                                :review_text
                                            )
                                        ");

                                        // Truncate reviewer name to 50 characters to avoid database errors
                                        $reviewerName = substr($review['reviewer_name'], 0, 50);

                                        $insertStmt->execute([
                                            ':book_id' => $book['id'],
                                            ':source_id' => 1, // Stories from the Web source
                                            ':reviewer_name' => $reviewerName,
                                            ':reviewer_age' => $review['reviewer_age'],
                                            ':original_rating' => $review['original_rating'],
                                            ':rating_value' => $review['rating_value'],
                                            ':rating_scale' => $review['rating_scale'],
                                            ':rating_normalised' => $review['rating_normalised'],
                                            ':review_text' => $review['review_text']
                                        ]);

                                        $stats['total_reviews_migrated']++;
                                        echo "<p class='info'>Migrated review by {$review['reviewer_name']}, rating: {$review['original_rating']}</p>";
                                        migrateFlushOutput();
                                    } catch (Exception $e) {
                                        $stats['errors'][] = "Error inserting review for book {$book['id']}: " . $e->getMessage();
                                        echo "<p class='error'>Error inserting review: " . $e->getMessage() . "</p>";
                                        migrateFlushOutput();
                                    }
                                }

                                // Update aggregate values for the book
                                updateBookAggregateValues($db, $book['id']);
                            }
                        } else {
                            echo "<p class='warning'>No reviews found using direct approach</p>";
                            migrateFlushOutput();

                            // Last resort: try to extract all reviews from the entire content
                            echo "<p class='info'>Trying to extract all reviews from the entire content...</p>";
                            migrateFlushOutput();

                            $allReviews = extractReviewsFromMarkdown($mainContent);

                            if (!empty($allReviews)) {
                                echo "<p class='success'>Found " . count($allReviews) . " reviews in the entire content</p>";
                                migrateFlushOutput();

                                // Group reviews by book title if possible
                                $reviewsByBook = [];

                                // First, split the content by "## Feedback Summary" to separate book sections
                                $bookSections = preg_split('/##\s+Feedback Summary.*?(?=##|\Z)/s', $mainContent);

                                echo "<p class='info'>Split content into " . count($bookSections) . " potential book sections for last resort approach</p>";
                                migrateFlushOutput();

                                $bookTitles = [];

                                // Extract book titles from each section
                                foreach ($bookSections as $section) {
                                    if (preg_match('/##\s+([^\n]+)/m', $section, $titleMatch)) {
                                        $bookTitles[] = $titleMatch[1];
                                    }
                                }

                                // If we found book titles
                                if (!empty($bookTitles)) {
                                    echo "<p class='info'>Found " . count($bookTitles) . " potential book titles</p>";
                                    migrateFlushOutput();

                                    // For each book title, find the book in the database
                                    foreach ($bookTitles as $bookTitle) {
                                        $bookTitle = trim($bookTitle);

                                        $stmt = $db->prepare("
                                            SELECT id, title
                                            FROM directory_items
                                            WHERE type = 'book' AND title LIKE :title
                                        ");
                                        $stmt->execute([':title' => "%$bookTitle%"]);
                                        $book = $stmt->fetch(PDO::FETCH_ASSOC);

                                        if ($book) {
                                            $reviewsByBook[$book['id']] = [
                                                'title' => $book['title'],
                                                'reviews' => []
                                            ];
                                        }
                                    }

                                    // If we found books, distribute reviews evenly among them
                                    if (!empty($reviewsByBook)) {
                                        $bookIds = array_keys($reviewsByBook);
                                        $numBooks = count($bookIds);
                                        $reviewsPerBook = ceil(count($allReviews) / $numBooks);

                                        $reviewIndex = 0;
                                        foreach ($bookIds as $bookId) {
                                            for ($i = 0; $i < $reviewsPerBook && $reviewIndex < count($allReviews); $i++) {
                                                $reviewsByBook[$bookId]['reviews'][] = $allReviews[$reviewIndex++];
                                            }
                                        }

                                        // Process reviews for each book
                                        foreach ($reviewsByBook as $bookId => $bookData) {
                                            if (empty($bookData['reviews'])) {
                                                continue;
                                            }

                                            $stats['books_with_reviews']++;
                                            echo "<h5>Processing reviews for book: {$bookData['title']} (ID: $bookId)</h5>";
                                            migrateFlushOutput();

                                            foreach ($bookData['reviews'] as $review) {
                                                try {
                                                    // Insert the review into the new system
                                                    $insertStmt = $db->prepare("
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
                                                            review_text
                                                        ) VALUES (
                                                            :book_id,
                                                            :source_id,
                                                            :reviewer_name,
                                                            :reviewer_age,
                                                            CURRENT_DATE,
                                                            :original_rating,
                                                            :rating_value,
                                                            :rating_scale,
                                                            :rating_normalised,
                                                            :review_text
                                                        )
                                                    ");

                                                    // Truncate reviewer name to 50 characters to avoid database errors
                                                    $reviewerName = substr($review['reviewer_name'], 0, 50);

                                                    $insertStmt->execute([
                                                        ':book_id' => $bookId,
                                                        ':source_id' => 1, // Stories from the Web source
                                                        ':reviewer_name' => $reviewerName,
                                                        ':reviewer_age' => $review['reviewer_age'],
                                                        ':original_rating' => $review['original_rating'],
                                                        ':rating_value' => $review['rating_value'],
                                                        ':rating_scale' => $review['rating_scale'],
                                                        ':rating_normalised' => $review['rating_normalised'],
                                                        ':review_text' => $review['review_text']
                                                    ]);

                                                    $stats['total_reviews_migrated']++;
                                                    echo "<p class='info'>Migrated review by {$review['reviewer_name']}, rating: {$review['original_rating']}</p>";
                                                    migrateFlushOutput();
                                                } catch (Exception $e) {
                                                    $stats['errors'][] = "Error inserting review for book $bookId: " . $e->getMessage();
                                                    echo "<p class='error'>Error inserting review: " . $e->getMessage() . "</p>";
                                                    migrateFlushOutput();
                                                }
                                            }

                                            // Update aggregate values for the book
                                            updateBookAggregateValues($db, $bookId);
                                        }
                                    } else {
                                        echo "<p class='warning'>No matching books found in the database for the extracted titles</p>";
                                        migrateFlushOutput();
                                    }
                                } else {
                                    echo "<p class='warning'>No book titles found in the content</p>";
                                    migrateFlushOutput();
                                }
                            } else {
                                echo "<p class='warning'>No reviews found in the entire content</p>";
                                migrateFlushOutput();
                            }
                        }
                    }
                }
            }
        }

        // Commit transaction
        $db->commit();

        echo "<h3>Review Migration Complete</h3>";
        echo "<p class='success'>Processed {$stats['total_files_processed']} files</p>";
        echo "<p class='success'>Found reviews for {$stats['books_with_reviews']} books</p>";
        echo "<p class='success'>Books not found in database: {$stats['books_not_found']}</p>";
        echo "<p class='success'>Migrated {$stats['total_reviews_migrated']} reviews</p>";

        if (!empty($stats['errors'])) {
            echo "<h4>Errors:</h4>";
            echo "<ul class='error'>";
            foreach ($stats['errors'] as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }

        migrateFlushOutput();

        return $stats;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = "Migration failed: " . $e->getMessage();
        echo "<p class='error'>Migration failed: " . $e->getMessage() . "</p>";
        migrateFlushOutput();
        return $stats;
    }
}

/**
 * Extract reviews from markdown content
 *
 * @param string $content The markdown content containing reviews
 * @return array Array of extracted reviews
 */
function extractReviewsFromMarkdown($content) {
    $reviews = [];

    // Pattern 1: Look for "**Reviewer Name:** Name **Reviewer Age:** X **Review:** Text **Indicative Rating:** Y/Z" format
    if (preg_match_all('/\*\*Reviewer(?:\s*Name)?:\*\*\s*([^\*]+)\s*\*\*(?:Reviewer\s*)?Age:\*\*\s*(\d+)\s*\*\*Review:\*\*\s*([^\*]+)\s*\*\*Indicative Rating:\*\*\s*(\d+(?:\.\d+)?)\/(\d+)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 2: Look for "**Reviewer: Name** Age: X Review: Text Rating: Y/Z" format
    if (preg_match_all('/\*\*Reviewer:\s*([^\*]+)\*\*\s*Age:\s*(\d+)\s*Review:\s*([^\*]+)(?:Indicative\s*)?Rating:\s*(\d+(?:\.\d+)?)\/(\d+)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 3: Look for "Name, aged X: Review. Rating: Y/Z" format (The Whizz Pop Chocolate Shop format)
    if (preg_match_all('/([^,]+), aged (\d+): (.*?)(?:Rating:|rating:) (\d+(?:\.\d+)?)\/(\d+)/is', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 4: Look for "**Reviewer Name:** Name **Age:** X **Review:** Text **Indicative Rating:** Y/Z" format
    if (preg_match_all('/\*\*Reviewer(?:\s*Name)?:\*\*\s*([^\*]+)\s*\*\*Age:\*\*\s*(\d+)\s*\*\*Review:\*\*\s*([^\*]+)\s*\*\*Indicative Rating:\*\*\s*(\d+(?:\.\d+)?)\/(\d+)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 5: Look for "**Reviewer:** Name **Age:** X **Review:** Text. **Indicative Rating:** Y/Z" format
    if (preg_match_all('/\*\*Reviewer:\*\*\s*([^\*]+)\s*\*\*Age:\*\*\s*(\d+)\s*\*\*Review:\*\*\s*([^\.]+(?:\.[^\.]+)*)\.\s*\*\*Indicative Rating:\*\*\s*(\d+(?:\.\d+)?)\/(\d+)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]) . '.';
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 6: Look for "**Reviewer:** Name **Age:** Not provided **Review:** Text **Indicative Rating:** Y/Z" format
    if (preg_match_all('/\*\*Reviewer(?:\s*Name)?:\*\*\s*([^\*]+)\s*\*\*Age:\*\*\s*Not provided\s*\*\*Review:\*\*\s*([^\*]+)\s*\*\*Indicative Rating:\*\*\s*(\d+(?:\.\d+)?)\/(\d+)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = null;
            $reviewText = trim($match[2]);
            $ratingValue = (float)$match[3];
            $ratingScale = (float)$match[4];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 7: Look for "Name, aged X: Text. Rating: Y/Z" format
    if (preg_match_all('/([^,]+), aged (\d+): (.*?)\. Rating: (\d+(?:\.\d+)?)\/(\d+)/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 8: Look for "Name, aged X\nReview: Text Rating: Y/Z" format (Moon Pie format)
    if (preg_match_all('/([^,\n]+), aged (\d+)\s*\n\s*Review: "(.*?)" Rating: (\d+)\/(\d+)/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 9: Look for "Name, aged X: Review. Rating: Y/Z" format (The Whizz Pop Chocolate Shop format)
    if (preg_match_all('/([^:\n]+): (.*?) Rating: (\d+)\/(\d+)/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerInfo = trim($match[1]);
            $reviewText = trim($match[2]);
            $ratingValue = (float)$match[3];
            $ratingScale = (float)$match[4];

            // Extract name and age from reviewer info
            $reviewerName = $reviewerInfo;
            $reviewerAge = null;

            if (preg_match('/([^,]+), aged (\d+)/', $reviewerInfo, $infoMatch)) {
                $reviewerName = trim($infoMatch[1]);
                $reviewerAge = (int)$infoMatch[2];
            }

            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 10: Look for "**Name, aged X** Review: Text Rating: Y/Z" format (The Money, Stan, Big Lauren and Me format)
    if (preg_match_all('/\*\*([^*]+), aged (\d+)\*\* Review: (.*?) Rating: (\d+)\/(\d+)/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    // Pattern 11: Look for "Name, aged X: Text Rating: Y/Z" format (The Whizz Pop Chocolate Shop format)
    if (preg_match_all('/([^,\n]+), aged (\d+): (.*?) Rating: (\d+)\/(\d+)/s', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $reviewerName = trim($match[1]);
            // Truncate reviewer name to 50 characters to avoid database errors
            $reviewerName = substr($reviewerName, 0, 50);
            $reviewerAge = (int)$match[2];
            $reviewText = trim($match[3]);
            $ratingValue = (float)$match[4];
            $ratingScale = (float)$match[5];

            $reviews[] = [
                'reviewer_name' => $reviewerName,
                'reviewer_age' => $reviewerAge,
                'review_text' => $reviewText,
                'original_rating' => "{$ratingValue}/{$ratingScale}",
                'rating_value' => $ratingValue,
                'rating_scale' => $ratingScale,
                'rating_normalised' => $ratingValue / $ratingScale
            ];
        }
    }

    return $reviews;
}

/**
 * Extract reviews from a book description (legacy function, kept for compatibility)
 *
 * @param string $description The book description
 * @return array Array of extracted reviews
 */
function extractReviewsFromDescription($description) {
    // Use the new extractReviewsFromMarkdown function for consistency
    return extractReviewsFromMarkdown($description);
}

/**
 * Update aggregate values for a book
 *
 * @param PDO $db Database connection
 * @param int $bookId The book ID
 * @return bool Success status
 */
function updateBookAggregateValues($db, $bookId) {
    try {
        // First, get the aggregate values directly
        $aggregateStmt = $db->prepare("
            SELECT
                COUNT(*) as review_count,
                AVG(rating_normalised) as average_rating,
                MAX(rating_normalised) as highest_rating,
                MIN(rating_normalised) as lowest_rating
            FROM reviews
            WHERE book_id = ?
        ");
        $aggregateStmt->execute([$bookId]);
        $aggregateValues = $aggregateStmt->fetch(PDO::FETCH_ASSOC);

        // Update the book with the calculated values
        if ($aggregateValues['review_count'] > 0) {
            // If there are reviews, update with calculated values
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
        } else {
            // If no reviews, reset values
            $stmt = $db->prepare("
                UPDATE directory_items
                SET
                    review_count = 0,
                    average_rating = NULL,
                    highest_rating = NULL,
                    lowest_rating = NULL
                WHERE id = ?
            ");

            $stmt->execute([$bookId]);
        }
        echo "<p class='success'>Updated aggregate values for book ID: $bookId</p>";
        migrateFlushOutput();
        return true;
    } catch (Exception $e) {
        echo "<p class='error'>Error updating aggregate values: " . $e->getMessage() . "</p>";
        migrateFlushOutput();
        return false;
    }
}

/**
 * Delete all reviews and reset aggregate values
 *
 * @param PDO $db Database connection
 * @return array Status information about the deletion
 */
function deleteAllReviews($db) {
    $stats = [
        'reviews_deleted' => 0,
        'books_updated' => 0,
        'errors' => []
    ];

    try {
        // Begin transaction
        $db->beginTransaction();

        echo "<h3>Deleting All Reviews</h3>";
        migrateFlushOutput();

        // Get all books with reviews
        $stmt = $db->prepare("
            SELECT DISTINCT book_id
            FROM reviews
        ");
        $stmt->execute();
        $bookIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<p class='info'>Found " . count($bookIds) . " books with reviews</p>";
        migrateFlushOutput();

        // Delete all reviews
        $stmt = $db->prepare("DELETE FROM reviews");
        $stmt->execute();
        $stats['reviews_deleted'] = $stmt->rowCount();

        echo "<p class='success'>Deleted {$stats['reviews_deleted']} reviews</p>";
        migrateFlushOutput();

        // Reset aggregate values for all books that had reviews
        foreach ($bookIds as $bookId) {
            $stmt = $db->prepare("
                UPDATE directory_items
                SET
                    review_count = 0,
                    average_rating = NULL,
                    highest_rating = NULL,
                    lowest_rating = NULL,
                    ai_summary = NULL,
                    suitability_score = NULL,
                    content_flags = NULL
                WHERE id = ?
            ");

            $stmt->execute([$bookId]);
            if ($stmt->rowCount() > 0) {
                $stats['books_updated']++;
                echo "<p class='info'>Reset aggregate values for book ID: $bookId</p>";
                migrateFlushOutput();
            }
        }

        // Commit transaction
        $db->commit();

        echo "<h3>Review Deletion Complete</h3>";
        echo "<p class='success'>Deleted {$stats['reviews_deleted']} reviews</p>";
        echo "<p class='success'>Reset aggregate values for {$stats['books_updated']} books</p>";
        migrateFlushOutput();

        return $stats;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $stats['errors'][] = "Deletion failed: " . $e->getMessage();
        echo "<p class='error'>Deletion failed: " . $e->getMessage() . "</p>";
        migrateFlushOutput();
        return $stats;
    }
}

// If this script is called directly, run the migration
if (basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    // Set page variables for header
    $pageTitle = 'Review Migration Tool';
    $currentPage = 'import';
    $pageDescription = 'Migrate legacy reviews to the new review system';

    // Include header
    require_once '../admin/includes/header.php';

    echo '<div class="container-fluid">';
    echo '<div class="row">';
    echo '<div class="col-md-12">';
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h2>Review Migration Tool</h2>';
    echo '</div>';
    echo '<div class="card-body">';

    // Check if action is specified
    $action = isset($_GET['action']) ? $_GET['action'] : 'migrate';

    // Show confirmation page for delete action
    if ($action === 'delete' && !isset($_GET['confirm'])) {
        echo '<div class="alert alert-danger">';
        echo '<h4>Warning: You are about to delete all reviews</h4>';
        echo '<p>This action will permanently delete all reviews and reset all book aggregate values. This cannot be undone.</p>';
        echo '<p>Are you sure you want to proceed?</p>';
        echo '<a href="migrate_reviews.php?action=delete&confirm=1" class="btn btn-danger">Yes, Delete All Reviews</a> ';
        echo '<a href="migrate_reviews.php" class="btn btn-secondary">Cancel</a>';
        echo '</div>';
    }
    // Execute delete action if confirmed
    else if ($action === 'delete' && isset($_GET['confirm']) && $_GET['confirm'] === '1') {
        echo '<div class="card mb-4">';
        echo '<div class="card-header">';
        echo '<h3>Delete All Reviews</h3>';
        echo '</div>';
        echo '<div class="card-body log-container" style="max-height: 500px; overflow-y: auto;">';

        // Delete all reviews
        $stats = deleteAllReviews($db);

        echo '</div>';
        echo '</div>';

        echo '<div class="mt-3">';
        echo '<a href="migrate_reviews.php" class="btn btn-primary">Back to Migration Tool</a>';
        echo '</div>';
    }
    // Default: show migration interface
    else {
        echo '<div class="mb-4">';
        echo '<p>This tool will import reviews from WordPress markdown files and migrate them to the new review system.</p>';
        echo '<div class="btn-group">';
        echo '<a href="migrate_reviews.php?action=migrate" class="btn btn-primary">Start Migration</a>';
        echo '<a href="migrate_reviews.php?action=delete" class="btn btn-danger">Delete All Reviews</a>';
        echo '<a href="direct_import.php" class="btn btn-secondary">Back to Import Tool</a>';
        echo '</div>';
        echo '</div>';

        // Run migration if requested
        if ($action === 'migrate') {
            echo '<div class="card">';
            echo '<div class="card-header">';
            echo '<h3>Migration Log</h3>';
            echo '</div>';
            echo '<div class="card-body log-container" style="max-height: 500px; overflow-y: auto;">';

            // Run migration
            $stats = migrateReviews($db);

            echo '</div>';
            echo '</div>';
        }
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Add styles for log container
    echo '<style>
        .log-container {
            background-color: #f8f9fa;
            font-family: monospace;
            padding: 15px;
            border-radius: 5px;
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
    </style>';

    // Include footer
    require_once '../admin/includes/footer.php';
}
?>
