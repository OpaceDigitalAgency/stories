<?php
/**
 * Directory Item Form Page
 *
 * This page displays a form for adding or editing a directory item.
 */

// Include auth check
require_once '../includes/auth-check.php';

// Include database connection
require_once '../includes/db-connect.php';

// Include image upload component
require_once '../includes/image-upload-component.php';

// Include AI image generator component
require_once '../includes/ai-image-generator-component.php';

// Include tag component if it exists
if (file_exists('../includes/tag-component.php')) {
    require_once '../includes/tag-component.php';
}

// Include publisher dropdown component
if (file_exists('../includes/publisher-dropdown.php')) {
    require_once '../includes/publisher-dropdown.php';
}

// Include series dropdown component
if (file_exists('../includes/series-dropdown.php')) {
    require_once '../includes/series-dropdown.php';
}

try {
    // Initialize variables
    $item = null;
    $categories = [];
    $error = null;
    $bookData = []; // Initialize book data
    $tags = [];
    $reviews = []; // Initialize reviews array
    $reviewCount = 0;
    $averageRating = 0;

    // Get all categories
    $stmt = $db->query("SHOW TABLES LIKE 'directory_categories'");
    if ($stmt->rowCount() > 0) {
        $categories = $db->query("SELECT * FROM directory_categories ORDER BY name")->fetchAll();
    }

    // Get tags if they exist
    if ($db->query("SHOW TABLES LIKE 'tags'")->rowCount() > 0) {
        // Get all tags and filter out age-related tags
        $allTags = $db->query("SELECT * FROM tags ORDER BY name")->fetchAll();
        $tags = array_filter($allTags, function($tag) {
            $name = strtolower($tag['name']);
            // Filter out age range tags
            return !(
                preg_match('/^\d+-\d+$/', $name) ||
                preg_match('/^\d+\+$/', $name) ||
                strpos($name, 'years') !== false ||
                strpos($name, 'age') !== false ||
                $name === 'teen' ||
                $name === 'young adult' ||
                $name === 'adult' ||
                $name === 'coming of age' ||
                $name === '12+' ||
                $name === '13+' ||
                $name === '14+' ||
                $name === '16+'
            );
        });
    }

    // Get authors for dropdown
    $authors = [];
    if ($db->query("SHOW TABLES LIKE 'authors'")->rowCount() > 0) {
        $authors = $db->query("SELECT id, name FROM authors ORDER BY name")->fetchAll();
    }

    // Get unique publishers from books table
    $publishers = [];
    try {
        $publisherStmt = $db->query("SELECT DISTINCT publisher FROM books WHERE publisher IS NOT NULL AND publisher != '' ORDER BY publisher");
        while ($row = $publisherStmt->fetch()) {
            $publishers[] = $row['publisher'];
        }
    } catch (PDOException $e) {
        // Silently fail
    }

    // Get unique series from books table
    $seriesList = [];
    try {
        $seriesStmt = $db->query("SELECT DISTINCT series FROM books WHERE series IS NOT NULL AND series != '' ORDER BY series");
        while ($row = $seriesStmt->fetch()) {
            $seriesList[] = $row['series'];
        }
    } catch (PDOException $e) {
        // Silently fail
    }

    // Get unique genres from books table
    $genreList = [];
    try {
        $genreStmt = $db->query("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
        while ($row = $genreStmt->fetch()) {
            $genreList[] = $row['genre'];
        }
    } catch (PDOException $e) {
        // Silently fail
    }

    // Get unique age ranges from books table
    $ageRangeList = [];
    try {
        $ageRangeStmt = $db->query("SELECT DISTINCT age_range FROM books WHERE age_range IS NOT NULL AND age_range != '' ORDER BY age_range");
        while ($row = $ageRangeStmt->fetch()) {
            $ageRangeList[] = $row['age_range'];
        }
    } catch (PDOException $e) {
        // Silently fail
    }

    // Get unique reading levels from books table
    $readingLevelList = [];
    try {
        $readingLevelStmt = $db->query("SELECT DISTINCT reading_level FROM books WHERE reading_level IS NOT NULL AND reading_level != '' ORDER BY reading_level");
        while ($row = $readingLevelStmt->fetch()) {
            $readingLevelList[] = $row['reading_level'];
        }
    } catch (PDOException $e) {
        // Silently fail
    }

    // Get directory item if editing
    if (isset($_GET['id'])) {
        try {
            $stmt = $db->prepare("SELECT * FROM directory_items WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $item = $stmt->fetch();

            // If this is a book type directory item, get the book data
            if ($item && isset($item['type']) && $item['type'] == 'book') {
                // Debug: Log the item ID we're looking for
                error_log("Looking for book data for directory item ID " . $_GET['id']);

                // First try to get book by directory_item_id
                $bookStmt = $db->prepare("SELECT * FROM books WHERE directory_item_id = ?");
                $bookStmt->execute([$_GET['id']]);
                $bookData = $bookStmt->fetch();

                // Debug: Log book data to error log
                if ($bookData) {
                    error_log("Found book data for directory item ID " . $_GET['id'] . ": " . print_r($bookData, true));

                    // Check if book_authors table exists
                    $tableCheck = $db->query("SHOW TABLES LIKE 'book_authors'");
                    if ($tableCheck->rowCount() > 0) {
                        // Get author relationship from book_authors table
                        $authorStmt = $db->prepare("
                            SELECT a.id, a.name
                            FROM authors a
                            JOIN book_authors ba ON a.id = ba.author_id
                            WHERE ba.directory_item_id = ? AND ba.role = 'author'
                        ");
                        $authorStmt->execute([$_GET['id']]);
                        $authorData = $authorStmt->fetch();

                        if ($authorData) {
                            error_log("Found author relationship: " . print_r($authorData, true));
                        } else {
                            error_log("No author relationship found in book_authors table for directory item ID " . $_GET['id']);
                        }
                    } else {
                        error_log("book_authors table does not exist");
                        $authorData = null;
                    }

                    if ($authorData) {
                        error_log("Found author relationship: " . print_r($authorData, true));
                        // Override the author field with the related author
                        $bookData['author'] = $authorData['name'];
                        $bookData['author_id'] = $authorData['id'];
                    }

                    // Check if book_authors table exists
                    $tableCheck = $db->query("SHOW TABLES LIKE 'book_authors'");
                    if ($tableCheck->rowCount() > 0) {
                        // Get publisher relationship from book_authors table
                        $publisherStmt = $db->prepare("
                            SELECT a.id, a.name
                            FROM authors a
                            JOIN book_authors ba ON a.id = ba.author_id
                            WHERE ba.directory_item_id = ? AND ba.role = 'publisher'
                        ");
                        $publisherStmt->execute([$_GET['id']]);
                        $publisherData = $publisherStmt->fetch();

                        if ($publisherData) {
                            error_log("Found publisher relationship: " . print_r($publisherData, true));
                        } else {
                            error_log("No publisher relationship found in book_authors table for directory item ID " . $_GET['id']);
                        }
                    } else {
                        error_log("book_authors table does not exist");
                        $publisherData = null;
                    }

                    if ($publisherData) {
                        error_log("Found publisher relationship: " . print_r($publisherData, true));
                        // Override the publisher field with the related publisher
                        $bookData['publisher'] = $publisherData['name'];
                        $bookData['publisher_id'] = $publisherData['id'];
                    }
                } else {
                    error_log("No book data found for directory item ID " . $_GET['id'] . " by directory_item_id");

                    // If no book data found, try to find by title match
                    $titleStmt = $db->prepare("SELECT * FROM books WHERE title = ?");
                    $titleStmt->execute([$item['title']]);
                    $bookData = $titleStmt->fetch();

                    if ($bookData) {
                        error_log("Found book data by title match: " . print_r($bookData, true));

                        // Update the book record to link it to this directory item
                        $updateStmt = $db->prepare("UPDATE books SET directory_item_id = ? WHERE id = ?");
                        $updateStmt->execute([$_GET['id'], $bookData['id']]);
                        error_log("Updated book record to link to directory item ID " . $_GET['id']);

                        // Check if book_authors table exists
                        $tableCheck = $db->query("SHOW TABLES LIKE 'book_authors'");
                        if ($tableCheck->rowCount() > 0) {
                            // Get author relationship from book_authors table
                            $authorStmt = $db->prepare("
                                SELECT a.id, a.name
                                FROM authors a
                                JOIN book_authors ba ON a.id = ba.author_id
                                WHERE ba.directory_item_id = ? AND ba.role = 'author'
                            ");
                            $authorStmt->execute([$_GET['id']]);
                            $authorData = $authorStmt->fetch();

                            if ($authorData) {
                                error_log("Found author relationship after title match: " . print_r($authorData, true));
                            } else {
                                error_log("No author relationship found in book_authors table after title match for directory item ID " . $_GET['id']);
                            }
                        } else {
                            error_log("book_authors table does not exist");
                            $authorData = null;
                        }

                        if ($authorData) {
                            // Override the author field with the related author
                            $bookData['author'] = $authorData['name'];
                            $bookData['author_id'] = $authorData['id'];
                        }
                    } else {
                        error_log("No book data found by title match either for: " . $item['title']);

                        // Create an empty book data array to avoid errors
                        $bookData = [
                            'isbn' => '',
                            'isbn13' => '',
                            'author' => '',
                            'author_id' => '',
                            'publisher' => '',
                            'publisher_id' => '',
                            'publication_date' => '',
                            'page_count' => '',
                            'genre' => '',
                            'series' => '',
                            'age_range' => '',
                            'reading_level' => '',
                            'purchase_links' => '{}'
                        ];
                    }
                }

                // Format purchase_links JSON for display
                if (isset($bookData['purchase_links']) && !empty($bookData['purchase_links'])) {
                    try {
                        $links = json_decode($bookData['purchase_links'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $bookData['purchase_links'] = json_encode($links, JSON_PRETTY_PRINT);
                        }
                    } catch (Exception $e) {
                        // Keep original if can't parse JSON
                        error_log("Error parsing purchase links JSON: " . $e->getMessage());
                    }
                }

                // Fetch reviews for this directory item
                try {
                    $reviewsStmt = $db->prepare("
                        SELECT r.*, s.name as source_name
                        FROM reviews r
                        LEFT JOIN review_sources s ON r.source_id = s.id
                        WHERE r.book_id = ?
                        ORDER BY r.review_date DESC
                    ");
                    $reviewsStmt->execute([$_GET['id']]);
                    $reviews = $reviewsStmt->fetchAll();

                    // Calculate average rating and review count
                    if (!empty($reviews)) {
                        $reviewCount = count($reviews);
                        $ratingSum = 0;

                        foreach ($reviews as $review) {
                            $ratingSum += $review['rating_normalised'];
                        }

                        $averageRating = $reviewCount > 0 ? $ratingSum / $reviewCount : 0;
                    }
                } catch (Exception $e) {
                    error_log("Error fetching reviews: " . $e->getMessage());
                }
            }

            // Get item tags if they exist
            if ($db->query("SHOW TABLES LIKE 'item_tags'")->rowCount() > 0) {
                $tagStmt = $db->prepare("
                    SELECT t.id, t.name
                    FROM tags t
                    JOIN item_tags it ON t.id = it.tag_id
                    WHERE it.item_id = ? AND it.item_type = 'directory_item'
                ");
                $tagStmt->execute([$_GET['id']]);
                $itemTags = $tagStmt->fetchAll();
            }

            if (!$item) {
                header("Location: directory-items.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Error loading directory item: " . $e->getMessage());
            header("Location: directory-items.php");
            exit;
        }
    }
} catch (PDOException $e) {
    error_log("Directory item form error: " . $e->getMessage());
    $error = "Error loading form data. Please try again.";
}

// Function to render star ratings
function renderStarRating($rating, $maxRating = 5, $size = 'md') {
    // Normalize rating to a scale of 0-5
    $normalizedRating = $rating * $maxRating;

    // Calculate full and half stars
    $fullStars = floor($normalizedRating);
    $halfStar = $normalizedRating - $fullStars >= 0.5;
    $emptyStars = $maxRating - $fullStars - ($halfStar ? 1 : 0);

    // Size classes
    $sizeClasses = [
        'sm' => 'width: 16px; height: 16px;',
        'md' => 'width: 20px; height: 20px;',
        'lg' => 'width: 24px; height: 24px;'
    ];

    $starStyle = $sizeClasses[$size] ?? $sizeClasses['md'];

    $html = '<div class="star-rating" style="display: inline-flex; align-items: center;">';

    // Full stars
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<svg style="' . $starStyle . ' color: #FFD166; margin-right: 2px;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
        </svg>';
    }

    // Half star
    if ($halfStar) {
        $html .= '<svg style="' . $starStyle . ' color: #FFD166; margin-right: 2px;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill-opacity="0.5"></path>
            <path d="M12 17.27V2L9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"></path>
        </svg>';
    }

    // Empty stars
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<svg style="' . $starStyle . ' color: #e0e0e0; margin-right: 2px;" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
        </svg>';
    }

    $html .= '</div>';

    return $html;
}

// Format date from MySQL date to readable format
function formatDate($date) {
    if (empty($date)) return '';

    // Check if it's a valid date format
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;

    return date('F j, Y', $timestamp);
}

// Page variables
$pageTitle = isset($_GET['id']) ? 'Edit Directory Item' : 'Add Directory Item';
$currentPage = 'directory';

// Add custom CSS for form styling
$extraHeadContent = '
<!-- Include purchase links formatter script -->
<script src="../assets/js/purchase-links-formatter.js"></script>
<!-- Include book form enhancements script -->
<script src="../assets/js/book-form-enhancements.js"></script>
<script>
    // Reviews management JavaScript
    document.addEventListener("DOMContentLoaded", function() {
        // Variables for review management
        let currentRating = 0;
        let editingReviewId = null;

        // Function to initialize star rating inputs
        function initStarRating() {
            const ratingStars = document.querySelectorAll(".rating-star");
            const ratingValue = document.querySelector(".rating-value");

            ratingStars.forEach((star, index) => {
                star.addEventListener("click", () => {
                    currentRating = (index + 1) / 5; // Normalize to 0-1 scale
                    updateStarDisplay();
                    if (ratingValue) {
                        ratingValue.textContent = ((index + 1) + "/5");
                    }
                    document.getElementById("rating_normalised").value = currentRating;
                    document.getElementById("original_rating").value = ((index + 1) + "/5");
                });

                star.addEventListener("mouseover", () => {
                    // Highlight stars on hover
                    ratingStars.forEach((s, i) => {
                        if (i <= index) {
                            s.style.color = "#FFD166";
                        } else {
                            s.style.color = "#e0e0e0";
                        }
                    });
                });

                star.addEventListener("mouseout", () => {
                    // Reset to current rating when not hovering
                    updateStarDisplay();
                });
            });

            // Initialize star display
            function updateStarDisplay() {
                const starCount = Math.round(currentRating * 5);
                ratingStars.forEach((star, index) => {
                    if (index < starCount) {
                        star.style.color = "#FFD166";
                    } else {
                        star.style.color = "#e0e0e0";
                    }
                });
            }
        }

        // Function to handle review form submission
        function setupReviewForm() {
            const reviewForm = document.getElementById("review-form");
            if (!reviewForm) return;

            reviewForm.addEventListener("submit", function(e) {
                e.preventDefault();

                const formData = new FormData(reviewForm);
                const reviewId = document.getElementById("review_id").value;
                formData.append("action", reviewId ? "update_review" : "add_review");

                // Get the book ID from the URL parameter
                const urlParams = new URLSearchParams(window.location.search);
                const bookId = urlParams.get("id");

                if (!bookId) {
                    console.error("Could not find book ID in URL");
                    alert("Error: Could not find book ID");
                    return;
                }

                formData.append("book_id", bookId);

                // The review_id is already in the form as a hidden field
                // No need to append it separately

                // AJAX request to save review
                fetch("../handlers/review-handler.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show updated reviews
                        window.location.reload();
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while saving the review.");
                });
            });

            // Cancel button handler
            document.getElementById("cancel-review").addEventListener("click", function() {
                resetReviewForm();
            });
        }

        // Function to reset the review form
        function resetReviewForm() {
            const reviewForm = document.getElementById("review-form");
            if (!reviewForm) return;

            reviewForm.reset();
            editingReviewId = null;
            currentRating = 0;
            document.getElementById("review_id").value = ""; // Clear the hidden review ID field
            document.getElementById("rating_normalised").value = "0";
            document.getElementById("original_rating").value = "";
            document.getElementById("review-form-modal-title").textContent = "Add New Review";
            document.getElementById("submit-review").textContent = "Add Review";

            // Reset star display
            const ratingStars = document.querySelectorAll(".rating-star");
            ratingStars.forEach(star => {
                star.style.color = "#e0e0e0";
            });

            const ratingValue = document.querySelector(".rating-value");
            if (ratingValue) {
                ratingValue.textContent = "0/5";
            }
        }

        // Function to handle edit review button clicks
        function setupEditButtons() {
            document.querySelectorAll(".edit-review").forEach(button => {
                button.addEventListener("click", function() {
                    const reviewId = this.getAttribute("data-id");
                    editingReviewId = reviewId;

                    // Get review data from hidden inputs
                    const reviewItem = document.getElementById("review-" + reviewId);
                    const reviewerName = reviewItem.getAttribute("data-reviewer-name");
                    const reviewerAge = reviewItem.getAttribute("data-reviewer-age");
                    const sourceId = reviewItem.getAttribute("data-source-id");
                    const reviewDate = reviewItem.getAttribute("data-review-date");
                    const ratingNormalised = parseFloat(reviewItem.getAttribute("data-rating-normalised"));
                    const originalRating = reviewItem.getAttribute("data-original-rating");
                    const reviewText = reviewItem.getAttribute("data-review-text");

                    // Populate form with review data
                    document.getElementById("review_id").value = reviewId; // Set the hidden review ID field
                    document.getElementById("reviewer_name").value = reviewerName;
                    document.getElementById("reviewer_age").value = reviewerAge;
                    document.getElementById("source_id").value = sourceId;
                    document.getElementById("review_date").value = reviewDate;
                    document.getElementById("rating_normalised").value = ratingNormalised;
                    document.getElementById("original_rating").value = originalRating;
                    document.getElementById("review_text").value = reviewText;

                    // Update current rating and star display
                    currentRating = ratingNormalised;
                    const ratingStars = document.querySelectorAll(".rating-star");
                    const starCount = Math.round(currentRating * 5);
                    ratingStars.forEach((star, index) => {
                        if (index < starCount) {
                            star.style.color = "#FFD166";
                        } else {
                            star.style.color = "#e0e0e0";
                        }
                    });

                    const ratingValue = document.querySelector(".rating-value");
                    if (ratingValue) {
                        ratingValue.textContent = originalRating || (Math.round(ratingNormalised * 5) + "/5");
                    }

                    // Update form title and button text
                    document.getElementById("review-form-modal-title").textContent = "Edit Review";
                    document.getElementById("submit-review").textContent = "Update Review";

                    // Scroll to form
                    const reviewForm = document.getElementById("review-form");
                    if (reviewForm) {
                        reviewForm.scrollIntoView({ behavior: "smooth" });
                    }
                });
            });
        }

        // Function to handle delete review button clicks
        function setupDeleteButtons() {
            document.querySelectorAll(".delete-review").forEach(button => {
                button.addEventListener("click", function() {
                    if (confirm("Are you sure you want to delete this review?")) {
                        const reviewId = this.getAttribute("data-id");

                        // Get the book ID from the URL parameter
                        const urlParams = new URLSearchParams(window.location.search);
                        const bookId = urlParams.get("id");

                        if (!bookId) {
                            console.error("Could not find book ID in URL");
                            alert("Error: Could not find book ID");
                            return;
                        }

                        console.log("Book ID from URL:", bookId);

                        console.log("Deleting review:", reviewId, "for book:", bookId);

                        // Create FormData object for the request
                        const formData = new FormData();
                        formData.append("action", "delete_review");
                        formData.append("review_id", reviewId);
                        // Still send the book_id for reference, but the handler will use the review ID to find the correct review
                        formData.append("book_id", bookId);

                        console.log("Sending delete request with review_id:", reviewId);

                        // AJAX request to delete review
                        fetch("../handlers/review-handler.php", {
                            method: "POST",
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reload the page to show updated reviews
                                window.location.reload();
                            } else {
                                alert("Error: " + data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            alert("An error occurred while deleting the review.");
                        });
                    }
                });
            });
        }

        // Initialize review functionality when DOM is loaded
        function initReviewFunctionality() {
            if (document.getElementById("reviews-section")) {
                console.log("Initializing review functionality");
                initStarRating();
                setupReviewForm();
                setupEditButtons();
                setupDeleteButtons();
            }
        }

        // Initialize immediately
        initReviewFunctionality();

        // Also initialize after a short delay to ensure all elements are loaded
        setTimeout(initReviewFunctionality, 500);
    });
</script>
<style>
    /* Grid layout for space efficiency */
    .form-row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -10px;
        margin-left: -10px;
    }

    .form-row > .col,
    .form-row > [class*="col-"] {
        padding-right: 10px;
        padding-left: 10px;
    }

    /* Compact form elements */
    .form-group {
        margin-bottom: 0.75rem;
    }

    .form-label {
        margin-bottom: 0.25rem;
        font-weight: 500;
    }

    .form-control {
        padding: 0.375rem 0.5rem;
    }

    .card, .wp-card {
        margin-bottom: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .card-header, .wp-card-header {
        padding: 0.5rem 0.75rem;
        background-color: rgba(0,0,0,0.03);
        font-weight: 600;
        border-bottom: 1px solid rgba(0,0,0,0.125);
    }

    .card-body, .wp-card-body {
        padding: 0.75rem;
    }

    /* Compact sections */
    .content-section {
        padding: 0.5rem !important;
    }

    .section-body {
        padding: 0.5rem !important;
    }

    /* Sticky save bar at bottom of screen */
    .sticky-save-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 10px 15px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #dee2e6;
    }

    /* Add padding to the bottom of the form to prevent content from being hidden behind the sticky bar */
    .content-form {
        padding-bottom: 60px;
    }

    .sticky-save-bar .btn-group {
        display: flex;
        gap: 8px;
    }

    /* Book fields toggle */
    .book-fields {
        /* Ensure book fields are visible when they should be */
        transition: opacity 0.2s ease-in-out;
    }

    /* Force visibility for book fields when type is book */
    body.has-book-type .book-fields {
        display: block !important;
    }

    /* Image preview styling */
    .image-preview-container {
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 0.5rem;
        background-color: #f8f9fa;
        margin-top: 0.5rem;
        min-height: 150px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .image-preview {
        max-width: 100%;
        max-height: 300px;
        object-fit: contain;
    }

    /* Tag styling */
    .tag-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .tag-badge {
        background-color: #e9ecef;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .tag-badge .remove-tag {
        cursor: pointer;
        color: #dc3545;
    }

    /* Stack in mobile */
    @media (max-width: 767px) {
        .form-row {
            flex-direction: column;
        }

        .form-row > .col,
        .form-row > [class*="col-"] {
            width: 100%;
        }
    }

    /* Reviews section styles */
    .reviews-section {
        margin-top: 15px;
    }
    .reviews-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .reviews-summary {
        display: flex;
        align-items: center;
    }
    .average-rating {
        font-size: 24px;
        font-weight: bold;
        margin-right: 10px;
    }
    .review-count {
        color: #666;
        font-size: 14px;
        margin-left: 10px;
    }
    .reviews-list {
        margin-top: 15px;
        max-height: 400px;
        overflow-y: auto;
    }
    .review-item {
        padding: 12px;
        background-color: #f8f9fa;
        border-radius: 6px;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
        position: relative;
    }
    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    .reviewer-info {
        font-weight: bold;
    }
    .reviewer-age {
        color: #666;
        font-size: 14px;
        font-weight: normal;
    }
    .review-source {
        color: #666;
        font-size: 14px;
    }
    .review-date {
        color: #666;
        font-size: 14px;
    }
    .review-rating {
        margin-bottom: 8px;
    }
    .review-text {
        line-height: 1.5;
    }
    .no-reviews {
        padding: 15px;
        text-align: center;
        color: #666;
        background-color: #f0f0f0;
        border-radius: 6px;
    }
    .review-actions {
        position: relative;
        top: 10px;
        width: 100%;
        text-align: right;
        margin-bottom: 20px;
    }
    .review-actions button {
        background: none;
        border: none;
        font-size: 14px;
        cursor: pointer;
        margin-left: 5px;
        padding: 3px 8px;
        border-radius: 4px;
    }
    .review-actions .edit-review {
        color: #007bff;
        background-color: rgba(0, 123, 255, 0.1);
    }
    .review-actions .delete-review {
        color: #dc3545;
        background-color: rgba(220, 53, 69, 0.1);
    }
    .review-actions button:hover {
        background-color: rgba(0, 0, 0, 0.1);
    }
    .add-review-form {
        margin-top: 15px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }
    .form-row-rating {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    .form-row-rating label {
        margin-right: 10px;
        font-weight: 500;
    }
    .rating-input {
        display: flex;
        align-items: center;
    }
    .rating-input svg {
        cursor: pointer;
    }
    .rating-value {
        margin-left: 10px;
        font-weight: bold;
    }
</style>
';



// Include header
require_once '../includes/header.php';

// Add script to ensure cover_url is properly synced
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Find the form
    const form = document.getElementById("directory-item-form");
    if (!form) return;

    // Find the cover_url input in the main form
    const coverUrlInput = form.querySelector("input[name=\'cover_url\']");
    if (!coverUrlInput) return;

    // Find the image upload component
    const imageComponent = document.querySelector(".image-upload-component");
    if (!imageComponent) return;

    // Find the image URL input in the component - it could be a hidden input or an input with name=cover_url
    const componentUrlInput = imageComponent.querySelector("input.image-url-input") || imageComponent.querySelector("input[name=\'cover_url\']");
    if (!componentUrlInput) return;

    // Function to sync the fields
    function syncCoverUrlFields() {
        if (componentUrlInput && componentUrlInput.value) {
            // Update the main form field with the component field value
            if (coverUrlInput) {
                coverUrlInput.value = componentUrlInput.value;
                console.log("Synced cover_url from component to main form:", componentUrlInput.value);
            }
        } else if (coverUrlInput && coverUrlInput.value) {
            // Update the component field with the main form field value
            if (componentUrlInput) {
                componentUrlInput.value = coverUrlInput.value;
                console.log("Synced cover_url from main form to component:", coverUrlInput.value);
            }
        }
    }

    // Sync fields on page load
    syncCoverUrlFields();

    // Sync fields when the component field changes
    if (componentUrlInput) {
        componentUrlInput.addEventListener("change", syncCoverUrlFields);
    }

    // Add specific handler for the remove image button
    const removeButton = imageComponent.querySelector(".remove-image");
    if (removeButton) {
        removeButton.addEventListener("click", function() {
            setTimeout(function() {
                coverUrlInput.value = "";
                console.log("Cleared cover_url after remove image clicked");
            }, 100);
        });
    }

    // Sync fields when the form is submitted
    form.addEventListener("submit", function() {
        // Get the image URL from the preview
        const previewImg = document.querySelector(".image-preview img");
        if (previewImg && previewImg.src && previewImg.style.display !== "none") {
            // Update both cover_url fields
            if (componentUrlInput) {
                componentUrlInput.value = previewImg.src;
            }
            if (coverUrlInput) {
                coverUrlInput.value = previewImg.src;
            }
            console.log("Updated cover_url fields from preview image:", previewImg.src);
        } else {
            // If no image is displayed, clear the fields
            if (imageComponent.classList.contains("empty")) {
                coverUrlInput.value = "";
                console.log("Image was removed, clearing cover_url field");
            }
        }
    });
});
</script>';

// Display error message if there is one
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger mb-3">
        <h5>Error</h5>
        <p>' . htmlspecialchars($_SESSION['error']) . '</p>
    </div>';
    unset($_SESSION['error']);
}


?>

<div class="content-section mb-3">
    <div class="section-body">
        <form method="POST" action="../content/save-directory-item.php" class="content-form" id="directory-item-form">
            <input type="hidden" name="id" value="<?php echo $item['id'] ?? ''; ?>">
            <input type="hidden" name="cover_url" value="<?php echo htmlspecialchars($item['cover_url'] ?? ''); ?>" id="cover_url_main">

            <div class="row">
                <!-- Left Column - Basic Info and Settings -->
                <div class="col-md-8">
                    <!-- Basic Information Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Basic Information</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" class="form-control" required
                                    value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="slug">Slug <span class="text-danger">*</span></label>
                                        <input type="text" id="slug" name="slug" class="form-control" required
                                            value="<?php echo htmlspecialchars($item['slug'] ?? ''); ?>">
                                        <small class="form-text text-muted">URL-friendly version (auto-generated if empty)</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="type">Item Type</label>
                                        <select id="type" name="type" class="form-control">
                                            <option value="general" <?php echo (isset($item['type']) && $item['type'] == 'general') ? 'selected' : ''; ?>>General</option>
                                            <option value="book" <?php echo (isset($item['type']) && $item['type'] == 'book') ? 'selected' : ''; ?>>Book</option>
                                            <option value="resource" <?php echo (isset($item['type']) && $item['type'] == 'resource') ? 'selected' : ''; ?>>Resource</option>
                                            <option value="organization" <?php echo (isset($item['type']) && $item['type'] == 'organization') ? 'selected' : ''; ?>>Organization</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="tag-select">Genre</label>
                                        <select id="tag-select" class="form-control">
                                            <option value="">Select a genre to add</option>
                                            <?php foreach ($tags as $tag): ?>
                                                <?php
                                                    // Clean up tag name (remove ** prefix if present)
                                                    $tagName = $tag['name'];
                                                    if (strpos($tagName, '**') === 0) {
                                                        $tagName = substr($tagName, 2);
                                                    }
                                                ?>
                                                <option value="<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tagName); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="tag-container" id="tag-container">
                                            <?php
                                            // Get item tags if they exist
                                            $itemTags = [];
                                            if (isset($_GET['id'])) {
                                                if ($db->query("SHOW TABLES LIKE 'directory_item_tags'")->rowCount() > 0) {
                                                    $tagStmt = $db->prepare("
                                                        SELECT t.id, t.name
                                                        FROM tags t
                                                        JOIN directory_item_tags dit ON t.id = dit.tag_id
                                                        WHERE dit.directory_item_id = ?
                                                    ");
                                                    $tagStmt->execute([$_GET['id']]);
                                                    $itemTags = $tagStmt->fetchAll();
                                                } else if ($db->query("SHOW TABLES LIKE 'item_tags'")->rowCount() > 0) {
                                                    $tagStmt = $db->prepare("
                                                        SELECT t.id, t.name
                                                        FROM tags t
                                                        JOIN item_tags it ON t.id = it.tag_id
                                                        WHERE it.item_id = ? AND it.item_type = 'directory_item'
                                                    ");
                                                    $tagStmt->execute([$_GET['id']]);
                                                    $itemTags = $tagStmt->fetchAll();
                                                }
                                            }

                                            if (!empty($itemTags)):
                                                foreach($itemTags as $tag):
                                                    // Clean up tag name (remove ** prefix if present)
                                                    $tagName = $tag['name'];
                                                    if (strpos($tagName, '**') === 0) {
                                                        $tagName = substr($tagName, 2);
                                                    }

                                                    // Skip age-related tags
                                                    $name = strtolower($tagName);
                                                    if (preg_match('/^\d+-\d+$/', $name) ||
                                                        preg_match('/^\d+\+$/', $name) ||
                                                        strpos($name, 'years') !== false ||
                                                        strpos($name, 'age') !== false ||
                                                        $name === 'teen' ||
                                                        $name === 'young adult' ||
                                                        $name === 'adult' ||
                                                        $name === 'coming of age' ||
                                                        $name === '12+' ||
                                                        $name === '13+' ||
                                                        $name === '14+' ||
                                                        $name === '16+') {
                                                        continue;
                                                    }
                                            ?>
                                                    <span class="tag-badge" data-tag-id="<?php echo $tag['id']; ?>">
                                                        <?php echo htmlspecialchars($tagName); ?>
                                                        <i class="fas fa-times remove-tag"></i>
                                                        <input type="hidden" name="tags[]" value="<?php echo $tag['id']; ?>">
                                                    </span>
                                            <?php
                                                endforeach;
                                            endif;
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="price_range">Price Range</label>
                                        <input type="text" id="price_range" name="price_range" class="form-control"
                                            value="<?php echo htmlspecialchars($item['price_range'] ?? ''); ?>"
                                            placeholder="Free, $10-50, Contact for pricing">
                                    </div>
                                </div>
                            </div>

                            <!-- Settings Options -->
                            <div class="form-row mt-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" id="is_published" name="is_published" class="form-check-input"
                                            value="1" <?php echo (!empty($item['is_published'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_published">Published</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" id="featured" name="featured" class="form-check-input"
                                            value="1" <?php echo (!empty($item['featured'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="featured">Featured Item</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Description</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>



                    <!-- Book Information Card -->
                    <div class="wp-card book-fields" id="book-information-card" <?php echo (isset($item['type']) && $item['type'] == 'book') ? 'style="display:block"' : ''; ?>>
                        <div class="wp-card-header">Book Information</div>
                        <div class="wp-card-body">
                            <?php if (empty($bookData)): ?>
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> No book data found for this directory item.
                                    This may happen if the book was imported but not properly linked to the directory item.
                                </div>
                            <?php else: ?>
                                <?php
                                // Define debug variable at the top of the file
                                $debug = false; // Set to true to enable debugging
                                if ($debug):
                                ?>
                                <div class="alert alert-info">
                                    <strong>Book Data Found:</strong>
                                    <ul>
                                        <li>Title: <?php echo htmlspecialchars($bookData['title'] ?? 'Not set'); ?></li>
                                        <li>Author: <?php echo htmlspecialchars($bookData['author'] ?? 'Not set'); ?></li>
                                        <li>Author ID: <?php echo htmlspecialchars($bookData['author_id'] ?? 'Not set'); ?></li>
                                        <li>Publisher: <?php echo htmlspecialchars($bookData['publisher'] ?? 'Not set'); ?></li>
                                        <li>Publisher ID: <?php echo htmlspecialchars($bookData['publisher_id'] ?? 'Not set'); ?></li>
                                        <li>ISBN: <?php echo htmlspecialchars($bookData['isbn'] ?? 'Not set'); ?></li>
                                        <li>ISBN-13: <?php echo htmlspecialchars($bookData['isbn13'] ?? 'Not set'); ?></li>
                                        <li>Genre: <?php echo htmlspecialchars($bookData['genre'] ?? 'Not set'); ?></li>
                                        <li>Series: <?php echo htmlspecialchars($bookData['series'] ?? 'Not set'); ?></li>
                                        <li>Age Range: <?php echo htmlspecialchars($bookData['age_range'] ?? 'Not set'); ?></li>
                                        <li>Reading Level: <?php echo htmlspecialchars($bookData['reading_level'] ?? 'Not set'); ?></li>
                                    </ul>
                                    <script>
                                        console.log('Debug Book Data:', <?php echo json_encode($bookData); ?>);

                                        // Log the raw values for debugging
                                        console.log('Raw age_range value:', <?php echo json_encode($bookData['age_range'] ?? null); ?>);
                                        console.log('Raw genre value:', <?php echo json_encode($bookData['genre'] ?? null); ?>);
                                        console.log('Raw reading_level value:', <?php echo json_encode($bookData['reading_level'] ?? null); ?>);
                                    </script>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="author">Author</label>
                                        <select id="author" name="book_author" class="form-control">
                                            <option value="">Select Author</option>
                                            <?php foreach ($authors as $author): ?>
                                                <?php
                                                    // Clean up author name (remove ** prefix if present)
                                                    $authorName = $author['name'];
                                                    if (strpos($authorName, '**') === 0) {
                                                        $authorName = substr($authorName, 2);
                                                    }

                                                    // Check if this author is selected
                                                    $isSelected = false;
                                                    if (isset($bookData['author'])) {
                                                        // Compare with and without ** prefix
                                                        $isSelected = ($bookData['author'] == $authorName ||
                                                                      $bookData['author'] == $author['name']);
                                                    }
                                                ?>
                                                <option value="<?php echo htmlspecialchars($authorName); ?>"
                                                        <?php echo $isSelected ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($authorName); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="custom" <?php echo (isset($bookData['author']) && !in_array($bookData['author'], array_column($authors, 'name')) && !in_array($bookData['author'], array_map(function($a) { return strpos($a['name'], '**') === 0 ? substr($a['name'], 2) : $a['name']; }, $authors))) ? 'selected' : ''; ?>>
                                                Other (enter manually)
                                            </option>
                                        </select>
                                        <input type="text" id="custom_author" name="custom_author" class="form-control mt-1 d-none"
                                            placeholder="Enter author name"
                                            value="<?php echo (!empty($bookData['author']) && !in_array($bookData['author'], array_column($authors, 'name'))) ? htmlspecialchars($bookData['author']) : ''; ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="publisher">Publisher</label>
                                        <?php if (function_exists('renderPublisherDropdown')): ?>
                                            <?php echo renderPublisherDropdown($db, $bookData['publisher'] ?? ''); ?>
                                        <?php else: ?>
                                            <select id="publisher" name="book_publisher" class="form-control">
                                                <option value="">Select Publisher</option>
                                                <?php foreach ($publishers as $publisher): ?>
                                                    <option value="<?php echo htmlspecialchars($publisher); ?>"
                                                            <?php echo (isset($bookData['publisher']) && $bookData['publisher'] == $publisher) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($publisher); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="custom" <?php echo (isset($bookData['publisher']) && !in_array($bookData['publisher'], $publishers)) ? 'selected' : ''; ?>>
                                                    Other (enter manually)
                                                </option>
                                            </select>
                                            <input type="text" id="custom_publisher" name="custom_publisher" class="form-control mt-1 <?php echo (isset($bookData['publisher']) && !in_array($bookData['publisher'], $publishers)) ? '' : 'd-none'; ?>"
                                                placeholder="Enter publisher name"
                                                value="<?php echo (isset($bookData['publisher']) && !in_array($bookData['publisher'], $publishers)) ? htmlspecialchars($bookData['publisher']) : ''; ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="isbn">ISBN</label>
                                        <input type="text" id="isbn" name="book_isbn" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['isbn'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="isbn13">ISBN-13</label>
                                        <input type="text" id="isbn13" name="book_isbn13" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['isbn13'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="page_count">Page Count</label>
                                        <input type="number" id="page_count" name="book_page_count" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['page_count'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="publication_date">Publication Date</label>
                                        <input type="date" id="publication_date" name="book_publication_date" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['publication_date'] ?? ''); ?>">
                                        <small class="text-muted">Format: YYYY-MM-DD</small>
                                    </div>
                                </div>

                                <!-- Genre dropdown removed - using tags section instead -->



                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="series">Series</label>
                                        <input type="text" id="series" name="book_series" class="form-control"
                                            value="<?php echo htmlspecialchars($bookData['series'] ?? ''); ?>"
                                            placeholder="Enter series name"
                                            list="series-list">
                                        <datalist id="series-list">
                                            <?php foreach ($seriesList as $series): ?>
                                                <option value="<?php echo htmlspecialchars($series); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                        <?php if (isset($debug) && $debug): ?>
                                        <div class="mt-2 small text-muted">
                                            Series value from database: "<?php echo htmlspecialchars($bookData['series'] ?? 'Not set'); ?>"
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="age_range">Age Range</label>
                                        <select id="age_range" name="book_age_range" class="form-control">
                                            <option value="">Select Age Range</option>
                                            <?php
                                            // Add common age ranges that should always be available
                                            $commonAgeRanges = [
                                                '0-3' => '0-3 years',
                                                '3-5' => '3-5 years',
                                                '5-7' => '5-7 years',
                                                '7-9' => '7-9 years',
                                                '7-10' => '7-10 years',
                                                '9-12' => '9-12 years',
                                                '12+' => '12+ years',
                                                'teen' => 'Teen',
                                                'young-adult' => 'Young Adult',
                                                'adult' => 'Adult'
                                            ];

                                            // Combine common age ranges with database age ranges
                                            $allAgeRanges = $commonAgeRanges;
                                            foreach ($ageRangeList as $ageRange) {
                                                if (!isset($allAgeRanges[$ageRange])) {
                                                    // Format the display name
                                                    if (preg_match('/^\d+-\d+$/', $ageRange)) {
                                                        $displayName = $ageRange . ' years';
                                                    } elseif (preg_match('/^\d+\+$/', $ageRange)) {
                                                        $displayName = $ageRange . ' years';
                                                    } else {
                                                        $displayName = ucwords(str_replace('-', ' ', $ageRange));
                                                    }
                                                    $allAgeRanges[$ageRange] = $displayName;
                                                }
                                            }

                                            // Add the current age range if it's not in the list
                                            if (isset($bookData['age_range']) && !empty($bookData['age_range']) && !isset($allAgeRanges[$bookData['age_range']])) {
                                                if (preg_match('/^\d+-\d+$/', $bookData['age_range'])) {
                                                    $displayName = $bookData['age_range'] . ' years';
                                                } elseif (preg_match('/^\d+\+$/', $bookData['age_range'])) {
                                                    $displayName = $bookData['age_range'] . ' years';
                                                } else {
                                                    $displayName = ucwords(str_replace('-', ' ', $bookData['age_range']));
                                                }
                                                $allAgeRanges[$bookData['age_range']] = $displayName;
                                            }

                                            // Sort age ranges
                                            uksort($allAgeRanges, function($a, $b) {
                                                // Extract first number from range if possible
                                                $aNum = intval($a);
                                                $bNum = intval($b);

                                                if ($aNum && $bNum) {
                                                    return $aNum - $bNum;
                                                }

                                                // If not numeric, use alphabetical order
                                                return strcmp($a, $b);
                                            });

                                            // Output all age range options
                                            foreach ($allAgeRanges as $value => $label) {
                                                $selected = (isset($bookData['age_range']) && $bookData['age_range'] == $value) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="reading_level">Reading Level</label>
                                        <select id="reading_level" name="book_reading_level" class="form-control">
                                            <option value="">Select Reading Level</option>
                                            <?php
                                            // Add common reading levels that should always be available
                                            $commonReadingLevels = [
                                                'early-reader' => 'Early Reader',
                                                'beginner' => 'Beginner',
                                                'intermediate' => 'Intermediate',
                                                'advanced' => 'Advanced',
                                                'chapter-book' => 'Chapter Book',
                                                'middle-grade' => 'Middle Grade',
                                                'young-adult' => 'Young Adult'
                                            ];

                                            // Combine common reading levels with database reading levels
                                            $allReadingLevels = $commonReadingLevels;
                                            foreach ($readingLevelList as $readingLevel) {
                                                if (!isset($allReadingLevels[$readingLevel])) {
                                                    // Format the display name
                                                    $displayName = ucwords(str_replace('-', ' ', $readingLevel));
                                                    $allReadingLevels[$readingLevel] = $displayName;
                                                }
                                            }

                                            // Add the current reading level if it's not in the list
                                            if (isset($bookData['reading_level']) && !empty($bookData['reading_level']) && !isset($allReadingLevels[$bookData['reading_level']])) {
                                                $displayName = ucwords(str_replace('-', ' ', $bookData['reading_level']));
                                                $allReadingLevels[$bookData['reading_level']] = $displayName;
                                            }

                                            // Sort reading levels alphabetically
                                            asort($allReadingLevels);

                                            // Output all reading level options
                                            foreach ($allReadingLevels as $value => $label) {
                                                $selected = (isset($bookData['reading_level']) && $bookData['reading_level'] == $value) ? 'selected' : '';
                                                echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="purchase_links">Purchase Links</label>
                                <div id="purchase-links-container">
                                    <!-- Purchase links will be dynamically added here -->
                                </div>
                                <button type="button" id="add-purchase-link-btn" class="btn btn-sm btn-primary mt-2">Add Purchase Link</button>
                                <textarea id="purchase_links" name="book_purchase_links" class="form-control" rows="3"><?php echo htmlspecialchars($bookData['purchase_links'] ?? '{}'); ?></textarea>
                                <small class="form-text text-muted">Purchase links in JSON format. You can edit this directly or use the Add Purchase Link button above.</small>
                            </div>


                        </div>
                    </div>

                    <!-- Reviews Card (Only for Books) -->
                    <?php if (isset($item['type']) && $item['type'] == 'book' && isset($item['id'])): ?>
                    <div class="wp-card book-fields" id="reviews-section">
                        <div class="wp-card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Reviews</h5>
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-primary mr-2" id="add-new-review-btn">
                                    <i class="fas fa-plus"></i> Add New Review
                                </button>
                                <div>
                                    <?php if ($reviewCount > 0): ?>
                                        <span class="badge badge-primary"><?php echo $reviewCount; ?> <?php echo $reviewCount === 1 ? 'review' : 'reviews'; ?></span>
                                        <span class="badge badge-success"><?php echo number_format($averageRating * 5, 1); ?>/5</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No reviews</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="wp-card-body">
                            <!-- Reviews Summary -->
                            <?php if ($reviewCount > 0): ?>
                                <div class="reviews-header">
                                    <div class="reviews-summary">
                                        <div class="average-rating"><?php echo number_format($averageRating * 5, 1); ?></div>
                                        <div>
                                            <?php echo renderStarRating($averageRating, 5, 'md'); ?>
                                            <div class="review-count"><?php echo $reviewCount; ?> <?php echo $reviewCount === 1 ? 'review' : 'reviews'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Reviews List -->
                            <div class="reviews-list">
                                <?php if ($reviewCount > 0): ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="review-item" id="review-<?php echo $review['id']; ?>"
                                            data-reviewer-name="<?php
                                                $reviewerName = $review['reviewer_name'] ?? '';
                                                $reviewerName = preg_replace('/^\*\*/', '', $reviewerName);
                                                echo htmlspecialchars($reviewerName);
                                            ?>"
                                            data-reviewer-age="<?php echo htmlspecialchars($review['reviewer_age'] ?? ''); ?>"
                                            data-source-id="<?php echo htmlspecialchars($review['source_id'] ?? ''); ?>"
                                            data-review-date="<?php echo htmlspecialchars($review['review_date'] ?? ''); ?>"
                                            data-rating-normalised="<?php echo htmlspecialchars($review['rating_normalised'] ?? ''); ?>"
                                            data-original-rating="<?php echo htmlspecialchars($review['original_rating'] ?? ''); ?>"
                                            data-review-text="<?php echo htmlspecialchars($review['review_text'] ?? ''); ?>">

                                            <div class="review-actions">
                                                <button type="button" class="edit-review" data-id="<?php echo $review['id']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="delete-review" data-id="<?php echo $review['id']; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>

                                            <div class="review-header">
                                                <div class="reviewer-info">
                                                    <?php
                                                        // Remove asterisks from reviewer name if present
                                                        $reviewerName = $review['reviewer_name'] ?? 'Anonymous';
                                                        $reviewerName = preg_replace('/^\*\*/', '', $reviewerName);
                                                        echo htmlspecialchars($reviewerName);
                                                    ?>
                                                    <?php if (!empty($review['reviewer_age'])): ?>
                                                        <span class="reviewer-age">(Age <?php echo htmlspecialchars($review['reviewer_age']); ?>)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <?php if (!empty($review['source_name'])): ?>
                                                        <span class="review-source"><?php echo htmlspecialchars($review['source_name']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($review['review_date'])): ?>
                                                        <span class="review-date"><?php echo formatDate($review['review_date']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="review-rating">
                                                <?php echo renderStarRating($review['rating_normalised'], 5, 'sm'); ?>
                                                <span style="margin-left: 10px; font-size: 14px; color: #666;">
                                                    <?php echo htmlspecialchars($review['original_rating'] ?? number_format($review['rating_normalised'] * 5, 1) . '/5'); ?>
                                                </span>
                                            </div>
                                            <?php if (!empty($review['review_text'])): ?>
                                                <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-reviews">
                                        <p>No reviews available for this book.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Placeholder for review form - actual form moved outside main form -->
                            <div class="add-review-form-placeholder" id="review-form-placeholder">
                                <div class="text-center p-4">
                                    <p>The review form now appears in a popup window for better usability.</p>
                                    <button type="button" class="btn btn-primary" onclick="$('#review-form-modal').modal('show')">
                                        <i class="fas fa-plus"></i> Open Review Form
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column - Image and Tags -->
                <div class="col-md-4">
                    <!-- Cover Image Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Cover Image</div>
                        <div class="wp-card-body">
                            <?php
                            // Get the cover URL from the item data or the book data
                            $coverUrl = $item['cover_url'] ?? '';

                            // If this is a book and we have book data with a cover image, use that instead
                            if (isset($item['type']) && $item['type'] == 'book' && isset($bookData['cover_url']) && !empty($bookData['cover_url'])) {
                                $coverUrl = $bookData['cover_url'];
                                error_log("Using book cover URL: " . $coverUrl);
                            }

                            renderImageUploadComponent(
                                'cover_url',
                                $coverUrl,
                                'Cover Image',
                                'directory_item',
                                $item['id'] ?? null
                            );

                            // Render AI image generator
                            if (function_exists('renderAiImageGenerator')) {
                                renderAiImageGenerator(
                                    'directory_item',
                                    [
                                        'title' => $item['title'] ?? '',
                                        'description' => $item['description'] ?? '',
                                        'summary' => $item['description'] ?? '' // Also include summary for compatibility
                                    ],
                                    'cover_url',
                                    'cover_url_preview'
                                );
                            }
                            ?>
                        </div>
                    </div>



                    <!-- Contact Information Card -->
                    <div class="wp-card">
                        <div class="wp-card-header">Contact Information</div>
                        <div class="wp-card-body">
                            <div class="form-group">
                                <label class="form-label" for="website_url">Website URL</label>
                                <input type="url" id="website_url" name="website_url" class="form-control"
                                    value="<?php echo htmlspecialchars($item['website_url'] ?? ''); ?>"
                                    placeholder="https://example.com">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="contact_email">Contact Email</label>
                                <input type="email" id="contact_email" name="contact_email" class="form-control"
                                    value="<?php echo htmlspecialchars($item['contact_email'] ?? ''); ?>"
                                    placeholder="contact@example.com">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="contact_phone">Contact Phone</label>
                                <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                                    value="<?php echo htmlspecialchars($item['contact_phone'] ?? ''); ?>"
                                    placeholder="+1 (123) 456-7890">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" rows="2"><?php echo htmlspecialchars($item['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div class="sticky-save-bar">
                <div>
                    <?php if (isset($item['id'])): ?>
                        <span class="text-muted">Editing item #<?php echo $item['id']; ?></span>
                    <?php else: ?>
                        <span class="text-muted">Creating new directory item</span>
                    <?php endif; ?>
                </div>

                <div class="btn-group">
                    <a href="directory-items.php" class="btn btn-secondary">Cancel</a>
                    <button type="button" id="preview-directory-item" class="btn btn-info">Preview</button>
                    <button type="submit" class="btn btn-primary">Save Directory Item</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript to handle dynamic behavior -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle book fields visibility based on item type
    const typeSelect = document.getElementById('type');
    const bookFields = document.querySelector('.book-fields');

    if (typeSelect && bookFields) {
        // Set initial visibility based on current selection
        if (typeSelect.value === 'book') {
            // Force display block and ensure it's applied immediately
            bookFields.style.display = 'block';

            // Add a class to the body to force book fields visibility via CSS
            document.body.classList.add('has-book-type');

            // Log the current display state
            console.log('Initial book fields display state:', bookFields.style.display);
            console.log('Computed style display:', getComputedStyle(bookFields).display);
            console.log('Body has class has-book-type:', document.body.classList.contains('has-book-type'));

            // Force a reflow to ensure the style is applied
            void bookFields.offsetWidth;

            // Initialize book form enhancements on page load if book type is selected
            // We'll use PHP-generated dropdowns for genre, age range, and reading level
            // and only use JavaScript for other enhancements
        } else {
            bookFields.style.display = 'none';
            document.body.classList.remove('has-book-type');
        }

        // Update visibility when selection changes
        typeSelect.addEventListener('change', function() {
            if (this.value === 'book') {
                // Force display block
                bookFields.style.display = 'block';

                // Add a class to the body to force book fields visibility via CSS
                document.body.classList.add('has-book-type');

                // Force a reflow to ensure the style is applied
                void bookFields.offsetWidth;

                console.log('Changed to book type, display state:', bookFields.style.display);
                console.log('Body has class has-book-type:', document.body.classList.contains('has-book-type'));

                // Manually trigger initialization of book form enhancements
                if (typeof initTagSelection === 'function') initTagSelection();
                if (typeof initAuthorDropdown === 'function') initAuthorDropdown();
                // Series is now a text field with datalist, no initialization needed
                if (typeof initPurchaseLinksManager === 'function') initPurchaseLinksManager();
                // Disable JavaScript enhancement for these fields - using PHP-generated dropdowns instead
                // if (typeof initAgeRangeDropdown === 'function') initAgeRangeDropdown();
                // if (typeof initGenreDropdown === 'function') initGenreDropdown();
                // if (typeof initReadingLevelDropdown === 'function') initReadingLevelDropdown();
                if (typeof initPublisherDropdown === 'function') initPublisherDropdown();
            } else {
                bookFields.style.display = 'none';
                document.body.classList.remove('has-book-type');
            }
        });
    }

    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');

    if (titleInput && slugInput && slugInput.value === '') {
        titleInput.addEventListener('input', function() {
            let slug = this.value.toLowerCase()
                .replace(/[^\w\s-]/g, '')  // Remove special characters
                .replace(/\s+/g, '-')      // Replace spaces with hyphens
                .replace(/-+/g, '-')       // Replace multiple hyphens with single hyphen
                .replace(/^-+|-+$/g, '');  // Remove hyphens from start and end

            slugInput.value = slug;
        });
    }

    // Tag management
    const tagSelect = document.getElementById('tag-select');
    const tagContainer = document.getElementById('tag-container');

    if (tagSelect && tagContainer) {
        // Add tag when selected from dropdown
        tagSelect.addEventListener('change', function() {
            if (this.value) {
                const tagId = this.value;
                const tagName = this.options[this.selectedIndex].text;

                // Skip age range tags if this is a book
                const isAgeRangeTag = tagName.match(/^\d+-\d+$/) || tagName.match(/^\d+\+$/) ||
                                     tagName.toLowerCase().includes('years') ||
                                     tagName.toLowerCase() === 'teen' ||
                                     tagName.toLowerCase() === 'young adult';

                const isBook = document.getElementById('type').value === 'book';

                if (isBook && isAgeRangeTag) {
                    alert('Please use the Age Range dropdown in the Book Information section instead of adding age range tags.');
                    this.value = '';
                    return;
                }

                // Check if tag already exists (case insensitive)
                const existingTag = document.querySelector(`.tag-badge[data-tag-id="${tagId}"]`);
                if (!existingTag) {
                    // Check for similar tags with different formatting
                    const similarTags = Array.from(tagContainer.querySelectorAll('.tag-badge'))
                        .filter(badge => {
                            const badgeText = badge.textContent.trim().replace(/\s+/g, ' ').toLowerCase();
                            const newTagText = tagName.trim().replace(/\s+/g, ' ').toLowerCase();

                            // Remove special characters for comparison
                            const cleanBadgeText = badgeText.replace(/[^\w\s]/g, '');
                            const cleanNewTagText = newTagText.replace(/[^\w\s]/g, '');

                            return cleanBadgeText === cleanNewTagText ||
                                   cleanBadgeText.includes(cleanNewTagText) ||
                                   cleanNewTagText.includes(cleanBadgeText);
                        });

                    if (similarTags.length > 0) {
                        if (!confirm(`A similar tag "${similarTags[0].textContent.trim()}" already exists. Do you still want to add "${tagName}"?`)) {
                            this.value = '';
                            return;
                        }
                    }

                    const tagBadge = document.createElement('span');
                    tagBadge.className = 'tag-badge';
                    tagBadge.setAttribute('data-tag-id', tagId);
                    tagBadge.innerHTML = `
                        ${tagName}
                        <i class="fas fa-times remove-tag"></i>
                        <input type="hidden" name="tags[]" value="${tagId}">
                    `;

                    tagContainer.appendChild(tagBadge);
                } else {
                    alert(`Tag "${tagName}" is already added.`);
                }

                // Reset select
                this.value = '';
            }
        });

        // Remove tag when clicked
        tagContainer.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tag')) {
                const badge = e.target.closest('.tag-badge');
                if (badge) {
                    badge.remove();
                }
            }
        });
    }

    // Preview functionality
    const previewButton = document.getElementById('preview-directory-item');

    if (previewButton) {
        previewButton.addEventListener('click', function() {
            const formData = new FormData(document.querySelector('form.content-form'));
            const id = formData.get('id');

            if (id) {
                window.open('../handlers/direct-directory-item-preview.php?id=' + id, '_blank');
            } else {
                alert('Please save the item first before previewing.');
            }
        });
    }

    // Handle custom fields (author, publisher, series)
    function setupCustomField(selectId, customInputId) {
        const select = document.getElementById(selectId);
        const customInput = document.getElementById(customInputId);

        if (select && customInput) {
            // Show/hide custom field based on selection
            select.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customInput.classList.remove('d-none');
                    customInput.focus();
                } else {
                    customInput.classList.add('d-none');
                }
            });

            // Initialize custom field visibility
            if (select.value === 'custom') {
                customInput.classList.remove('d-none');
            }
        }
    }

    // Setup all custom fields
    setupCustomField('author', 'custom_author');
    setupCustomField('publisher', 'custom_publisher');

    // Handle form submission for all custom fields
    const form = document.querySelector('form.content-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Handle custom author
            const authorSelect = document.getElementById('author');
            const customAuthorInput = document.getElementById('custom_author');
            if (authorSelect && customAuthorInput && authorSelect.value === 'custom' && customAuthorInput.value.trim()) {
                authorSelect.value = customAuthorInput.value.trim();
            }

            // Handle custom publisher
            const publisherSelect = document.getElementById('publisher');
            const customPublisherInput = document.getElementById('custom_publisher');
            if (publisherSelect && customPublisherInput && publisherSelect.value === 'custom' && customPublisherInput.value.trim()) {
                publisherSelect.value = customPublisherInput.value.trim();
            }

            // Series is now a text field with datalist, no custom handling needed
        });
    }
});
</script>

<!-- Include image upload script -->
<script src="../assets/js/image-upload.js"></script>

<!-- Force re-initialization of image uploader -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Re-initialize the image uploader to ensure it picks up all components
    if (window.imageUploader) {
        console.log('Re-initializing image uploader');
        window.imageUploader.initComponents();
    } else {
        console.log('Image uploader not found, will be initialized by image-upload.js');
    }
});</script>

<!-- Script to sync the cover_url field with the main form -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get the main cover_url field and the image upload component field
    const mainCoverUrlField = document.getElementById("cover_url_main");
    const componentCoverUrlField = document.querySelector(".image-upload-component input.image-url-input") || document.querySelector(".image-upload-component input[name='cover_url']");

    // Function to sync the fields
    function syncCoverUrlFields() {
        if (componentCoverUrlField && componentCoverUrlField.value) {
            // Update the main form field with the component field value
            if (mainCoverUrlField) {
                mainCoverUrlField.value = componentCoverUrlField.value;
                console.log("Synced cover_url from component to main form:", componentCoverUrlField.value);
            }
        } else if (mainCoverUrlField && mainCoverUrlField.value) {
            // Update the component field with the main form field value
            if (componentCoverUrlField) {
                componentCoverUrlField.value = mainCoverUrlField.value;
                console.log("Synced cover_url from main form to component:", mainCoverUrlField.value);
            }
        }
    }

    // Sync fields on page load
    syncCoverUrlFields();

    // Sync fields when the component field changes
    if (componentCoverUrlField) {
        componentCoverUrlField.addEventListener("change", syncCoverUrlFields);
    }

    // Add specific handler for the remove image button
    const removeButton = document.querySelector(".image-upload-component .remove-image");
    if (removeButton) {
        removeButton.addEventListener("click", function() {
            setTimeout(function() {
                if (mainCoverUrlField) {
                    mainCoverUrlField.value = "";
                    console.log("Cleared cover_url_main after remove image clicked");
                }
            }, 100);
        });
    }

    // Sync fields when the form is submitted
    const form = document.getElementById("directory-item-form");
    if (form) {
        form.addEventListener("submit", function() {
            // Get the image URL from the preview
            const previewImg = document.querySelector(".image-preview img");
            if (previewImg && previewImg.src && previewImg.style.display !== "none") {
                // Update both cover_url fields
                if (componentCoverUrlField) {
                    componentCoverUrlField.value = previewImg.src;
                }
                if (mainCoverUrlField) {
                    mainCoverUrlField.value = previewImg.src;
                }
                console.log("Updated cover_url fields from preview image:", previewImg.src);
            } else {
                // If no image is displayed, clear the fields
                const imageComponent = document.querySelector(".image-upload-component");
                if (imageComponent && imageComponent.classList.contains("empty")) {
                    if (mainCoverUrlField) {
                        mainCoverUrlField.value = "";
                    }
                    console.log("Image was removed, clearing cover_url_main field");
                }
            }
        });
    }
});
</script>

<!-- Custom fix for directory item form -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Directory item form fix script loaded');

    // Find the form
    const form = document.getElementById('directory-item-form');
    console.log('Form element by ID:', form);

    // Find all forms on the page
    const allForms = document.querySelectorAll('form');
    console.log('All forms on page:', allForms.length);

    allForms.forEach((formElement, index) => {
        console.log(`Form #${index+1}:`, formElement);
        console.log(`Form #${index+1} ID:`, formElement.id);
        console.log(`Form #${index+1} action:`, formElement.getAttribute('action'));

        // Check if this form has the image_updated field
        const imageUpdatedField = formElement.querySelector('input[name="image_updated"]');
        console.log(`Form #${index+1} has image_updated field:`, !!imageUpdatedField);

        // Check if this form has the cover_url field
        const coverUrlField = formElement.querySelector('input[name="cover_url"]');
        console.log(`Form #${index+1} has cover_url field:`, !!coverUrlField);
    });

    // Find the image_updated field
    const imageUpdatedField = document.getElementById('image_updated_field');
    console.log('Image updated field by ID:', imageUpdatedField);

    // Find the image upload component
    const imageUploadComponent = document.querySelector('.image-upload-component');
    if (imageUploadComponent) {
        console.log('Image upload component found');

        // Create a MutationObserver to watch for changes to the image preview
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' || mutation.type === 'attributes') {
                    console.log('Image component mutation detected');

                    // Check if the image was removed (placeholder is visible)
                    const placeholder = imageUploadComponent.querySelector('.placeholder');
                    const isVisible = placeholder && (window.getComputedStyle(placeholder).display !== 'none');

                    if (isVisible) {
                        console.log('Image was removed (placeholder is visible)');

                        // Set the image_updated field to 1
                        if (imageUpdatedField) {
                            imageUpdatedField.value = '1';
                            console.log('Set image_updated to 1 because image was removed');
                        }
                    }
                }
            });
        });

        // Start observing the image upload component
        observer.observe(imageUploadComponent, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    }

    // Direct event listener for the remove button
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-image') ||
            (event.target.parentElement && event.target.parentElement.classList.contains('remove-image'))) {
            console.log('Remove button clicked (captured by event delegation)');

            // Set the image_updated field to 1
            if (imageUpdatedField) {
                imageUpdatedField.value = '1';
                console.log('Set image_updated to 1 because remove button was clicked');

                // Also clear the cover_url field
                const coverUrlField = document.querySelector('input[name="cover_url"]');
                if (coverUrlField) {
                    coverUrlField.value = '';
                    console.log('Cleared cover_url field');
                }
            }
        }
    });

    // Add a submit handler to the form
    if (form) {
        form.addEventListener('submit', function() {
            console.log('Form is being submitted');
            console.log('image_updated value:', imageUpdatedField ? imageUpdatedField.value : 'not found');

            // Check if the cover_url field is empty
            const coverUrlField = document.querySelector('input[name="cover_url"]');
            if (coverUrlField && !coverUrlField.value) {
                console.log('cover_url is empty, setting image_updated to 1');
                if (imageUpdatedField) {
                    imageUpdatedField.value = '1';
                }
            }
        });
    }
});
</script>

<!-- Include directory item preview script -->
<link rel="stylesheet" href="../assets/css/story-preview.css">
<!-- Create a custom preview script instead of using the shared one -->
<script>
// Custom preview script for directory items
document.addEventListener('DOMContentLoaded', function() {
    // Base URL for the frontend
    const frontendBaseUrl = window.location.hostname === 'localhost'
        ? 'http://localhost:3000'
        : 'https://storiesfromtheweb.org';

    // Find the preview button
    const previewButton = document.querySelector('.preview-button');
    if (!previewButton) return;

    // Add click event listener
    previewButton.addEventListener('click', function(e) {
        e.preventDefault();

        // Get the directory item ID
        const idInput = document.querySelector('input[name="id"]');
        if (!idInput) return;

        const id = idInput.value;
        if (!id) return;

        // Open the preview in a new tab
        const previewUrl = `${frontendBaseUrl}/directory/${id}`;
        window.open(previewUrl, '_blank');
    });

    console.log('Directory item custom preview initialized');
});
</script>

<!-- Include book form enhancements -->
<link rel="stylesheet" href="../assets/css/book-form-enhancements.css">
<script src="../assets/js/book-form-enhancements.js"></script>

<!-- Force initialization of book fields if needed -->
<script>
// Add PHP data to JavaScript for direct access - make it globally available
window.bookData = <?php echo json_encode($bookData ?? []); ?>;
console.log('PHP Book Data:', window.bookData);

// Function to directly set dropdown values - make it globally available
window.setDropdownValue = function(selectId, value) {
    if (!value) return;

    const select = document.getElementById(selectId);
    if (!select) return;

    console.log(`Setting ${selectId} to value: "${value}"`);

    // First try exact match
    let found = false;
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === value) {
            select.options[i].selected = true;
            found = true;
            console.log(`Found exact match for ${selectId}: ${value}`);
            break;
        }
    }

    // If no exact match, try case-insensitive match
    if (!found) {
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value.toLowerCase() === value.toLowerCase()) {
                select.options[i].selected = true;
                found = true;
                console.log(`Found case-insensitive match for ${selectId}: ${value}`);
                break;
            }
        }
    }

    // If still no match, add a new option
    if (!found && value) {
        console.log(`No match found for ${selectId}, adding new option: ${value}`);
        const newOption = document.createElement('option');
        newOption.value = value;
        newOption.text = value.charAt(0).toUpperCase() + value.slice(1).replace(/-/g, ' ');
        newOption.selected = true;
        select.appendChild(newOption);
    }
}

// Add a backup initialization that runs after everything else
window.addEventListener('load', function() {
    // Check if we're on a book type and the fields aren't visible
    const typeSelect = document.getElementById('type');
    const bookFields = document.querySelector('.book-fields');

    if (typeSelect && bookFields && typeSelect.value === 'book') {
        console.log('Window load event: Ensuring book fields are visible');

        // Force display block
        bookFields.style.display = 'block';
        document.body.classList.add('has-book-type');

        // Force initialization of book form enhancements
        if (typeof initTagSelection === 'function') initTagSelection();
        if (typeof initAuthorDropdown === 'function') initAuthorDropdown();
        // Series is now a text field with datalist, no initialization needed
        if (typeof initPurchaseLinksManager === 'function') initPurchaseLinksManager();
        // Disable JavaScript enhancement for these fields - using PHP-generated dropdowns instead
        // if (typeof initAgeRangeDropdown === 'function') initAgeRangeDropdown();
        // if (typeof initGenreDropdown === 'function') initGenreDropdown();
        // if (typeof initReadingLevelDropdown === 'function') initReadingLevelDropdown();
        if (typeof initPublisherDropdown === 'function') initPublisherDropdown();

        // We're now using PHP to set dropdown values directly in the HTML
        // No need to use JavaScript for this
    }
});

// Add a final check that runs after a short delay
setTimeout(function() {
    const typeSelect = document.getElementById('type');
    const bookFields = document.getElementById('book-information-card');

    if (typeSelect && bookFields && typeSelect.value === 'book') {
        console.log('Final check: Ensuring book fields are visible');

        // Force display block with !important via inline style
        bookFields.setAttribute('style', 'display: block !important');
        document.body.classList.add('has-book-type');

        // Log the current state
        console.log('Book fields final display state:', bookFields.style.display);
        console.log('Book fields computed style:', getComputedStyle(bookFields).display);

        // We're now using PHP to set dropdown values directly in the HTML
        // No need to use JavaScript for this

        // Ensure the form preserves the selected values
        const form = document.querySelector('form.content-form');
        if (form) {
            form.addEventListener('submit', function() {
                // Log the values before submission
                const ageRangeSelect = document.getElementById('age_range');
                const genreSelect = document.getElementById('genre');
                const readingLevelSelect = document.getElementById('reading_level');

                if (ageRangeSelect) {
                    console.log('Submitting with age range value:', ageRangeSelect.value);
                    console.log('Selected age range option:', ageRangeSelect.options[ageRangeSelect.selectedIndex]?.text);
                }

                if (genreSelect) {
                    console.log('Submitting with genre value:', genreSelect.value);
                    console.log('Selected genre option:', genreSelect.options[genreSelect.selectedIndex]?.text);
                }

                if (readingLevelSelect) {
                    console.log('Submitting with reading level value:', readingLevelSelect.value);
                    console.log('Selected reading level option:', readingLevelSelect.options[readingLevelSelect.selectedIndex]?.text);
                }
            });
        }
    }
}, 500);
</script>

<style>
/* Image preview container styling */
.image-preview-container {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.5rem;
    background-color: #f8f9fa;
    margin-top: 0.5rem;
    min-height: 150px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.image-preview {
    max-width: 100%;
    max-height: 300px;
    object-fit: contain;
}
</style>

<!-- Reviews Management Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize review form functionality
    initReviewForm();

    // Initialize review actions (edit, delete)
    initReviewActions();

    // Initialize star rating functionality
    initStarRating();
});

/**
 * Initialize the review form functionality
 */
function initReviewForm() {
    const reviewForm = document.getElementById('review-form');
    const submitButton = document.getElementById('submit-review');
    const cancelButton = document.getElementById('cancel-review');
    
    if (!reviewForm) return;

    // Handle submit button click
    if (submitButton) {
        submitButton.addEventListener('click', function() {
            submitReviewForm();
        });
    }

    // Handle cancel button
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            // Reset the form
            reviewForm.reset();

            // Clear the review ID
            document.getElementById('review_id').value = '';

            // Reset the form title
            document.getElementById('review-form-modal-title').textContent = 'Add New Review';

            // Reset the submit button text
            submitButton.textContent = 'Add Review';

            // Reset the star rating
            resetStarRating();
            
            // Hide the modal
            $('#review-form-modal').modal('hide');
        });
    }
}

// Function to submit the review form
function submitReviewForm() {
    const reviewForm = document.getElementById('review-form');
    if (!reviewForm) return;
    
    // Get form data
    const formData = new FormData(reviewForm);
        
    // Get the book ID from the URL parameter or input field
    let bookId;
    const urlParams = new URLSearchParams(window.location.search);
    const idFromUrl = urlParams.get('id');
    const idFromInput = document.querySelector('input[name="id"]')?.value;
    
    bookId = idFromUrl || idFromInput;
    
    if (!bookId) {
        console.error('Could not find book ID from URL or input field');
        alert('Error: Could not find book ID. Please try again.');
        return;
    }
    
    console.log('Using book ID:', bookId, 'from', idFromUrl ? 'URL' : 'input field');
    formData.append('book_id', bookId);

    // Determine if this is an add or update action
    const reviewId = document.getElementById('review_id').value;
    const action = reviewId ? 'update_review' : 'add_review';
    formData.append('action', action);

    // Log the form data for debugging
    console.log('Submitting review form with action:', action);
    console.log('Review ID:', reviewId);
    console.log('Book ID:', bookId);
    
    // Log all form data
    console.log('Form data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Send the request to the review handler
    fetch('../handlers/review-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json().catch(err => {
            console.error('Error parsing JSON response:', err);
            throw new Error('Invalid JSON response from server');
        });
    })
    .then(data => {
        console.log('Review response:', data);

        if (data.success) {
            // Show success message
            alert(data.message);
            
            // Hide the modal
            $('#review-form-modal').modal('hide');
            
            // Don't reload the page automatically, let the user inspect the console
            if (confirm('Review saved successfully. Reload page to see changes?')) {
                // Only reload if user confirms
                window.location.href = window.location.href;
            }
        } else {
            // Show error message
            console.error('Error from server:', data.message || 'Unknown error');
            alert('Error: ' + (data.message || 'Failed to save review'));
        }
    })
    .catch(error => {
        console.error('Error submitting review:', error);
        alert('Error submitting review: ' + error.message);
    });
    });

    // Handle cancel button
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            // Reset the form
            reviewForm.reset();

            // Clear the review ID
            document.getElementById('review_id').value = '';

            // Reset the form title
            formTitle.textContent = 'Add New Review';

            // Reset the submit button text
            submitButton.textContent = 'Add Review';

            // Reset the star rating
            resetStarRating();
        });
    }
}

/**
 * Initialize review actions (edit, delete)
 */
function initReviewActions() {
    // Add New Review button is handled by jQuery in the modal script

    // Edit review buttons
    const editButtons = document.querySelectorAll('.edit-review');

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-id');
            console.log('Edit button clicked for review ID:', reviewId);
            
            const reviewItem = document.getElementById('review-' + reviewId);
            console.log('Review item element:', reviewItem);

            if (!reviewItem) {
                console.error('Review item not found for ID:', reviewId);
                return;
            }

            // Get review data from data attributes
            const reviewerName = reviewItem.getAttribute('data-reviewer-name');
            const reviewerAge = reviewItem.getAttribute('data-reviewer-age');
            const sourceId = reviewItem.getAttribute('data-source-id');
            const reviewDate = reviewItem.getAttribute('data-review-date');
            const ratingNormalised = reviewItem.getAttribute('data-rating-normalised');
            const originalRating = reviewItem.getAttribute('data-original-rating');
            const reviewText = reviewItem.getAttribute('data-review-text');
            
            console.log('Review data from attributes:', {
                reviewerName,
                reviewerAge,
                sourceId,
                reviewDate,
                ratingNormalised,
                originalRating,
                reviewText
            });

            // Reset the form first
            const reviewForm = document.getElementById('review-form');
            if (reviewForm) {
                reviewForm.reset();
            }

            // Populate the form
            try {
                console.log('Attempting to populate form fields...');
                
                document.getElementById('review_id').value = reviewId;
                console.log('Set review_id to:', reviewId);
                
                document.getElementById('reviewer_name').value = reviewerName || '';
                console.log('Set reviewer_name to:', reviewerName || '');
                
                document.getElementById('reviewer_age').value = reviewerAge || '';
                console.log('Set reviewer_age to:', reviewerAge || '');
                
                document.getElementById('source_id').value = sourceId || '1';
                console.log('Set source_id to:', sourceId || '1');
                
                document.getElementById('review_date').value = reviewDate || '';
                console.log('Set review_date to:', reviewDate || '');
                
                document.getElementById('rating_normalised').value = ratingNormalised || '0';
                console.log('Set rating_normalised to:', ratingNormalised || '0');
                
                document.getElementById('original_rating').value = originalRating || '';
                console.log('Set original_rating to:', originalRating || '');
                
                document.getElementById('review_text').value = reviewText || '';
                console.log('Set review_text to:', reviewText || '');
                
                console.log('Form populated successfully');
            } catch (error) {
                console.error('Error populating form:', error);
            }

            // Update the form title in the modal
            document.getElementById('review-form-modal-title').textContent = 'Edit Review';

            // Update the submit button text
            document.getElementById('submit-review').textContent = 'Update Review';

            // Update the star rating
            console.log('Calling updateStarRating with normalized rating:', ratingNormalised);
            const starRating = parseFloat(ratingNormalised) * 5;
            console.log('Calculated star rating (0-5 scale):', starRating);
            updateStarRating(starRating);

            // Show the modal
            $('#review-form-modal').modal('show');
        });
    });

    // Delete review buttons
    const deleteButtons = document.querySelectorAll('.delete-review');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reviewId = this.getAttribute('data-id');
            const bookId = document.querySelector('input[name="id"]').value;

            if (!confirm('Are you sure you want to delete this review?')) return;

            // Create form data
            const formData = new FormData();
            formData.append('action', 'delete_review');
            formData.append('review_id', reviewId);
            formData.append('book_id', bookId);

            // Send the request to the review handler
            fetch('../handlers/review-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Delete review response:', data);

                // Always reload the page, even if there's an error
                // This ensures the UI is updated even if the review was already deleted
                alert(data.success ? data.message : 'Review deleted');
                window.location.reload();
            })
            .catch(error => {
                console.error('Error deleting review:', error);
                alert('Error deleting review. Please try again.');
            });
        });
    });
}

/**
 * Initialize star rating functionality
 */
function initStarRating() {
    const stars = document.querySelectorAll('.rating-star');
    const ratingValueDisplay = document.querySelector('.rating-value');
    const ratingNormalisedInput = document.getElementById('rating_normalised');
    const originalRatingInput = document.getElementById('original_rating');

    if (!stars.length || !ratingValueDisplay || !ratingNormalisedInput) return;

    // Set up star click events
    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = index + 1;
            updateStarRating(rating);

            // Update hidden inputs
            ratingNormalisedInput.value = (rating / 5).toFixed(2);
            originalRatingInput.value = rating + '/5';
        });

        // Add hover effect
        star.addEventListener('mouseenter', function() {
            // Highlight this star and all previous stars
            for (let i = 0; i <= index; i++) {
                stars[i].style.color = '#ffc107'; // Yellow color
            }

            // Unhighlight all subsequent stars
            for (let i = index + 1; i < stars.length; i++) {
                stars[i].style.color = '#e0e0e0'; // Gray color
            }
        });
    });

    // Reset stars on mouse leave
    const ratingInput = document.querySelector('.rating-input');
    if (ratingInput) {
        ratingInput.addEventListener('mouseleave', function() {
            const currentRating = parseFloat(ratingNormalisedInput.value) * 5;
            updateStarRating(currentRating);
        });
    }
}

/**
 * Update the star rating display
 * @param {number} rating - The rating value (1-5)
 */

/**
 * Update the star rating display
 * @param {number} rating - The rating value (1-5)
 */
function updateStarRating(rating) {
    console.log('updateStarRating called with rating:', rating);
    
    const stars = document.querySelectorAll('.rating-star');
    console.log('Found stars elements:', stars.length);
    
    const ratingValueDisplay = document.querySelector('.rating-value');
    console.log('Found rating value display:', ratingValueDisplay);

    if (!stars.length || !ratingValueDisplay) {
        console.error('Missing stars or rating value display elements');
        return;
    }

    // Update stars
    stars.forEach((star, index) => {
        const highlighted = index < rating;
        console.log(`Star ${index+1}: ${highlighted ? 'highlighted' : 'not highlighted'}`);
        
        if (highlighted) {
            star.style.color = '#ffc107'; // Yellow color
        } else {
            star.style.color = '#e0e0e0'; // Gray color
        }
    });

    // Update rating value display
    const displayText = rating.toFixed(1) + '/5';
    console.log('Setting rating display text to:', displayText);
    ratingValueDisplay.textContent = displayText;
    
    // Also update the hidden input field
    const ratingNormalisedInput = document.getElementById('rating_normalised');
    if (ratingNormalisedInput) {
        const normalizedValue = (rating / 5).toFixed(2);
        console.log('Setting normalized rating value to:', normalizedValue);
        ratingNormalisedInput.value = normalizedValue;
    } else {
        console.error('Could not find rating_normalised input field');
    }
}

/**
 * Reset the star rating display
 */
function resetStarRating() {
    const stars = document.querySelectorAll('.rating-star');
    const ratingValueDisplay = document.querySelector('.rating-value');
    const ratingNormalisedInput = document.getElementById('rating_normalised');
    const originalRatingInput = document.getElementById('original_rating');

    if (!stars.length || !ratingValueDisplay) return;

    // Reset stars
    stars.forEach(star => {
        star.style.color = '#e0e0e0'; // Gray color
    });

    // Reset rating value display
    ratingValueDisplay.textContent = '0/5';

    // Reset hidden inputs
    if (ratingNormalisedInput) ratingNormalisedInput.value = '0';
    if (originalRatingInput) originalRatingInput.value = '';
}
</script>

<!-- Review Form (moved outside main form to prevent nesting issues) -->
<div class="modal fade" id="review-form-modal" tabindex="-1" role="dialog" aria-labelledby="review-form-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="review-form-modal-title">Add/Edit Review</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="review-form">
                    <!-- Hidden field for review ID when editing -->
                    <input type="hidden" id="review_id" name="review_id" value="">
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reviewer_name">Reviewer Name</label>
                                <input type="text" class="form-control" id="reviewer_name" name="reviewer_name" maxlength="50">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="reviewer_age">Reviewer Age</label>
                                <input type="number" class="form-control" id="reviewer_age" name="reviewer_age" min="1" max="120">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="source_id">Source</label>
                                <select class="form-control" id="source_id" name="source_id">
                                    <option value="1">Stories from the Web</option>
                                    <option value="2">Goodreads</option>
                                    <option value="3">Amazon</option>
                                    <option value="4">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="review_date">Review Date</label>
                                <input type="date" class="form-control" id="review_date" name="review_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Rating</label>
                                <div class="form-row-rating">
                                    <div class="rating-input">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <svg class="rating-star" style="width: 24px; height: 24px; color: #e0e0e0; cursor: pointer; margin-right: 5px;" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"></path>
                                            </svg>
                                        <?php endfor; ?>
                                        <span class="rating-value">0/5</span>
                                    </div>
                                </div>
                                <input type="hidden" id="rating_normalised" name="rating_normalised" value="0">
                                <input type="hidden" id="original_rating" name="original_rating" value="">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="review_text">Review Text</label>
                        <textarea class="form-control" id="review_text" name="review_text" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancel-review">Cancel</button>
                <button type="button" class="btn btn-primary" id="submit-review">Add Review</button>
            </div>
        </div>
    </div>
</div>

<script>
// Update the review form handling to use the modal
$(document).ready(function() {
    // Show modal when "Add New Review" is clicked
    $('#add-new-review-btn').on('click', function() {
        // Reset the form
        $('#review-form')[0].reset();
        
        // Clear the review ID
        $('#review_id').val('');
        
        // Reset the form title
        $('#review-form-modal-title').text('Add New Review');
        
        // Reset the submit button text
        $('#submit-review').text('Add Review');
        
        // Reset the star rating
        resetStarRating();
        
        // Show the modal
        $('#review-form-modal').modal('show');
    });
    
    // Handle form submission via the submit button
    $('#submit-review').on('click', function() {
        // Trigger the form submission handler
        submitReviewForm();
    });
});

// Function to submit the review form
function submitReviewForm() {
    // Get form data
    const formData = new FormData(document.getElementById('review-form'));
    
    // Get the book ID from the URL parameter or input field
    let bookId;
    const urlParams = new URLSearchParams(window.location.search);
    const idFromUrl = urlParams.get('id');
    const idFromInput = document.querySelector('input[name="id"]')?.value;
    
    bookId = idFromUrl || idFromInput;
    
    if (!bookId) {
        console.error('Could not find book ID from URL or input field');
        alert('Error: Could not find book ID. Please try again.');
        return;
    }
    
    console.log('Using book ID:', bookId, 'from', idFromUrl ? 'URL' : 'input field');
    formData.append('book_id', bookId);

    // Determine if this is an add or update action
    const reviewId = document.getElementById('review_id').value;
    const action = reviewId ? 'update_review' : 'add_review';
    formData.append('action', action);

    // Log the form data for debugging
    console.log('Submitting review form with action:', action);
    console.log('Review ID:', reviewId);
    console.log('Book ID:', bookId);
    
    // Log all form data
    console.log('Form data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Send the request to the review handler
    fetch('../handlers/review-handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json().catch(err => {
            console.error('Error parsing JSON response:', err);
            throw new Error('Invalid JSON response from server');
        });
    })
    .then(data => {
        console.log('Review response:', data);

        if (data.success) {
            // Show success message
            alert(data.message);
            
            // Close the modal
            $('#review-form-modal').modal('hide');
            
            // Don't reload the page automatically, let the user inspect the console
            if (confirm('Review saved successfully. Reload page to see changes?')) {
                // Only reload if user confirms
                window.location.href = window.location.href;
            }
        } else {
            // Show error message
            console.error('Error from server:', data.message || 'Unknown error');
            alert('Error: ' + (data.message || 'Failed to save review'));
        }
    })
    .catch(error => {
        console.error('Error submitting review:', error);
        alert('Error submitting review: ' + error.message);
    });
}
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
