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
                    // Get validation parameters
                    $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
                    $forceRefresh = isset($_GET['force']) && $_GET['force'] == '1';

                    // Check if we should skip VPS headless browser
                    $skipVps = isset($_GET['skip_vps']) && $_GET['skip_vps'] == 1;
                    if ($skipVps) {
                        // Set environment variable to skip VPS headless browser
                        putenv('SKIP_VPS_HEADLESS=true');
                        error_log("Skipping VPS headless browser for validation (user requested)");
                    }

                    // Log force parameter
                    error_log("Force refresh from URL: " . ($forceRefresh ? 'Yes' : 'No'));

                    $validationResult = validateBookData($bookId, $isbn, $book['title'], $db, $forceRefresh);

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

        case 'refresh_data':
            // Refresh validation data for a book
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
                    // Clear cache and force refresh validation data
                    $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');

                    // Clear the validation cache
                    require_once 'book-import-validate/functions/cache-functions.php';
                    clearValidationCacheNew($bookId, $isbn, $book['title'], $db);

                    // Re-validate with fresh data
                    $validationResult = validateBookData($bookId, $isbn, $book['title'], $db, true);

                    if ($validationResult['status'] === 'success') {
                        $message = 'Book data refreshed successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Error refreshing book data: ' . $validationResult['message'];
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

        case 'clear_google_books_cache':
            // Clear Google Books cache
            require_once 'book-import-validate/functions/cache-functions.php';
            if (clearSourceValidationCache('google_books', $db)) {
                $message = 'Google Books cache cleared successfully.';
                $messageType = 'success';

                // If a book ID is provided, refresh that book's data
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
                        // Re-validate with fresh data
                        $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
                        $validationResult = validateBookData($bookId, $isbn, $book['title'], $db, true);

                        if ($validationResult['status'] === 'success') {
                            $message .= ' Book data refreshed successfully.';
                        } else {
                            $message .= ' Error refreshing book data: ' . $validationResult['message'];
                        }
                    }
                }
            } else {
                $message = 'Error clearing Google Books cache.';
                $messageType = 'danger';
            }
            break;

        case 'bypass_all_caches':
            // Bypass all caches and force fresh data
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
                    // Clear all caches
                    require_once 'book-import-validate/functions/cache-functions.php';

                    // Clear PHP validation cache
                    $isbn = $book['isbn'] ?? ($book['isbn13'] ?? '');
                    clearValidationCacheNew($bookId, $isbn, $book['title'], $db);

                    // Clear source-specific caches
                    clearSourceValidationCache('google_books', $db);
                    clearSourceValidationCache('open_library', $db);
                    clearSourceValidationCache('goodreads', $db);

                    // Set environment variables to force fresh data
                    putenv('FORCE_FRESH_DATA=true');
                    putenv('SKIP_CACHE=true');

                    // Force the VPS headless browser to bypass its cache
                    putenv('VPS_BYPASS_CACHE=true');

                    // Log the environment variables for debugging
                    error_log("Environment variables set for cache bypass:");
                    error_log("FORCE_FRESH_DATA=" . getenv('FORCE_FRESH_DATA'));
                    error_log("SKIP_CACHE=" . getenv('SKIP_CACHE'));
                    error_log("VPS_BYPASS_CACHE=" . getenv('VPS_BYPASS_CACHE'));

                    // Re-validate with completely fresh data
                    $validationResult = validateBookData($bookId, $isbn, $book['title'], $db, true);

                    if ($validationResult['status'] === 'success') {
                        $message = 'All caches bypassed and fresh data fetched successfully.';
                        $messageType = 'success';
                    } else {
                        $message = 'Error fetching fresh data: ' . $validationResult['message'];
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
    }

    // Get book data if a book ID is provided
    $bookData = null;
    $validationData = null;

    if ($bookId) {
        try {
            // Log the book ID for debugging
            error_log("Attempting to validate book ID: $bookId");

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
                error_log("Book found: " . $bookData['title']);

                // Get validation data
                $isbn = $bookData['isbn'] ?? ($bookData['isbn13'] ?? '');
                error_log("ISBN for validation: " . ($isbn ?: 'None'));

                // Check if we should skip VPS headless browser
                $skipVps = isset($_GET['skip_vps']) && $_GET['skip_vps'] == 1;
                if ($skipVps) {
                    // Set environment variable to skip VPS headless browser
                    putenv('SKIP_VPS_HEADLESS=true');
                    error_log("Skipping VPS headless browser for validation (user requested)");
                }

                // Get force parameter from URL
                $forceRefresh = isset($_GET['force']) && $_GET['force'] == '1';
                if ($forceRefresh) {
                    putenv('VPS_BYPASS_CACHE=true');
                    error_log("Force refresh requested - bypassing cache");
                }

                // Validate book data
                $validationData = validateBookData($bookId, $isbn, $bookData['title'], $db, $forceRefresh);
                error_log("Validation status: " . ($validationData['status'] ?? 'unknown'));

                // Set up sources for the template
                $sources = ['google_books', 'open_library', 'goodreads'];

                // Set up validation history
                $validationHistory = $validationData['history'] ?? [];

                // Make book data available as $book for the template
                $book = $bookData;
            } else {
                error_log("Book not found for ID: $bookId");
                $message = "Book not found with ID: $bookId";
                $messageType = "danger";
            }
        } catch (Exception $e) {
            error_log("Error in book validation: " . $e->getMessage());
            $message = "Error processing book data: " . $e->getMessage();
            $messageType = "danger";
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
                    <a href="book-validation.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Book Validation
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($bookData && $validationData): ?>
                        <!-- Include the validation interface template -->
                        <?php include 'book-import-validate/templates/validation-interface.php'; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <?php if ($bookId): ?>
                                <p><strong>Error:</strong> Unable to validate book data. This could be due to:</p>
                                <ul>
                                    <li>Book not found in the database</li>
                                    <li>Missing ISBN information</li>
                                    <li>External API services are unavailable</li>
                                </ul>
                                <p>Please try again or check the book details in the admin.</p>
                            <?php else: ?>
                                <p>Select books to validate or enrich data from the ISBN & Data Validation tab.</p>
                            <?php endif; ?>
                            <a href="book-validation.php" class="btn btn-primary mt-2">
                                <i class="fas fa-list"></i> Return to Book Validation
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include validation CSS -->
<link rel="stylesheet" href="book-import-validate/css/validation.css">

<!-- Include validation JavaScript -->
<script src="book-import-validate/js/validation.js"></script>
<script src="book-import-validate/js/field-updater.js"></script>
<script src="book-import-validate/js/ui-components.js"></script>

<?php
// Include the footer
require_once '../includes/footer.php';
?>
