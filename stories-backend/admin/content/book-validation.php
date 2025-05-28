<?php
/**
 * Book Validation Admin Page
 *
 * This page provides an interface for validating book ISBNs and enriching book data.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include admin functions
require_once '../includes/admin-functions.php';

// Include tag functions
require_once '../includes/tag-functions.php';

// Include components
require_once '../includes/enhanced-table-component.php';
require_once '../includes/bulk-actions-component.php';
require_once '../includes/pagination-component.php';

// Include validation functions
require_once 'book-import-validate/functions/validation-functions.php';
require_once 'book-import-validate/functions/open-library-validation-functions.php';
require_once 'book-import-validate/functions/google-books-validation-functions.php';

// Set page variables for header
$pageTitle = 'ISBN & Data Validation';
$currentPage = 'book-import-tool';

// Process form submissions
$message = '';
$messageType = '';

try {
    // ISBN tab pagination
    $isbnPage = isset($_GET['isbn_page']) ? max(1, intval($_GET['isbn_page'])) : 1;
    $isbnPerPage = isset($_GET['isbn_per_page']) ? intval($_GET['isbn_per_page']) : 10;

    // Log the parameters for debugging
    error_log("ISBN Page: $isbnPage, ISBN Per Page: $isbnPerPage");

    // Calculate offsets
    $isbnOffset = ($isbnPage - 1) * $isbnPerPage;
    $isbnOffset = max(0, $isbnOffset);

    // Initialize standard per page values
    $validPerPageValues = [10, 25, 50, 100];

    // Count total books
    $bookCountQuery = "
        SELECT COUNT(*)
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE di.type = 'book'
    ";
    $bookCountStmt = $db->prepare($bookCountQuery);
    $bookCountStmt->execute();
    $totalBooks = $bookCountStmt->fetchColumn();

    // Add total items as a valid per_page value
    if (!in_array($totalBooks, $validPerPageValues)) {
        $validPerPageValues[] = $totalBooks;
    }

    // Calculate pagination
    $totalIsbnPages = ceil($totalBooks / $isbnPerPage);

    // Get books with pagination for ISBN validation - include all fields needed for missing data detection
    $isbnBooksStmt = $db->prepare("
        SELECT di.id, di.title, di.slug, di.review_count, di.average_rating, di.cover_url,
               b.isbn, b.isbn13, b.author, b.publisher, b.publication_date, b.page_count, b.series, b.price_range,
               b.age_range, b.reading_level, b.language, b.format, b.cover_url as book_cover_url,
               b.preview_link, b.awards, b.characters, b.settings
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE di.type = 'book'
        ORDER BY di.title ASC
        LIMIT $isbnPerPage OFFSET $isbnOffset
    ");
    $isbnBooksStmt->execute();
    $isbnBooks = $isbnBooksStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = 'Error: ' . $e->getMessage();
    $messageType = 'danger';
}

// Include header
require_once '../includes/header.php';
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
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Book Data Validation</h4>
                        <div>
                            <a href="book-import-tool.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Import Tool
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p>Check and fix incorrect ISBNs, and enrich missing book data from external sources.</p>

                    <div class="card mb-4">
                        <div class="card-body">

                            <?php
                            // Function to do basic ISBN format validation only (no API calls)
                            function validateISBNFormat($isbn) {
                                if (empty($isbn)) {
                                    return ['status' => 'missing', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'No ISBN'];
                                }

                                // Clean ISBN
                                $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
                                $length = strlen($cleanIsbn);

                                // Debug: Log the validation process
                                error_log("Validating ISBN: '$isbn' -> cleaned: '$cleanIsbn' -> length: $length");

                                // Basic format check
                                if ($length != 10 && $length != 13) {
                                    error_log("ISBN format invalid: $length digits");
                                    return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => "Invalid format ($length digits)"];
                                }

                                // For valid format ISBNs, do a quick checksum validation
                                if ($length == 13) {
                                    // ISBN-13 checksum validation
                                    $sum = 0;
                                    for ($i = 0; $i < 12; $i++) {
                                        $digit = intval($cleanIsbn[$i]);
                                        $sum += ($i % 2 == 0) ? $digit : $digit * 3;
                                    }
                                    $checkDigit = (10 - ($sum % 10)) % 10;
                                    $actualCheckDigit = intval($cleanIsbn[12]);

                                    if ($checkDigit != $actualCheckDigit) {
                                        return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid ISBN-13 checksum'];
                                    }
                                } else if ($length == 10) {
                                    // ISBN-10 checksum validation
                                    $sum = 0;
                                    for ($i = 0; $i < 9; $i++) {
                                        $digit = intval($cleanIsbn[$i]);
                                        $sum += $digit * (10 - $i);
                                    }
                                    $checkDigit = (11 - ($sum % 11)) % 11;
                                    $actualCheckDigit = ($cleanIsbn[9] == 'X') ? 10 : intval($cleanIsbn[9]);

                                    if ($checkDigit != $actualCheckDigit) {
                                        return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid ISBN-10 checksum'];
                                    }
                                }

                                // Format and checksum are valid, but we need to check against APIs
                                return ['status' => 'unknown', 'class' => 'secondary', 'icon' => 'question-circle', 'message' => 'Click Validate to check'];
                            }

                            // Prepare table data for ISBN validation
                            $tableData = [];
                            foreach ($isbnBooks as $book) {
                                $isbn = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');

                                // Only do basic format validation (no API calls)
                                $validation = validateISBNFormat($isbn);
                                $isbnStatus = $validation['status'];
                                $statusClass = $validation['class'];
                                $statusIcon = $validation['icon'];
                                $statusMessage = $validation['message'];

                                // Get genre tags
                                $genreTags = getGenreTagsForDirectoryItem($db, $book['id']);
                                $genreDisplay = !empty($genreTags) ?
                                    htmlspecialchars(formatTagsForDisplay($genreTags)) :
                                    '<span class="text-muted">No genres</span>';

                                // Use the proper getMissingFields function from search-functions.php
                                require_once 'book-import-validate/functions/search-functions.php';
                                $missingFields = getMissingFields($book);

                                // Add additional fields that are specific to the admin interface (tags-based)
                                if (empty($genreTags)) $missingFields[] = 'Genre';

                                $ageRangeTags = getAgeRangeTagsForDirectoryItem($db, $book['id']);
                                if (empty($ageRangeTags)) $missingFields[] = 'Age Range Tags';

                                $missingDataDisplay = !empty($missingFields) ?
                                    '<span class="badge badge-warning" title="' . htmlspecialchars(implode(', ', $missingFields)) . '">' .
                                    count($missingFields) . ' field' . (count($missingFields) > 1 ? 's' : '') . '</span> ' .
                                    '<small class="text-muted">' . htmlspecialchars(implode(', ', $missingFields)) . '</small>' :
                                    '<span class="badge badge-success">Complete</span>';

                                // Format publisher, date, and format for display
                                $publisher = !empty($book['publisher']) ? htmlspecialchars($book['publisher']) : '<span class="text-muted">Unknown</span>';
                                $pubDate = !empty($book['publication_date']) ? htmlspecialchars($book['publication_date']) : '<span class="text-muted">Unknown</span>';
                                $format = !empty($book['format']) ? htmlspecialchars($book['format']) : '<span class="text-muted">Unknown</span>';

                                $tableData[] = [
                                    'id' => $book['id'],
                                    'title' => htmlspecialchars($book['title']),
                                    'isbn' => !empty($isbn) ? htmlspecialchars($isbn) : '<span class="text-danger">Missing</span>',
                                    'publisher' => $publisher,
                                    'pub_date' => $pubDate,
                                    'format' => $format,
                                    'status' => '<span class="badge badge-' . $statusClass . '" title="' . htmlspecialchars($statusMessage) . '"><i class="fas fa-' . $statusIcon . '"></i> ' . ucfirst($isbnStatus) . '</span>' .
                                               '<br><span class="goodreads-status badge badge-secondary" data-book-id="' . $book['id'] . '" data-isbn="' . htmlspecialchars($book['isbn13'] ?? $book['isbn'] ?? '') . '"><i class="fas fa-spinner fa-spin"></i> Checking...</span>',
                                    'missing_data' => $missingDataDisplay,
                                    'actions' => '<a href="book-import-validate-new.php?action=validate_book&book_id=' . $book['id'] . '" ' .
                                               'class="btn btn-sm btn-info" title="View detailed validation data">' .
                                               '<i class="fas fa-search"></i></a>' .
                                               ($isbnStatus === 'invalid' || $isbnStatus === 'mismatch' ?
                                                   ' <button class="btn btn-sm btn-warning fix-isbn-btn" ' .
                                                   'data-book-id="' . $book['id'] . '" ' .
                                                   'data-book-title="' . htmlspecialchars($book['title']) . '" ' .
                                                   'data-author="' . htmlspecialchars($book['author']) . '" ' .
                                                   'data-publisher="' . htmlspecialchars($book['publisher'] ?? '') . '" ' .
                                                   'data-pub-date="' . htmlspecialchars($book['publication_date'] ?? '') . '" ' .
                                                   'data-format="' . htmlspecialchars($book['format'] ?? '') . '">' .
                                                   '<i class="fas fa-wrench"></i> Fix</button>' : '') .
                                               (!empty($missingFields) ?
                                                   ' <button class="btn btn-sm btn-success enrich-data-btn" ' .
                                                   'data-book-id="' . $book['id'] . '" ' .
                                                   'data-book-title="' . htmlspecialchars($book['title']) . '" ' .
                                                   'data-author="' . htmlspecialchars($book['author']) . '" ' .
                                                   'data-current-isbn="' . htmlspecialchars($book['isbn13'] ?? $book['isbn'] ?? '') . '" ' .
                                                   'title="Enrich missing data from external sources">' .
                                                   '<i class="fas fa-database"></i> Enrich</button>' : '')
                                ];
                            }

                            // Define table columns - include new fields for comparison
                            $columns = [
                                'title' => 'Title',
                                'isbn' => 'ISBN',
                                'publisher' => 'Publisher',
                                'pub_date' => 'Date',
                                'format' => 'Format',
                                'status' => 'Status',
                                'missing_data' => 'Missing Data',
                                'actions' => 'Actions'
                            ];

                            // Render enhanced table
                            renderEnhancedTable(
                                $tableData,
                                $columns,
                                'isbn',
                                'isbn-validation-table',
                                [
                                    'showCheckboxes' => true,
                                    'showActions' => false, // Don't show the last actions column
                                    'thumbnailField' => false, // Don't show the image column
                                    'actions' => ['validate', 'edit'],
                                    'bulkActions' => ['validate', 'enrich'],
                                    'itemsPerPage' => $isbnPerPage,
                                    'currentPage' => $isbnPage,
                                    'totalItems' => $totalBooks,
                                    'htmlFields' => ['isbn', 'publisher', 'pub_date', 'format', 'status', 'missing_data', 'actions'],
                                    'showPagination' => false,
                                    'showItemsPerPage' => false
                                ]
                            );
                            ?>
                            <?php
                            // Render pagination
                            renderPagination($totalBooks, $isbnPerPage, $isbnPage, visiblePages: 5, options: [
                                'pageParam' => 'isbn_page',
                                'perPageParam' => 'isbn_per_page',
                                'validPerPageValues' => [10, 25, 50, 100, $totalBooks],
                                'perPageLabel' => 'Show',
                                'showAllLabel' => 'Show All'
                            ]);
                            ?>

                            <div class="mt-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> ISBNs are automatically validated on page load. Use the "Fix" button for invalid ISBNs.
                                    <div class="mt-2">
                                        <button id="disable-auto-validation" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-pause"></i> Disable Auto-Validation
                                        </button>
                                        <button id="manual-validation" class="btn btn-sm btn-primary" style="display: none;">
                                            <i class="fas fa-play"></i> Run Validation Now
                                        </button>
                                        <button id="test-enrichment" class="btn btn-sm btn-success">
                                            <i class="fas fa-database"></i> Test Enrichment
                                        </button>
                                        <button id="test-ajax" class="btn btn-sm btn-info">
                                            <i class="fas fa-wifi"></i> Test AJAX
                                        </button>
                                        <button id="debug-goodreads" class="btn btn-sm btn-warning">
                                            <i class="fas fa-bug"></i> Debug Goodreads
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress indicator for validation -->
                            <div id="validation-progress" class="mt-3" style="display: none;">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted mt-1 d-block">Validating ISBNs...</small>
                            </div>


                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Data Enrichment</h5>
                        </div>
                        <div class="card-body">
                            <p>Enrich your book data by fetching missing information from external sources.</p>

                            <form id="enrichForm" method="post" action="book-import-validate-new.php">
                                <input type="hidden" name="action" value="enrich_data">

                                <div class="form-group">
                                    <label>Fields to Enrich</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enrich_series" name="enrich_fields[]" value="series" checked>
                                        <label class="form-check-label" for="enrich_series">Series</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enrich_reading_age" name="enrich_fields[]" value="reading_age" checked>
                                        <label class="form-check-label" for="enrich_reading_age">Reading Age</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enrich_page_count" name="enrich_fields[]" value="page_count" checked>
                                        <label class="form-check-label" for="enrich_page_count">Page Count</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enrich_genre" name="enrich_fields[]" value="genre" checked>
                                        <label class="form-check-label" for="enrich_genre">Genre</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enrich_publisher" name="enrich_fields[]" value="publisher" checked>
                                        <label class="form-check-label" for="enrich_publisher">Publisher</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enrich_price_range" name="enrich_fields[]" value="price_range" checked>
                                        <label class="form-check-label" for="enrich_price_range">Price Range</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="enrichBookSelection">Book Selection</label>
                                    <select class="form-control" id="enrichBookSelection" name="enrich_book_selection">
                                        <option value="all">All Books</option>
                                        <option value="missing">Books with Missing Data</option>
                                        <option value="specific">Specific Books</option>
                                    </select>
                                </div>

                                <div class="form-group enrich-specific" style="display: none;">
                                    <label for="enrichSpecificBooks">Select Books</label>
                                    <select class="form-control" id="enrichSpecificBooks" name="enrich_specific_books[]" multiple>
                                        <?php
                                        // Get all books for the dropdown (not paginated)
                                        $allBooksStmt = $db->prepare("
                                            SELECT di.id, di.title
                                            FROM directory_items di
                                            JOIN books b ON di.id = b.directory_item_id
                                            WHERE di.type = 'book'
                                            ORDER BY di.title ASC
                                        ");
                                        $allBooksStmt->execute();
                                        $allBooks = $allBooksStmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($allBooks as $book):
                                        ?>
                                            <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple books</small>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync"></i> Start Data Enrichment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Only include the script once per page load using a static variable
static $bookValidationScriptLoaded = false;
if (!$bookValidationScriptLoaded) {
    $bookValidationScriptLoaded = true;
    echo '<script src="../assets/js/book-validation.js"></script>';
}
?>

<!-- All JavaScript functionality has been moved to external files for better performance -->

<?php
// Include the data enrichment modal
require_once 'book-import-validate/modals/data-enrichment-modal.php';

// Include footer
require_once '../includes/footer.php';
?>