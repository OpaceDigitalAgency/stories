<?php
/**
 * Book Data Enrichment
 *
 * This script provides a modern interface for validating and enriching book data
 * from multiple sources including Google Books, Open Library, and Goodreads.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page title and current page
$pageTitle = 'Book Data Enrichment';
$currentPage = 'book-import-tool';
$pageDescription = 'Validate and enrich book data from multiple sources';

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
require_once '../../services/ReviewFetcher/ReviewFetcherFactory.php';

// Include tag functions
require_once '../includes/tag-functions.php';

// Include validation functions
require_once 'book-import-validate/functions/validation-functions.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutes

// Process form submissions
$message = '';
$messageType = '';
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

try {
    // Database connection is already established in db-connect.php
    // $db is already available

    // Handle different actions
    switch ($action) {
        case 'validate_book':
            // Validate a single book
            if ($bookId) {
                // Get book details
                $stmt = $db->prepare("
                    SELECT di.id, di.title, b.*
                    FROM directory_items di
                    JOIN books b ON di.id = b.directory_item_id
                    WHERE di.id = ?
                ");
                $stmt->execute([$bookId]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($book) {
                    // Force refresh validation data
                    $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
                    $validationResult = validateBookData($bookId, $isbn, $book['title'], $db, true);

                    if ($validationResult['status'] === 'success') {
                        $message = 'Book data validated successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Error validating book data: ' . $validationResult['message'];
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Book not found.';
                    $messageType = 'danger';
                }
            } else {
                $message = 'No book ID provided.';
                $messageType = 'danger';
            }
            break;

        case 'update_field':
            // Update a single field
            if ($bookId && isset($_POST['field']) && isset($_POST['value']) && isset($_POST['source'])) {
                $field = $_POST['field'];
                $value = $_POST['value'];
                $source = $_POST['source'];

                $updateResult = updateBookField($bookId, $field, $value, $source, $db);

                if ($updateResult['status'] === 'success') {
                    $message = "Field '$field' updated successfully.";
                    $messageType = 'success';
                } else {
                    $message = 'Error updating field: ' . $updateResult['message'];
                    $messageType = 'danger';
                }
            } else {
                $message = 'Missing required parameters.';
                $messageType = 'danger';
            }
            break;

        case 'apply_all_valid':
            // Apply all valid values
            if ($bookId) {
                $applyResult = applyAllValidValues($bookId, $db);

                if ($applyResult['status'] === 'success') {
                    $message = $applyResult['message'];
                    $messageType = 'success';
                } else {
                    $message = 'Error applying values: ' . $applyResult['message'];
                    $messageType = 'danger';
                }
            } else {
                $message = 'No book ID provided.';
                $messageType = 'danger';
            }
            break;

        case 'apply_all_from_source':
            // Apply all values from a specific source
            if ($bookId && isset($_POST['source'])) {
                $source = $_POST['source'];
                $applyResult = applyAllFromSource($bookId, $source, $db);

                if ($applyResult['status'] === 'success') {
                    $message = $applyResult['message'];
                    $messageType = 'success';
                } else {
                    $message = 'Error applying values: ' . $applyResult['message'];
                    $messageType = 'danger';
                }
            } else {
                $message = 'Missing required parameters.';
                $messageType = 'danger';
            }
            break;
    }

    // Get book data if a book ID is provided
    $bookData = null;
    $validationData = null;

    if ($bookId) {
        // Get book details
        $stmt = $db->prepare("
            SELECT di.id, di.title, di.slug, di.review_count, di.average_rating, b.*
            FROM directory_items di
            JOIN books b ON di.id = b.directory_item_id
            WHERE di.id = ?
        ");
        $stmt->execute([$bookId]);
        $bookData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($bookData) {
            // Get validation data
            $isbn = $bookData['isbn'] ?? ($bookData['isbn13'] ?? '');
            $validationData = validateBookData($bookId, $isbn, $bookData['title'], $db);

            // Set up sources for the template
            $sources = ['google_books', 'open_library', 'goodreads'];

            // Set up validation history
            $validationHistory = $validationData['history'] ?? [];

            // Make book data available as $book for the template
            $book = $bookData;
        }
    }

} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
    $messageType = 'danger';
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Book Data Enrichment</h5>
                    <a href="book-import-tool.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Import Tool
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($bookData && $validationData): ?>
                        <!-- Include the validation interface template -->
                        <?php include 'book-import-validate/templates/validation-interface.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>Select books to validate or enrich data from the ISBN & Data Validation tab.</p>
                            <a href="book-validation.php" class="btn btn-primary mt-2">
                                <i class="fas fa-list"></i> Go to Book List
                            </a>
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
