<?php
/**
 * Reviews Management
 * 
 * This page allows administrators to manage book reviews.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include admin functions
require_once '../includes/admin-functions.php';

// Include pagination functions
require_once '../includes/pagination.php';

// Set up error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sourceFilter = isset($_GET['source']) ? intval($_GET['source']) : 0;
$bookFilter = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;
$ratingFilter = isset($_GET['rating']) ? floatval($_GET['rating']) : 0;
$successMessage = '';
$errorMessage = '';

// Process bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $selectedIds = isset($_POST['selected_reviews']) ? $_POST['selected_reviews'] : [];
    $action = $_POST['bulk_action'];
    
    if (!empty($selectedIds)) {
        try {
            $db->beginTransaction();
            
            switch ($action) {
                case 'delete':
                    // Delete selected reviews
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $stmt = $db->prepare("DELETE FROM reviews WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    
                    // Update book ratings
                    $bookIds = [];
                    $bookStmt = $db->prepare("SELECT DISTINCT book_id FROM reviews WHERE id IN ($placeholders)");
                    $bookStmt->execute($selectedIds);
                    while ($row = $bookStmt->fetch()) {
                        $bookIds[] = $row['book_id'];
                    }
                    
                    foreach ($bookIds as $bookId) {
                        updateBookRatings($db, $bookId);
                    }
                    
                    $successMessage = count($selectedIds) . ' reviews deleted successfully.';
                    break;
                    
                case 'analyze':
                    // Analyze selected reviews with AI
                    require_once '../../services/AI/ReviewAnalyzer.php';
                    $analyzer = new \Services\AI\ReviewAnalyzer($db);
                    
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $stmt = $db->prepare("SELECT * FROM reviews WHERE id IN ($placeholders)");
                    $stmt->execute($selectedIds);
                    $reviews = $stmt->fetchAll();
                    
                    $analyzedCount = 0;
                    foreach ($reviews as $review) {
                        if ($analyzer->analyzeReview($review['id'])) {
                            $analyzedCount++;
                        }
                    }
                    
                    $successMessage = "$analyzedCount reviews analyzed successfully.";
                    break;
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $errorMessage = 'Error: ' . $e->getMessage();
        }
    } else {
        $errorMessage = 'No reviews selected.';
    }
}

// Function to update book ratings
function updateBookRatings($db, $bookId) {
    $stmt = $db->prepare("
        UPDATE directory_items d
        SET 
            d.average_rating = (
                SELECT AVG(r.rating_normalised)
                FROM reviews r
                WHERE r.book_id = d.id
            ),
            d.review_count = (
                SELECT COUNT(*)
                FROM reviews r
                WHERE r.book_id = d.id
            ),
            d.highest_rating = (
                SELECT MAX(r.rating_normalised)
                FROM reviews r
                WHERE r.book_id = d.id
            ),
            d.lowest_rating = (
                SELECT MIN(r.rating_normalised)
                FROM reviews r
                WHERE r.book_id = d.id
            )
        WHERE d.id = ?
    ");
    $stmt->execute([$bookId]);
}

// Build query conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(r.reviewer_name LIKE ? OR r.review_text LIKE ? OR d.title LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($sourceFilter > 0) {
    $conditions[] = "r.source_id = ?";
    $params[] = $sourceFilter;
}

if ($bookFilter > 0) {
    $conditions[] = "r.book_id = ?";
    $params[] = $bookFilter;
}

if ($ratingFilter > 0) {
    $conditions[] = "r.rating_normalised >= ?";
    $params[] = $ratingFilter / 5; // Convert to 0-1 scale
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Count total reviews
$countQuery = "
    SELECT COUNT(*) 
    FROM reviews r
    LEFT JOIN directory_items d ON r.book_id = d.id
    LEFT JOIN review_sources s ON r.source_id = s.id
    $whereClause
";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalItems = $countStmt->fetchColumn();

// Calculate pagination
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;

// Get reviews
$query = "
    SELECT r.*, d.title as book_title, s.name as source_name, s.is_third_party
    FROM reviews r
    LEFT JOIN directory_items d ON r.book_id = d.id
    LEFT JOIN review_sources s ON r.source_id = s.id
    $whereClause
    ORDER BY r.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get review sources for filter
$sourcesStmt = $db->query("SELECT id, name FROM review_sources ORDER BY name");
$sources = $sourcesStmt->fetchAll();

// Get books for filter
$booksStmt = $db->query("
    SELECT d.id, d.title 
    FROM directory_items d
    WHERE EXISTS (SELECT 1 FROM reviews r WHERE r.book_id = d.id)
    ORDER BY d.title
");
$books = $booksStmt->fetchAll();

// Set page variables for header
$pageTitle = 'Reviews Management';
$currentPage = 'reviews';
$pageDescription = 'Manage book reviews from various sources';
$pageActions = '
<div class="d-flex gap-2">
    <a href="directory-items.php" class="btn btn-secondary">
        <i class="fas fa-book"></i> Books
    </a>
    <a href="../scripts/clean-duplicate-reviews.php" class="btn btn-warning">
        <i class="fas fa-broom"></i> Clean Duplicates
    </a>
    <a href="review-settings.php" class="btn btn-info">
        <i class="fas fa-cog"></i> Settings
    </a>
</div>';

// Include header
require_once '../includes/header.php';
?>

<div class="content-section">
    <div class="section-body">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <h3>Filters</h3>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search reviews...">
                    </div>
                    <div class="col-md-3">
                        <label for="source" class="form-label">Source</label>
                        <select class="form-control" id="source" name="source">
                            <option value="0">All Sources</option>
                            <?php foreach ($sources as $source): ?>
                                <option value="<?php echo $source['id']; ?>" <?php echo $sourceFilter == $source['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($source['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="book_id" class="form-label">Book</label>
                        <select class="form-control" id="book_id" name="book_id">
                            <option value="0">All Books</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo $book['id']; ?>" <?php echo $bookFilter == $book['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="rating" class="form-label">Min Rating</label>
                        <select class="form-control" id="rating" name="rating">
                            <option value="0" <?php echo $ratingFilter == 0 ? 'selected' : ''; ?>>Any Rating</option>
                            <option value="1" <?php echo $ratingFilter == 1 ? 'selected' : ''; ?>>★ (1+)</option>
                            <option value="2" <?php echo $ratingFilter == 2 ? 'selected' : ''; ?>>★★ (2+)</option>
                            <option value="3" <?php echo $ratingFilter == 3 ? 'selected' : ''; ?>>★★★ (3+)</option>
                            <option value="4" <?php echo $ratingFilter == 4 ? 'selected' : ''; ?>>★★★★ (4+)</option>
                            <option value="5" <?php echo $ratingFilter == 5 ? 'selected' : ''; ?>>★★★★★ (5)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="reviews.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Reviews Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3>Reviews (<?php echo number_format($totalItems); ?>)</h3>
                <div>
                    <span class="text-muted">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                </div>
            </div>
            <div class="card-body">
                <form method="post" id="reviews-form">
                    <div class="bulk-actions mb-3">
                        <div class="d-flex gap-2">
                            <select class="form-control w-auto" name="bulk_action" id="bulk-action">
                                <option value="">Bulk Actions</option>
                                <option value="delete">Delete</option>
                                <option value="analyze">Analyze with AI</option>
                            </select>
                            <button type="submit" class="btn btn-primary" id="apply-bulk-action">Apply</button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>Book</th>
                                    <th>Reviewer</th>
                                    <th>Rating</th>
                                    <th>Source</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reviews)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No reviews found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="selected_reviews[]" value="<?php echo $review['id']; ?>" class="review-checkbox">
                                            </td>
                                            <td>
                                                <a href="directory-item-form.php?id=<?php echo $review['book_id']; ?>">
                                                    <?php echo htmlspecialchars($review['book_title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                                            <td>
                                                <?php 
                                                $stars = round($review['rating_normalised'] * 5);
                                                echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
                                                echo ' (' . $review['original_rating'] . ')';
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($review['source_name']); ?>
                                                <?php if ($review['is_third_party']): ?>
                                                    <span class="badge badge-info">External</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $review['review_date']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-info view-review" data-id="<?php echo $review['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="edit-review.php?id=<?php echo $review['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger delete-review" data-id="<?php echo $review['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sourceFilter > 0 ? '&source=' . $sourceFilter : ''; ?><?php echo $bookFilter > 0 ? '&book_id=' . $bookFilter : ''; ?><?php echo $ratingFilter > 0 ? '&rating=' . $ratingFilter : ''; ?>">
                                        First
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sourceFilter > 0 ? '&source=' . $sourceFilter : ''; ?><?php echo $bookFilter > 0 ? '&book_id=' . $bookFilter : ''; ?><?php echo $ratingFilter > 0 ? '&rating=' . $ratingFilter : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sourceFilter > 0 ? '&source=' . $sourceFilter : ''; ?><?php echo $bookFilter > 0 ? '&book_id=' . $bookFilter : ''; ?><?php echo $ratingFilter > 0 ? '&rating=' . $ratingFilter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sourceFilter > 0 ? '&source=' . $sourceFilter : ''; ?><?php echo $bookFilter > 0 ? '&book_id=' . $bookFilter : ''; ?><?php echo $ratingFilter > 0 ? '&rating=' . $ratingFilter : ''; ?>">
                                        Next
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $sourceFilter > 0 ? '&source=' . $sourceFilter : ''; ?><?php echo $bookFilter > 0 ? '&book_id=' . $bookFilter : ''; ?><?php echo $ratingFilter > 0 ? '&rating=' . $ratingFilter : ''; ?>">
                                        Last
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Review View Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Review Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="reviewModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('select-all');
    const reviewCheckboxes = document.querySelectorAll('.review-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            reviewCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // Bulk action confirmation
    const reviewsForm = document.getElementById('reviews-form');
    const bulkActionSelect = document.getElementById('bulk-action');
    const applyBulkActionButton = document.getElementById('apply-bulk-action');
    
    if (reviewsForm && bulkActionSelect && applyBulkActionButton) {
        reviewsForm.addEventListener('submit', function(e) {
            const selectedAction = bulkActionSelect.value;
            const selectedReviews = document.querySelectorAll('.review-checkbox:checked');
            
            if (!selectedAction) {
                e.preventDefault();
                alert('Please select an action.');
                return;
            }
            
            if (selectedReviews.length === 0) {
                e.preventDefault();
                alert('Please select at least one review.');
                return;
            }
            
            if (selectedAction === 'delete') {
                if (!confirm('Are you sure you want to delete the selected reviews? This action cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });
    }
    
    // View review details
    const viewButtons = document.querySelectorAll('.view-review');
    const reviewModal = document.getElementById('reviewModal');
    const reviewModalBody = document.getElementById('reviewModalBody');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-id');
            
            // Show loading spinner
            reviewModalBody.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            `;
            
            // Show modal
            $('#reviewModal').modal('show');
            
            // Fetch review details
            fetch(`../handlers/get-review.php?id=${reviewId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const review = data.review;
                        const stars = '★'.repeat(Math.round(review.rating_normalised * 5)) + '☆'.repeat(5 - Math.round(review.rating_normalised * 5));
                        
                        reviewModalBody.innerHTML = `
                            <div class="review-details">
                                <h4>${review.book_title}</h4>
                                <div class="review-meta">
                                    <p><strong>Reviewer:</strong> ${review.reviewer_name}</p>
                                    <p><strong>Rating:</strong> ${stars} (${review.original_rating})</p>
                                    <p><strong>Source:</strong> ${review.source_name}</p>
                                    <p><strong>Date:</strong> ${review.review_date}</p>
                                </div>
                                <div class="review-content mt-3">
                                    <h5>Review Text:</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            ${review.review_text}
                                        </div>
                                    </div>
                                </div>
                                ${review.ai_summary ? `
                                <div class="ai-analysis mt-3">
                                    <h5>AI Analysis:</h5>
                                    <div class="card">
                                        <div class="card-body">
                                            <p><strong>Summary:</strong> ${review.ai_summary}</p>
                                            <p><strong>Suitability Score:</strong> ${review.suitability_score ? (review.suitability_score * 100).toFixed(0) + '%' : 'Not analyzed'}</p>
                                            ${review.content_flags ? `
                                            <p><strong>Content Flags:</strong> ${JSON.parse(review.content_flags).join(', ')}</p>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                    } else {
                        reviewModalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    reviewModalBody.innerHTML = `<div class="alert alert-danger">Error loading review details: ${error.message}</div>`;
                });
        });
    });
    
    // Delete review
    const deleteButtons = document.querySelectorAll('.delete-review');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'post';
                form.action = 'reviews.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = 'delete';
                
                const reviewInput = document.createElement('input');
                reviewInput.type = 'hidden';
                reviewInput.name = 'selected_reviews[]';
                reviewInput.value = reviewId;
                
                form.appendChild(actionInput);
                form.appendChild(reviewInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
