<?php
/**
 * Book Scrape Reviews
 *
 * This script handles the scraping of reviews for a specific book.
 * It provides a form to select sources and configure scraping options.
 */

// Set page title and current page
$pageTitle = 'Scrape Book Reviews';
$currentPage = 'book-import-tool';
$pageDescription = 'Scrape reviews for a book from various sources';

// Include the header
require_once '../includes/auth-check.php';
require_once '../includes/header.php';

// Include database connection
require_once '../includes/db-connect.php';

// Get book ID from URL parameter
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// If no book ID provided, redirect to book import tool
if ($bookId <= 0) {
    header('Location: book-import-tool.php');
    exit;
}

// Get book details
$bookStmt = $db->prepare("
    SELECT di.id, di.title, di.review_count, di.average_rating, b.isbn, b.isbn13, b.author
    FROM directory_items di
    JOIN books b ON di.id = b.directory_item_id
    WHERE di.id = ?
");
$bookStmt->execute([$bookId]);
$book = $bookStmt->fetch(PDO::FETCH_ASSOC);

// If book not found, redirect to book import tool
if (!$book) {
    header('Location: book-import-tool.php');
    exit;
}

// Get available review sources
$sourcesStmt = $db->prepare("
    SELECT id, name, url, is_third_party
    FROM review_sources
    WHERE name = 'Goodreads'
    ORDER BY name ASC
");
$sourcesStmt->execute();
$sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get existing reviews for this book
$reviewsStmt = $db->prepare("
    SELECT r.id, r.reviewer_name, r.rating_normalised, r.review_text, r.created_at,
           rs.name as source_name, rs.is_third_party
    FROM reviews r
    JOIN review_sources rs ON r.source_id = rs.id
    WHERE r.book_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviewsStmt->execute([$bookId]);
$existingReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count total reviews for this book
$reviewCountStmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ?");
$reviewCountStmt->execute([$bookId]);
$totalReviews = $reviewCountStmt->fetchColumn();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Scrape Reviews for Book</h5>
                    <a href="book-import-tool.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Book List
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                            <p><strong>ISBN:</strong> <?php echo !empty($book['isbn13']) ? htmlspecialchars($book['isbn13']) : htmlspecialchars($book['isbn']); ?></p>
                            <p><strong>Current Reviews:</strong> <?php echo $book['review_count'] ?? 0; ?></p>
                            <p><strong>Average Rating:</strong> <?php echo !empty($book['average_rating']) ? number_format($book['average_rating'] * 5, 1) . ' / 5' : 'N/A'; ?></p>
                        </div>
                        <div class="col-md-4">
                            <?php if ($totalReviews > 0): ?>
                                <div class="alert alert-info">
                                    <p><strong><?php echo $totalReviews; ?></strong> reviews already exist for this book.</p>
                                    <p>The most recent <?php echo min($totalReviews, 10); ?> reviews are shown below.</p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <p>No reviews exist for this book yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form action="book-import-scrape.php" method="post" id="scrape-form">
                        <input type="hidden" name="book_id" value="<?php echo $bookId; ?>">
                        <?php error_log("book-scrape-reviews.php: Form action set to book-import-scrape.php with book_id: " . $bookId); ?>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Select Sources</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($sources as $source): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="sources[]"
                                                       value="<?php echo $source['id']; ?>" id="source-<?php echo $source['id']; ?>" checked>
                                                <label class="form-check-label" for="source-<?php echo $source['id']; ?>">
                                                    <?php echo htmlspecialchars($source['name']); ?>
                                                    <?php if ($source['is_third_party']): ?>
                                                        <span class="badge bg-info">External</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Scraping Options</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="review_limit" class="form-label">Maximum Reviews per Source</label>
                                            <input type="number" class="form-control" id="review_limit" name="review_limit"
                                                   min="10" max="1000" value="100">
                                            <div class="form-text">Number of reviews to fetch from each source (10-1000)</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_pages" class="form-label">Maximum Pages to Scrape</label>
                                            <input type="number" class="form-control" id="max_pages" name="max_pages"
                                                   min="1" max="100" value="20">
                                            <div class="form-text">Maximum number of pages to scrape per source (1-100)</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="force_refresh" name="force_refresh" value="1">
                                            <label class="form-check-label" for="force_refresh">
                                                Force Refresh (ignore cache)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="continue_from_last" name="continue_from_last" value="1" checked>
                                            <label class="form-check-label" for="continue_from_last">
                                                Continue from Last Scrape
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="run_ai_analysis" name="run_ai_analysis" value="1">
                                            <label class="form-check-label" for="run_ai_analysis">
                                                Run AI Analysis
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-cloud-download-alt"></i> Start Scraping Reviews
                            </button>
                        </div>
                    </form>

                    <?php if ($totalReviews > 0): ?>
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5>Existing Reviews</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th>Reviewer</th>
                                                <th>Rating</th>
                                                <th>Review</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($existingReviews as $review): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($review['source_name']); ?>
                                                        <?php if ($review['is_third_party']): ?>
                                                            <span class="badge bg-info">External</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                                                    <td>
                                                        <?php
                                                        $stars = round($review['rating_normalised'] * 5);
                                                        echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $reviewText = $review['review_text'];
                                                        echo htmlspecialchars(substr($reviewText, 0, 100)) . (strlen($reviewText) > 100 ? '...' : '');
                                                        ?>
                                                    </td>
                                                    <td><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($totalReviews > 10): ?>
                                    <div class="text-center mt-3">
                                        <a href="book-reviews.php?book_id=<?php echo $bookId; ?>" class="btn btn-outline-primary">
                                            View All <?php echo $totalReviews; ?> Reviews
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('book-scrape-reviews.php: DOM loaded');

    // Add event listener to the form
    const scrapeForm = document.getElementById('scrape-form');
    if (scrapeForm) {
        console.log('book-scrape-reviews.php: Form found with ID: scrape-form');

        scrapeForm.addEventListener('submit', function(event) {
            console.log('book-scrape-reviews.php: Form submitted');
        });
    } else {
        console.error('book-scrape-reviews.php: Form not found with ID: scrape-form');
    }
});
</script>
