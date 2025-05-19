<?php
// Include required files
require_once '../includes/auth-check.php';
require_once '../includes/db-connect.php';
require_once '../includes/admin-functions.php';
require_once '../includes/tag-functions.php';
require_once '../includes/pagination-component.php';
require_once '../includes/bulk-actions-component.php';
require_once '../includes/enhanced-table-component.php';

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
                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'import' ? 'show active' : ''; ?>" id="import" role="tabpanel">
                            <!-- Import tab content -->
                        </div>

                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'reviews' ? 'show active' : ''; ?>" id="reviews" role="tabpanel">
                            <!-- Reviews tab content -->
                        </div>

                        <div class="tab-pane fade <?php echo isset($_GET['tab']) && $_GET['tab'] == 'sources' ? 'show active' : ''; ?>" id="sources" role="tabpanel">
                            <!-- Sources tab content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
