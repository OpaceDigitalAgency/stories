<?php
/**
 * Book Import Process
 * 
 * This script handles the processing of book import requests from the book-import-tool.php page.
 * It supports importing books by author, publisher, year, age range, or ISBN list.
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

// Function to generate a unique slug
function generateUniqueSlug($db, $title) {
    // Convert to lowercase and replace non-alphanumeric with hyphens
    $baseSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
    $baseSlug = trim($baseSlug, '-');
    $slug = $baseSlug;

    // Check if slug exists in directory_items
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM directory_items WHERE slug = ?");
    $stmt->execute([$slug]);
    $result = $stmt->fetch();

    // If slug exists, append a number
    if ($result['count'] > 0) {
        $i = 1;
        $newSlug = $slug;
        do {
            $newSlug = $slug . '-' . $i++;
            $stmt->execute([$newSlug]);
            $result = $stmt->fetch();
        } while ($result['count'] > 0);
        $slug = $newSlug;
    }

    return $slug;
}

// Function to check if a book already exists
function bookExists($db, $isbn, $isbn13, $title) {
    $stmt = $db->prepare("
        SELECT di.id 
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE di.type = 'book' AND (
            (b.isbn = ? AND ? != '') OR 
            (b.isbn13 = ? AND ? != '') OR 
            LOWER(di.title) = LOWER(?)
        )
    ");
    $stmt->execute([$isbn, $isbn, $isbn13, $isbn13, $title]);
    return $stmt->fetch();
}

// Function to import a book
function importBook($db, $bookData) {
    try {
        // Check if book already exists
        $existingBook = bookExists(
            $db, 
            $bookData['isbn'] ?? '', 
            $bookData['isbn13'] ?? '', 
            $bookData['title']
        );

        if ($existingBook) {
            echo "<p class='warning'>Book already exists: {$bookData['title']} (ID: {$existingBook['id']})</p>";
            flushOutput();
            return ['success' => false, 'message' => 'Book already exists', 'id' => $existingBook['id']];
        }

        // Begin transaction
        $db->beginTransaction();

        // Generate slug
        $slug = generateUniqueSlug($db, $bookData['title']);

        // Insert into directory_items
        $stmt = $db->prepare("
            INSERT INTO directory_items (
                title, description, website_url, category_id, type, slug, 
                cover_url, is_published, published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'book', ?, ?, 1, NOW(), NOW(), NOW())
        ");

        $stmt->execute([
            $bookData['title'],
            $bookData['description'] ?? '',
            $bookData['website_url'] ?? '',
            $bookData['category_id'] ?? 1,
            $slug,
            $bookData['cover_url'] ?? ''
        ]);

        $directoryItemId = $db->lastInsertId();

        // Insert into books
        $stmt = $db->prepare("
            INSERT INTO books (
                directory_item_id, isbn, isbn13, author, publisher,
                publication_date, page_count, age_range, reading_level,
                cover_url, purchase_links, metadata, genre, series
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $directoryItemId,
            $bookData['isbn'] ?? '',
            $bookData['isbn13'] ?? '',
            $bookData['author'] ?? '',
            $bookData['publisher'] ?? '',
            $bookData['publication_date'] ?? null,
            $bookData['page_count'] ?? null,
            $bookData['age_range'] ?? '',
            $bookData['reading_level'] ?? '',
            $bookData['cover_url'] ?? '',
            $bookData['purchase_links'] ?? '{}',
            $bookData['metadata'] ?? '{}',
            $bookData['genre'] ?? '',
            $bookData['series'] ?? ''
        ]);

        // If author_id is provided, link the book to the author
        if (!empty($bookData['author_id'])) {
            $stmt = $db->prepare("
                INSERT INTO book_authors (directory_item_id, author_id, role)
                VALUES (?, ?, 'author')
            ");
            $stmt->execute([$directoryItemId, $bookData['author_id']]);
        }

        // If publisher_id is provided, link the book to the publisher
        if (!empty($bookData['publisher_id'])) {
            $stmt = $db->prepare("
                INSERT INTO book_authors (directory_item_id, author_id, role)
                VALUES (?, ?, 'publisher')
            ");
            $stmt->execute([$directoryItemId, $bookData['publisher_id']]);
        }

        // Commit transaction
        $db->commit();

        echo "<p class='success'>Imported book: {$bookData['title']} (ID: $directoryItemId)</p>";
        flushOutput();

        return [
            'success' => true, 
            'message' => 'Book imported successfully', 
            'id' => $directoryItemId
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "<p class='error'>Error importing book: " . $e->getMessage() . "</p>";
        flushOutput();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Function to search for books by author
function searchBooksByAuthor($authorId, $limit = 10) {
    // This would typically call an external API like Google Books
    // For now, we'll return a sample response
    return [
        [
            'title' => 'Sample Book 1 by Author',
            'isbn' => '1234567890',
            'isbn13' => '9781234567890',
            'description' => 'This is a sample book description.',
            'author' => 'Sample Author',
            'publisher' => 'Sample Publisher',
            'publication_date' => '2023-01-01',
            'page_count' => 200,
            'age_range' => '9-12',
            'reading_level' => 'middle-grade',
            'cover_url' => 'https://example.com/cover1.jpg',
            'genre' => 'Fiction',
            'series' => 'Sample Series'
        ],
        [
            'title' => 'Sample Book 2 by Author',
            'isbn' => '0987654321',
            'isbn13' => '9780987654321',
            'description' => 'Another sample book description.',
            'author' => 'Sample Author',
            'publisher' => 'Sample Publisher',
            'publication_date' => '2023-02-01',
            'page_count' => 250,
            'age_range' => '9-12',
            'reading_level' => 'middle-grade',
            'cover_url' => 'https://example.com/cover2.jpg',
            'genre' => 'Fiction',
            'series' => 'Sample Series'
        ]
    ];
}

// Function to search for books by publisher
function searchBooksByPublisher($publisherId, $limit = 10) {
    // Similar to searchBooksByAuthor, but for publishers
    return [
        [
            'title' => 'Sample Book 1 by Publisher',
            'isbn' => '1122334455',
            'isbn13' => '9781122334455',
            'description' => 'This is a sample book from a publisher.',
            'author' => 'Various Authors',
            'publisher' => 'Sample Publisher',
            'publication_date' => '2023-03-01',
            'page_count' => 300,
            'age_range' => '6-8',
            'reading_level' => 'chapter-book',
            'cover_url' => 'https://example.com/cover3.jpg',
            'genre' => 'Non-fiction',
            'series' => ''
        ]
    ];
}

// Function to search for books by year
function searchBooksByYear($year, $limit = 10) {
    // Search for books published in a specific year
    return [
        [
            'title' => "Sample Book from $year",
            'isbn' => '5566778899',
            'isbn13' => '9785566778899',
            'description' => "This is a sample book published in $year.",
            'author' => 'Year Author',
            'publisher' => 'Year Publisher',
            'publication_date' => "$year-06-01",
            'page_count' => 180,
            'age_range' => '13+',
            'reading_level' => 'young-adult',
            'cover_url' => 'https://example.com/cover4.jpg',
            'genre' => 'Mystery',
            'series' => ''
        ]
    ];
}

// Function to search for books by age range
function searchBooksByAgeRange($ageRange, $limit = 10) {
    // Search for books suitable for a specific age range
    return [
        [
            'title' => "Sample Book for $ageRange",
            'isbn' => '9988776655',
            'isbn13' => '9789988776655',
            'description' => "This is a sample book for ages $ageRange.",
            'author' => 'Age-Appropriate Author',
            'publisher' => 'Kids Publisher',
            'publication_date' => '2023-09-01',
            'page_count' => 120,
            'age_range' => $ageRange,
            'reading_level' => 'chapter-book',
            'cover_url' => 'https://example.com/cover5.jpg',
            'genre' => 'Adventure',
            'series' => 'Age Series'
        ]
    ];
}

// Function to search for books by ISBN list
function searchBooksByISBN($isbnList, $limit = 10) {
    // Search for books by ISBN
    $books = [];
    $isbns = explode("\n", $isbnList);
    $isbns = array_slice($isbns, 0, $limit);
    
    foreach ($isbns as $isbn) {
        $isbn = trim($isbn);
        if (empty($isbn)) continue;
        
        $books[] = [
            'title' => "Book with ISBN $isbn",
            'isbn' => strlen($isbn) <= 10 ? $isbn : '',
            'isbn13' => strlen($isbn) > 10 ? $isbn : '',
            'description' => "This is a book found by ISBN $isbn.",
            'author' => 'ISBN Author',
            'publisher' => 'ISBN Publisher',
            'publication_date' => '2023-10-01',
            'page_count' => 150,
            'age_range' => '9-12',
            'reading_level' => 'middle-grade',
            'cover_url' => 'https://example.com/cover6.jpg',
            'genre' => 'Science Fiction',
            'series' => ''
        ];
    }
    
    return $books;
}

// Main processing logic
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Import Process</title>
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
        <h1>Book Import Process</h1>
        
        <div class="progress-container">
            <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
        </div>
        
        <div class="log-container" id="logContainer">
            <p class="info">Starting book import process...</p>
            <?php
            // Process the form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $importType = $_POST['import_type'] ?? '';
                $limit = (int)($_POST['limit'] ?? 10);
                $scrapeReviews = isset($_POST['scrape_reviews']) && $_POST['scrape_reviews'] == 1;
                
                echo "<p class='info'>Import type: $importType, Limit: $limit, Scrape reviews: " . ($scrapeReviews ? 'Yes' : 'No') . "</p>";
                flushOutput();
                
                $books = [];
                
                // Get books based on import type
                switch ($importType) {
                    case 'author':
                        $authorId = $_POST['author_id'] ?? 0;
                        echo "<p class='info'>Searching for books by author ID: $authorId</p>";
                        flushOutput();
                        $books = searchBooksByAuthor($authorId, $limit);
                        break;
                        
                    case 'publisher':
                        $publisherId = $_POST['publisher_id'] ?? 0;
                        echo "<p class='info'>Searching for books by publisher ID: $publisherId</p>";
                        flushOutput();
                        $books = searchBooksByPublisher($publisherId, $limit);
                        break;
                        
                    case 'year':
                        $year = $_POST['year'] ?? date('Y');
                        echo "<p class='info'>Searching for books published in year: $year</p>";
                        flushOutput();
                        $books = searchBooksByYear($year, $limit);
                        break;
                        
                    case 'age':
                        $ageRange = $_POST['age_range'] ?? '9-12';
                        echo "<p class='info'>Searching for books for age range: $ageRange</p>";
                        flushOutput();
                        $books = searchBooksByAgeRange($ageRange, $limit);
                        break;
                        
                    case 'isbn':
                        $isbnList = $_POST['isbn_list'] ?? '';
                        echo "<p class='info'>Searching for books by ISBN list</p>";
                        flushOutput();
                        $books = searchBooksByISBN($isbnList, $limit);
                        break;
                        
                    default:
                        echo "<p class='error'>Invalid import type: $importType</p>";
                        flushOutput();
                        $books = [];
                }
                
                // Import books
                $totalBooks = count($books);
                echo "<p class='info'>Found $totalBooks books to import</p>";
                flushOutput();
                
                $importedBooks = [];
                $failedBooks = [];
                
                foreach ($books as $index => $bookData) {
                    $progress = round(($index / $totalBooks) * 100);
                    echo "<script>
                        document.getElementById('progressBar').style.width = '$progress%';
                        document.getElementById('progressBar').innerText = '$progress%';
                    </script>";
                    flushOutput();
                    
                    echo "<p class='info'>Processing book " . ($index + 1) . " of $totalBooks: {$bookData['title']}</p>";
                    flushOutput();
                    
                    $result = importBook($db, $bookData);
                    
                    if ($result['success']) {
                        $importedBooks[] = [
                            'id' => $result['id'],
                            'title' => $bookData['title']
                        ];
                    } else {
                        $failedBooks[] = [
                            'title' => $bookData['title'],
                            'reason' => $result['message']
                        ];
                    }
                }
                
                // Update progress to 100%
                echo "<script>
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('progressBar').innerText = '100%';
                </script>";
                flushOutput();
                
                // Summary
                echo "<h3>Import Summary</h3>";
                echo "<p>Total books found: $totalBooks</p>";
                echo "<p>Successfully imported: " . count($importedBooks) . "</p>";
                echo "<p>Failed to import: " . count($failedBooks) . "</p>";
                
                if (!empty($importedBooks) && $scrapeReviews) {
                    echo "<p class='info'>Redirecting to review scraping for imported books...</p>";
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'book-import-scrape.php?books=" . implode(',', array_column($importedBooks, 'id')) . "';
                        }, 3000);
                    </script>";
                } else {
                    echo "<p><a href='book-import-tool.php' class='btn btn-primary'>Return to Book Import Tool</a></p>";
                }
            } else {
                echo "<p class='error'>Invalid request method. Please submit the form from the Book Import Tool page.</p>";
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
