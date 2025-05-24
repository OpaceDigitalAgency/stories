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
                                    'status' => '<span class="badge badge-' . $statusClass . '" title="' . htmlspecialchars($statusMessage) . '"><i class="fas fa-' . $statusIcon . '"></i> ' . ucfirst($isbnStatus) . '</span>',
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
                                                   '<i class="fas fa-wrench"></i> Fix</button>' : '')
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

<script>
$(document).ready(function() {
    // Auto-validate all ISBNs on page load
    autoValidateAllISBNs();

    // ISBN Validation Tab Handlers
    $('.select-all-checkbox').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.item-checkbox').prop('checked', isChecked);
    });

    // Use event delegation for dynamically created fix buttons
    $(document).on('click', '.fix-isbn-btn', function() {
        const bookId = $(this).data('book-id');
        const bookTitle = $(this).data('book-title');
        const author = $(this).data('author');

        // Show a confirmation dialog
        if (confirm(`Search for correct ISBN for "${bookTitle}" by ${author}?`)) {
            const $button = $(this);
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Searching...');

            // Make AJAX call to get ISBN suggestions
            $.ajax({
                url: 'book-validation-ajax.php',
                method: 'POST',
                data: {
                    action: 'fix_isbn',
                    book_id: bookId
                },
                success: function(response) {
                    if (typeof response === 'object' && response.status === 'success') {
                        // Create a proper selection interface
                        showISBNSelectionModal(response.suggestions, response.current_title, bookId);
                    } else {
                        alert('Error: ' + (response.message || 'Failed to find ISBN suggestions'));
                    }
                    $button.prop('disabled', false).html('<i class="fas fa-wrench"></i> Fix');
                },
                error: function(xhr, status, error) {
                    alert('Error searching for ISBN: ' + error);
                    $button.prop('disabled', false).html('<i class="fas fa-wrench"></i> Fix');
                }
            });
        }
    });

    // Function to auto-validate all ISBNs on page load
    function autoValidateAllISBNs() {
        const $progress = $('#validation-progress');
        const $progressBar = $progress.find('.progress-bar');
        const $progressText = $progress.find('small');

        // Get all book rows - use ID selector for the table
        const $rows = $('#isbn-validation-table tbody tr');
        const totalBooks = $rows.length;
        let completedBooks = 0;

        if (totalBooks === 0) {
            alert('No books found to validate.');
            return;
        }

        // Show progress bar
        $progress.show();
        $progressBar.css('width', '0%');
        $progressText.text('Auto-validating ISBNs...');

        // Process each book
        $rows.each(function(index) {
            const $row = $(this);
            const bookId = $row.find('.item-checkbox').val(); // Use the correct checkbox class
            const $statusCell = $row.find('td:nth-child(7)'); // Status column (adjust for new columns: checkbox, title, isbn, publisher, date, format, status)

            // Add a small delay to avoid overwhelming the APIs
            setTimeout(() => {
                // Show loading state
                $statusCell.html('<span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i> Checking...</span>');

                // Make AJAX call to validate this book
                $.ajax({
                    url: 'book-validation-ajax.php',
                    method: 'POST',
                    data: {
                        action: 'validate_isbn',
                        book_id: bookId
                    },
                    success: function(response) {
                        // jQuery automatically parses JSON when Content-Type is application/json
                        if (typeof response === 'object' && response.status === 'success') {
                            const validation = response.validation;
                            $statusCell.html(`<span class="badge badge-${validation.class}" title="${validation.message}"><i class="fas fa-${validation.icon}"></i> ${validation.status.charAt(0).toUpperCase() + validation.status.slice(1)}</span>`);

                            // Update Fix button if needed
                            const $actionsCell = $row.find('td:last-child');
                            const bookTitle = $row.find('td:nth-child(2)').text().trim(); // Title column
                            const detailsButton = `<a href="book-import-validate-new.php?action=validate_book&book_id=${bookId}" class="btn btn-sm btn-info" title="View detailed validation data"><i class="fas fa-search"></i></a>`;

                            if (validation.status === 'invalid' || validation.status === 'mismatch') {
                                if (!$actionsCell.find('.fix-isbn-btn').length) {
                                    const author = 'Unknown'; // We'll need to get this from somewhere else
                                    $actionsCell.html(detailsButton + ' <button class="btn btn-sm btn-warning fix-isbn-btn" data-book-id="' + bookId + '" data-book-title="' + bookTitle + '" data-author="' + author + '"><i class="fas fa-wrench"></i> Fix</button>');
                                }
                            } else {
                                // Show only Details button for valid ISBNs
                                $actionsCell.html(detailsButton);
                            }
                        } else {
                            $statusCell.html('<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Error</span>');
                        }

                        // Update progress
                        completedBooks++;
                        const progress = Math.round((completedBooks / totalBooks) * 100);
                        $progressBar.css('width', progress + '%');
                        $progressText.text(`Validated ${completedBooks} of ${totalBooks} books...`);

                        // Check if all done
                        if (completedBooks === totalBooks) {
                            setTimeout(() => {
                                $progress.hide();
                                $progressText.text('Auto-validation complete!');
                            }, 1000);
                        }
                    },
                    error: function() {
                        $statusCell.html('<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> Error</span>');

                        // Update progress even on error
                        completedBooks++;
                        const progress = Math.round((completedBooks / totalBooks) * 100);
                        $progressBar.css('width', progress + '%');
                        $progressText.text(`Validated ${completedBooks} of ${totalBooks} books...`);

                        if (completedBooks === totalBooks) {
                            setTimeout(() => {
                                $progress.hide();
                            }, 1000);
                        }
                    }
                });
            }, index * 200); // 200ms delay between each request
        });
    }

    // Function to show ISBN selection modal
    function showISBNSelectionModal(suggestions, currentTitle, bookId) {
        if (suggestions.length === 0) {
            alert('No ISBN suggestions found for this book.');
            return;
        }

        // Check if we have a very high confidence match (score >= 150)
        const topMatch = suggestions[0];
        if (topMatch.match_score >= 150) {
            if (confirm(`High confidence match found!\n\n${topMatch.title}\nPublisher: ${topMatch.publisher || 'Unknown'}\nISBN-13: ${topMatch.isbn13 || 'N/A'}\nMatch reasons: ${topMatch.match_reasons}\n\nApply this ISBN automatically?`)) {
                const selectedISBN = topMatch.isbn13 || topMatch.isbn;
                selectISBN(selectedISBN, topMatch.isbn, bookId);
                return;
            }
        }

        const suggestionsToShow = suggestions.slice(0, 5); // Show top 5 matches

        // Create modal content
        let modalContent = `
            <div style="max-width: 600px;">
                <h4>Select Correct ISBN for "${currentTitle}"</h4>
                <p>Found ${suggestionsToShow.length} possible matches. Select the correct one:</p>
                <div style="max-height: 400px; overflow-y: auto;">
        `;

        suggestionsToShow.forEach((suggestion, index) => {
            const isbn10 = suggestion.isbn || 'N/A';
            const isbn13 = suggestion.isbn13 || 'N/A';
            const publisher = suggestion.publisher || 'Unknown';
            const matchScore = suggestion.match_score || 0;
            const matchReasons = suggestion.match_reasons || 'No specific reasons';

            // Color code based on match score
            let borderColor = '#ddd';
            let bgColor = '#fff';
            if (matchScore >= 150) {
                borderColor = '#28a745'; // Green for high confidence
                bgColor = '#f8fff9';
            } else if (matchScore >= 100) {
                borderColor = '#ffc107'; // Yellow for medium confidence
                bgColor = '#fffef8';
            }

            modalContent += `
                <div style="border: 2px solid ${borderColor}; background: ${bgColor}; margin: 10px 0; padding: 15px; border-radius: 5px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h5 style="margin: 0;">${suggestion.title}</h5>
                        <span style="background: ${borderColor}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                            Score: ${matchScore}
                        </span>
                    </div>
                    <p><strong>Author:</strong> ${suggestion.author}</p>
                    <p><strong>Publisher:</strong> ${publisher}</p>
                    <p><strong>ISBN-10:</strong> ${isbn10}</p>
                    <p><strong>ISBN-13:</strong> ${isbn13}</p>
                    <p><strong>Match reasons:</strong> <em>${matchReasons}</em></p>
                    <button class="btn btn-primary" onclick="selectISBN('${isbn13}', '${isbn10}', ${bookId})">
                        Select This ISBN
                    </button>
                </div>
            `;
        });

        modalContent += `
                </div>
                <div style="margin-top: 20px;">
                    <button class="btn btn-secondary" onclick="closeISBNModal()">Cancel</button>
                </div>
            </div>
        `;

        // Create and show modal
        const modal = document.createElement('div');
        modal.id = 'isbn-selection-modal';
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000;
            display: flex; align-items: center; justify-content: center;
        `;

        const modalDialog = document.createElement('div');
        modalDialog.style.cssText = `
            background: white; padding: 20px; border-radius: 10px;
            max-width: 90%; max-height: 90%; overflow-y: auto;
        `;
        modalDialog.innerHTML = modalContent;

        modal.appendChild(modalDialog);
        document.body.appendChild(modal);
    }

    // Function to select an ISBN and update the database
    window.selectISBN = function(isbn13, isbn10, bookId) {
        // Use the best available ISBN (prefer ISBN-13)
        const selectedISBN = (isbn13 && isbn13 !== 'N/A') ? isbn13 : isbn10;

        if (!selectedISBN || selectedISBN === 'N/A') {
            alert('No valid ISBN selected.');
            return;
        }

        // Make AJAX call to update the ISBN
        $.ajax({
            url: 'book-validation-ajax.php',
            method: 'POST',
            data: {
                action: 'update_isbn',
                book_id: bookId,
                isbn: selectedISBN
            },
            success: function(response) {
                if (typeof response === 'object' && response.status === 'success') {
                    alert('ISBN updated successfully!');
                    closeISBNModal();
                    // Refresh the page to show updated data
                    window.location.reload();
                } else {
                    alert('Error updating ISBN: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error updating ISBN: ' + error);
            }
        });
    };

    // Function to close the ISBN selection modal
    window.closeISBNModal = function() {
        const modal = document.getElementById('isbn-selection-modal');
        if (modal) {
            modal.remove();
        }
    };

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