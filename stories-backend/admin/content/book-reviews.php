<?php
/**
 * Book Reviews Admin Page
 *
 * This page provides an interface for managing book reviews.
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
$pageTitle = 'Book Reviews';
$currentPage = 'book-reviews';

// Process form submissions
$message = '';
$messageType = '';

try {
    // Reviews tab pagination
    $reviewsPage = isset($_GET['reviews_page']) ? max(1, intval($_GET['reviews_page'])) : 1;
    $reviewsPerPage = isset($_GET['reviews_per_page']) ? intval($_GET['reviews_per_page']) : 10;
    
    // Ensure reviewsPerPage is a valid value
    if ($reviewsPerPage <= 0) {
        $reviewsPerPage = 10;
    }
    
    // Force debug output
    error_log("REVIEWS PER PAGE: " . $reviewsPerPage);
    
    // Log the parameters for debugging
    error_log("Reviews Page: $reviewsPage, Reviews Per Page: $reviewsPerPage");
    
    // Calculate offsets
    $reviewsOffset = ($reviewsPage - 1) * $reviewsPerPage;
    $reviewsOffset = max(0, $reviewsOffset);

    // Initialize standard per page values
    $validPerPageValues = [5, 10, 15, 20, 25, 50, 100];

    // Get all review sources
    $sourcesStmt = $db->prepare("
        SELECT id, name, url, is_third_party
        FROM review_sources
        ORDER BY name ASC
    ");
    $sourcesStmt->execute();
    $reviewSources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize reviews variables
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

    // Add total items as a valid per_page value
    if (!in_array($totalReviews, $validPerPageValues)) {
        $validPerPageValues[] = $totalReviews;
    }
    
    // Add the current reviewsPerPage to the valid values if it's not already there
    if (!in_array($reviewsPerPage, $validPerPageValues)) {
        $validPerPageValues[] = $reviewsPerPage;
    }

    // Calculate pagination
    $totalReviewPages = ceil($totalReviews / $reviewsPerPage);

    // Get reviews
    $reviewQuery = "
        SELECT r.*,
               d.title as book_title,
               b.title as book_title_fallback,
               s.name as source_name,
               s.is_third_party
        FROM reviews r
        LEFT JOIN directory_items d ON r.book_id = d.id
        LEFT JOIN books b ON b.directory_item_id = r.book_id
        LEFT JOIN review_sources s ON r.source_id = s.id
        $reviewWhereClause
        ORDER BY r.created_at DESC
        LIMIT $reviewsPerPage OFFSET $reviewsOffset
    ";
    $reviewStmt = $db->prepare($reviewQuery);
    $reviewStmt->execute($reviewParams);
    $reviews = $reviewStmt->fetchAll();

    // Get all books for the dropdown
    $booksStmt = $db->prepare("
        SELECT di.id, di.title
        FROM directory_items di
        JOIN books b ON di.id = b.directory_item_id
        WHERE di.type = 'book'
        ORDER BY di.title ASC
    ");
    $booksStmt->execute();
    $books = $booksStmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <h4 class="mb-0">Book Reviews</h4>
                        <div>
                            <a href="book-import-tool.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Import Tool
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p>Manage book reviews from various sources.</p>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Filters</h5>
                        </div>
                        <div class="card-body">
                            <form method="get" class="row g-3" id="review-filter-form">
                                <!-- Reset page to 1 when applying filters -->
                                <input type="hidden" name="reviews_page" value="1">
                                <!-- Don't include reviews_per_page as hidden field since we have a dropdown for it -->
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
                                <div class="col-md-2">
                                    <label for="reviews_per_page" class="form-label">Show</label>
                                    <select class="form-control" id="reviews_per_page">
                                        <option value="5" <?php echo $reviewsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                                        <option value="10" <?php echo $reviewsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="15" <?php echo $reviewsPerPage == 15 ? 'selected' : ''; ?>>15</option>
                                        <option value="20" <?php echo $reviewsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="25" <?php echo $reviewsPerPage == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $reviewsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $reviewsPerPage == 100 ? 'selected' : ''; ?>>100</option>
                                        <option value="<?php echo $totalReviews; ?>" <?php echo $reviewsPerPage == $totalReviews ? 'selected' : ''; ?>>All (<?php echo $totalReviews; ?>)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="book-reviews.php" class="btn btn-secondary">
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
                                             htmlspecialchars(!empty($review['book_title']) ? $review['book_title'] :
                                                (!empty($review['book_title_fallback']) ? $review['book_title_fallback'] : 'Unknown Book')) . '</a>',
                                    'reviewer' => htmlspecialchars(!empty($review['reviewer_name']) ? $review['reviewer_name'] : 'Anonymous'),
                                    'rating' => $rating,
                                    'source' => $source,
                                    'date' => $review['created_at'],
                                    'actions' => $actions
                                ];
                            }

                            // Define table columns - include actions in the columns
                            $columns = [
                                'book' => 'Book',
                                'reviewer' => 'Reviewer',
                                'rating' => 'Rating',
                                'source' => 'Source',
                                'date' => 'Date',
                                'actions' => 'Actions'
                            ];

                            // Render enhanced table
                            // Disable enhanced table's built-in pagination
                            renderEnhancedTable(
                                $tableData,
                                $columns,
                                'review',
                                'reviews-table',
                                [
                                    'showCheckboxes' => true,
                                    'showActions' => false, // Don't show the last actions column
                                    'actions' => ['view', 'edit', 'delete'],
                                    'bulkActions' => ['delete', 'analyze'],
                                    'htmlFields' => ['book', 'rating', 'source', 'actions'],
                                    'showPagination' => false,
                                    'showItemsPerPage' => false
                                ]
                            );
                            ?>
                        </div>
                        <?php
                        // Render pagination with custom options
                        renderPagination($totalReviews, $reviewsPerPage, $reviewsPage, 5, [
                            'pageParam' => 'reviews_page',
                            'perPageParam' => 'reviews_per_page',
                            'validPerPageValues' => [10, 25, 50, 100, $totalReviews],
                            'perPageLabel' => 'Show',
                            'showAllLabel' => 'Show All',
                            'showItemsPerPage' => true, // Enable the built-in per-page dropdown for consistency
                            'tab' => '' // Ensure no tab parameter is added
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle per-page dropdown change for both dropdowns
    $('#reviews_per_page, .per-page-select').on('change', function() {
        // Get current URL
        let url = new URL(window.location.href);
        
        // Update reviews_per_page parameter
        url.searchParams.set('reviews_per_page', $(this).val());
        
        // Reset to page 1 when changing items per page
        url.searchParams.set('reviews_page', '1');
        
        // Redirect to the new URL
        window.location.href = url.toString();
    });
    
    // Ensure both dropdowns are synchronized
    function syncDropdowns() {
        const value = $('#reviews_per_page').val();
        $('.per-page-select').val(value);
    }
    
    // Sync on page load
    syncDropdowns();

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
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>