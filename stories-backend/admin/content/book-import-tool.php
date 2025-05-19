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

// Include components
require_once '../includes/enhanced-table-component.php';
require_once '../includes/bulk-actions-component.php';
require_once '../includes/pagination-component.php';

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

// Get current tab from URL or default to 'existing'
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'existing';

// Validate tab value
$validTabs = ['existing', 'import', 'reviews', 'sources', 'batch', 'ai', 'validate'];
if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'existing';
}
$pageDescription = 'Import books and scrape reviews from various sources';

// Process form submissions
$message = '';
$messageType = '';

try {
    // Initialize pagination variables
    $currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'existing';

    // Books pagination
    $booksPage = isset($_GET['books_page']) ? max(1, intval($_GET['books_page'])) : 1;
    $booksPerPage = isset($_GET['books_per_page']) ? intval($_GET['books_per_page']) : 10;
    $bookSearch = isset($_GET['book_search']) ? trim($_GET['book_search']) : '';

    // Sources pagination
    $sourcesPage = isset($_GET['sources_page']) ? max(1, intval($_GET['sources_page'])) : 1;
    $sourcesPerPage = isset($_GET['sources_per_page']) ? intval($_GET['sources_per_page']) : 10;

    // Reviews pagination
    $reviewsPage = isset($_GET['reviews_page']) ? max(1, intval($_GET['reviews_page'])) : 1;
    $reviewsPerPage = isset($_GET['reviews_per_page']) ? intval($_GET['reviews_per_page']) : 10;

    // ISBN validation pagination
    $isbnPage = isset($_GET['isbn_page']) ? max(1, intval($_GET['isbn_page'])) : 1;
    $isbnPerPage = isset($_GET['isbn_per_page']) ? intval($_GET['isbn_per_page']) : 10;

    // Validate per page values
    $validPerPageValues = [10, 25, 50, 100];
    foreach (['books', 'reviews', 'sources', 'isbn'] as $section) {
        $perPageVar = $section . 'PerPage';
        if (!in_array($$perPageVar, $validPerPageValues)) {
            $$perPageVar = 10;
        }
    }

    // Build query conditions for books
    $bookConditions = ["di.type = 'book'"];
    $bookParams = [];

    if (!empty($bookSearch)) {
        $bookConditions[] = "(di.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.isbn13 LIKE ?)";
        $searchParam = "%$bookSearch%";
        $bookParams[] = $searchParam;
        $bookParams[] = $searchParam;
        $bookParams[] = $searchParam;
        $bookParams[] = $searchParam;
    }

    $bookWhereClause = implode(" AND ", $bookConditions);

    // Count total books
    $bookCountQuery = "
        SELECT COUNT(*)
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE $bookWhereClause
    ";
    $bookCountStmt = $db->prepare($bookCountQuery);
    $bookCountStmt->execute($bookParams);
    $totalBooks = $bookCountStmt->fetchColumn();

    // Calculate pagination
    $totalBookPages = ceil($totalBooks / $booksPerPage);
    $bookOffset = ($booksPage - 1) * $booksPerPage;
    
    // Ensure offset is not negative
    $bookOffset = max(0, $bookOffset);

    // Get books with pagination
    $booksStmt = $db->prepare("
        SELECT di.id, di.title, di.slug, di.review_count, di.average_rating,
               b.isbn, b.isbn13, b.author, b.publisher, b.page_count, b.series, b.price_range
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE $bookWhereClause
        ORDER BY di.title ASC
        LIMIT $booksPerPage OFFSET $bookOffset
    ");
    $booksStmt->execute($bookParams);
    $books = $booksStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all review sources
    $sourcesStmt = $db->prepare("
        SELECT id, name, url, is_third_party
        FROM review_sources
        ORDER BY name ASC
    ");
    $sourcesStmt->execute();
    $reviewSources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

    // If no review sources exist, create default ones
    if (empty($reviewSources)) {
        $db->beginTransaction();

        // Create default review sources
        $defaultSources = [
            ['Stories from the Web', 'https://storiesfromtheweb.org', 0],
            ['Google Books', 'https://books.google.com', 1],
            ['Open Library', 'https://openlibrary.org', 1],
            ['Goodreads', 'https://goodreads.com', 1],
            ['Amazon', 'https://amazon.com', 1]
        ];

        $insertStmt = $db->prepare("
            INSERT INTO review_sources (name, url, is_third_party, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");

        foreach ($defaultSources as $source) {
            $insertStmt->execute($source);
        }

        $db->commit();

        // Fetch the newly created sources
        $sourcesStmt->execute();
        $reviewSources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

        $message = 'Default review sources created successfully.';
        $messageType = 'success';
    }

    // Get all authors
    $authorsStmt = $db->prepare("
        SELECT id, name
        FROM authors
        WHERE author_type = 'retail'
        ORDER BY name ASC
    ");
    $authorsStmt->execute();
    $authors = $authorsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all publishers
    $publishersStmt = $db->prepare("
        SELECT id, name
        FROM authors
        WHERE author_type = 'publisher'
        ORDER BY name ASC
    ");
    $publishersStmt->execute();
    $publishers = $publishersStmt->fetchAll(PDO::FETCH_ASSOC);

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

            <?php
            // Function to check if Git Auto Deploy webhook is running
            function is_webhook_online() {
                // ONLY check the known working IP address: 37.27.31.107
                // Based on diagnostic results, this is the only address that works

                // Try direct socket connection first (most reliable)
                $fp = @fsockopen('37.27.31.107', 8080, $errno, $errstr, 1);
                if ($fp) {
                    fclose($fp);
                    return true;
                }

                // If socket fails, try cURL
                if (function_exists('curl_init')) {
                    $ch = curl_init('http://37.27.31.107:8080');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($response !== false || ($httpCode > 0 && $httpCode < 500)) {
                        return true;
                    }
                }

                // Last resort: try file_get_contents
                $context = stream_context_create([
                    'http' => [
                        'method' => 'HEAD',
                        'timeout' => 1,
                        'ignore_errors' => true
                    ]
                ]);

                $response = @file_get_contents('http://37.27.31.107:8080', false, $context);
                if ($response !== false) {
                    return true;
                }

                return false; // All checks failed
            }

            // Get the last auto-pull timestamp
            function get_last_pull_timestamp() {
                // Try multiple log file locations
                $logFiles = [
                    '/var/log/git-auto-deploy.log',
                    '/home/stories/logs/git-auto-deploy.log',
                    '/opt/stories/logs/git-auto-deploy.log',
                    '/var/log/webhook/git-auto-deploy.log'
                ];

                foreach ($logFiles as $logFile) {
                    if (file_exists($logFile)) {
                        $lastLine = exec("tail -n 1 " . escapeshellarg($logFile));
                        if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $lastLine, $matches)) {
                            return $matches[1];
                        }
                    }
                }

                // Check if we can get the last pull time from the webhook server directly
                if (function_exists('curl_init')) {
                    $ch = curl_init('http://37.27.31.107:8080/status');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    if ($response && preg_match('/Last pull: (\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $response, $matches)) {
                        return $matches[1];
                    }
                }

                // Fallback: check the git directory for last commit time
                $gitDir = dirname(dirname(dirname(__DIR__))) . '/.git';
                if (is_dir($gitDir)) {
                    $lastCommitTime = exec("git --git-dir=" . escapeshellarg($gitDir) . " log -1 --format=%cd --date=format:'%Y-%m-%d %H:%M:%S'");
                    if ($lastCommitTime) {
                        return $lastCommitTime;
                    }
                }

                // Last resort: check the modification time of the current file
                $currentFile = __FILE__;
                if (file_exists($currentFile)) {
                    $modTime = filemtime($currentFile);
                    if ($modTime) {
                        return date('Y-m-d H:i:s', $modTime);
                    }
                }

                return date('Y-m-d H:i:s'); // Return current time as last resort
            }

            $webhookStatus = is_webhook_online();
            $statusClass = $webhookStatus ? 'alert-success' : 'alert-danger';
            $statusIcon = $webhookStatus ? 'check-circle' : 'exclamation-triangle';
            $statusText = $webhookStatus ? '🟢 Git Auto Deploy: Online' : '🔴 Git Auto Deploy: Not running!';
            $lastPullTime = get_last_pull_timestamp();
            $statusDesc = $webhookStatus ?
                "Code changes will be automatically deployed to the server. Last auto-pull: {$lastPullTime}" :
                "WARNING: Code changes will NOT be automatically deployed to the server! Please restart the Git Auto Deploy service.";
            ?>
            <div class="alert <?php echo $statusClass; ?> d-flex align-items-center mb-4" role="alert">
                <i class="fas fa-<?php echo $statusIcon; ?> me-2"></i>
                <div>
                    <strong><?php echo $statusText; ?></strong> - <?php echo $statusDesc; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <p class="text-muted">Import books and scrape reviews from various sources</p>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="importTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentTab === 'existing' ? 'active' : ''; ?>"
                               id="existing-tab" data-toggle="tab" href="#existing" role="tab" data-tab="existing">
                                <i class="fas fa-book"></i> Existing Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentTab === 'import' ? 'active' : ''; ?>"
                               id="import-tab" data-toggle="tab" href="#import" role="tab" data-tab="import">
                                <i class="fas fa-file-import"></i> Import New Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentTab === 'reviews' ? 'active' : ''; ?>"
                               id="reviews-tab" data-toggle="tab" href="#reviews" role="tab" data-tab="reviews">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $currentTab === 'sources' ? 'active' : ''; ?>"
                               id="sources-tab" data-toggle="tab" href="#sources" role="tab" data-tab="sources">
                                <i class="fas fa-database"></i> Review Sources
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="batch-tab" data-toggle="tab" href="#batch" role="tab">
                                <i class="fas fa-tasks"></i> Batch Processing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="ai-tab" data-toggle="tab" href="#ai" role="tab">
                                <i class="fas fa-robot"></i> AI Analysis
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="validate-tab" data-toggle="tab" href="#validate" role="tab">
                                <i class="fas fa-check-circle"></i> ISBN & Data Validation
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-3" id="importTabsContent">
                        <!-- Existing Books Tab -->
                        <div class="tab-pane fade show active" id="existing" role="tabpanel">
                            <h4>Existing Books</h4>
                            <p>View books already imported and scrape reviews for them.</p>

                            <!-- Search Form -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Search Books</h5>
                                </div>
                                <div class="card-body">
                                    <form method="get" class="row g-3">
                                        <input type="hidden" name="tab" value="existing">
                                        <input type="hidden" name="tab" value="existing">
                                        <div class="col-md-8">
                                            <label for="book_search" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="book_search" name="book_search" value="<?php echo htmlspecialchars($bookSearch); ?>" placeholder="Search by title, author, or ISBN...">
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            <a href="book-import-tool.php?tab=existing" class="btn btn-secondary ml-2">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5>Books (<?php echo number_format($totalBooks); ?>)</h5>
                                    <div>
                                        <span class="text-muted">Page <?php echo $booksPage; ?> of <?php echo $totalBookPages; ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Initialize table data
                                    $tableData = [];
                                    $books = $books ?? [];
                                    foreach ($books as $book) {
                                        $rating = !empty($book['average_rating'])
                                            ? number_format(round($book['average_rating'] * 5, 1), 1) . ' / 5'
                                            : 'N/A';

                                        $isbn = !empty($book['isbn13'])
                                            ? htmlspecialchars($book['isbn13'])
                                            : (!empty($book['isbn']) ? htmlspecialchars($book['isbn']) : 'N/A');

                                        $tableData[] = [
                                            'id' => $book['id'],
                                            'title' => htmlspecialchars($book['title']),
                                            'author' => htmlspecialchars($book['author']),
                                            'isbn' => $isbn,
                                            'reviews' => (int)$book['review_count'],
                                            'rating' => $rating,
                                            'actions' => '<button class="btn btn-sm btn-primary scrape-reviews-btn" data-book-id="' . $book['id'] . '" data-book-title="' . htmlspecialchars($book['title']) . '"><i class="fas fa-sync"></i> Scrape Reviews</button>'
                                        ];
                                    }

                                    // Define table columns
                                    $columns = [
                                        'title' => 'Title',
                                        'author' => 'Author',
                                        'isbn' => 'ISBN',
                                        'reviews' => 'Reviews',
                                        'rating' => 'Rating',
                                        'actions' => 'Actions'
                                    ];

                                    // Render enhanced table
                                    renderEnhancedTable(
                                        $tableData,
                                        $columns,
                                        'book',
                                        'books-table',
                                        [
                                            'showCheckboxes' => true,
                                            'showActions' => true,
                                            'actions' => ['view', 'edit', 'validate', 'scrape'],
                                            'bulkActions' => ['delete', 'validate', 'scrape'],
                                            'itemsPerPage' => $booksPerPage,
                                            'currentPage' => $booksPage,
                                            'totalItems' => $totalBooks,
                                            'htmlFields' => ['rating', 'actions'],
                                            'showPagination' => true,
                                            'showItemsPerPage' => true,
                                            'validPerPageValues' => [10, 25, 50, 100, $totalBooks],
                                            'perPageLabel' => 'Show',
                                            'showAllLabel' => 'Show All'
                                        ]
                                    );
                                    ?>
                                </div>
                                <?php
                                // Ensure tab parameter is in URL for pagination
                                $_GET['tab'] = 'existing';
                                // Render pagination for books table
                                renderPagination($totalBooks, $booksPerPage, $booksPage, 5, ['tab' => 'existing']);
                                ?>
                            </div>
                        </div>

                        <!-- Import New Books Tab -->
                        <div class="tab-pane fade" id="import" role="tabpanel">
                            <h4>Import New Books</h4>
                            <p>Import new books by author, publisher, year, age, etc.</p>

                            <form id="importForm" method="post" action="book-import-process.php">
                                <div class="form-group">
                                    <label for="importType">Import Type</label>
                                    <select class="form-control" id="importType" name="import_type">
                                        <option value="author">By Author</option>
                                        <option value="publisher">By Publisher</option>
                                        <option value="year">By Year</option>
                                        <option value="age">By Age Range</option>
                                        <option value="isbn">By ISBN List</option>
                                    </select>
                                </div>

                                <div class="form-group import-author">
                                    <label for="authorId">Select Author</label>
                                    <select class="form-control" id="authorId" name="author_id">
                                        <?php foreach ($authors as $author): ?>
                                            <option value="<?php echo $author['id']; ?>"><?php echo htmlspecialchars($author['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group import-publisher" style="display: none;">
                                    <label for="publisherId">Select Publisher</label>
                                    <select class="form-control" id="publisherId" name="publisher_id">
                                        <?php foreach ($publishers as $publisher): ?>
                                            <option value="<?php echo $publisher['id']; ?>"><?php echo htmlspecialchars($publisher['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group import-year" style="display: none;">
                                    <label for="year">Publication Year</label>
                                    <input type="number" class="form-control" id="year" name="year" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                                </div>

                                <div class="form-group import-age" style="display: none;">
                                    <label for="ageRange">Age Range</label>
                                    <select class="form-control" id="ageRange" name="age_range">
                                        <option value="3-5">3-5 years</option>
                                        <option value="6-8">6-8 years</option>
                                        <option value="9-12">9-12 years</option>
                                        <option value="13+">13+ years</option>
                                    </select>
                                </div>

                                <div class="form-group import-isbn" style="display: none;">
                                    <label for="isbnList">ISBN List (one per line)</label>
                                    <textarea class="form-control" id="isbnList" name="isbn_list" rows="5" placeholder="Enter ISBNs, one per line"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="limit">Limit Results</label>
                                    <input type="number" class="form-control" id="limit" name="limit" min="1" max="100" value="10">
                                    <small class="form-text text-muted">Maximum number of books to import in one batch</small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="scrapeReviews" name="scrape_reviews" value="1" checked>
                                        <label class="custom-control-label" for="scrapeReviews">Automatically scrape reviews after import</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-file-import"></i> Start Import
                                </button>
                            </form>
                        </div>

                        <!-- Review Sources Tab -->
                        <div class="tab-pane fade" id="sources" role="tabpanel">
                            <h4>Review Sources</h4>
                            <p>Manage sources for scraping book reviews.</p>

                            <?php
                            // Prepare table data for review sources
                            $tableData = [];
                            foreach ($reviewSources as $source) {
                                $tableData[] = [
                                    'id' => $source['id'],
                                    'name' => htmlspecialchars($source['name']),
                                    'url' => htmlspecialchars($source['url']),
                                    'type' => $source['is_third_party'] ? 'Third-party' : 'Internal',
                                    'actions' => '<button class="btn btn-sm btn-primary edit-source-btn" ' .
                                               'data-source-id="' . $source['id'] . '" ' .
                                               'data-source-name="' . htmlspecialchars($source['name']) . '" ' .
                                               'data-source-url="' . htmlspecialchars($source['url']) . '" ' .
                                               'data-source-type="' . $source['is_third_party'] . '">' .
                                               '<i class="fas fa-edit"></i> Edit</button>'
                                ];
                            }

                            // Define table columns
                            $columns = [
                                'name' => 'Name',
                                'url' => 'URL',
                                'type' => 'Type',
                                'actions' => 'Actions'
                            ];

                            // Render enhanced table
                            renderEnhancedTable(
                                $tableData,
                                $columns,
                                'source',
                                'sources-table',
                                [
                                    'showCheckboxes' => true,
                                    'showActions' => true,
                                    'actions' => ['edit', 'delete'],
                                    'bulkActions' => ['delete', 'toggle'],
                                    'itemsPerPage' => $sourcesPerPage,
                                    'currentPage' => $sourcesPage,
                                    'totalItems' => count($reviewSources),
                                    'htmlFields' => ['actions'],
                                    'showPagination' => true,
                                    'showItemsPerPage' => true,
                                    'validPerPageValues' => [10, 25, 50, 100, count($reviewSources)],
                                    'perPageLabel' => 'Show',
                                    'showAllLabel' => 'Show All'
                                ]
                            );
                            ?>
                            <?php
                            // Ensure tab parameter is in URL for pagination
                            $_GET['tab'] = 'sources';
                            // Render pagination for sources table
                            renderPagination(count($reviewSources), $sourcesPerPage, $sourcesPage, 5, ['tab' => 'sources']);
                            ?>

                            <button class="btn btn-success" id="addSourceBtn">
                                <i class="fas fa-plus"></i> Add New Source
                            </button>
                        </div>

                        <!-- Batch Processing Tab -->
                        <div class="tab-pane fade" id="batch" role="tabpanel">
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

                        <!-- Reviews Tab -->
                        <div class="tab-pane fade" id="reviews" role="tabpanel">
                            <h4>Reviews Management</h4>
                            <p>Manage book reviews from various sources.</p>

                            <?php
                            // Initialize reviews variables
                            $reviewPage = isset($_GET['review_page']) ? max(1, intval($_GET['review_page'])) : 1;
                            $reviewsPerPage = 20;
                            $reviewSearch = isset($_GET['review_search']) ? trim($_GET['review_search']) : '';
                            $reviewSourceFilter = isset($_GET['review_source']) ? intval($_GET['review_source']) : 0;
                            $reviewBookFilter = isset($_GET['review_book_id']) ? intval($_GET['review_book_id']) : 0;
                            $reviewRatingFilter = isset($_GET['review_rating']) ? floatval($_GET['review_rating']) : 0;

                            // Build query conditions for reviews
                            $reviewConditions = [];
                            $reviewParams = [];

                            if (!empty($reviewSearch)) {
                                $reviewConditions[] = "(r.reviewer_name LIKE ? OR r.review_text LIKE ? OR d.title LIKE ?)";
                                $searchParam = "%$reviewSearch%";
                                $reviewParams[] = $searchParam;
                                $reviewParams[] = $searchParam;
                                $reviewParams[] = $searchParam;
                            }

                            if ($reviewSourceFilter > 0) {
                                $reviewConditions[] = "r.source_id = ?";
                                $reviewParams[] = $reviewSourceFilter;
                            }

                            if ($reviewBookFilter > 0) {
                                $reviewConditions[] = "r.book_id = ?";
                                $reviewParams[] = $reviewBookFilter;
                            }

                            if ($reviewRatingFilter > 0) {
                                $reviewConditions[] = "r.rating_normalised >= ?";
                                $reviewParams[] = $reviewRatingFilter / 5; // Convert to 0-1 scale
                            }

                            $reviewWhereClause = !empty($reviewConditions) ? "WHERE " . implode(" AND ", $reviewConditions) : "";

                            // Count total reviews
                            $reviewCountQuery = "
                                SELECT COUNT(*)
                                FROM reviews r
                                LEFT JOIN directory_items d ON r.book_id = d.id
                                LEFT JOIN review_sources s ON r.source_id = s.id
                                $reviewWhereClause
                            ";
                            $reviewCountStmt = $db->prepare($reviewCountQuery);
                            $reviewCountStmt->execute($reviewParams);
                            $totalReviews = $reviewCountStmt->fetchColumn();

                            // Calculate pagination
                            $totalReviewPages = ceil($totalReviews / $reviewsPerPage);
                            $reviewOffset = ($reviewsPage - 1) * $reviewsPerPage;
                            
                            // Ensure offset is not negative
                            $reviewOffset = max(0, $reviewOffset);

                            // Get reviews
                            $reviewQuery = "
                                SELECT r.*, d.title as book_title, s.name as source_name, s.is_third_party
                                FROM reviews r
                                LEFT JOIN directory_items d ON r.book_id = d.id
                                LEFT JOIN review_sources s ON r.source_id = s.id
                                $reviewWhereClause
                                ORDER BY r.created_at DESC
                                LIMIT $reviewsPerPage OFFSET $reviewOffset
                            ";
                            $reviewStmt = $db->prepare($reviewQuery);
                            $reviewStmt->execute($reviewParams);
                            $reviews = $reviewStmt->fetchAll();
                            ?>

                            <!-- Filters -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>Filters</h5>
                                </div>
                                <div class="card-body">
                                    <form method="get" class="row g-3" id="review-filter-form">
                                        <input type="hidden" name="tab" value="reviews">
                                        <div class="col-md-4">
                                            <label for="review_search" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="review_search" name="review_search" value="<?php echo htmlspecialchars($reviewSearch); ?>" placeholder="Search reviews...">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="review_source" class="form-label">Source</label>
                                            <select class="form-control" id="review_source" name="review_source">
                                                <option value="0">All Sources</option>
                                                <?php foreach ($reviewSources as $source): ?>
                                                    <option value="<?php echo $source['id']; ?>" <?php echo $reviewSourceFilter == $source['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($source['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="review_book_id" class="form-label">Book</label>
                                            <select class="form-control" id="review_book_id" name="review_book_id">
                                                <option value="0">All Books</option>
                                                <?php foreach ($books as $book): ?>
                                                    <option value="<?php echo $book['id']; ?>" <?php echo $reviewBookFilter == $book['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($book['title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="review_rating" class="form-label">Min Rating</label>
                                            <select class="form-control" id="review_rating" name="review_rating">
                                                <option value="0" <?php echo $reviewRatingFilter == 0 ? 'selected' : ''; ?>>Any Rating</option>
                                                <option value="1" <?php echo $reviewRatingFilter == 1 ? 'selected' : ''; ?>>★ (1+)</option>
                                                <option value="2" <?php echo $reviewRatingFilter == 2 ? 'selected' : ''; ?>>★★ (2+)</option>
                                                <option value="3" <?php echo $reviewRatingFilter == 3 ? 'selected' : ''; ?>>★★★ (3+)</option>
                                                <option value="4" <?php echo $reviewRatingFilter == 4 ? 'selected' : ''; ?>>★★★★ (4+)</option>
                                                <option value="5" <?php echo $reviewRatingFilter == 5 ? 'selected' : ''; ?>>★★★★★ (5)</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-filter"></i> Apply Filters
                                            </button>
                                            <a href="book-import-tool.php?tab=reviews" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Clear Filters
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Reviews Table -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5>Reviews (<?php echo number_format($totalReviews); ?>)</h5>
                                    <div>
                                        <span class="text-muted">Page <?php echo $reviewsPage; ?> of <?php echo $totalReviewPages; ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Prepare table data for reviews
                                    $tableData = [];
                                    foreach ($reviews as $review) {
                                        $stars = round($review['rating_normalised'] * 5);
                                        $rating = str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) .
                                                ' (' . $review['original_rating'] . ')';
                                        
                                        $source = htmlspecialchars($review['source_name']);
                                        if ($review['is_third_party']) {
                                            $source .= ' <span class="badge badge-info">External</span>';
                                        }

                                        $actions = '<div class="btn-group">' .
                                                  '<button type="button" class="btn btn-sm btn-info view-review" data-id="' . $review['id'] . '">' .
                                                  '<i class="fas fa-eye"></i></button>' .
                                                  '<a href="edit-review.php?id=' . $review['id'] . '" class="btn btn-sm btn-primary">' .
                                                  '<i class="fas fa-edit"></i></a>' .
                                                  '<button type="button" class="btn btn-sm btn-danger delete-review" data-id="' . $review['id'] . '">' .
                                                  '<i class="fas fa-trash"></i></button>' .
                                                  '</div>';

                                        $tableData[] = [
                                            'id' => $review['id'],
                                            'book' => '<a href="directory-item-form.php?id=' . $review['book_id'] . '">' .
                                                     htmlspecialchars($review['book_title']) . '</a>',
                                            'reviewer' => htmlspecialchars($review['reviewer_name']),
                                            'rating' => $rating,
                                            'source' => $source,
                                            'date' => $review['review_date'],
                                            'actions' => $actions
                                        ];
                                    }

                                    // Define table columns
                                    $columns = [
                                        'book' => 'Book',
                                        'reviewer' => 'Reviewer',
                                        'rating' => 'Rating',
                                        'source' => 'Source',
                                        'date' => 'Date',
                                        'actions' => 'Actions'
                                    ];

                                    // Render enhanced table
                                    renderEnhancedTable(
                                        $tableData,
                                        $columns,
                                        'review',
                                        'reviews-table',
                                        [
                                            'showCheckboxes' => true,
                                            'showActions' => true,
                                            'actions' => ['view', 'edit', 'delete'],
                                            'bulkActions' => ['delete', 'analyze'],
                                            'itemsPerPage' => $reviewsPerPage,
                                            'currentPage' => $reviewsPage,
                                            'totalItems' => $totalReviews,
                                            'htmlFields' => ['book', 'rating', 'source', 'actions'],
                                            'showPagination' => true,
                                            'showItemsPerPage' => true,
                                            'validPerPageValues' => [10, 25, 50, 100, $totalReviews],
                                            'perPageLabel' => 'Show',
                                            'showAllLabel' => 'Show All'
                                        ]
                                    );
                                    ?>
                                </div>
                                <?php
                                // Ensure tab parameter is in URL for pagination
                                $_GET['tab'] = 'reviews';
                                // Render pagination for reviews table
                                renderPagination($totalReviews, $reviewsPerPage, $reviewsPage);
                                ?>
                            </div>
                        </div>

                        <!-- AI Analysis Tab -->
                        <div class="tab-pane fade" id="ai" role="tabpanel">
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

                        <!-- ISBN & Data Validation Tab -->
                        <div class="tab-pane fade" id="validate" role="tabpanel">
                            <h4>ISBN & Data Validation</h4>
                            <p>Check and fix incorrect ISBNs, and enrich missing book data from external sources.</p>

                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5>ISBN Validation</h5>
                                </div>
                                <div class="card-body">
                                    <p>This tool checks ISBNs against external sources like Goodreads, Google Books, and Open Library to verify their accuracy.</p>

                                    <?php
                                    // Prepare table data for ISBN validation
                                    $tableData = [];
                                    foreach ($books as $book) {
                                        $isbn = !empty($book['isbn13']) ? $book['isbn13'] : (!empty($book['isbn']) ? $book['isbn'] : '');
                                        $isbnStatus = 'unknown';
                                        $statusClass = 'secondary';
                                        $statusIcon = 'question-circle';

                                        if (empty($isbn)) {
                                            $isbnStatus = 'missing';
                                            $statusClass = 'danger';
                                            $statusIcon = 'times-circle';
                                        } else {
                                            // Basic format check
                                            $cleanIsbn = preg_replace('/[^0-9X]/i', '', $isbn);
                                            if (strlen($cleanIsbn) != 10 && strlen($cleanIsbn) != 13) {
                                                $isbnStatus = 'invalid';
                                                $statusClass = 'warning';
                                                $statusIcon = 'exclamation-circle';
                                            } else {
                                                $isbnStatus = 'valid';
                                                $statusClass = 'success';
                                                $statusIcon = 'check-circle';
                                            }
                                        }

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
                                            'author' => htmlspecialchars($book['author']),
                                            'isbn' => !empty($isbn) ? htmlspecialchars($isbn) : '<span class="text-danger">Missing</span>',
                                            'status' => '<span class="badge badge-' . $statusClass . '"><i class="fas fa-' . $statusIcon . '"></i> ' . ucfirst($isbnStatus) . '</span>',
                                            'genre' => $genreDisplay,
                                            'missing_data' => $missingDataDisplay,
                                            'actions' => '<button class="btn btn-sm btn-primary validate-isbn-btn" ' .
                                                       'data-book-id="' . $book['id'] . '" ' .
                                                       'data-book-title="' . htmlspecialchars($book['title']) . '" ' .
                                                       'data-isbn="' . htmlspecialchars($isbn) . '">' .
                                                       '<i class="fas fa-check"></i> Validate</button>'
                                        ];
                                    }

                                    // Define table columns
                                    $columns = [
                                        'title' => 'Title',
                                        'author' => 'Author',
                                        'isbn' => 'ISBN',
                                        'status' => 'Status',
                                        'genre' => 'Genre',
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
                                            'showActions' => true,
                                            'actions' => ['validate', 'edit'],
                                            'bulkActions' => ['validate', 'enrich'],
                                            'itemsPerPage' => $isbnPerPage,
                                            'currentPage' => $isbnPage,
                                            'totalItems' => count($books),
                                            'htmlFields' => ['isbn', 'status', 'genre', 'missing_data', 'actions'],
                                            'showPagination' => true,
                                            'showItemsPerPage' => true,
                                            'validPerPageValues' => [10, 25, 50, 100, count($books)],
                                            'perPageLabel' => 'Show',
                                            'showAllLabel' => 'Show All'
                                        ]
                                    );
                                    ?>
                                    <?php
                                    // Render pagination for ISBN validation table
                                    renderPagination(count($books), $isbnPerPage, $isbnPage);
                                    ?>

                                    <div class="mt-3">
                                        <button id="validate-selected-isbns" class="btn btn-primary">
                                            <i class="fas fa-check-double"></i> Validate Selected ISBNs
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

                                    <form id="enrichForm" method="post" action="book-import-validate.php">
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
                                                <?php foreach ($books as $book): ?>
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
    </div>
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
                                <?php if ($source['is_third_party'] && (strtolower($source['name']) === 'goodreads' || strtolower($source['name']) === 'amazon')): ?>
                                    <option value="<?php echo $source['id']; ?>" <?php echo strtolower($source['name']) === 'goodreads' ? 'selected' : ''; ?>><?php echo htmlspecialchars($source['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted"><strong>Note:</strong> Currently only Goodreads and Amazon are available for review scraping due to issues with other sources.</small>
                    </div>

                    <div class="form-group">
                        <label for="reviewLimit">Maximum Reviews to Fetch</label>
                        <input type="number" class="form-control" id="reviewLimit" name="limit" value="100" min="10" max="1000">
                        <small class="form-text text-muted">Higher values will take longer but fetch more reviews. Maximum 1000.</small>
                    </div>

                    <div class="form-group">
                        <label for="maxPages">Maximum Pages to Scrape</label>
                        <input type="number" class="form-control" id="maxPages" name="max_pages" value="20" min="1" max="100">
                        <small class="form-text text-muted">Higher values will allow more reviews to be fetched.</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="forceRefresh" name="force_refresh" value="1">
                            <label class="custom-control-label" for="forceRefresh">Force refresh (replace existing reviews)</label>
                            <small class="form-text text-muted">If checked, will replace existing reviews instead of skipping duplicates.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="continueFromLast" name="continue_from_last" value="1" checked>
                            <label class="custom-control-label" for="continueFromLast">Continue from last scrape</label>
                            <small class="form-text text-muted">If checked, will attempt to fetch reviews beyond those already collected.</small>
                        </div>
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

    // Select All Checkbox
    $('.select-all-checkbox').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.review-checkbox').prop('checked', isChecked);
    });

    // Individual Delete Buttons
    $('.delete-review').on('click', function() {
        const reviewId = $(this).data('id');
        if (confirm('Are you sure you want to delete this review?')) {
            // Create a temporary form to submit the delete request
            const form = $('<form>', {
                'method': 'post',
                'action': 'review-bulk-actions.php'
            });

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'bulk_action',
                'value': 'delete'
            }));

            form.append($('<input>', {
                'type': 'hidden',
                'name': 'selected_reviews[]',
                'value': reviewId
            }));

            $('body').append(form);
            form.submit();
        }
    });

    // Apply Bulk Action Button
    $('#apply-bulk-action').on('click', function(e) {
        const action = $('#bulk-action').val();
        const selectedReviews = $('.review-checkbox:checked').length;

        if (!action) {
            e.preventDefault();
            alert('Please select an action to perform.');
            return false;
        }

        if (selectedReviews === 0) {
            e.preventDefault();
            alert('Please select at least one review.');
            return false;
        }

        if (action === 'delete' && !confirm(`Are you sure you want to delete ${selectedReviews} selected reviews?`)) {
            e.preventDefault();
            return false;
        }

        return true;
    });

    // ISBN Validation Tab Handlers
    $('#select-all-isbn').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.isbn-checkbox').prop('checked', isChecked);
    });

    $('.validate-isbn-btn').on('click', function() {
        const bookId = $(this).data('book-id');
        const bookTitle = $(this).data('book-title');
        const isbn = $(this).data('isbn');

        // Redirect to the validation page with the book ID
        window.location.href = `book-import-validate.php?action=validate_isbn&book_id=${bookId}&isbn=${isbn}`;
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
            'action': 'book-import-validate.php'
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

<!-- Include tab state handler -->
<script src="../js/tab-state-handler.js"></script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
