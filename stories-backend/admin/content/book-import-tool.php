<?php
/**
 * Book Import Tool Admin Page
 *
 * This page provides an interface for managing book imports and review scraping.
 * It allows administrators to:
 * 1. View existing books and scrape reviews for them
 * 2. Import new books by author, publisher, year, age, etc.
 * 3. Manage review sources
 * 4. Configure batch processing
 * 5. Run AI analysis on reviews
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

// Include pagination component
require_once '../includes/pagination-component.php';

// Include bulk actions component
require_once '../includes/bulk-actions-component.php';

// Include enhanced table component
require_once '../includes/enhanced-table-component.php';

// Include the review fetcher services
require_once '../../services/ReviewFetcher/ReviewFetcherInterface.php';
require_once '../../services/ReviewFetcher/AbstractReviewFetcher.php';
require_once '../../services/ReviewFetcher/GoogleBooksReviewFetcher.php';
require_once '../../services/ReviewFetcher/OpenLibraryReviewFetcher.php';
require_once '../../services/ReviewFetcher/GoodreadsReviewFetcher.php';
require_once '../../services/ReviewFetcher/AmazonReviewFetcher.php';
require_once '../../services/ReviewFetcher/ReviewFetcherFactory.php';

// Include the AI review analyzer
require_once '../../services/AI/ReviewAnalyzer.php';

// Set page variables for header
$pageTitle = 'Book Import Tool';
$currentPage = 'book-import-tool';
$pageDescription = 'Import books and scrape reviews from various sources';

// Process form submissions
$message = '';
$messageType = '';

// Initialize pagination variables
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$validPerPageValues = [10, 25, 50, 100];
if (!in_array($perPage, $validPerPageValues)) {
    $perPage = 10;
}

// Get search parameters
$bookSearch = isset($_GET['book_search']) ? trim($_GET['book_search']) : '';

// Build query conditions
$bookConditions = ["di.type = 'book'"];
$bookParams = [];

if (!empty($bookSearch)) {
    $bookConditions[] = "(di.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.isbn13 LIKE ?)";
    $searchParam = "%$bookSearch%";
    $bookParams = array_fill(0, 4, $searchParam);
}

$bookWhereClause = implode(" AND ", $bookConditions);

// Get total count
$bookCountStmt = $db->prepare("SELECT COUNT(*) FROM directory_items di JOIN books b ON di.id = b.directory_item_id WHERE $bookWhereClause");
$bookCountStmt->execute($bookParams);
$totalBooks = $bookCountStmt->fetchColumn();

// Calculate pagination
$totalPages = ceil($totalBooks / $perPage);
$offset = ($page - 1) * $perPage;

// Get books with pagination
$booksStmt = $db->prepare("
    SELECT di.id, di.title, di.slug, di.review_count, di.average_rating,
           b.isbn, b.isbn13, b.author, b.publisher, b.page_count, b.series, b.price_range
    FROM directory_items di
    JOIN books b ON di.id = b.directory_item_id
    WHERE $bookWhereClause
    ORDER BY di.title ASC
    LIMIT $perPage OFFSET $offset
");
$booksStmt->execute($bookParams);
$books = $booksStmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <p class="text-muted">Import books and scrape reviews from various sources</p>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="importTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo empty($_GET['tab']) || $_GET['tab'] == 'existing' ? 'active' : ''; ?>"
                               id="existing-tab" data-toggle="tab" href="#existing" role="tab"
                               onclick="updateUrlParam('tab', 'existing')">
                                <i class="fas fa-book"></i> Books & Validation
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] == 'import' ? 'active' : ''; ?>"
                               id="import-tab" data-toggle="tab" href="#import" role="tab"
                               onclick="updateUrlParam('tab', 'import')">
                                <i class="fas fa-file-import"></i> Import New Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] == 'reviews' ? 'active' : ''; ?>"
                               id="reviews-tab" data-toggle="tab" href="#reviews" role="tab"
                               onclick="updateUrlParam('tab', 'reviews')">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] == 'sources' ? 'active' : ''; ?>"
                               id="sources-tab" data-toggle="tab" href="#sources" role="tab"
                               onclick="updateUrlParam('tab', 'sources')">
                                <i class="fas fa-database"></i> Review Sources
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] == 'batch' ? 'active' : ''; ?>"
                               id="batch-tab" data-toggle="tab" href="#batch" role="tab"
                               onclick="updateUrlParam('tab', 'batch')">
                                <i class="fas fa-tasks"></i> Batch Processing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] == 'ai' ? 'active' : ''; ?>"
                               id="ai-tab" data-toggle="tab" href="#ai" role="tab"
                               onclick="updateUrlParam('tab', 'ai')">
                                <i class="fas fa-robot"></i> AI Analysis
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-3" id="importTabsContent">
                        <!-- Existing Books Tab -->
                        <div class="tab-pane fade <?php echo empty($_GET['tab']) || $_GET['tab'] == 'existing' ? 'show active' : ''; ?>" id="existing" role="tabpanel">
                            <h4>Existing Books</h4>
                            <p>View books already imported and scrape reviews for them.</p>

                            <!-- Search Form -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Search Books</h5>
                                </div>
                                <div class="card-body">
                                    <form method="get" class="row g-3">
                                        <div class="col-md-8">
                                            <label for="book_search" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="book_search" name="book_search" value="<?php echo htmlspecialchars($bookSearch); ?>" placeholder="Search by title, author, or ISBN...">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <a href="book-import-tool.php" class="btn btn-secondary ml-2">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        </div>
                                        <input type="hidden" name="page" value="1">
                                    </form>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5>Books (<?php echo number_format($totalBooks); ?>)</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Use enhanced bulk actions component
                                    renderEnhancedBulkActionsComponent('books', [
                                        'delete' => 'Delete Selected',
                                        'validate' => 'Validate ISBNs',
                                        'scrape' => 'Scrape Reviews'
                                    ]);

                                    // Prepare table data
                                    $tableData = [];
                                    foreach ($books as $book) {
                                        $ratingDisplay = !empty($book['average_rating']) 
                                            ? number_format(round($book['average_rating'] * 5, 1), 1) . ' / 5'
                                            : 'N/A';

                                        $isbnDisplay = !empty($book['isbn13'])
                                            ? htmlspecialchars($book['isbn13'])
                                            : (!empty($book['isbn']) ? htmlspecialchars($book['isbn']) : 'N/A');

                                        $tableData[] = [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'author' => $book['author'],
                                            'isbn' => $isbnDisplay,
                                            'reviews' => (int)$book['review_count'],
                                            'rating' => $ratingDisplay,
                                            'series' => !empty($book['series']) ? $book['series'] : 'N/A',
                                            'publisher' => !empty($book['publisher']) ? $book['publisher'] : 'N/A'
                                        ];
                                    }

                                    // Define table columns
                                    $columns = [
                                        'title' => 'Title',
                                        'author' => 'Author',
                                        'isbn' => 'ISBN',
                                        'reviews' => 'Reviews',
                                        'rating' => 'Rating',
                                        'series' => 'Series',
                                        'publisher' => 'Publisher'
                                    ];

                                    // Define editable fields
                                    $editableFields = ['title', 'author', 'series', 'publisher'];

                                    // Render the enhanced table
                                    renderEnhancedTable(
                                        $tableData,
                                        $columns,
                                        'book',
                                        'books-table',
                                        [
                                            'showCheckboxes' => true,
                                            'showActions' => true,
                                            'actions' => ['view', 'edit', 'validate', 'scrape'],
                                            'editableFields' => $editableFields,
                                            'bulkActions' => ['delete', 'validate', 'scrape'],
                                            'itemsPerPage' => $perPage,
                                            'currentPage' => $page,
                                            'totalItems' => $totalBooks,
                                            'htmlFields' => ['rating', 'status', 'missing_data', 'actions'],
                                            'showPagination' => true,
                                            'showItemsPerPage' => true,
                                            'validPerPageValues' => [10, 25, 50, 100, $totalBooks],
                                            'perPageLabel' => 'Show',
                                            'showAllLabel' => 'Show All'
                                        ]
                                    );
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Other tabs remain unchanged -->
                        <!-- Import Tab -->
                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'import' ? 'show active' : ''; ?>" id="import" role="tabpanel">
                            <h4>Import New Books</h4>
                            <p>Import books by ISBN or title and validate their data.</p>
                            
                            <form method="post" action="book-import-validate.php" class="mb-4">
                                <div class="form-group">
                                    <label for="import_data">Enter ISBNs or Titles (one per line)</label>
                                    <textarea class="form-control" id="import_data" name="import_data" rows="5" required></textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="validate_immediately" name="validate_immediately" value="1" checked>
                                    <label class="form-check-label" for="validate_immediately">Validate immediately after import</label>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-import"></i> Import Books
                                </button>
                            </form>
                        </div>

                        <!-- Reviews Tab -->
                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'reviews' ? 'show active' : ''; ?>" id="reviews" role="tabpanel">
                            <h4>Review Management</h4>
                            <p>Configure and manage review scraping settings.</p>

                            <form method="post" action="book-import-scrape.php" class="mb-4">
                                <div class="form-group">
                                    <label>Select Review Sources</label>
                                    <?php
                                    // Get available review sources
                                    $sourcesStmt = $db->prepare("SELECT id, name FROM review_sources WHERE is_third_party = 1");
                                    $sourcesStmt->execute();
                                    $sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($sources as $source) {
                                        echo '<div class="form-check">';
                                        echo '<input type="checkbox" class="form-check-input" name="sources[]" value="' . $source['id'] . '" checked>';
                                        echo '<label class="form-check-label">' . htmlspecialchars($source['name']) . '</label>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>

                                <div class="form-group">
                                    <label for="review_limit">Reviews Per Book</label>
                                    <input type="number" class="form-control" id="review_limit" name="review_limit" value="100" min="10" max="1000">
                                </div>

                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="run_ai_analysis" name="run_ai_analysis" value="1">
                                    <label class="form-check-label" for="run_ai_analysis">Run AI Analysis on Reviews</label>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sync"></i> Start Review Scraping
                                </button>
                            </form>
                        </div>
                        
                        <!-- Source Modal -->
                        <div class="modal fade" id="sourceModal" tabindex="-1" role="dialog" aria-labelledby="sourceModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="sourceModalLabel">Add/Edit Review Source</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="sourceForm" method="post" action="book-import-source.php">
                                            <input type="hidden" id="sourceId" name="source_id" value="">
                                            
                                            <div class="form-group">
                                                <label for="sourceName">Source Name</label>
                                                <input type="text" class="form-control" id="sourceName" name="source_name" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="sourceUrl">Source URL</label>
                                                <input type="url" class="form-control" id="sourceUrl" name="source_url" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="isThirdParty" name="is_third_party" value="1">
                                                    <label class="custom-control-label" for="isThirdParty">Third-party Source</label>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" id="saveSourceBtn">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Review Scraping Modal -->
                        <div class="modal fade" id="scrapeModal" tabindex="-1" role="dialog" aria-labelledby="scrapeModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="scrapeModalLabel">Scrape Reviews</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <form id="scrapeForm" method="post" action="book-import-scrape.php">
                                            <input type="hidden" id="scrapeBookId" name="book_id" value="">
                                            
                                            <div class="form-group">
                                                <label for="scrapeSources">Select Sources</label>
                                                <select class="form-control" id="scrapeSources" name="sources[]" multiple required>
                                                    <?php foreach ($reviewSources as $source): ?>
                                                        <?php if ($source['is_third_party']): ?>
                                                            <option value="<?php echo $source['id']; ?>"><?php echo htmlspecialchars($source['name']); ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple sources</small>
                                            </div>
                                            
                                            <div class="form-group">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="runAiAnalysis" name="run_ai_analysis" value="1">
                                                    <label class="custom-control-label" for="runAiAnalysis">Run AI analysis after scraping</label>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" id="startScrapeBtn">Start Scraping</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <script>
                        $(document).ready(function() {
                            // Import Type Change Handler
                            $('#importType').change(function() {
                                const type = $(this).val();
                                $('.import-author, .import-publisher, .import-year, .import-age, .import-isbn').hide();
                                $(`.import-${type}`).show();
                            });
                            
                            // Book Selection Change Handler
                            $('#bookSelection').change(function() {
                                const selection = $(this).val();
                                if (selection === 'specific') {
                                    $('.book-specific').show();
                                } else {
                                    $('.book-specific').hide();
                                }
                            });
                            
                            // Add Source Button
                            $('#addSourceBtn').click(function() {
                                $('#sourceModalLabel').text('Add Review Source');
                                $('#sourceId').val('');
                                $('#sourceName').val('');
                                $('#sourceUrl').val('');
                                $('#isThirdParty').prop('checked', true);
                                $('#sourceModal').modal('show');
                            });
                            
                            // Edit Source Button
                            $('.edit-source-btn').click(function() {
                                $('#sourceModalLabel').text('Edit Review Source');
                                $('#sourceId').val($(this).data('source-id'));
                                $('#sourceName').val($(this).data('source-name'));
                                $('#sourceUrl').val($(this).data('source-url'));
                                $('#isThirdParty').prop('checked', $(this).data('source-type') == 1);
                                $('#sourceModal').modal('show');
                            });
                            
                            // Save Source Button
                            $('#saveSourceBtn').click(function() {
                                $('#sourceForm').submit();
                            });
                            
                            // Scrape Reviews Button
                            $('.scrape-reviews-btn').click(function() {
                                const bookId = $(this).data('book-id');
                                const bookTitle = $(this).data('book-title');
                                
                                $('#scrapeModalLabel').text(`Scrape Reviews for: ${bookTitle}`);
                                $('#scrapeBookId').val(bookId);
                                $('#scrapeModal').modal('show');
                            });
                            
                            // Start Scrape Button
                            $('#startScrapeBtn').click(function() {
                                $('#scrapeForm').submit();
                            });
                        });
                        </script>
                        
                        <?php
                        // Include footer
                        require_once '../includes/footer.php';
                        ?>

                        <!-- Batch Processing Tab -->
                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'batch' ? 'show active' : ''; ?>" id="batch" role="tabpanel">
                            <h4>Batch Processing</h4>
                            <p>Configure and run batch imports for books and reviews.</p>
                            
                            <form id="batchForm" method="post" action="book-import-batch.php">
                                <div class="form-group">
                                    <label for="batchType">Batch Type</label>
                                    <select class="form-control" id="batchType" name="batch_type">
                                        <option value="import_books">Import New Books</option>
                                        <option value="scrape_reviews">Scrape Reviews for Existing Books</option>
                                        <option value="update_metadata">Update Book Metadata</option>
                                        <option value="fetch_images">Fetch Author/Publisher Images</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="batchSize">Batch Size</label>
                                    <input type="number" class="form-control" id="batchSize" name="batch_size" min="1" max="100" value="10">
                                    <small class="form-text text-muted">Number of items to process in each batch</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="batchDelay">Delay Between Batches (seconds)</label>
                                    <input type="number" class="form-control" id="batchDelay" name="batch_delay" min="0" max="60" value="5">
                                    <small class="form-text text-muted">Delay between batches to prevent rate limiting</small>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="runAsCron" name="run_as_cron" value="1">
                                        <label class="custom-control-label" for="runAsCron">Configure as cron job</label>
                                    </div>
                                    <small class="form-text text-muted">If checked, this will generate a cron command you can use to automate this task</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Start Batch Process
                                </button>
                            </form>
                        </div>
                        
                        <!-- AI Analysis Tab -->
                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'ai' ? 'show active' : ''; ?>" id="ai" role="tabpanel">
                            <h4>AI Analysis</h4>
                            <p>Run AI analysis on reviews to find age-related content and generate summaries.</p>
                            
                            <form id="aiForm" method="post" action="book-import-ai.php">
                                <div class="form-group">
                                    <label for="analysisType">Analysis Type</label>
                                    <select class="form-control" id="analysisType" name="analysis_type">
                                        <option value="age_suitability">Age Suitability Analysis</option>
                                        <option value="content_flags">Content Flags Detection</option>
                                        <option value="review_summary">Generate Review Summaries</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="bookSelection">Book Selection</label>
                                    <select class="form-control" id="bookSelection" name="book_selection">
                                        <option value="all">All Books</option>
                                        <option value="missing">Books Missing Analysis</option>
                                        <option value="specific">Specific Books</option>
                                    </select>
                                </div>
                                
                                <div class="form-group book-specific" style="display: none;">
                                    <label for="specificBooks">Select Books</label>
                                    <select class="form-control" id="specificBooks" name="specific_books[]" multiple>
                                        <?php foreach ($books as $book): ?>
                                            <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple books</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="aiModel">AI Model</label>
                                    <select class="form-control" id="aiModel" name="ai_model">
                                        <option value="gpt-4o">GPT-4o</option>
                                        <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-robot"></i> Start AI Analysis
                                </button>
                            </form>
                        </div>

                        <!-- Sources Tab -->
                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'sources' ? 'show active' : ''; ?>" id="sources" role="tabpanel">
                            <h4>Review Sources</h4>
                            <p>Manage and configure review sources.</p>

                            <?php
                            // Get all review sources
                            $sourcesStmt = $db->prepare("
                                SELECT rs.*, COUNT(r.id) as review_count
                                FROM review_sources rs
                                LEFT JOIN reviews r ON rs.id = r.source_id
                                GROUP BY rs.id
                                ORDER BY rs.name ASC
                            ");
                            $sourcesStmt->execute();
                            $sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

                            // Display sources table
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-striped">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Source</th>';
                            echo '<th>Type</th>';
                            echo '<th>Reviews</th>';
                            echo '<th>Status</th>';
                            echo '<th>Actions</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            foreach ($sources as $source) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($source['name']) . '</td>';
                                echo '<td>' . ($source['is_third_party'] ? 'External' : 'Internal') . '</td>';
                                echo '<td>' . number_format($source['review_count']) . '</td>';
                                echo '<td><span class="badge ' . ($source['is_active'] ? 'bg-success' : 'bg-danger') . '">' .
                                     ($source['is_active'] ? 'Active' : 'Inactive') . '</span></td>';
                                echo '<td>';
                                echo '<button class="btn btn-sm btn-primary me-1" onclick="toggleSource(' . $source['id'] . ')">';
                                echo '<i class="fas fa-power-off"></i></button>';
                                echo '<button class="btn btn-sm btn-info" onclick="editSource(' . $source['id'] . ')">';
                                echo '<i class="fas fa-edit"></i></button>';
                                echo '</td>';
                                echo '</tr>';
                            }

                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                            ?>

                            <script>
                            function toggleSource(sourceId) {
                                // Add source toggle functionality
                                console.log('Toggle source:', sourceId);
                            }

                            function editSource(sourceId) {
                                // Add source edit functionality
                                console.log('Edit source:', sourceId);
                            }
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
