<?php
/**
 * Edit Review
 * 
 * This page allows administrators to edit a review.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Initialize variables
$reviewId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$successMessage = '';
$errorMessage = '';
$review = null;
$sources = [];
$books = [];

// Get review sources
try {
    $sourcesStmt = $db->query("SELECT id, name FROM review_sources ORDER BY name");
    $sources = $sourcesStmt->fetchAll();
} catch (Exception $e) {
    $errorMessage = 'Error loading review sources: ' . $e->getMessage();
}

// Get books
try {
    $booksStmt = $db->query("
        SELECT id, title 
        FROM directory_items 
        WHERE EXISTS (SELECT 1 FROM reviews WHERE book_id = directory_items.id)
        ORDER BY title
    ");
    $books = $booksStmt->fetchAll();
} catch (Exception $e) {
    $errorMessage = 'Error loading books: ' . $e->getMessage();
}

// Get review data
if ($reviewId > 0) {
    try {
        $stmt = $db->prepare("
            SELECT r.*, d.title as book_title, s.name as source_name
            FROM reviews r
            LEFT JOIN directory_items d ON r.book_id = d.id
            LEFT JOIN review_sources s ON r.source_id = s.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
        
        if (!$review) {
            $errorMessage = 'Review not found.';
        }
    } catch (Exception $e) {
        $errorMessage = 'Error loading review: ' . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_review'])) {
    try {
        $bookId = intval($_POST['book_id']);
        $sourceId = intval($_POST['source_id']);
        $reviewerName = trim($_POST['reviewer_name']);
        $reviewerAge = !empty($_POST['reviewer_age']) ? intval($_POST['reviewer_age']) : null;
        $reviewDate = !empty($_POST['review_date']) ? $_POST['review_date'] : null;
        $ratingValue = floatval($_POST['rating_value']);
        $ratingScale = floatval($_POST['rating_scale']);
        $ratingNormalised = $ratingScale > 0 ? $ratingValue / $ratingScale : 0;
        $reviewText = trim($_POST['review_text']);
        
        // Validate required fields
        if (empty($reviewerName) || empty($reviewText) || $bookId <= 0 || $sourceId <= 0) {
            throw new Exception('Please fill in all required fields.');
        }
        
        // Format original rating
        $originalRating = "{$ratingValue}/{$ratingScale}";
        
        if ($reviewId > 0) {
            // Update existing review
            $stmt = $db->prepare("
                UPDATE reviews
                SET
                    book_id = ?,
                    source_id = ?,
                    reviewer_name = ?,
                    reviewer_age = ?,
                    review_date = ?,
                    original_rating = ?,
                    rating_value = ?,
                    rating_scale = ?,
                    rating_normalised = ?,
                    review_text = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $bookId,
                $sourceId,
                $reviewerName,
                $reviewerAge,
                $reviewDate,
                $originalRating,
                $ratingValue,
                $ratingScale,
                $ratingNormalised,
                $reviewText,
                $reviewId
            ]);
            
            $successMessage = 'Review updated successfully.';
        } else {
            // Insert new review
            $stmt = $db->prepare("
                INSERT INTO reviews (
                    book_id,
                    source_id,
                    reviewer_name,
                    reviewer_age,
                    review_date,
                    original_rating,
                    rating_value,
                    rating_scale,
                    rating_normalised,
                    review_text,
                    created_at,
                    updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");
            
            $stmt->execute([
                $bookId,
                $sourceId,
                $reviewerName,
                $reviewerAge,
                $reviewDate,
                $originalRating,
                $ratingValue,
                $ratingScale,
                $ratingNormalised,
                $reviewText
            ]);
            
            $reviewId = $db->lastInsertId();
            $successMessage = 'Review added successfully.';
        }
        
        // Update book ratings
        $updateStmt = $db->prepare("
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
        $updateStmt->execute([$bookId]);
        
        // Reload review data
        $stmt = $db->prepare("
            SELECT r.*, d.title as book_title, s.name as source_name
            FROM reviews r
            LEFT JOIN directory_items d ON r.book_id = d.id
            LEFT JOIN review_sources s ON r.source_id = s.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reviewId]);
        $review = $stmt->fetch();
    } catch (Exception $e) {
        $errorMessage = 'Error saving review: ' . $e->getMessage();
    }
}

// Set page variables for header
$pageTitle = $reviewId > 0 ? 'Edit Review' : 'Add Review';
$currentPage = 'reviews';
$pageDescription = $reviewId > 0 ? 'Edit an existing review' : 'Add a new review';
$pageActions = '
<div class="d-flex gap-2">
    <a href="reviews.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Reviews
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
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $reviewId > 0 ? 'Edit Review' : 'Add Review'; ?></h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="book_id">Book <span class="text-danger">*</span></label>
                                <select class="form-control" id="book_id" name="book_id" required>
                                    <option value="">Select a Book</option>
                                    <?php foreach ($books as $book): ?>
                                        <option value="<?php echo $book['id']; ?>" <?php echo $review && $review['book_id'] == $book['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="source_id">Source <span class="text-danger">*</span></label>
                                <select class="form-control" id="source_id" name="source_id" required>
                                    <option value="">Select a Source</option>
                                    <?php foreach ($sources as $source): ?>
                                        <option value="<?php echo $source['id']; ?>" <?php echo $review && $review['source_id'] == $source['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($source['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reviewer_name">Reviewer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" value="<?php echo $review ? htmlspecialchars($review['reviewer_name']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="reviewer_age">Reviewer Age</label>
                                <input type="number" class="form-control" id="reviewer_age" name="reviewer_age" value="<?php echo $review && $review['reviewer_age'] ? htmlspecialchars($review['reviewer_age']) : ''; ?>" min="1" max="120">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="review_date">Review Date</label>
                                <input type="date" class="form-control" id="review_date" name="review_date" value="<?php echo $review && $review['review_date'] ? htmlspecialchars($review['review_date']) : date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="rating_value">Rating Value <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="rating_value" name="rating_value" value="<?php echo $review ? htmlspecialchars($review['rating_value']) : '4'; ?>" min="0" max="10" step="0.1" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="rating_scale">Rating Scale <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="rating_scale" name="rating_scale" value="<?php echo $review ? htmlspecialchars($review['rating_scale']) : '5'; ?>" min="1" max="10" step="0.1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Normalized Rating</label>
                                <div class="rating-preview mt-2">
                                    <div class="stars">
                                        <span class="star-rating" id="star-rating">
                                            <?php 
                                            $normalizedRating = $review ? $review['rating_normalised'] : 0.8;
                                            $stars = round($normalizedRating * 5);
                                            echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
                                            ?>
                                        </span>
                                        <span class="rating-value" id="normalized-rating">
                                            (<?php echo number_format($normalizedRating * 100, 0); ?>%)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label for="review_text">Review Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="review_text" name="review_text" rows="6" required><?php echo $review ? htmlspecialchars($review['review_text']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-actions mt-4">
                        <button type="submit" name="save_review" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Review
                        </button>
                        <a href="reviews.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update normalized rating preview
    const ratingValue = document.getElementById('rating_value');
    const ratingScale = document.getElementById('rating_scale');
    const starRating = document.getElementById('star-rating');
    const normalizedRating = document.getElementById('normalized-rating');
    
    function updateRatingPreview() {
        const value = parseFloat(ratingValue.value) || 0;
        const scale = parseFloat(ratingScale.value) || 1;
        const normalized = scale > 0 ? value / scale : 0;
        const stars = Math.round(normalized * 5);
        
        starRating.innerHTML = '★'.repeat(stars) + '☆'.repeat(5 - stars);
        normalizedRating.textContent = `(${Math.round(normalized * 100)}%)`;
    }
    
    if (ratingValue && ratingScale) {
        ratingValue.addEventListener('input', updateRatingPreview);
        ratingScale.addEventListener('input', updateRatingPreview);
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
