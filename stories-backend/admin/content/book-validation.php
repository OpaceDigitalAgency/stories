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

    // Get books with pagination for ISBN validation
    $isbnBooksStmt = $db->prepare("
        SELECT di.id, di.title, di.slug, di.review_count, di.average_rating,
               b.isbn, b.isbn13, b.author, b.publisher, b.page_count, b.series, b.price_range
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
                            // Function to validate ISBN against external APIs
                            function validateISBNQuick($isbn, $title, $author) {
                                if (empty($isbn)) {
                                    return ['status' => 'missing', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'No ISBN'];
                                }

                                // Clean ISBN
                                $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

                                // Basic format check first
                                if (strlen($cleanIsbn) != 10 && strlen($cleanIsbn) != 13) {
                                    return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'Invalid format'];
                                }

                                // Quick OpenLibrary check (fastest)
                                if (validateIsbnWithOpenLibrary($cleanIsbn)) {
                                    return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (OpenLibrary)'];
                                }

                                // Quick Google Books check
                                if (validateIsbnWithGoogleBooks($cleanIsbn)) {
                                    return ['status' => 'valid', 'class' => 'success', 'icon' => 'check-circle', 'message' => 'Valid (Google Books)'];
                                }

                                // If ISBN validation fails, check if we can find the book by title/author
                                if (!empty($title)) {
                                    // Try to find suggestions by title/author
                                    $suggestions = searchOpenLibraryByTitleAuthor($title, $author, 1);
                                    if (!empty($suggestions)) {
                                        return ['status' => 'mismatch', 'class' => 'warning', 'icon' => 'exclamation-triangle', 'message' => 'ISBN invalid, but book found by title'];
                                    }
                                }

                                return ['status' => 'invalid', 'class' => 'danger', 'icon' => 'times-circle', 'message' => 'ISBN not found'];
                            }

                            // Prepare table data for ISBN validation
                            $tableData = [];
                            foreach ($isbnBooks as $book) {
                                $isbn = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');

                                // Validate ISBN against external APIs
                                $validation = validateISBNQuick($isbn, $book['title'], $book['author']);
                                $isbnStatus = $validation['status'];
                                $statusClass = $validation['class'];
                                $statusIcon = $validation['icon'];
                                $statusMessage = $validation['message'];

                                // Get genre tags
                                $genreTags = getGenreTagsForDirectoryItem($db, $book['id']);
                                $genreDisplay = !empty($genreTags) ?
                                    htmlspecialchars(formatTagsForDisplay($genreTags)) :
                                    '<span class="text-muted">No genres</span>';

                                // Check missing fields
                                $missingFields = [];
                                if (empty($book['publisher']) || strtolower($book['publisher']) == 'unknown') $missingFields[] = 'Publisher';
                                if (empty($book['page_count']) || $book['page_count'] == '0') $missingFields[] = 'Page Count';
                                if (empty($genreTags)) $missingFields[] = 'Genre';
                                if (empty($book['series']) || strtolower($book['series']) == 'unknown') $missingFields[] = 'Series';
                                if (empty($book['price_range'])) $missingFields[] = 'Price Range';

                                $ageRangeTags = getAgeRangeTagsForDirectoryItem($db, $book['id']);
                                if (empty($ageRangeTags)) $missingFields[] = 'Age Range';

                                $missingDataDisplay = !empty($missingFields) ?
                                    '<span class="badge badge-warning" title="' . htmlspecialchars(implode(', ', $missingFields)) . '">' .
                                    count($missingFields) . ' field' . (count($missingFields) > 1 ? 's' : '') . '</span> ' .
                                    '<small class="text-muted">' . htmlspecialchars(implode(', ', $missingFields)) . '</small>' :
                                    '<span class="badge badge-success">Complete</span>';

                                $tableData[] = [
                                    'id' => $book['id'],
                                    'title' => htmlspecialchars($book['title']),
                                    'isbn' => !empty($isbn) ? htmlspecialchars($isbn) : '<span class="text-danger">Missing</span>',
                                    'status' => '<span class="badge badge-' . $statusClass . '" title="' . htmlspecialchars($statusMessage) . '"><i class="fas fa-' . $statusIcon . '"></i> ' . ucfirst($isbnStatus) . '</span>',
                                    'missing_data' => $missingDataDisplay,
                                    'actions' => '<button class="btn btn-sm btn-primary validate-isbn-btn" ' .
                                               'data-book-id="' . $book['id'] . '" ' .
                                               'data-book-title="' . htmlspecialchars($book['title']) . '" ' .
                                               'data-isbn="' . htmlspecialchars($isbn) . '">' .
                                               '<i class="fas fa-check"></i> Validate</button>' .
                                               ($isbnStatus === 'invalid' || $isbnStatus === 'mismatch' ?
                                                   ' <button class="btn btn-sm btn-warning fix-isbn-btn" ' .
                                                   'data-book-id="' . $book['id'] . '" ' .
                                                   'data-book-title="' . htmlspecialchars($book['title']) . '" ' .
                                                   'data-author="' . htmlspecialchars($book['author']) . '">' .
                                                   '<i class="fas fa-wrench"></i> Fix</button>' : '')
                                ];
                            }

                            // Define table columns - include actions in the columns
                            $columns = [
                                'title' => 'Title',
                                'isbn' => 'ISBN',
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
                                    'htmlFields' => ['isbn', 'status', 'missing_data', 'actions'],
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
                                <button id="validate-selected-isbns" class="btn btn-primary">
                                    <i class="fas fa-check-double"></i> Validate Selected ISBNs
                                </button>
                                <button id="fix-invalid-isbns" class="btn btn-warning ml-2">
                                    <i class="fas fa-wrench"></i> Fix Invalid ISBNs
                                </button>
                                <button id="refresh-validation" class="btn btn-secondary ml-2">
                                    <i class="fas fa-sync"></i> Refresh Validation
                                </button>
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

<script>
$(document).ready(function() {
    // ISBN Validation Tab Handlers
    $('#select-all-isbn').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.isbn-checkbox').prop('checked', isChecked);
    });

    $('.validate-isbn-btn').on('click', function() {
        const bookId = $(this).data('book-id');
        const bookTitle = $(this).data('book-title');
        const isbn = $(this).data('isbn');

        // Redirect to the new validation page with the book ID
        window.location.href = `book-import-validate-new.php?action=validate_book&book_id=${bookId}`;
    });

    $('.fix-isbn-btn').on('click', function() {
        const bookId = $(this).data('book-id');
        const bookTitle = $(this).data('book-title');
        const author = $(this).data('author');

        // Show a modal or redirect to a fix page
        if (confirm(`Fix ISBN for "${bookTitle}" by searching with title and author?`)) {
            window.location.href = `book-import-validate-new.php?action=fix_isbn&book_id=${bookId}&title=${encodeURIComponent(bookTitle)}&author=${encodeURIComponent(author)}`;
        }
    });

    $('#validate-selected-isbns').on('click', function() {
        const selectedBooks = $('.isbn-checkbox:checked').length;

        if (selectedBooks === 0) {
            alert('Please select at least one book to validate.');
            return false;
        }

        // Create a form to submit the selected books
        const form = $('<form>', {
            'method': 'post',
            'action': 'book-import-validate-new.php'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'validate_isbns'
        }));

        $('.isbn-checkbox:checked').each(function() {
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'book_ids[]',
                'value': $(this).val()
            }));
        });

        $('body').append(form);
        form.submit();
    });

    $('#fix-invalid-isbns').on('click', function() {
        if (confirm('This will attempt to fix all invalid ISBNs by searching with title and author. Continue?')) {
            window.location.href = 'book-import-validate-new.php?action=fix_all_invalid_isbns';
        }
    });

    $('#refresh-validation').on('click', function() {
        // Simply reload the page to refresh validation status
        window.location.reload();
    });

    // Data Enrichment Tab Handlers
    $('#enrichBookSelection').change(function() {
        const selection = $(this).val();
        if (selection === 'specific') {
            $('.enrich-specific').show();
        } else {
            $('.enrich-specific').hide();
        }
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>